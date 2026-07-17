"""
Process Supervisor
==================
Keeps the long-running Python "loop" scripts (jobsched_loops.py, table_sched_*.py,
*_loop.py, etc.) alive without console windows.

- Reads its list of managed scripts from MySQL: nahsi.managed_processes
- Launches each enabled script hidden, stdout/stderr captured to a per-script log file
- Auto-restarts scripts that die (with backoff; gives up after repeated rapid failures)
- Writes status / pid / heartbeat back to the same table every cycle
- Polls the `command` column for start / stop / restart requests written by the
  nahsi dashboard (admin_processes.php)
- Supports `--only PROC_NAME` for a deliberately scoped supervisor that keeps
  one registered process alive without starting other enabled rows
- Single-instance: a second copy exits immediately, so a Task Scheduler watchdog
  trigger can run this script repeatedly without risk of duplicates

Install at boot with install_supervisor_task.bat. Debug in a console with
run_supervisor_console.bat (Ctrl+C stops the supervisor and all children).
"""

import os
import socket
import subprocess
import sys
import time
import logging
import logging.handlers
import importlib
import tempfile
from datetime import datetime

import mysql.connector
from mysql.connector import Error as MySQLError

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
DB_CONFIG = {
    "host": os.environ.get("PROCESS_SUPERVISOR_DB_HOST", "127.0.0.1"),
    "port": int(os.environ.get("PROCESS_SUPERVISOR_DB_PORT", "3306")),
    "user": os.environ.get("PROCESS_SUPERVISOR_DB_USER", "root"),
    "password": os.environ.get("PROCESS_SUPERVISOR_DB_PASSWORD", ""),
    "database": os.environ.get("PROCESS_SUPERVISOR_DB_NAME", "nahsi"),
    "connection_timeout": int(os.environ.get("PROCESS_SUPERVISOR_DB_TIMEOUT_SECONDS", "10")),
    "autocommit": True,
}
TABLE = "managed_processes"
SUPERVISOR_ROW = "__supervisor__"

POLL_SECONDS = 5            # main loop cycle
RESTART_DELAY = 30          # wait before restarting a crashed child
RAPID_FAIL_WINDOW = 600     # seconds; failures inside this window count as "rapid"
MAX_RAPID_FAILS = 5         # this many rapid failures -> status 'crashed', stop retrying
DB_RETRY_SECONDS = 15       # retry interval while MySQL is unavailable (e.g. at boot)
LOG_ROTATE_BYTES = 5 * 1024 * 1024   # rotate child log at spawn if larger than this
SINGLE_INSTANCE_PORT = 58472         # loopback port used as a single-instance lock

PROD_EXPECTED_PROCESSES = (
    "gill_update", "hep_update", "ukgill_update_all_tables",
    "breaklunch_ftp_1", "breaklunch_ftp_2", "breaklunch_ftp_locus",
    "gallin_update", "offsys_slot_update", "offsys_slot_update_CAN",
    "nahsi_shorts_item", "jobsched_loops", "table_sched_mysqlupdates",
    "todaypage_loop", "printvis_caserefresh", "printvis_table_sched",
    "printvis_loc_oh_loop", "printvis_pack_start_loop", "receiving_cost_loop",
)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_DIR = os.path.join(BASE_DIR, "logs")

CREATE_NO_WINDOW = getattr(subprocess, "CREATE_NO_WINDOW", 0x08000000)

log = logging.getLogger("supervisor")


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def setup_logging():
    os.makedirs(LOG_DIR, exist_ok=True)
    log.setLevel(logging.INFO)
    fmt = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")
    fh = logging.handlers.RotatingFileHandler(
        os.path.join(LOG_DIR, "_supervisor.log"), maxBytes=2 * 1024 * 1024, backupCount=3
    )
    fh.setFormatter(fmt)
    log.addHandler(fh)
    sh = logging.StreamHandler()
    sh.setFormatter(fmt)
    log.addHandler(sh)


def acquire_single_instance_lock():
    """Bind a loopback port for the life of the process. A second instance fails
    the bind and exits, which lets a Task Scheduler watchdog trigger re-run this
    script every N minutes safely."""
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        sock.bind(("127.0.0.1", SINGLE_INSTANCE_PORT))
        sock.listen(1)
        return sock
    except OSError:
        return None


def connect_db_with_retry():
    while True:
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            log.info("Connected to MySQL")
            return conn
        except MySQLError as e:
            log.warning("MySQL not available (%s) - retrying in %ss", e, DB_RETRY_SECONDS)
            time.sleep(DB_RETRY_SECONDS)


def connect_db_once():
    """Connect once for installer preflight; never wait forever."""
    return mysql.connector.connect(**DB_CONFIG)


def pid_command_line(pid):
    """Return the command line of a PID, or None if the process is gone.
    Used only at startup reconciliation."""
    try:
        out = subprocess.run(
            ["powershell", "-NoProfile", "-Command",
             "(Get-CimInstance Win32_Process -Filter \"ProcessId={}\").CommandLine".format(int(pid))],
            capture_output=True, text=True, timeout=30, creationflags=CREATE_NO_WINDOW,
        )
        cmdline = (out.stdout or "").strip()
        return cmdline if cmdline else None
    except Exception:
        return None


def kill_tree(pid):
    """taskkill the whole tree: the loop scripts spawn cmd/bat/php grandchildren
    via os.system, and plain terminate() would orphan those."""
    subprocess.run(
        ["taskkill", "/PID", str(int(pid)), "/T", "/F"],
        capture_output=True, creationflags=CREATE_NO_WINDOW,
    )


def rotate_log(path):
    try:
        if os.path.exists(path) and os.path.getsize(path) > LOG_ROTATE_BYTES:
            old = path + ".1"
            if os.path.exists(old):
                os.remove(old)
            os.rename(path, old)
    except OSError as e:
        log.warning("Could not rotate log %s: %s", path, e)


# ---------------------------------------------------------------------------
# Managed child bookkeeping (in-memory, keyed by proc_id)
# ---------------------------------------------------------------------------
class Child:
    def __init__(self):
        self.popen = None
        self.log_handle = None
        self.fail_times = []      # timestamps of recent unexpected exits
        self.next_start_at = 0    # backoff: don't start before this time
        self.gave_up = False      # too many rapid failures
        self.expected_stop = False

    def running(self):
        return self.popen is not None and self.popen.poll() is None

    def close_log(self):
        if self.log_handle:
            try:
                self.log_handle.close()
            except OSError:
                pass
            self.log_handle = None


class Supervisor:
    def __init__(self, only_proc=None):
        self.conn = None
        self.children = {}   # proc_id -> Child
        self.only_proc = only_proc

    # ---------------- DB access ----------------
    def db(self):
        if self.conn is None or not self.conn.is_connected():
            self.conn = connect_db_with_retry()
        return self.conn

    def query(self, sql, params=None):
        cur = self.db().cursor(dictionary=True)
        cur.execute(sql, params or ())
        rows = cur.fetchall()
        cur.close()
        return rows

    def execute(self, sql, params=None):
        cur = self.db().cursor()
        cur.execute(sql, params or ())
        cur.close()

    def fetch_rows(self):
        if self.only_proc:
            return self.query(
                "SELECT * FROM {} WHERE proc_name = %s ORDER BY proc_name".format(TABLE),
                (self.only_proc,),
            )
        return self.query(
            "SELECT * FROM {} WHERE proc_name <> %s ORDER BY proc_name".format(TABLE),
            (SUPERVISOR_ROW,),
        )

    def update_row(self, proc_id, **cols):
        sets = ", ".join("{} = %s".format(c) for c in cols)
        self.execute(
            "UPDATE {} SET {} WHERE proc_id = %s".format(TABLE, sets),
            list(cols.values()) + [proc_id],
        )

    def heartbeat_row(self, proc_id):
        self.execute(
            "UPDATE {} SET last_heartbeat = NOW() WHERE proc_id = %s".format(TABLE),
            (proc_id,),
        )

    def supervisor_heartbeat(self):
        detail = "cycle every {}s".format(POLL_SECONDS)
        if self.only_proc:
            detail += "; scope={}".format(self.only_proc)
        self.execute(
            "UPDATE {} SET status='running', pid=%s, last_heartbeat=NOW(), "
            "status_detail=%s WHERE proc_name=%s".format(TABLE),
            (os.getpid(), detail, SUPERVISOR_ROW),
        )

    # ---------------- process control ----------------
    def resolve_log_path(self, row):
        path = (row.get("log_path") or "").strip()
        if not path:
            path = os.path.join(LOG_DIR, row["proc_name"] + ".log")
        elif not os.path.isabs(path):
            path = os.path.join(LOG_DIR, path)
        return os.path.abspath(path)

    def start_child(self, row):
        proc_id = row["proc_id"]
        child = self.children.setdefault(proc_id, Child())
        if child.running():
            return

        script = (row["script_path"] or "").strip()
        if not os.path.isfile(script):
            log.error("[%s] script not found: %s", row["proc_name"], script)
            self.update_row(proc_id, status="crashed", pid=None,
                            status_detail="Script not found: {}".format(script[:200]))
            child.gave_up = True
            return

        workdir = (row["working_dir"] or "").strip() or os.path.dirname(script)
        pyexe = (row["python_exe"] or "").strip()
        if not pyexe or pyexe.lower() in ("python", "python.exe"):
            pyexe = sys.executable
        log_path = self.resolve_log_path(row)

        try:
            os.makedirs(os.path.dirname(log_path), exist_ok=True)
            rotate_log(log_path)
            child.close_log()
            child.log_handle = open(log_path, "ab")
            banner = "\n----- supervisor start {} -----\n".format(datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
            child.log_handle.write(banner.encode())
            child.log_handle.flush()
        except OSError as e:
            log.error("[%s] log path is not writable: %s", row["proc_name"], e)
            child.close_log()
            self.update_row(proc_id, status="crashed", pid=None,
                            status_detail="Log path failed: {}".format(str(e)[:200]))
            child.gave_up = True
            return

        env = dict(os.environ)
        env["PYTHONUNBUFFERED"] = "1"   # otherwise child print() output sits in a buffer

        try:
            child.popen = subprocess.Popen(
                [pyexe, script],
                cwd=workdir,
                stdout=child.log_handle,
                stderr=subprocess.STDOUT,
                stdin=subprocess.DEVNULL,   # a bat ending in `pause` returns instead of hanging
                env=env,
                creationflags=CREATE_NO_WINDOW,
            )
        except OSError as e:
            log.error("[%s] failed to launch: %s", row["proc_name"], e)
            self.update_row(proc_id, status="crashed", pid=None,
                            status_detail="Launch failed: {}".format(str(e)[:200]))
            child.gave_up = True
            return

        child.expected_stop = False
        log.info("[%s] started pid %s", row["proc_name"], child.popen.pid)
        self.update_row(
            proc_id,
            status="running",
            pid=child.popen.pid,
            log_path=log_path,
            status_detail="",
        )
        self.execute(
            "UPDATE {} SET started_at = NOW(), last_heartbeat = NOW() WHERE proc_id = %s".format(TABLE),
            (proc_id,),
        )

    def stop_child(self, row, mark_status="stopped", detail="Stopped via dashboard"):
        proc_id = row["proc_id"]
        child = self.children.get(proc_id)
        if child and child.running():
            child.expected_stop = True
            kill_tree(child.popen.pid)
            try:
                child.popen.wait(timeout=15)
            except subprocess.TimeoutExpired:
                log.warning("[%s] pid %s did not exit after taskkill", row["proc_name"], child.popen.pid)
            log.info("[%s] stopped", row["proc_name"])
        if child:
            child.popen = None
            child.close_log()
            child.fail_times = []
            child.next_start_at = 0
            child.gave_up = False
        self.update_row(proc_id, status=mark_status, pid=None, status_detail=detail)

    # ---------------- startup reconciliation ----------------
    def reconcile(self):
        """After a supervisor crash/reboot the table may show stale pids, and the
        children themselves may still be alive (they outlive the supervisor).
        We cannot re-attach to them, so verify and kill leftovers, then start fresh."""
        for row in self.fetch_rows():
            pid = row["pid"]
            if pid:
                cmdline = pid_command_line(pid)
                script_path = os.path.normcase(os.path.abspath((row["script_path"] or "").strip()))
                normalized_cmdline = os.path.normcase(cmdline or "")
                if cmdline and script_path and script_path in normalized_cmdline:
                    log.info("[%s] leftover pid %s from previous run - killing tree", row["proc_name"], pid)
                    kill_tree(pid)
                else:
                    log.info("[%s] stale pid %s (process gone or reused)", row["proc_name"], pid)
            self.update_row(row["proc_id"], status="stopped", pid=None,
                            status_detail="Reset at supervisor startup")
        self.execute(
            "UPDATE {} SET status='running', pid=%s, started_at=NOW(), last_heartbeat=NOW(), "
            "log_path=%s, status_detail='Supervisor started' WHERE proc_name=%s".format(TABLE),
            (os.getpid(), os.path.join(LOG_DIR, "_supervisor.log"), SUPERVISOR_ROW),
        )

    # ---------------- command + lifecycle handling ----------------
    def clear_command(self, proc_id):
        self.execute(
            "UPDATE {} SET command = NULL, command_requested_at = NULL WHERE proc_id = %s".format(TABLE),
            (proc_id,),
        )

    def handle_command(self, row):
        cmd = row["command"]
        if not cmd:
            return
        log.info("[%s] command received: %s", row["proc_name"], cmd)
        child = self.children.setdefault(row["proc_id"], Child())
        if cmd == "stop":
            self.stop_child(row)
        elif cmd == "start":
            child.fail_times = []
            child.next_start_at = 0
            child.gave_up = False
            if not child.running():
                self.start_child(row)
        elif cmd == "restart":
            self.stop_child(row, mark_status="starting", detail="Restarting")
            child = self.children[row["proc_id"]]
            child.gave_up = False
            self.start_child(row)
        self.clear_command(row["proc_id"])

    def handle_exit(self, row, child):
        """Child exited without us stopping it."""
        code = child.popen.returncode
        child.popen = None
        child.close_log()
        if child.expected_stop:
            return
        now = time.time()
        child.fail_times = [t for t in child.fail_times if now - t < RAPID_FAIL_WINDOW] + [now]
        log.warning("[%s] exited unexpectedly with code %s (%s rapid failures)",
                    row["proc_name"], code, len(child.fail_times))
        self.execute(
            "UPDATE {} SET last_exit_code = %s, restart_count = restart_count + 1 WHERE proc_id = %s".format(TABLE),
            (code, row["proc_id"]),
        )
        if len(child.fail_times) >= MAX_RAPID_FAILS:
            child.gave_up = True
            self.update_row(row["proc_id"], status="crashed", pid=None,
                            status_detail="{} failures in {} min - giving up. Use Start to retry.".format(
                                len(child.fail_times), RAPID_FAIL_WINDOW // 60))
            log.error("[%s] giving up after repeated failures", row["proc_name"])
        else:
            child.next_start_at = now + RESTART_DELAY
            self.update_row(row["proc_id"], status="starting", pid=None,
                            status_detail="Exited with code {}. Restarting in {}s".format(code, RESTART_DELAY))

    def cycle(self):
        rows = self.fetch_rows()
        seen_ids = set()
        for row in rows:
            seen_ids.add(row["proc_id"])
            child = self.children.setdefault(row["proc_id"], Child())

            self.handle_command(row)

            # detect exits
            if child.popen is not None and child.popen.poll() is not None:
                self.handle_exit(row, child)

            # keep enabled scripts running (respecting backoff / give-up)
            if int(row["enabled"] or 0) == 1 and not child.running() and not child.gave_up:
                if time.time() >= child.next_start_at and not row["command"]:
                    self.start_child(row)

            if child.running():
                self.heartbeat_row(row["proc_id"])

        # rows deleted from the table -> stop their children
        for proc_id in list(self.children.keys()):
            if proc_id not in seen_ids:
                child = self.children.pop(proc_id)
                if child.running():
                    log.info("Row for pid %s removed from table - stopping child", child.popen.pid)
                    kill_tree(child.popen.pid)
                child.close_log()

        self.supervisor_heartbeat()

    def shutdown(self):
        log.info("Shutting down - stopping all children")
        for row in self.fetch_rows():
            child = self.children.get(row["proc_id"])
            if child and child.running():
                self.stop_child(row, detail="Supervisor shut down")
        self.execute(
            "UPDATE {} SET status='stopped', pid=NULL, status_detail='Supervisor shut down' "
            "WHERE proc_name=%s".format(TABLE),
            (SUPERVISOR_ROW,),
        )

    def run(self):
        self.conn = connect_db_with_retry()
        self.reconcile()
        if self.only_proc:
            log.info("Supervisor running (pid %s), scope=%s, polling every %ss",
                     os.getpid(), self.only_proc, POLL_SECONDS)
        else:
            log.info("Supervisor running (pid %s), polling every %ss", os.getpid(), POLL_SECONDS)
        while True:
            try:
                self.cycle()
            except MySQLError as e:
                log.warning("MySQL error mid-cycle (%s) - reconnecting", e)
                try:
                    self.conn.close()
                except Exception:
                    pass
                self.conn = None
            time.sleep(POLL_SECONDS)


def run_preflight(expect_prod=False):
    """Read-only deployment checks. This never launches a managed job."""
    errors = []
    print("Process Supervisor preflight")
    print("Python: {}".format(sys.executable))
    print("Database: {}:{}/{} as {}".format(
        DB_CONFIG["host"], DB_CONFIG["port"], DB_CONFIG["database"], DB_CONFIG["user"]
    ))

    for module_name in ("mysql.connector", "schedule", "pytz"):
        try:
            importlib.import_module(module_name)
            print("[OK] import {}".format(module_name))
        except Exception as e:
            errors.append("import {} failed: {}".format(module_name, e))
            print("[FAIL] import {}".format(module_name))

    try:
        os.makedirs(LOG_DIR, exist_ok=True)
        with tempfile.NamedTemporaryFile(prefix="preflight_", suffix=".tmp", dir=LOG_DIR, delete=True):
            pass
        print("[OK] log directory writable: {}".format(LOG_DIR))
    except OSError as e:
        errors.append("log directory is not writable: {}".format(e))
        print("[FAIL] log directory writable: {}".format(LOG_DIR))

    conn = None
    try:
        conn = connect_db_once()
        cur = conn.cursor(dictionary=True)
        cur.execute(
            "SELECT TABLE_NAME FROM information_schema.TABLES "
            "WHERE TABLE_SCHEMA=%s AND TABLE_NAME IN "
            "('managed_processes','managed_process_catalog','managed_process_steps','managed_process_impacts')",
            (DB_CONFIG["database"],),
        )
        found_tables = {row["TABLE_NAME"] for row in cur.fetchall()}
        required_tables = {
            "managed_processes", "managed_process_catalog",
            "managed_process_steps", "managed_process_impacts",
        }
        for table_name in sorted(required_tables):
            if table_name in found_tables:
                print("[OK] table {}".format(table_name))
            else:
                errors.append("missing table {}".format(table_name))
                print("[FAIL] table {}".format(table_name))

        if "managed_processes" in found_tables:
            cur.execute(
                "SELECT proc_name, script_path FROM managed_processes "
                "WHERE proc_name <> %s ORDER BY proc_name",
                (SUPERVISOR_ROW,),
            )
            rows = cur.fetchall()
            configured = {row["proc_name"]: row["script_path"] for row in rows}
            expected = set(PROD_EXPECTED_PROCESSES) if expect_prod else set(configured)
            for proc_name in sorted(expected):
                script_path = configured.get(proc_name)
                if not script_path:
                    errors.append("{} is not registered".format(proc_name))
                    print("[FAIL] {} registered".format(proc_name))
                elif not os.path.isfile(script_path):
                    errors.append("{} script not found: {}".format(proc_name, script_path))
                    print("[FAIL] {} path: {}".format(proc_name, script_path))
                else:
                    print("[OK] {} path: {}".format(proc_name, script_path))

        if expect_prod and {
            "managed_process_catalog", "managed_process_steps", "managed_process_impacts"
        }.issubset(found_tables):
            cur.execute(
                "SELECT c.proc_name, c.source_verified, c.production_path_verified, "
                "COUNT(DISTINCT s.step_id) AS step_count, "
                "COUNT(DISTINCT i.impact_id) AS impact_count "
                "FROM managed_process_catalog c "
                "LEFT JOIN managed_process_steps s ON s.proc_name=c.proc_name "
                "LEFT JOIN managed_process_impacts i ON i.proc_name=c.proc_name "
                "WHERE c.is_production=1 "
                "GROUP BY c.proc_name, c.source_verified, c.production_path_verified"
            )
            coverage = {row["proc_name"]: row for row in cur.fetchall()}
            for proc_name in sorted(PROD_EXPECTED_PROCESSES):
                row = coverage.get(proc_name)
                if row is None:
                    errors.append("{} is missing from the production catalog".format(proc_name))
                    print("[FAIL] {} catalog entry".format(proc_name))
                    continue
                ready = (
                    int(row["source_verified"] or 0) == 1
                    and int(row["production_path_verified"] or 0) == 1
                    and int(row["step_count"] or 0) > 0
                    and int(row["impact_count"] or 0) > 0
                )
                if ready:
                    print("[OK] {} lineage coverage".format(proc_name))
                else:
                    errors.append("{} lineage/source/path verification is incomplete".format(proc_name))
                    print("[FAIL] {} lineage coverage".format(proc_name))
        cur.close()
    except Exception as e:
        errors.append("database preflight failed: {}".format(e))
        print("[FAIL] database connection/schema check")
    finally:
        if conn is not None:
            try:
                conn.close()
            except Exception:
                pass

    if errors:
        print("")
        print("Preflight failed with {} issue(s):".format(len(errors)))
        for error in errors:
            print(" - {}".format(error))
        return 1
    print("")
    print("Preflight passed. No managed jobs were launched.")
    return 0


def main():
    if "--preflight" in sys.argv:
        return run_preflight(expect_prod="--expect-prod" in sys.argv)
    only_proc = None
    if "--only" in sys.argv:
        option_index = sys.argv.index("--only")
        if option_index + 1 >= len(sys.argv) or sys.argv[option_index + 1].startswith("--"):
            print("Usage: process_supervisor.py [--only PROC_NAME]")
            return 2
        only_proc = sys.argv[option_index + 1].strip()
        if not only_proc or only_proc == SUPERVISOR_ROW:
            print("--only requires a registered child process name.")
            return 2
    setup_logging()
    lock = acquire_single_instance_lock()
    if lock is None:
        # Another instance is already running - normal when the watchdog task fires.
        print("Supervisor already running - exiting.")
        return 0
    sup = Supervisor(only_proc=only_proc)
    try:
        sup.run()
    except KeyboardInterrupt:
        sup.shutdown()
    except Exception:
        log.exception("Fatal supervisor error")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
