-- Production-readiness coverage. All rows must report ready=1 before sign-off.
SELECT
  c.proc_name,
  IF(mp.proc_name IS NULL, 0, 1) AS registered,
  COUNT(DISTINCT s.step_id) AS step_count,
  COUNT(DISTINCT i.impact_id) AS impact_count,
  c.source_verified,
  c.production_path_verified,
  c.runtime_observed,
  IF(
    mp.proc_name IS NOT NULL
    AND COUNT(DISTINCT s.step_id) > 0
    AND COUNT(DISTINCT i.impact_id) > 0
    AND c.source_verified = 1
    AND c.production_path_verified = 1,
    1,
    0
  ) AS ready
FROM nahsi.managed_process_catalog c
LEFT JOIN nahsi.managed_processes mp ON mp.proc_name = c.proc_name
LEFT JOIN nahsi.managed_process_steps s ON s.proc_name = c.proc_name
LEFT JOIN nahsi.managed_process_impacts i ON i.proc_name = c.proc_name
WHERE c.is_production = 1
GROUP BY c.proc_name, mp.proc_name, c.source_verified,
         c.production_path_verified, c.runtime_observed
ORDER BY c.proc_name;

SELECT
  COUNT(*) AS production_catalog_count,
  SUM(CASE WHEN ready = 1 THEN 1 ELSE 0 END) AS production_ready_count
FROM (
  SELECT
    c.proc_name,
    IF(
      mp.proc_name IS NOT NULL
      AND COUNT(DISTINCT s.step_id) > 0
      AND COUNT(DISTINCT i.impact_id) > 0
      AND c.source_verified = 1
      AND c.production_path_verified = 1,
      1,
      0
    ) AS ready
  FROM nahsi.managed_process_catalog c
  LEFT JOIN nahsi.managed_processes mp ON mp.proc_name = c.proc_name
  LEFT JOIN nahsi.managed_process_steps s ON s.proc_name = c.proc_name
  LEFT JOIN nahsi.managed_process_impacts i ON i.proc_name = c.proc_name
  WHERE c.is_production = 1
  GROUP BY c.proc_name, mp.proc_name, c.source_verified, c.production_path_verified
) coverage;
