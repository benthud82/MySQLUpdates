<?php
/**
 * Comparison Script for Customer Returns Merge Update
 * 
 * This script can be used to verify that the refactored version produces
 * identical output to the original version for a specific date.
 * 
 * Usage: Modify the $test_date variable below and run this script to compare outputs.
 */

set_time_limit(99999);
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

include '../connections/conn_custaudit.php';

// Test date - modify this to test specific dates
$test_date = '2025-07-03'; // Format: YYYY-MM-DD

echo "\n";
echo "Customer Returns Merge Update Comparison Script\n";
echo "\n";
echo "Test Date: $test_date\n";
echo "\n";

// Function to convert date to Julian format used by the scripts
function dateToJulian($date) {
    $year = date('y', strtotime($date));
    $day = date('z', strtotime($date)) + 1;
    if ($day < 10) {
        $day = '00' . $day;
    } else if ($day < 100) {
        $day = '0' . $day;
    }
    return intval('1' . $year . $day);
}

$test_julian = dateToJulian($test_date);
echo "Test Date in Julian format: $test_julian\n";
echo "\n";

// Backup existing custreturns data for the test date
echo "1. BACKING UP existing custreturns data for test date...\n";
$backup_sql = "CREATE TABLE IF NOT EXISTS custaudit.custreturns_comparison_backup AS 
               SELECT * FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date'";
$conn1->exec($backup_sql);
echo "   Backup completed\n";
echo "\n";

// Clear test data
echo "2. CLEARING existing test data...\n";
$clear_sql = "DELETE FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date'";
$conn1->exec($clear_sql);

$clear_complaint_sql = "DELETE FROM custaudit.complaint_detail WHERE ORD_RETURNDATE = '$test_date'";
$conn1->exec($clear_complaint_sql);
echo "   Test data cleared\n";
echo "\n";

echo "3. TEST SETUP COMPLETE - Follow these steps:\n";
echo "\n";

echo "STEP A: Run the ORIGINAL script\n";
echo "   php custreturnsmergeupdate.php\n";
echo "\n";

echo "STEP B: Export the ORIGINAL results\n";
echo "   Run these queries and save the output:\n";
echo "\n";
echo "   SELECT * FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date' ORDER BY WCSNUM, WONUM, ITEMCODE;\n";
echo "\n";
echo "   SELECT * FROM custaudit.complaint_detail WHERE ORD_RETURNDATE = '$test_date' ORDER BY WCSNUM, WONUM, ITEMCODE;\n";
echo "\n";

echo "STEP C: Clear the data again\n";
echo "   DELETE FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date';\n";
echo "   DELETE FROM custaudit.complaint_detail WHERE ORD_RETURNDATE = '$test_date';\n";
echo "\n";

echo "STEP D: Run the REFACTORED script\n";
echo "   php test_custreturnsmergeupdate_refactored.php\n";
echo "\n";

echo "STEP E: Export the REFACTORED results and compare\n";
echo "   Run the same queries as Step B and compare with original results\n";
echo "\n";

echo "STEP F: Restore original data\n";
echo "   INSERT INTO custaudit.custreturns SELECT * FROM custaudit.custreturns_comparison_backup;\n";
echo "   DROP TABLE custaudit.custreturns_comparison_backup;\n";
echo "\n";

// Provide some helper queries for comparison
echo "HELPER QUERIES FOR COMPARISON\n";
echo "\n";

echo "Quick Record Count Check:\n";
echo "   SELECT COUNT(*) as record_count FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date';\n";
echo "\n";
echo "   SELECT COUNT(*) as record_count FROM custaudit.complaint_detail WHERE ORD_RETURNDATE = '$test_date';\n";
echo "\n";

echo "Sample Data Preview (first 10 records):\n";
echo "   SELECT * FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date' ORDER BY WCSNUM, WONUM, ITEMCODE LIMIT 10;\n";
echo "\n";

echo "ADVANCED DIFFERENCE DETECTION\n";
echo "(Run this AFTER both scripts to detect any differences)\n";
echo "\n";
echo "Step 1: Save original results in temp table:\n";
echo "   CREATE TEMPORARY TABLE original_results AS SELECT * FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$test_date';\n";
echo "\n";

echo "Step 2: After running refactored script, check differences:\n";
echo "   SELECT 'In Original but not in Refactored' as difference_type, COUNT(*) as count\n";
echo "   FROM original_results o\n";
echo "   LEFT JOIN custaudit.custreturns r ON o.WCSNUM = r.WCSNUM AND o.WONUM = r.WONUM AND o.ITEMCODE = r.ITEMCODE\n";
echo "   WHERE r.WCSNUM IS NULL\n";
echo "   UNION ALL\n";
echo "   SELECT 'In Refactored but not in Original' as difference_type, COUNT(*) as count\n";
echo "   FROM custaudit.custreturns r\n";
echo "   LEFT JOIN original_results o ON o.WCSNUM = r.WCSNUM AND o.WONUM = r.WONUM AND o.ITEMCODE = r.ITEMCODE\n";
echo "   WHERE o.WCSNUM IS NULL AND r.ORD_RETURNDATE = '$test_date';\n";
echo "\n";

echo "PERFORMANCE MONITORING\n";
echo "\n";
echo "Monitor Active Queries:\n";
echo "   SHOW FULL PROCESSLIST;\n";
echo "\n";

echo "Check Table Sizes:\n";
echo "   SELECT table_name, table_rows, ROUND(data_length/1024/1024,2) as data_mb, ROUND(index_length/1024/1024,2) as index_mb\n";
echo "   FROM information_schema.tables\n";
echo "   WHERE table_schema = 'custaudit' AND table_name IN ('custreturns', 'custreturnsmerge', 'complaint_detail');\n";
echo "\n";

echo "Script setup completed successfully!\n";
echo "Follow the numbered steps above to compare the outputs.\n";

?> 