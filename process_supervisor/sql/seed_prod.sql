-- PROD seed (D:\xampp\htdocs) - all managed loop scripts, enabled.
-- receiving_cost_loop is a PHP loop: its interpreter column holds php.exe.
-- working_dir and log_path are left blank: working_dir defaults to the script's
-- folder, log_path defaults to process_supervisor\logs\<proc_name>.log.
-- Verify each path on prod before running. Re-runs correct configuration paths
-- without changing enabled/status/PID/command/restart state.

INSERT INTO nahsi.managed_processes (proc_name, script_path, working_dir, python_exe, log_path, enabled) VALUES
('gill_update',              'D:\\xampp\\htdocs\\gillingham\\MySQLUpdates\\gill_update.py', '', 'python', '', 1),
('hep_update',               'D:\\xampp\\htdocs\\hep\\MySQLUpdates\\hep_update.py', '', 'python', '', 1),
('ukgill_update_all_tables', 'D:\\xampp\\htdocs\\emea_ukgill_logic\\update_all_tables.py', '', 'python', '', 1),
('breaklunch_ftp_1',         'D:\\xampp\\htdocs\\breaklunch\\table_sched_ftp_1.py', '', 'python', '', 1),
('breaklunch_ftp_2',         'D:\\xampp\\htdocs\\breaklunch\\table_sched_ftp_2.py', '', 'python', '', 1),
('breaklunch_ftp_locus',     'D:\\xampp\\htdocs\\breaklunch\\table_sched_ftp_locus.py', '', 'python', '', 1),
('gallin_update',            'D:\\xampp\\htdocs\\gallin_logic\\gallin_update.py', '', 'python', '', 1),
('offsys_slot_update',       'D:\\xampp\\htdocs\\heatmap_logic\\offsys_slot_update.py', '', 'python', '', 1),
('offsys_slot_update_CAN',   'D:\\xampp\\htdocs\\heatmap_logic\\offsys_slot_update_CAN.py', '', 'python', '', 1),
('nahsi_shorts_item',        'D:\\xampp\\htdocs\\heatmap_logic\\tbl_update_NAHSI_shorts_item.py', '', 'python', '', 1),
('jobsched_loops',           'D:\\xampp\\htdocs\\MySQLUpdates\\jobsched_loops.py', '', 'python', '', 1),
('table_sched_mysqlupdates', 'D:\\xampp\\htdocs\\MySQLUpdates\\table_sched_mysqlupdates.py', '', 'python', '', 1),
('todaypage_loop',           'D:\\xampp\\htdocs\\MySQLUpdates\\todaypage_loop.py', '', 'python', '', 1),
('printvis_caserefresh',     'D:\\xampp\\htdocs\\printvis\\datapull\\jobsched_caserefresh_bat.py', '', 'python', '', 1),
('printvis_table_sched',     'D:\\xampp\\htdocs\\printvis\\datapull\\table_sched.py', '', 'python', '', 1),
('printvis_loc_oh_loop',     'D:\\xampp\\htdocs\\printvis_logic\\loc_oh_loop.py', '', 'python', '', 1),
('printvis_pack_start_loop', 'D:\\xampp\\htdocs\\printvis_logic\\pack_start_loop.py', '', 'python', '', 1),
('receiving_cost_loop',       'D:\\xampp\\htdocs\\heatmap_logic\\receiving_location_cost_loop.php', '', 'D:\\xampp\\php\\php.exe', '', 1)
ON DUPLICATE KEY UPDATE
  script_path = VALUES(script_path),
  working_dir = VALUES(working_dir),
  python_exe = VALUES(python_exe),
  log_path = VALUES(log_path);
