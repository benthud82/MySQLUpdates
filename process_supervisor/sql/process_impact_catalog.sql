-- Process Monitor operational-impact catalog (MySQL 5.6 compatible).
-- Run after managed_processes.sql and before seed_process_impact_catalog.sql.

CREATE TABLE IF NOT EXISTS nahsi.managed_process_catalog (
  proc_name VARCHAR(64) NOT NULL,
  display_name VARCHAR(120) NOT NULL,
  system_name VARCHAR(80) NOT NULL,
  purpose TEXT NOT NULL,
  schedule_summary VARCHAR(255) NOT NULL DEFAULT '',
  timezone_name VARCHAR(64) NOT NULL DEFAULT '',
  criticality ENUM('critical','high','standard') DEFAULT NULL,
  outage_effect TEXT NOT NULL,
  source_repository VARCHAR(255) NOT NULL DEFAULT '',
  documentation_note TEXT NOT NULL,
  is_production TINYINT(1) NOT NULL DEFAULT 1,
  source_verified TINYINT(1) NOT NULL DEFAULT 0,
  source_verified_at DATETIME DEFAULT NULL,
  production_path_verified TINYINT(1) NOT NULL DEFAULT 0,
  production_path_verified_at DATETIME DEFAULT NULL,
  runtime_observed TINYINT(1) NOT NULL DEFAULT 0,
  runtime_observed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (proc_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS nahsi.managed_process_steps (
  step_id INT NOT NULL AUTO_INCREMENT,
  proc_name VARCHAR(64) NOT NULL,
  step_key VARCHAR(80) NOT NULL,
  step_order SMALLINT UNSIGNED NOT NULL,
  step_type ENUM('python','batch','php','sql','external','service') NOT NULL,
  display_label VARCHAR(160) NOT NULL,
  object_path VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  evidence_path VARCHAR(255) NOT NULL DEFAULT '',
  source_verified TINYINT(1) NOT NULL DEFAULT 0,
  production_path_required TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (step_id),
  UNIQUE KEY uq_process_step_key (proc_name, step_key),
  KEY ix_process_step_order (proc_name, step_order),
  CONSTRAINT fk_process_steps_proc
    FOREIGN KEY (proc_name) REFERENCES nahsi.managed_process_catalog (proc_name)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS nahsi.managed_process_impacts (
  impact_id INT NOT NULL AUTO_INCREMENT,
  proc_name VARCHAR(64) NOT NULL,
  impact_key VARCHAR(80) NOT NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  object_type ENUM('mysql_table','php_page','dashboard_page','source_system','external_output','dependency','declaration') NOT NULL,
  relationship ENUM('reads','writes','rebuilds','appends','exports','feeds','executes','depends_on','none') NOT NULL,
  schema_name VARCHAR(64) NOT NULL DEFAULT '',
  object_name VARCHAR(255) NOT NULL DEFAULT '',
  display_label VARCHAR(160) NOT NULL,
  route_path VARCHAR(255) NOT NULL DEFAULT '',
  operational_effect TEXT NOT NULL,
  evidence_path VARCHAR(255) NOT NULL DEFAULT '',
  source_verified TINYINT(1) NOT NULL DEFAULT 0,
  production_path_required TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (impact_id),
  UNIQUE KEY uq_process_impact_key (proc_name, impact_key),
  KEY ix_process_impact_order (proc_name, display_order),
  CONSTRAINT fk_process_impacts_proc
    FOREIGN KEY (proc_name) REFERENCES nahsi.managed_process_catalog (proc_name)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
