# Process Supervisor and Process Monitor — Production Deployment Guide

**Prepared:** 2026-07-14

**Production web root:** `D:\xampp\htdocs`

**Goal:** deploy the supervisor and Admin > Process Monitor without duplicating or interrupting the production scheduler loops that are currently running standalone.

Companion document: `README.md` describes normal operation and the impact catalog. This guide covers the production deployment, validation, cutover, and rollback sequence.

---

## 1. The short answer about interference

Copying the files, creating the four monitor tables, seedin1g the catalog, and opening the dashboard do **not** start or stop a business process.

Starting `process_supervisor.py` can interfere if a registered row has `enabled=1` while the same script is already running from a console, batch file, or Scheduled Task. That would create two copies of the same scheduler and could duplicate database refreshes, exports, AS/400 reads, or file transfers.

The safe deployment model is therefore:

1. Deploy the files and SQL while the current standalone jobs keep running.
2. Force all 18 production registrations to `enabled=0`.
3. Deploy and test the dashboard.
4. Complete the no-launch production preflight.
5. Install the supervisor Scheduled Tasks. The supervisor itself starts, but no business job starts because every row is disabled.
6. Cut over one job at a time: stop that job's old launcher, confirm its process is gone, then start it from Process Monitor.

Do **not** stop all current scripts at the beginning. Do **not** click **Start All** during the staged cutover.

## 2. Architecture and ownership

```text
Windows Scheduled Tasks
  PyProcessSupervisor_Boot       (at startup)
  PyProcessSupervisor_Watchdog   (every 15 minutes)
            |
            v
process_supervisor.py, running as SYSTEM
  - loopback single-instance lock on 127.0.0.1:58472
  - polls nahsi.managed_processes every 5 seconds
  - launches enabled children with no console window
  - writes child stdout/stderr to process_supervisor\logs
  - updates PID, status, command, and heartbeat state in MySQL
            |
            v
/nahsi/admin_processes.php
  - status feed: get_process_status.php
  - impact/lineage detail: get_process_detail.php
  - admin commands: process_action.php
```

Important behavior:

- A green child row means the supervisor sees a live child PID and is refreshing its heartbeat. It does not prove the scheduled business update succeeded.
- **Start** and **Restart** set `enabled=1`. **Stop** sets `enabled=0`.
- Stop/Restart uses `taskkill /T /F` on the recorded child process tree. Use it during a safe operating window because it is a hard stop, not a graceful application-level shutdown.
- An unexpected child exit is retried after 30 seconds. Five failures inside ten minutes mark the row `crashed` and stop automatic retries until an administrator uses Start.
- A second supervisor exits because of the loopback lock. The 15-minute watchdog can therefore run safely while the Boot instance is healthy.
- If MySQL is unavailable at boot, the supervisor retries the connection every 15 seconds.
- Child logs rotate at 5 MB when a child starts. `_supervisor.log` rotates continuously at 2 MB with three backups.



## 3. Deployment inventory

There are three repository surfaces. Copy only the files listed here; the local repositories contain unrelated working-tree changes.

### 3.1 `MySQLUpdates`

Production destination: `D:\xampp\htdocs\MySQLUpdates\process_supervisor\`

Deploy:

- `process_supervisor.py`
- `install_supervisor_task.bat`
- `install_supervisor_task.ps1`
- `uninstall_supervisor_task.bat`
- `run_supervisor_console.bat`
- `README.md`
- `PROCESS_MONITOR_PROD_DEPLOYMENT.md`
- `.gitignore`
- `sql\managed_processes.sql`
- `sql\seed_prod.sql`
- `sql\process_impact_catalog.sql`
- `sql\seed_process_impact_catalog.sql`
- `sql\process_impact_coverage.sql`

Create an empty writable directory:

- `logs\`

Do not deploy from the developer machine:

- `logs\*.log`
- `logs\*.1`
- `__pycache__\`
- `*.pyc` or `*.pyo`
- `sql\seed_local.sql`
- `test_loop.py` unless it is being copied temporarily fo4r an explicitly approved non-production control test; never register `test_loop` as a production job.



### 3.2 `nahsi`

Production destination: `D:\xampp\htdocs\nahsi\`

- `admin_processes.php`
- `get_process_status.php`
- `get_process_detail.php`
- `process_action.php`
- `css\process_monitor.css`

These endpoints use the existing shared session, database connection, and administrator authorization includes. They must remain administrator-only.

### 3.3 `global_dash`

Production destination: `D:\xampp\htdocs\global_dash\verticalnav.php`

Deploy only this NAHSI-only navigation block in the Admin section:

```php
<?php if (isset($schema) && $schema === 'nahsi') { ?>
<li id="admin_processes_link" class="">
    <a href="admin_processes.php">
        <i class="menu-icon fa fa-heartbeat"></i>Process Monitor
    </a>
</li>
<?php } ?>
```

Do not replace the entire production `verticalnav.php` without comparing it first. It is a shared file and may contain unrelated production-only navigation changes.

The dashboard can also be tested directly at `/nahsi/admin_processes.php` before publishing the navigation link.

## 4. The 18 production registrations

The production seed contains these jobs. The first 17 use the same Python executable that launches the supervisor when `python_exe='python'`. Receiving Location Cost explicitly uses production PHP.


| Process name               | Production entry point                                           | Interpreter            |
| -------------------------- | ---------------------------------------------------------------- | ---------------------- |
| `gill_update`              | `D:\xampp\htdocs\gillingham\MySQLUpdates\gill_update.py`         | Supervisor Python      |
| `hep_update`               | `D:\xampp\htdocs\hep\MySQLUpdates\hep_update.py`                 | Supervisor Python      |
| `ukgill_update_all_tables` | `D:\xampp\htdocs\emea_ukgill_logic\update_all_tables.py`         | Supervisor Python      |
| `breaklunch_ftp_1`         | `D:\xampp\htdocs\breaklunch\table_sched_ftp_1.py`                | Supervisor Python      |
| `breaklunch_ftp_2`         | `D:\xampp\htdocs\breaklunch\table_sched_ftp_2.py`                | Supervisor Python      |
| `breaklunch_ftp_locus`     | `D:\xampp\htdocs\breaklunch\table_sched_ftp_locus.py`            | Supervisor Python      |
| `gallin_update`            | `D:\xampp\htdocs\gallin_logic\gallin_update.py`                  | Supervisor Python      |
| `offsys_slot_update`       | `D:\xampp\htdocs\heatmap_logic\offsys_slot_update.py`            | Supervisor Python      |
| `offsys_slot_update_CAN`   | `D:\xampp\htdocs\heatmap_logic\offsys_slot_update_CAN.py`        | Supervisor Python      |
| `nahsi_shorts_item`        | `D:\xampp\htdocs\heatmap_logic\tbl_update_NAHSI_shorts_item.py`  | Supervisor Python      |
| `jobsched_loops`           | `D:\xampp\htdocs\MySQLUpdates\jobsched_loops.py`                 | Supervisor Python      |
| `table_sched_mysqlupdates` | `D:\xampp\htdocs\MySQLUpdates\table_sched_mysqlupdates.py`       | Supervisor Python      |
| `todaypage_loop`           | `D:\xampp\htdocs\MySQLUpdates\todaypage_loop.py`                 | Supervisor Python      |
| `printvis_caserefresh`     | `D:\xampp\htdocs\printvis\datapull\jobsched_caserefresh_bat.py`  | Supervisor Python      |
| `printvis_table_sched`     | `D:\xampp\htdocs\printvis\datapull\table_sched.py`               | Supervisor Python      |
| `printvis_loc_oh_loop`     | `D:\xampp\htdocs\printvis_logic\loc_oh_loop.py`                  | Supervisor Python      |
| `printvis_pack_start_loop` | `D:\xampp\htdocs\printvis_logic\pack_start_loop.py`              | Supervisor Python      |
| `receiving_cost_loop`      | `D:\xampp\htdocs\heatmap_logic\receiving_location_cost_loop.php` | `D:\xampp\php\php.exe` |


If any production path differs, change `sql\seed_prod.sql` before running it. Do not correct production paths only in the database and leave the version-controlled seed inaccurate.

## 5. Production prerequisites



### 5.1 Administrator access

The Scheduled Task installer must be run from an elevated Administrator shell. It registers both tasks as Windows `SYSTEM` with highest privileges.

### 5.2 Python and required modules

From an elevated PowerShell window on production:

```powershell
where.exe python
python --version
python -c "import sys; print(sys.executable)"
python -m pip install mysql-connector-python schedule pytz
python -c "import mysql.connector, schedule, pytz; print('imports OK')"
```

Use `python -m pip`, not an unqualified `pip`. The installer rejects a Windows Store alias under `WindowsApps`.

The Scheduled Task action stores the absolute Python executable resolved during installation. Install the packages where that interpreter can load them when running as `SYSTEM`; a package installed only in the administrator's user-site directory may not be visible to `SYSTEM`.

### 5.3 SYSTEM-account dependency warning

Every managed child inherits the supervisor's `SYSTEM` identity. This may differ from the logged-in account currently running the standalone consoles.

Before each job is cut over, confirm that `SYSTEM` can access its complete execution chain, including:

- system ODBC DSNs and AS/400 drivers;
- SQL Server or other database drivers;
- network paths and UNC shares;
- FTP/SFTP credentials and host keys;
- writable export and log directories;
- downstream `.bat`, PHP, Python, R, or executable files;
- environment variables used by the job.

Mapped drive letters and user-scoped ODBC DSNs are common failure points for `SYSTEM`. Prefer UNC paths and System DSNs where the existing job permits it.

The supervisor preflight confirms main script paths and catalog coverage. It does not execute the business jobs or prove that every downstream credential works under `SYSTEM`.

### 5.4 Database connection

Defaults in `process_supervisor.py` are:

- host: `127.0.0.1`
- port: `3306`
- database: `nahsi`
- user: `root`
- password: blank

If production differs, set machine-level environment variables visible to `SYSTEM`:

- `PROCESS_SUPERVISOR_DB_HOST`
- `PROCESS_SUPERVISOR_DB_PORT`
- `PROCESS_SUPERVISOR_DB_USER`
- `PROCESS_SUPERVISOR_DB_PASSWORD`
- `PROCESS_SUPERVISOR_DB_NAME`
- `PROCESS_SUPERVISOR_DB_TIMEOUT_SECONDS` (default `10`)

Example from elevated PowerShell:

```powershell
[Environment]::SetEnvironmentVariable('PROCESS_SUPERVISOR_DB_HOST', '127.0.0.1', 'Machine')
[Environment]::SetEnvironmentVariable('PROCESS_SUPERVISOR_DB_PORT', '3306', 'Machine')
[Environment]::SetEnvironmentVariable('PROCESS_SUPERVISOR_DB_USER', 'root', 'Machine')
[Environment]::SetEnvironmentVariable('PROCESS_SUPERVISOR_DB_NAME', 'nahsi', 'Machine')
```

Set the password separately without printing it into a console transcript, source file, or deployment guide. Restart the Scheduled Tasks after changing machine-level variables.

## 6. Phase A — deploy without touching current jobs



### 6.1 Inventory the currently running standalone jobs

Capture the current process and Scheduled Task state before changing anything:

```powershell
Get-CimInstance Win32_Process |
    Where-Object { $_.Name -in @('python.exe', 'python3.exe', 'php.exe') } |
    Select-Object ProcessId, ParentProcessId, Name, CommandLine |
    Sort-Object Name, ProcessId

Get-ScheduledTask |
    Where-Object { $_.Actions.Execute -match 'python|php|\.bat' } |
    Select-Object TaskName, State, TaskPath, Actions
```

Record, for each of the 18 job names:

- its current PID and parent PID;
- its current launcher: console, batch, Scheduled Task, or another wrapper;
- the task name if applicable;
- who it runs as;
- its normal business schedule and timezone;
- how to verify its last successful business output;
- how to restore its old launcher during rollback.

This inventory is required for avoiding duplicates. Do not infer the old launcher solely from the script filename.

### 6.2 Copy the files

Copy the exact inventory in Section 3 to production. Keep `.bak` copies of the five `nahsi` files and `verticalnav.php` if any already exist.

Create and check the log directory:

```powershell
New-Item -ItemType Directory -Force -Path 'D:\xampp\htdocs\MySQLUpdates\process_supervisor\logs'
icacls 'D:\xampp\htdocs\MySQLUpdates\process_supervisor\logs'
```

The directory must be writable by `SYSTEM` and readable by the Apache/PHP identity so the administrator dashboard can display log tails.

### 6.3 Validate copied source files

```powershell
python -m py_compile D:\xampp\htdocs\MySQLUpdates\process_supervisor\process_supervisor.py

D:\xampp\php\php.exe -l D:\xampp\htdocs\nahsi\admin_processes.php
D:\xampp\php\php.exe -l D:\xampp\htdocs\nahsi\get_process_status.php
D:\xampp\php\php.exe -l D:\xampp\htdocs\nahsi\get_process_detail.php
D:\xampp\php\php.exe -l D:\xampp\htdocs\nahsi\process_action.php
```

Do not start `run_supervisor_console.bat` yet.

## 7. Install the database objects in disabled state

The monitor uses four tables:

- `nahsi.managed_processes`
- `nahsi.managed_process_catalog`
- `nahsi.managed_process_steps`
- `nahsi.managed_process_impacts`

From a production MySQL client, run these scripts in this order:

1. `sql\managed_processes.sql`
2. `sql\seed_prod.sql`
3. `sql\process_impact_catalog.sql`
4. `sql\seed_process_impact_catalog.sql`
5. Review the output of `sql\process_impact_coverage.sql`

From Command Prompt with the default blank-password connection:

```bat
D:\xampp\mysql\bin\mysql.exe -u root nahsi < D:\xampp\htdocs\MySQLUpdates\process_supervisor\sql\managed_processes.sql
D:\xampp\mysql\bin\mysql.exe -u root nahsi < D:\xampp\htdocs\MySQLUpdates\process_supervisor\sql\seed_prod.sql
D:\xampp\mysql\bin\mysql.exe -u root nahsi < D:\xampp\htdocs\MySQLUpdates\process_supervisor\sql\process_impact_catalog.sql
D:\xampp\mysql\bin\mysql.exe -u root nahsi < D:\xampp\htdocs\MySQLUpdates\process_supervisor\sql\seed_process_impact_catalog.sql
D:\xampp\mysql\bin\mysql.exe -u root nahsi < D:\xampp\htdocs\MySQLUpdates\process_supervisor\sql\process_impact_coverage.sql
```

The revised production seed inserts new jobs with `enabled=0`. On duplicate rows it deliberately preserves existing enabled/status/PID/command state. Therefore, on a first staged deployment, and only after confirming no supervisor is currently running, force the safe state explicitly:

```sql
UPDATE nahsi.managed_processes
SET enabled = 0,
    command = NULL,
    command_requested_at = NULL,
    status = 'stopped',
    pid = NULL,
    status_detail = 'Staged for supervised cutover'
WHERE proc_name <> '__supervisor__';
```

Verify the registration:

```sql
SELECT COUNT(*) AS production_job_count
FROM nahsi.managed_processes
WHERE proc_name <> '__supervisor__';
-- Expect 18.

SELECT SUM(enabled = 0) AS disabled_count,
       SUM(enabled = 1) AS enabled_count
FROM nahsi.managed_processes
WHERE proc_name <> '__supervisor__';
-- Expect disabled_count=18 and enabled_count=0.

SELECT proc_name, script_path, python_exe, enabled, status, pid, command
FROM nahsi.managed_processes
ORDER BY proc_name;
```

If `test_loop` exists from a local seed, do not leave it in the production list. Confirm it has no PID and delete that row before go-live.

## 8. Deploy and validate the administrator dashboard

Open:

```text
http://<production-server>/nahsi/admin_processes.php
```

Before the supervisor is installed, the expected state is:

- the page is accessible to an administrator;
- a non-administrator is denied by the existing admin check;
- 18 production process rows appear;
- all 18 rows are disabled/stopped;
- the supervisor banner shows stopped or no fresh heartbeat;
- the Impact modal loads catalog steps and impacts;
- no business process starts merely by refreshing the page.

Do not click Start, Restart, Start All, Stop All, or any bulk action during this page-only validation.

The dashboard action endpoint is POST-only, administrator-protected, and CSRF-protected. Confirm an unauthenticated or non-admin direct request is rejected rather than returning process data.

## 9. Complete the production lineage and path gate

The catalog deliberately separates three claims:

- **Source verified:** the complete code path was traced without running the job.
- **Production paths verified:** every required production path was inspected and exists.
- **Runtime observed:** a later natural business execution was confirmed from its real output.

Runtime observation is not required to install the supervisor. Source and production-path verification are required by `--preflight --expect-prod`.

### 9.1 Find incomplete catalog entries

Run `sql\process_impact_coverage.sql`. Its final totals must eventually be:

```text
production_catalog_count = 18
production_ready_count   = 18
```

Amber rows are intentional until their source chain and production paths have actually been inspected. Do not bulk-mark all jobs verified merely to make preflight green.

### 9.2 Verify main entry points

The following PowerShell check reads the registered paths without launching them:

```powershell
$mysql = 'D:\xampp\mysql\bin\mysql.exe'
$rows = & $mysql -u root -N -B -e "SELECT CONCAT(proc_name, CHAR(9), script_path) FROM nahsi.managed_processes WHERE proc_name <> '__supervisor__' ORDER BY proc_name"
$rows | ForEach-Object {
    $parts = $_ -split "`t", 2
    [pscustomobject]@{
        Process = $parts[0]
        Exists  = Test-Path -LiteralPath $parts[1] -PathType Leaf
        Path    = $parts[1]
    }
} | Format-Table -AutoSize
```

Every entry must report `Exists=True`.

### 9.3 Verify the complete required path chain

Use the catalog to list every required downstream path:

```sql
SELECT proc_name, step_order, step_type, display_label, object_path,
       source_verified, production_path_required
FROM nahsi.managed_process_steps
WHERE production_path_required = 1
ORDER BY proc_name, step_order;
```

For each process, inspect every Python, batch, PHP, R, executable, external export, and nested subprocess hop. Confirm the production file exists and record the evidence. Only then mark that one process's production paths verified:

```sql
UPDATE nahsi.managed_process_catalog
SET production_path_verified = 1,
    production_path_verified_at = NOW()
WHERE proc_name = 'verified_process_name';
```

Source verification belongs in `sql\seed_process_impact_catalog.sql`, including accurate steps and impacts. Re-run the seed after a reviewed catalog correction. Do not convert candidate lineage into a verified statement without evidence.

### 9.4 Receiving Location Cost prerequisite

Before `receiving_cost_loop` is eligible for cutover:

- deploy the corrected `receiving_location_cost_downstream.php` and `receiving_location_cost_runner.php`;
- run all four Receiving self-tests;
- complete one successful `--mode=once --whse=7` cycle;
- confirm the updater heartbeat stays fresh during the downstream stage;
- confirm no standalone receiving loop remains when the supervisor-managed copy starts.



## 10. No-launch production preflight

Run from an elevated shell:

```bat
python D:\xampp\htdocs\MySQLUpdates\process_supervisor\process_supervisor.py --preflight --expect-prod
```

This check does not launch a managed job. It verifies:

- the exact Python interpreter and imports;
- MySQL connectivity and schema;
- all four required tables;
- log-directory writability;
- all 18 production registrations;
- all 18 main entry-point paths;
- source/path/step/impact coverage for all 18 catalog entries.

Resolve every `[FAIL]`. The installer will not create or modify Scheduled Tasks while preflight is failing.

Immediately before installing the tasks, repeat the disabled-state check:

```sql
SELECT proc_name, enabled, command, status, pid
FROM nahsi.managed_processes
WHERE proc_name <> '__supervisor__' AND (enabled <> 0 OR command IS NOT NULL OR pid IS NOT NULL);
-- Expect zero rows.
```



## 11. Install the supervisor Scheduled Tasks

From an elevated Administrator Command Prompt:

```bat
D:\xampp\htdocs\MySQLUpdates\process_supervisor\install_supervisor_task.bat
```

The installer:

1. resolves the absolute non-Windows-Store Python executable;
2. checks `mysql.connector`, `schedule`, and `pytz`;
3. runs the no-launch production preflight;
4. registers `PyProcessSupervisor_Boot` and `PyProcessSupervisor_Watchdog` as `SYSTEM`;
5. configures unlimited execution time, IgnoreNew, start-when-available, and restart-on-failure;
6. starts the Boot task immediately.

Because all business rows are disabled, the Boot task should start only the supervisor heartbeat. The existing standalone jobs should continue exactly as before.

Verify:

```powershell
Get-ScheduledTask -TaskName PyProcessSupervisor_Boot,PyProcessSupervisor_Watchdog |
    Select-Object TaskName, State, Principal, Actions, Settings

Get-ScheduledTaskInfo -TaskName PyProcessSupervisor_Boot
Get-ScheduledTaskInfo -TaskName PyProcessSupervisor_Watchdog

Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like '*process_supervisor.py*' } |
    Select-Object ProcessId, ParentProcessId, Name, CommandLine
```

Expected results:

- exactly one live `process_supervisor.py` process;
- fresh `__supervisor__` heartbeat, normally under ten seconds old and never over 30 seconds while healthy;
- all 18 business rows still stopped/disabled;
- no new duplicate Python or PHP business process;
- `_supervisor.log` shows startup and MySQL connection without fatal errors.

If the task exits under `SYSTEM` even though the interactive import check passed, inspect the Scheduled Task result and `_supervisor.log`. The usual cause is a module, environment, or permission that exists only for the interactive administrator account.

## 12. Phase B — cut over one job at a time

Use this sequence independently for each approved job.

### 12.1 Choose a safe cutover window

Avoid the minute when the scheduler is due to launch its real business update. Check the old log and schedule first. A green scheduler PID is not enough to tell whether its child batch is currently writing data.

### 12.2 Confirm the supervisor row is disabled

```sql
SELECT proc_id, proc_name, enabled, command, status, pid, script_path
FROM nahsi.managed_processes
WHERE proc_name = 'job_name';
```

Expect `enabled=0`, `command=NULL`, `status='stopped'`, and `pid=NULL`.

### 12.3 Disable the old launcher

If the old job is a Scheduled Task, disable it first so it cannot relaunch after the process is stopped. If it is a console or batch wrapper, close only that job's launcher.

Confirm its exact command line before stopping it:

```powershell
Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like '*exact_script_name*' } |
    Select-Object ProcessId, ParentProcessId, Name, CommandLine
```

Stop the exact old process tree if required. Do not terminate every `python.exe` or `php.exe` process.

### 12.4 Confirm zero old copies

Repeat the command-line query. There must be no standalone copy of that script before the Process Monitor Start command is issued.

### 12.5 Start from Process Monitor

Use **Start** on that one row. Within approximately five seconds verify:

- `enabled=1`;
- `command` clears back to `NULL`;
- status becomes `running`;
- a new PID appears;
- heartbeat age stays fresh;
- the command line contains the exact registered script path;
- the per-process log receives a supervisor start banner and expected scheduler startup output.

Do not use **Start All** during this process.

### 12.6 Observe before moving to the next job

Keep the old launcher disabled but available for rollback. Observe the managed scheduler through at least its startup and an agreed stability window. When practical, wait for its next natural business schedule and verify the expected database, file, dashboard, or external output.

Only after the job is accepted should the next job be cut over.

## 13. Receiving Location Cost cutover

Receiving uses a PHP loop and has additional database locks/run health.

1. Confirm a manual production cycle succeeds:
  ```bat
   cd /d D:\xampp\htdocs\heatmap_logic
   D:\xampp\php\php.exe receiving_location_cost_collector.php --self-test
   D:\xampp\php\php.exe receiving_location_cost_downstream.php --self-test
   D:\xampp\php\php.exe receiving_location_cost_scorer.php --self-test
   D:\xampp\php\php.exe receiving_location_cost_runner.php --self-test
   D:\xampp\php\php.exe receiving_location_cost_runner.php --mode=once --whse=7
  ```
2. Stop the old standalone `receiving_location_cost_loop.php`, batch loop, or runner process tree. Do not stop unrelated PHP jobs.
3. Confirm the old command lines are gone.
4. Confirm the warehouse locks are released:
  ```sql
   SELECT IS_USED_LOCK('receiving_location_cost_runner_7') AS runner_lock,
          IS_USED_LOCK('receiving_location_cost_downstream_7') AS downstream_lock,
          IS_USED_LOCK('receiving_location_cost_collector_7_same-item') AS collector_lock,
          IS_USED_LOCK('receiving_location_cost_scorer_7') AS scorer_lock;
  ```
   Released locks return `NULL`. If a name differs because of the candidate scope, inspect `IS_USED_LOCK` using the exact lock name emitted by the script.
5. Start only `receiving_cost_loop` from Process Monitor.
6. Verify `process_supervisor\logs\receiving_cost_loop.log`, `nahsi.receiving_updater_run`, and the Receiving dashboard Run Health.
7. During downstream processing, `receiving_updater_run.heartbeat_at` must continue updating rather than freezing at the stage start.

Run either the standalone receiving loop or the supervisor-managed loop, never both.

## 14. Validation after all approved cutovers



### Process-level validation

- exactly one supervisor process exists;
- each enabled job has exactly one child scheduler process;
- no retired standalone Scheduled Task is still enabled;
- every enabled row has a fresh heartbeat;
- no row has a permanently queued command;
- no job is in rapid-failure `crashed` state;
- representative log tails load for administrators.



### Business-level validation

For each job, record:

- the first naturally scheduled run under supervisor ownership;
- the child log evidence;
- expected MySQL table/run-row freshness;
- expected dashboard freshness;
- expected file/FTP/SFTP output where applicable;
- any downstream batch exit status.

Only after that evidence exists should `runtime_observed` be marked for that process:

```sql
UPDATE nahsi.managed_process_catalog
SET runtime_observed = 1,
    runtime_observed_at = NOW()
WHERE proc_name = 'observed_process_name';
```

Do not equate a green scheduler PID with runtime-observed business success.

### Reboot validation

After all approved jobs are stable and a maintenance window is available:

1. reboot the production server;
2. confirm the Boot task starts one supervisor;
3. confirm the Watchdog does not create a second instance;
4. confirm enabled jobs recover with new PIDs;
5. confirm disabled jobs remain disabled;
6. confirm machine-level environment variables and `SYSTEM` dependencies remain available;
7. confirm the next natural business outputs.



## 15. Per-job rollback

If one managed job fails:

1. Use **Stop** on that one Process Monitor row. This disables it and hard-stops its recorded process tree.
2. Confirm the managed PID and descendants are gone.
3. Re-enable or restart only that job's previous standalone launcher.
4. Confirm exactly one copy is running.
5. Preserve the supervisor and child logs for diagnosis.
6. Leave the other successfully managed jobs alone.

For a job that writes non-transactionally, inspect its output before immediately rerunning the old launcher; the hard stop may have interrupted a partial refresh.

## 16. Full rollback

Use this only if the supervisor itself must be removed.

1. Use **Stop All** and wait for all supervisor-owned child PIDs to disappear.
2. Run as Administrator:
  ```bat
   D:\xampp\htdocs\MySQLUpdates\process_supervisor\uninstall_supervisor_task.bat
  ```
3. Confirm both Scheduled Tasks are deleted.
4. Confirm no detached `process_supervisor.py` process remains.
5. Restore the old standalone launchers one at a time, verifying exactly one copy of each.
6. Preserve `logs\` and the four MySQL tables for incident evidence.

Removing the dashboard files or navigation link does not stop the supervisor. Deleting the MySQL rows while children are running is also not a safe substitute for Stop; use the dashboard commands first.

## 17. Upgrade procedure after initial go-live

For later supervisor-code changes:

1. Do not overwrite `process_supervisor.py` while relying on the watchdog to restart it blindly.
2. Record current enabled states and PIDs.
3. Use Stop All during an approved window, or stop only affected jobs if the change does not require a supervisor restart.
4. end both supervisor Scheduled Task instances;
5. back up and replace the files;
6. run Python compile and `--preflight --expect-prod`;
7. start the Boot task;
8. confirm the supervisor heartbeat;
9. restore only the previously approved enabled rows;
10. validate process and business outputs.

Re-running `seed_prod.sql` corrects paths and interpreters but preserves existing enabled/status/command/PID state on duplicate rows. Review the resulting state before restarting the supervisor.

## 18. Production acceptance checklist



### Files and database

- [ ] Exact `MySQLUpdates`, `nahsi`, and `global_dash` file inventory deployed
- [ ] No developer logs, bytecode, `seed_local.sql`, or unrelated repo files copied
- [ ] Empty `logs\` directory writable by SYSTEM and readable by Apache/PHP
- [ ] Python compile and four PHP lint checks pass
- [ ] Four monitor tables exist
- [ ] Exactly 18 production jobs registered
- [ ] `test_loop` absent from production registrations
- [ ] All 18 jobs disabled before supervisor installation



### Security and preflight

- [ ] Dashboard restricted to administrators
- [ ] Action endpoint rejects missing/invalid CSRF
- [ ] Status/detail endpoints reject unauthorized users
- [ ] Main and downstream production paths inspected
- [ ] `production_catalog_count=18`
- [ ] `production_ready_count=18`
- [ ] `--preflight --expect-prod` passes with zero failures



### Supervisor-only start

- [ ] Boot and Watchdog tasks registered as SYSTEM
- [ ] Exactly one supervisor process exists
- [ ] Supervisor heartbeat is fresh
- [ ] All 18 business jobs remain disabled after task installation
- [ ] Existing standalone jobs were not interrupted
- [ ] No duplicate business process appeared



### Controlled cutover

- [ ] Old launcher inventory recorded for every approved job
- [ ] Each old launcher disabled before its managed copy starts
- [ ] Each old process confirmed gone before Start
- [ ] Jobs cut over one at a time; Start All not used
- [ ] SYSTEM access to ODBC, network, credential, and output dependencies verified
- [ ] Per-job child logs and natural business output validated
- [ ] Receiving manual cycle passes before `receiving_cost_loop` cutover
- [ ] Receiving updater heartbeat remains fresh during downstream processing



### Finalization

- [ ] Reboot recovery tested in an approved window
- [ ] Disabled rows remain disabled after reboot
- [ ] Watchdog produces no duplicate supervisor
- [ ] Per-job rollback instructions and old launcher details retained
- [ ] Runtime observation recorded only from real business-output evidence
- [ ] Local Process Monitor changes reviewed and committed intentionally after approval
