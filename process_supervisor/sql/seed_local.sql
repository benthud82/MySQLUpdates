-- LOCAL seed (C:\Users\Bentley.Hudson\xampp\htdocs).
-- test_loop is enabled for safe end-to-end testing.
-- The two real scripts that exist locally are seeded DISABLED:
--   jobsched_loops.py calls a D:\ bat path (prod path) and offsys_slot_update.py
--   kicks off the full nightly NAHSI chain at 03:10 - enable them deliberately.

INSERT INTO nahsi.managed_processes (proc_name, script_path, working_dir, python_exe, log_path, enabled) VALUES
('test_loop',
 'C:\\Users\\Bentley.Hudson\\xampp\\htdocs\\MySQLUpdates\\process_supervisor\\test_loop.py',
 '', 'python', '', 1),
('jobsched_loops',
 'C:\\Users\\Bentley.Hudson\\xampp\\htdocs\\MySQLUpdates\\jobsched_loops.py',
 '', 'python', '', 0),
('offsys_slot_update',
 'C:\\Users\\Bentley.Hudson\\xampp\\htdocs\\heatmap_logic\\offsys_slot_update.py',
 '', 'python', '', 0),
('receiving_cost_loop',
 'C:\\Users\\Bentley.Hudson\\xampp\\htdocs\\heatmap_logic\\receiving_location_cost_loop.php',
 '', 'C:\\Users\\Bentley.Hudson\\xampp\\php\\php.exe', '', 1)
ON DUPLICATE KEY UPDATE
  script_path = VALUES(script_path),
  working_dir = VALUES(working_dir),
  python_exe = VALUES(python_exe),
  log_path = VALUES(log_path);
