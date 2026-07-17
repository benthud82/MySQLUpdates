# Process Supervisor and Admin Process Monitor

Keeps the long-running Python scheduler loops alive without console windows,
captures their output, provides controlled retry/backoff, and reports process
status to `nahsi/admin_processes.php` (Admin > Process Monitor).

The monitor also includes an Operational Impact catalog. It documents the
intended execution chain, MySQL reads/writes, affected application pages,
external outputs, and verification state for every production loop.

For the production file inventory, disabled-first installation, one-job-at-a-time
cutover, validation, and rollback procedure, use
`PROCESS_MONITOR_PROD_DEPLOYMENT.md`.

## What a green status means

Green means the supervisor sees a live child PID and is refreshing its process
heartbeat. It does **not** prove that:

- the Python scheduler is internally responsive;
- its scheduled business update has run today; or
- every downstream batch/PHP/database step succeeded.

The impact modal keeps three separate verification states:

- **Source verified** - code was traced without executing the update.
- **Production paths verified** - the production files were inspected and exist.
- **Runtime observed** - a naturally scheduled business update was later confirmed.

`Runtime not yet observed` is expected immediately after deployment.

## Architecture

```text
Windows Scheduled Tasks (boot + 15-minute watchdog)
  -> process_supervisor.py
       -> launches enabled Python loops hidden
       -> logs/<proc_name>.log
       -> nahsi.managed_processes status/commands

Admin > Process Monitor
  -> get_process_status.php (5-second process status)
  -> get_process_detail.php (on-demand lineage/impact)
  -> process_action.php (admin + CSRF protected commands)
```

The supervisor reads its managed-job list from MySQL. It does not discover
Python files from the filesystem.

## Deployment inventory (three repositories)

Deploy only the process-monitor files. The local repositories contain unrelated
working-tree changes, so do not use an unreviewed `git add -A` or blanket copy.

### MySQLUpdates

- `process_supervisor/process_supervisor.py`
- `process_supervisor/install_supervisor_task.bat`
- `process_supervisor/install_supervisor_task.ps1`
- `process_supervisor/uninstall_supervisor_task.bat`
- `process_supervisor/run_supervisor_console.bat`
- `process_supervisor/README.md`
- `process_supervisor/PROCESS_MONITOR_PROD_DEPLOYMENT.md`
- `process_supervisor/.gitignore`
- `process_supervisor/sql/managed_processes.sql`
- `process_supervisor/sql/seed_prod.sql`
- `process_supervisor/sql/process_impact_catalog.sql`
- `process_supervisor/sql/seed_process_impact_catalog.sql`
- `process_supervisor/sql/process_impact_coverage.sql`

Runtime logs and Python bytecode are ignored and must not be deployed from a
developer machine. `test_loop.py` and `sql/seed_local.sql` are local-only and
must not be registered or run in production.

### nahsi

- `admin_processes.php`
- `get_process_status.php`
- `get_process_detail.php`
- `process_action.php`
- `css/process_monitor.css`

### global_dash

- `verticalnav.php` Process Monitor link only; preserve unrelated changes in
  that shared file.

## Production prerequisites

- Run the install step from an elevated Administrator shell.
- Confirm the real Python installation that will be used by SYSTEM:

```bat
where python
python --version
python -c "import sys; print(sys.executable)"
python -m pip install mysql-connector-python schedule pytz
python -c "import mysql.connector, schedule, pytz; print('imports OK')"
```

Do not use an unqualified `pip`; it can install into a different interpreter.
The installer rejects Windows Store Python aliases.

The supervisor uses its own `sys.executable` for rows whose `python_exe` is
blank or `python`. An explicit absolute per-job interpreter remains supported.

### Database configuration

Defaults match the existing XAMPP environment: `127.0.0.1:3306`, database
`nahsi`, user `root`, blank password. Override without editing source by setting
machine-level environment variables visible to SYSTEM:

- `PROCESS_SUPERVISOR_DB_HOST`
- `PROCESS_SUPERVISOR_DB_PORT`
- `PROCESS_SUPERVISOR_DB_USER`
- `PROCESS_SUPERVISOR_DB_PASSWORD`
- `PROCESS_SUPERVISOR_DB_NAME`
- `PROCESS_SUPERVISOR_DB_TIMEOUT_SECONDS` (default `10`)

Never print or commit the password.

## Database install order

Run these against production MySQL in order:

1. `sql/managed_processes.sql`
2. `sql/seed_prod.sql`
3. `sql/process_impact_catalog.sql`
4. `sql/seed_process_impact_catalog.sql`
5. Review `sql/process_impact_coverage.sql`

The production seed registers exactly 18 loops, including
`receiving_cost_loop`. Fresh registrations are deliberately **disabled** so
the supervisor cannot duplicate standalone production jobs during deployment.
Re-running the seed corrects
`script_path`, `working_dir`, `python_exe`, and `log_path` but deliberately
preserves enabled state, commands, PID/status, heartbeats, and counters. Restart
a running job after changing its path or interpreter.

After seeding, compare the registered names and paths:

```sql
SELECT proc_name, script_path, python_exe, enabled
FROM nahsi.managed_processes
WHERE proc_name <> '__supervisor__'
ORDER BY proc_name;
```

Remove a stale local `test_loop` row from production if it was accidentally
seeded there. Do not delete a production row while its child is still needed;
the supervisor interprets deletion as a stop request.

## Read-only production lineage inventory

Do not execute the 18 business updates during documentation. For each job:

1. Open the production Python file and record its schedule/timezone.
2. Trace every `os.system`, `subprocess`, batch, PHP, and secondary Python call.
3. Confirm every referenced production path exists.
4. Inspect downstream SQL and classify reads, writes, rebuilds, appends, and
   external exports.
5. Search dashboard/application source for pages consuming those tables.
6. Record explicit no-write/no-page declarations where applicable.
7. Update the version-controlled catalog seed and mark Source verified only
   when the complete chain is supported by evidence.
8. Mark Production paths verified only after the production files were checked:

```sql
UPDATE nahsi.managed_process_catalog
SET production_path_verified = 1,
    production_path_verified_at = NOW()
WHERE proc_name = 'verified_process_name';
```

Run this narrowly for each inspected process; never bulk-mark uninspected paths.

The initial repository audit fully source-verified four chains:

- `breaklunch_ftp_1`
- `breaklunch_ftp_2`
- `offsys_slot_update`
- `jobsched_loops`

Other catalog entries intentionally show amber verification requirements until
their production-only wrappers are inspected. Do not convert candidate lineage
into a verified claim without evidence.

Production sign-off requires `process_impact_coverage.sql` to return:

- `production_catalog_count = 18`
- `production_ready_count = 18`

Runtime observation is tracked separately and is not required for deployment.

## No-launch preflight

After SQL and catalog installation, run:

```bat
python D:\xampp\htdocs\MySQLUpdates\process_supervisor\process_supervisor.py --preflight --expect-prod
```

This checks the exact interpreter imports, database/schema, four required
catalog/status tables, log-directory writability, all 18 registrations, and all
18 main script paths. It does not start a managed loop.

Resolve every `[FAIL]` before installing Scheduled Tasks.

## Avoid duplicate processes

Do not stop every existing standalone job at the beginning of deployment. The
production seed registers all 18 business jobs with `enabled=0`, so files, SQL,
the dashboard, preflight, and the Scheduled Tasks can be installed while the
current standalone jobs continue running. Before starting the Boot task, verify
that every non-supervisor row is disabled.

Cut over one job at a time: identify and stop that job's old console or
Scheduled Task, confirm its process is gone, then use **Start** in Process
Monitor. The supervisor can reconcile PIDs it owns or has recorded, but it
cannot reliably identify every unrelated historical console. Never leave the
standalone and supervisor-managed copy of the same job running together.

## Install Scheduled Tasks

From an elevated Administrator shell:

```bat
D:\xampp\htdocs\MySQLUpdates\process_supervisor\install_supervisor_task.bat
```

The PowerShell installer:

- resolves and validates the absolute Python executable;
- validates required imports;
- runs the read-only production preflight;
- creates Boot and 15-minute Watchdog tasks as SYSTEM;
- sets unlimited execution time, `IgnoreNew`, start-when-available, and
  restart-on-failure settings;
- starts the Boot task and displays both registered actions.

Verify independently:

```powershell
Get-ScheduledTask -TaskName PyProcessSupervisor_Boot,PyProcessSupervisor_Watchdog |
  Select-Object TaskName,State,Principal,Actions,Settings
Get-ScheduledTaskInfo -TaskName PyProcessSupervisor_Boot
Get-ScheduledTaskInfo -TaskName PyProcessSupervisor_Watchdog
```

## Dashboard and log validation

1. Open `/nahsi/admin_processes.php` as an administrator.
2. Confirm the supervisor banner is clear and its heartbeat is fresh.
3. Confirm exactly 18 production rows are present and initially disabled.
4. Confirm scheduler PIDs remain green. This can be done without firing their
   scheduled business work.
5. Open several Impact modals and confirm execution, tables, pages, evidence,
   and verification badges.
6. Review `logs/_supervisor.log` and representative child logs.
7. Test Start, Stop, and Restart for a safe job and confirm CSRF-protected
   command handling.
8. Reboot when practical and confirm both task state and process recovery.

The supervisor retries an unexpectedly exited child after 30 seconds. Five
failures within ten minutes mark it crashed and stop retries until an admin uses
Start. Stop kills the recorded process tree and disables automatic restart.

## Natural runtime observation

Allow each job to reach its normal production schedule. Mark Runtime observed
only after its log/output and expected database or external result confirm a
successful business execution. A live PID or process heartbeat alone is not
runtime evidence.

## Rollback / uninstall

1. Use **Stop All** in the dashboard and wait for every child to show stopped.
2. Run `uninstall_supervisor_task.bat` as Administrator; it ends and deletes
   both Boot and Watchdog tasks.
3. Check for a manually detached `process_supervisor.py` process and terminate
   that exact process tree if present.
4. Verify the recorded child PIDs are gone.
5. Preserve `logs/` and the database rows for diagnosis; do not delete evidence
   during incident response.

## Operational notes

- Adding a catalog row documents a job; adding a `managed_processes` row makes
  it runnable. These are intentionally separate so missing registrations appear
  in coverage checks.
- Log paths default to `logs/<proc_name>.log`; relative custom paths are resolved
  under the log directory.
- Child logs rotate at 5 MB when the child starts. The supervisor log uses a
  rotating handler continuously.
- A second supervisor exits through the loopback single-instance lock. The
  watchdog can safely fire while the primary process is alive.
- MySQL startup delays are retried. Fatal non-MySQL errors are written to
  `_supervisor.log` before the task exits.
- Headless children receive EOF on stdin, so a simple batch `pause` returns;
  truly interactive prompts are not supported.
