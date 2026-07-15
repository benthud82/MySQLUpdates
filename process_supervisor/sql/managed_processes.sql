-- Process supervisor: config + live status + command queue in one table.
-- The supervisor (process_supervisor.py) polls this table every ~5s;
-- the nahsi dashboard (admin_processes.php) reads status and writes commands.

CREATE TABLE IF NOT EXISTS nahsi.managed_processes (
  proc_id INT NOT NULL AUTO_INCREMENT,
  proc_name VARCHAR(64) NOT NULL,
  script_path VARCHAR(255) NOT NULL DEFAULT '',
  working_dir VARCHAR(255) NOT NULL DEFAULT '',   -- blank = folder of script_path
  python_exe VARCHAR(255) NOT NULL DEFAULT 'python',
  log_path VARCHAR(255) NOT NULL DEFAULT '',      -- blank = process_supervisor\logs\<proc_name>.log
  enabled TINYINT(1) NOT NULL DEFAULT 1,          -- 1 = supervisor keeps it running
  command ENUM('start','stop','restart') DEFAULT NULL,  -- written by dashboard, cleared by supervisor
  command_requested_at DATETIME DEFAULT NULL,
  status ENUM('running','stopped','crashed','starting') NOT NULL DEFAULT 'stopped',
  pid INT DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  last_heartbeat DATETIME DEFAULT NULL,
  restart_count INT NOT NULL DEFAULT 0,
  last_exit_code INT DEFAULT NULL,
  status_detail VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (proc_id),
  UNIQUE KEY uq_proc_name (proc_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Heartbeat row for the supervisor itself (never launched as a child).
INSERT IGNORE INTO nahsi.managed_processes (proc_name, script_path, enabled, status, status_detail)
VALUES ('__supervisor__', '', 0, 'stopped', 'Supervisor heartbeat row');
