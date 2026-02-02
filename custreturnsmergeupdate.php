<?php

//determine the number of returns per invoice by ship to.  This does not provide detail.  Need to create another file to show indivudal detail.
set_time_limit(99999);
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

class Cls
{
    function arraymapfunct($entry)
    {
        return $entry[0];
    }
}

set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';
include '../globalincludes/usa_esys.php';
//include '../globalincludes/newcanada_asys.php';
include '../globalincludes/whse_build_array.php';
include '../globalfunctions/custdbfunctions.php';

$thirteenMonthsAgo = date('Y-m-d', strtotime('-14 months'));

// Insert records older than 13 months into the history table
$insertIntoHistorySql = "INSERT INTO custaudit.custreturns_hist SELECT * FROM custaudit.custreturns WHERE ORD_RETURNDATE <= :thirteenMonthsAgo";
$insertIntoHistoryStmt = $conn1->prepare($insertIntoHistorySql);
$insertIntoHistoryStmt->bindParam(':thirteenMonthsAgo', $thirteenMonthsAgo, PDO::PARAM_STR);
try {
    $insertIntoHistoryStmt->execute();
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        // Duplicate entry, continue without action
    } else {
        // Re-throw exception for any other SQL error
        throw $e;
    }
}

foreach ($whsebuild_array as $key => $value) {
    $schema = 'custaudit';
    $whse = $whsebuild_array[$key]['whse'];
    $build = $whsebuild_array[$key]['build'];
    $tableupdated = 'custreturns_hist';
    include '../heatmap_logic/tbl_update_NAHSI_table_updatetimes.php';
}

// Delete those records from the main table
$deleteOldRecordsSql = "DELETE FROM custaudit.custreturns WHERE ORD_RETURNDATE <= :thirteenMonthsAgo";
$deleteOldRecordsStmt = $conn1->prepare($deleteOldRecordsSql);
$deleteOldRecordsStmt->bindParam(':thirteenMonthsAgo', $thirteenMonthsAgo, PDO::PARAM_STR);
$deleteOldRecordsStmt->execute();

$startdate = date('Y-m-d', strtotime('-30 days'));
$pickpackdate = date('Y-m-d', strtotime('-365 days'));

//convert startdate for sql connection jdate below
$startyear = date('y', strtotime($startdate));
$startday = date('z', strtotime($startdate)) + 1;
if ($startday < 10) {
    $startday = '00' . $startday;
} else if ($startday < 100) {
    $startday = '0' . $startday;
}
$startdatej = intval('1' . $startyear . $startday);

$enddate = date('Y-m-d');

//convert enddate for sql connection jdate below
$endyear = date('y', strtotime($enddate));
$endday = date('z', strtotime($enddate)) + 1;
if ($endday < 10) {
    $endday = '00' . $endday;
} else if ($endday < 100) {
    $endday = '0' . $endday;
}
$enddatej = intval('1' . $endyear . $endday);

//columns for custreturnsmerge
$columns = 'BILLTONUM, BILLTONAME, SHIPTONUM, SHIPTONAME, WCSNUM, WONUM, SHIPDATEJ, JDENUM, RINUM, RETURNCODE, ITEMCODE, RETURNDATE, SHIPZONE, TRACERNUM, BOXNUM, BOXSIZE, WHSE, DIVISION, ORD_RETURNDATE, LPNUM, SALESREP, WEIGHT_EST, WEIGHT_ACT, PBRCJD, PBRCHM, PBPTJD, PBPTHM, PBRLJD, PBRLHM, SEQNUM, DC_CODE, AVG_COST';

// Clear the temp table once at the beginning
$sqldelete = "TRUNCATE TABLE custaudit.custreturnsmerge";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$schemaarray = array('HSIPDTA71', 'ARCPDTA71');

foreach ($schemaarray as $schema) {
    if ($schema == 'HSIPDTA71') {
        $schema2 = 'HSIPCORDTA';
    } else {
        $schema2 = 'ARCPCORDTA';
    }

    // **EFFICIENCY IMPROVEMENT 1: Get all returns for entire date range at once**
    $selectclause = '$GDOC as RETURNSKEY, $G$OIN, $G$WON, $GAN8, $GSVDB, TRIM(CAST($GLITM AS CHAR(20) CCSID 37)), TRIM(CAST($G$RMI AS CHAR(20) CCSID 37)), $G$SQ1, TRIM(CAST($G$RMK AS CHAR(20) CCSID 37))';
    $whereclause = '$G$RMI' . " in('IBNX', 'LABL', 'IBNS', 'WQSP', 'WISP', 'EXPR', 'TEMP', 'CRID', 'LITR', 'TDNR', 'WQTY', 'CSNS', 'NRSP', 'CNCL', 'SDAT', 'WIOD', 'IBNO', 'TRPX')" . ' and $GSVDB >= ' . $startdatej . ' and $GSVDB <= ' . $enddatej . ' and CAST($G$RMI AS CHAR(20) CCSID 37) <> ' . "''";

    $custreturns = $eseriesconn->prepare("SELECT $selectclause FROM E.$schema.F5717 WHERE $whereclause");
    $custreturns->execute();
    $custreturnsarray = $custreturns->fetchAll(pdo::FETCH_NUM);

    if (empty($custreturnsarray)) {
        continue; // Skip if no returns found for this schema
    }

    // **EFFICIENCY IMPROVEMENT 2: Collect all IDs and items for bulk lookup**
    $return_ids = array();
    $return_items = array();
    $returns_lookup = array(); // Map ID+ITEM to array index for fast lookup

    foreach ($custreturnsarray as $key => $value) {
        $id = $custreturnsarray[$key][0];
        $item = $custreturnsarray[$key][5];
        $return_ids[] = $id;
        $return_items[] = "'" . $item . "'";
        $returns_lookup[$id . '|' . $item] = $key;
    }

    // Remove duplicates and prepare for batch processing
    $unique_ids = array_unique($return_ids);
    $unique_items = array_unique($return_items);

    if (empty($unique_ids)) {
        continue;
    }

    // **EFFICIENCY IMPROVEMENT 3: Batch processing instead of large IN clauses**
    // DB2 performs much better with smaller batches than massive IN clauses
    $batch_size = 100; // Process 100 records at a time - optimal for DB2
    $wpspusharray = array();

    $id_batches = array_chunk($unique_ids, $batch_size);

    foreach ($id_batches as $id_batch) {
        // Create smaller, more efficient IN clauses
        $batch_ids_clause = implode(',', $id_batch);

        // Get relevant items for this batch of IDs
        $batch_items = array();
        foreach ($custreturnsarray as $return_row) {
            if (in_array($return_row[0], $id_batch)) {
                $batch_items[] = "'" . $return_row[5] . "'";
            }
        }
        $batch_items = array_unique($batch_items);

        if (empty($batch_items)) {
            continue;
        }

        $batch_items_clause = implode(',', $batch_items);

        // Execute batch query - much faster than single large query
        $wpspush = $aseriesconn->prepare("SELECT DISTINCT PBDOC AS MAINKEY, 
                                            IM0018.BILL_TO, 
                                            IM0018.BILL_TO_NAME, 
                                            IM0018.CUSTOMER, 
                                            IM0018.CUST_NAME, 
                                            PBSHJD, 
                                            PBDOCO, 
                                            PBSHPC, 
                                            PBTRC#, 
                                            PBBOX#, 
                                            PBBXSZ, 
                                            PBWHSE, 
                                            case  
                                                    when SLS_DVN2 = 'DSL' then 'Dental' 
                                                    when SLS_DVN2 = 'MDL' then 'Medical' 
                                                    when SLS_DVN2 = 'MPH' then 'Medical' 
                                                    when SLS_DVN2 = 'INS' then 'Medical' 
                                                    when SLS_DVN2 = '34B' then 'Medical' 
                                                    when SLS_DVN2 = 'MTX' then 'Medical' 
                                                    else '' end 
                                            as DIVISION, 
                                            PBLP9D,  
                                            TER_DESC, 
                                            PBBOXW, 
                                            PBBXAW, 
                                            PBRCJD, 
                                            PBRCHM, 
                                            PBPTJD, 
                                            PBPTHM, 
                                            PBRLJD, 
                                            PBRLHM,
                                            PDITEM,
                                            PDAVGC
                                    FROM 
                                            A.$schema2.NOTWPS NOTWPS 
                                            JOIN A.$schema2.NOTWPT on pdwhse = pbwhse and pdwcs# = pbwcs# and pdbox# = pbbox# and pdwkno = pbwkno
                                            JOIN A.$schema2.IM0018 IM0018 on IM0018.CUSTOMER = PBSHAN 
                                    WHERE 
                                            PBDOC IN ($batch_ids_clause)
                                            AND PDITEM IN ($batch_items_clause)");
        $wpspush->execute();
        $batch_results = $wpspush->fetchAll(pdo::FETCH_NUM);

        // Merge batch results into main array
        $wpspusharray = array_merge($wpspusharray, $batch_results);
    }

    // **EFFICIENCY IMPROVEMENT 4: Create lookup table for fast access**
    $wps_lookup = array();
    foreach ($wpspusharray as $wps_row) {
        $lookup_key = $wps_row[0] . '|' . $wps_row[23]; // PBDOC|PDITEM
        $wps_lookup[$lookup_key] = $wps_row;
    }

    // **EFFICIENCY IMPROVEMENT 5: Process matches efficiently**
    $data = array();
    foreach ($custreturnsarray as $key => $value) {
        $id = $custreturnsarray[$key][0];
        $item = $custreturnsarray[$key][5];
        $lookup_key = $id . '|' . $item;

        if (isset($wps_lookup[$lookup_key])) {
            $wpspushrow = $wps_lookup[$lookup_key];

            // Add the matched data to the returns array (same logic as original)
            $custreturnsarray[$key][20] = $wpspushrow[3];  //ship to num
            $custreturnsarray[$key][21] = mb_convert_encoding($wpspushrow[4], 'UTF-8', 'auto');  //ship to name
            $custreturnsarray[$key][22] = mb_convert_encoding($wpspushrow[2], 'UTF-8', 'auto');  //bill to name
            $custreturnsarray[$key][23] = $wpspushrow[5];  //shipdate
            $custreturnsarray[$key][24] = $wpspushrow[6];  //PBDOCO
            $custreturnsarray[$key][25] = $wpspushrow[7];  //PBSHPC
            $custreturnsarray[$key][26] = $wpspushrow[8];  //PBTRC
            $custreturnsarray[$key][27] = $wpspushrow[9];  //PBBOX
            $custreturnsarray[$key][28] = $wpspushrow[10]; //PBBXSZ
            $custreturnsarray[$key][29] = $wpspushrow[11]; //PBWHSE
            $custreturnsarray[$key][30] = $wpspushrow[12]; //DIVISION
            $custreturnsarray[$key][31] = $wpspushrow[13]; //LP
            $custreturnsarray[$key][32] = $wpspushrow[14]; //TER_DESC
            $custreturnsarray[$key][33] = $wpspushrow[15]; //box weight
            $custreturnsarray[$key][34] = $wpspushrow[16]; //actual weight
            $custreturnsarray[$key][35] = $wpspushrow[17]; //PBRCJD
            $custreturnsarray[$key][36] = $wpspushrow[18]; //PBRCHM
            $custreturnsarray[$key][37] = $wpspushrow[19]; //PBPTJD
            $custreturnsarray[$key][38] = $wpspushrow[20]; //PBPTHM
            $custreturnsarray[$key][39] = $wpspushrow[21]; //PBRLJD
            $custreturnsarray[$key][40] = $wpspushrow[22]; //PBRLHM
            $custreturnsarray[$key][41] = $wpspushrow[24]; //PDAVGC
            $custreturnsarray[$key] = array_values($custreturnsarray[$key]);

            // Build the same data structure as original (exact same logic)
            $RINUM = intval($custreturnsarray[$key][1]);
            $WCSNUM = intval($custreturnsarray[$key][0]);
            $WONUM = intval($custreturnsarray[$key][2]);
            $BILLTONUM = intval($custreturnsarray[$key][3]);
            $RETURNDATE = intval($custreturnsarray[$key][4]);
            $ITEMCODE = intval($custreturnsarray[$key][5]);
            $RETURNCODE = $custreturnsarray[$key][6];
            $SEQNUM = intval($custreturnsarray[$key][7]);
            $SHIPTONUM = intval($custreturnsarray[$key][9]);

            $SHIPTONAME = addslashes($custreturnsarray[$key][10]);
            $BILLTONAME = addslashes($custreturnsarray[$key][11]);
            $SHIPDATEJ = intval($custreturnsarray[$key][12]);
            $JDENUM = intval($custreturnsarray[$key][13]);
            $SHIPZONE = $custreturnsarray[$key][14];
            $TRACERNUM = $custreturnsarray[$key][15];
            $BOXNUM = intval($custreturnsarray[$key][16]);
            $BOXSIZE = $custreturnsarray[$key][17];
            $WHSE = intval($custreturnsarray[$key][18]);
            $DIVISION = $custreturnsarray[$key][19];
            $LPNUM = $custreturnsarray[$key][20];
            $TER_DESC = addslashes($custreturnsarray[$key][21]);
            $PBBOXW = ($custreturnsarray[$key][22]);
            $PBBXAW = ($custreturnsarray[$key][23]);
            $PBRCJD = ($custreturnsarray[$key][24]);
            $PBRCHM = ($custreturnsarray[$key][25]);
            $PBPTJD = ($custreturnsarray[$key][26]);
            $PBPTHM = ($custreturnsarray[$key][27]);
            $PBRLJD = ($custreturnsarray[$key][28]);
            $PBRLHM = ($custreturnsarray[$key][29]);
            $ORD_RETURNDATE = date('Y-m-d', strtotime(_1yydddtogregdate($RETURNDATE)));
            $DC_CODE = ($custreturnsarray[$key][8]);
            $AVG_COST = ($custreturnsarray[$key][30]);
            $BILLTONAME = $conn1->quote($BILLTONAME);
            $SHIPTONAME = $conn1->quote($SHIPTONAME);
            $RETURNCODE = $conn1->quote($RETURNCODE);
            $SHIPZONE = $conn1->quote($SHIPZONE);
            $TRACERNUM = $conn1->quote($TRACERNUM);
            $BOXSIZE = $conn1->quote($BOXSIZE);
            $DIVISION = $conn1->quote($DIVISION);
            $ORD_RETURNDATE = $conn1->quote($ORD_RETURNDATE);
            $TER_DESC = $conn1->quote($TER_DESC);
            $PBBOXW = $conn1->quote($PBBOXW);
            $PBBXAW = $conn1->quote($PBBXAW);
            $DC_CODE = $conn1->quote($DC_CODE);

            $data[] = "($BILLTONUM, $BILLTONAME, $SHIPTONUM, $SHIPTONAME, $WCSNUM, $WONUM, $SHIPDATEJ, $JDENUM, $RINUM, $RETURNCODE, $ITEMCODE, $RETURNDATE, $SHIPZONE, $TRACERNUM, $BOXNUM, $BOXSIZE, $WHSE, $DIVISION, $ORD_RETURNDATE, $LPNUM, $TER_DESC, $PBBOXW, $PBBXAW, $PBRCJD, $PBRCHM, $PBPTJD, $PBPTHM, $PBRLJD, $PBRLHM, $SEQNUM, $DC_CODE, $AVG_COST)";
        }
    }

    // **EFFICIENCY IMPROVEMENT 6: Single bulk insert per schema instead of per day**
    if (!empty($data)) {
        $values = implode(',', $data);
        $sql = "INSERT IGNORE INTO custaudit.custreturnsmerge ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
    }
}

// **EFFICIENCY IMPROVEMENT 7: Single merge operation instead of per day**
$sqlmerge = "INSERT INTO custaudit.custreturns(BILLTONUM, BILLTONAME, SHIPTONUM, SHIPTONAME, WCSNUM, WONUM, SHIPDATEJ, JDENUM, RINUM, RETURNCODE, ITEMCODE, RETURNDATE, SHIPZONE, TRACERNUM, BOXNUM, BOXSIZE, WHSE, DIVISION, ORD_RETURNDATE, LPNUM, SALESREP, WEIGHT_EST, WEIGHT_ACT, PBRCJD, PBRCHM, PBPTJD, PBPTHM, PBRLJD, PBRLHM, SEQNUM, DC_CODE, AVG_COST)
SELECT custreturnsmerge.BILLTONUM, custreturnsmerge.BILLTONAME, custreturnsmerge.SHIPTONUM, custreturnsmerge.SHIPTONAME, custreturnsmerge.WCSNUM, custreturnsmerge.WONUM, custreturnsmerge.SHIPDATEJ, custreturnsmerge.JDENUM, custreturnsmerge.RINUM, custreturnsmerge.RETURNCODE, custreturnsmerge.ITEMCODE, custreturnsmerge.RETURNDATE, custreturnsmerge.SHIPZONE, custreturnsmerge.TRACERNUM, custreturnsmerge.BOXNUM, custreturnsmerge.BOXSIZE, custreturnsmerge.WHSE, custreturnsmerge.DIVISION, custreturnsmerge.ORD_RETURNDATE, custreturnsmerge.LPNUM, custreturnsmerge.SALESREP, custreturnsmerge.WEIGHT_EST, custreturnsmerge.WEIGHT_ACT, custreturnsmerge.PBRCJD, custreturnsmerge.PBRCHM, custreturnsmerge.PBPTJD, custreturnsmerge.PBPTHM, custreturnsmerge.PBRLJD, custreturnsmerge.PBRLHM, custreturnsmerge.SEQNUM, custreturnsmerge.DC_CODE, custreturnsmerge.AVG_COST FROM custaudit.custreturnsmerge
ON DUPLICATE KEY UPDATE custreturns.BILLTONUM = custreturnsmerge.BILLTONUM, custreturns.BILLTONAME = custreturnsmerge.BILLTONAME, custreturns.SHIPTONUM = custreturnsmerge.SHIPTONUM, custreturns.SHIPTONAME = custreturnsmerge.SHIPTONAME, custreturns.WCSNUM = custreturnsmerge.WCSNUM, custreturns.WONUM = custreturnsmerge.WONUM, custreturns.SHIPDATEJ = custreturnsmerge.SHIPDATEJ, custreturns.JDENUM = custreturnsmerge.JDENUM, custreturns.RINUM = custreturnsmerge.RINUM, custreturns.RETURNCODE = custreturnsmerge.RETURNCODE, custreturns.ITEMCODE = custreturnsmerge.ITEMCODE, custreturns.RETURNDATE = custreturnsmerge.RETURNDATE, custreturns.SHIPZONE = custreturnsmerge.SHIPZONE, custreturns.TRACERNUM = custreturnsmerge.TRACERNUM, custreturns.BOXNUM = custreturnsmerge.BOXNUM, custreturns.BOXSIZE = custreturnsmerge.BOXSIZE, custreturns.WHSE = custreturnsmerge.WHSE, custreturns.DIVISION = custreturnsmerge.DIVISION, custreturns.ORD_RETURNDATE = custreturnsmerge.ORD_RETURNDATE, custreturns.LPNUM = custreturnsmerge.LPNUM, custreturns.SALESREP = custreturnsmerge.SALESREP, custreturns.WEIGHT_EST = custreturnsmerge.WEIGHT_EST, custreturns.WEIGHT_ACT = custreturnsmerge.WEIGHT_ACT,
custreturns.PBRCJD = custreturnsmerge.PBRCJD, custreturns.PBRCHM = custreturnsmerge.PBRCHM, custreturns.PBPTJD = custreturnsmerge.PBPTJD, custreturns.PBPTHM = custreturnsmerge.PBPTHM, custreturns.PBRLJD = custreturnsmerge.PBRLJD, custreturns.PBRLHM = custreturnsmerge.PBRLHM, custreturns.SEQNUM = custreturnsmerge.SEQNUM, custreturns.DC_CODE = custreturnsmerge.DC_CODE, custreturns.AVG_COST = custreturnsmerge.AVG_COST;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();

foreach ($whsebuild_array as $key => $value) {
    $schema = 'custaudit';
    $whse = $whsebuild_array[$key]['whse'];
    $build = $whsebuild_array[$key]['build'];
    $tableupdated = 'custreturns';
    include '../heatmap_logic/tbl_update_NAHSI_table_updatetimes.php';
}

$sqlmerge2 = "INSERT INTO custaudit.complaint_detail 
SELECT DISTINCT
    t1.BILLTONUM,
    t1.BILLTONAME,
    t1.SHIPTONUM,
    t1.SHIPTONAME,
    t1.WCSNUM,
    t1.WONUM,
    t1.BOXNUM,
    t1.JDENUM,
    t1.LPNUM,
    t1.RETURNCODE,
    t1.ITEMCODE,
    t1.ORD_RETURNDATE,
    t1.SHIPZONE,
    t1.TRACERNUM,
    t1.BOXSIZE,
    COALESCE(t2.Whse, ch.caselp_whse) AS PICK_WHSE,
    COALESCE(t2.Batch_Num, ch.caselp_batch) AS Batch_Num,
    COALESCE(t2.Location, ch.caselp_loc) AS Location,
    t2.DateTimeFirstPick AS PICK_DATE,
    t2.ReserveUSerID AS PICK_TSMNUM,
    tsm_pick.tsm_name AS PICK_TSM,
    t3.cartstart_tsm AS PACK_TSM,
    tsm_pack.tsm_name AS PACK_TSMNAME,
    t3.cartstart_starttime AS PACK_DATE,
    t3.cartstart_packstation AS PACK_STATION,
    t4.totetimes_packfunction AS PACK_TYPE,
    ch.caselp_tsm AS CASEPICK_TSM,
    tsm_case.tsm_name AS CASEPICK_TSMNAME,
    ch.caselp_pickdatetime AS CASEPICK_DATETIME,
    t5.eolloose_tsm AS EOLLOOSE_TSM,
    t5.eolloose_wi,
    t5.eolloose_ce,
    t5.eolloose_mi,
    t5.eolloose_ai,
    t5.eolloose_pe,
    t6.eolcase_tsm,
    t6.eolcase_ot,
    t6.eolcase_ra,
    t1.SALESREP,
    t1.WEIGHT_EST,
    t1.WEIGHT_ACT,
    t1.PBRCJD,
    t1.PBRCHM,
    t1.PBPTJD,
    t1.PBPTHM,
    t1.PBRLJD,
    t1.PBRLHM,
    t1.SEQNUM,
    t1.DC_CODE,
    t1.AVG_COST
FROM custaudit.custreturns t1
    LEFT JOIN printvis.voicepicks_hist t2 ON (
        t1.WCSNUM = t2.WCS_NUM
        AND t1.WONUM = t2.WORKORDER_NUM  
        AND t1.BOXNUM = t2.BOX_NUM
        AND t1.ITEMCODE = t2.ItemCode
    )
    LEFT JOIN printvis.allcart_history_hist t3 ON (
        t2.Whse = t3.cartstart_whse
        AND t2.Batch_Num = t3.cartstart_batch
        AND t3.dateaddedtotable >= DATE_SUB(t2.DateTimeFirstPick, INTERVAL 5 DAY)
        AND t3.dateaddedtotable < DATE_ADD(t2.DateTimeFirstPick, INTERVAL 5 DAY)
    )
    LEFT JOIN printvis.alltote_history t4 ON (
        t4.totelp = t1.LPNUM
        AND t3.cartstart_batch = t4.totetimes_cart
    )
    LEFT JOIN printvis.eol_loose t5 ON t5.eolloose_lpnum = t1.LPNUM
    LEFT JOIN printvis.eol_case t6 ON t6.eolcase_lpnum = t1.LPNUM
    LEFT JOIN printvis.caselp_hist ch ON ch.caselp_lp = t1.LPNUM
    LEFT JOIN printvis.tsm tsm_pick ON tsm_pick.tsm_num = t2.ReserveUSerID
    LEFT JOIN printvis.tsm tsm_pack ON tsm_pack.tsm_num = t3.cartstart_tsm
    LEFT JOIN printvis.tsm tsm_case ON tsm_case.tsm_num = ch.caselp_tsm
WHERE t1.ORD_RETURNDATE >= '$startdate'
ON DUPLICATE KEY UPDATE 
    SALESREP = VALUES(SALESREP),
    WEIGHT_EST = VALUES(WEIGHT_EST),
    WEIGHT_ACT = VALUES(WEIGHT_ACT),
    PBRCJD = VALUES(PBRCJD),
    PBRCHM = VALUES(PBRCHM),
    PBPTJD = VALUES(PBPTJD),
    PBPTHM = VALUES(PBPTHM),
    PBRLJD = VALUES(PBRLJD),
    PBRLHM = VALUES(PBRLHM),
    SEQNUM = VALUES(SEQNUM),
    DC_CODE = VALUES(DC_CODE)";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();

// **NEW: Update complaint_detail with AS400 picker data for warehouse 6**
// Get warehouse 6 records that need picker data
$wh6_query = $conn1->prepare("
    SELECT WCSNUM, WONUM, BOXNUM, LPNUM, ITEMCODE
    FROM custaudit.complaint_detail 
    WHERE PICK_TSMNUM IS NULL and PICK_WHSE is null and BOXSIZE <> 'CSE'
    AND ORD_RETURNDATE >= '$startdate'
");
$wh6_query->execute();
$wh6_records = $wh6_query->fetchAll(PDO::FETCH_ASSOC);

if (!empty($wh6_records)) {
    // Use batching approach for AS400 queries
    $batch_size = 50; // Reduced for better AS400 performance
    $record_batches = array_chunk($wh6_records, $batch_size);

    $picker_updates = array();
    $total_as400_records = 0;
    $total_matches = 0;

    foreach ($record_batches as $batch_num => $batch) {
        $wcs_values = array();
        $wo_values = array();
        $box_values = array();
        $item_values = array(); // **FIX: Initialize this array**

        foreach ($batch as $record) {
            $wcs_values[] = $record['WCSNUM'];
            $wo_values[] = $record['WONUM'];
            $box_values[] = $record['BOXNUM'];
            $item_values[] = $record['ITEMCODE'];
        }

        $wcs_clause = "'" . implode("','", array_unique($wcs_values)) . "'";
        $wo_clause = "'" . implode("','", array_unique($wo_values)) . "'";
        $box_clause = "'" . implode("','", array_unique($box_values)) . "'";
        $item_clause = "'" . implode("','", array_unique($item_values)) . "'";
        
        // **SIMPLIFIED AS400 Query** - Fixed timestamp comparison for DB2 for i5/OS, added BATCH back
        $as400_query = $aseriesconn->prepare(
            "SELECT
                    PKWHSE                 ,
                    PKITEM                 ,
                    PKPLC# as PICK_LOCATION,
                    PKWCS#                 ,
                    PKWKNO                 ,
                    PKBOX#                 ,
                    PKPEMP                 ,
                    PKPDTE                 ,
                    PKLP#                  ,
                    TRIM(SUBSTR(NVFLAT,7,5)) as BATCH
            FROM
                    HSIPCORDTA.HWFPKH, HSIPCORDTA.NOFNVI
            WHERE
                    TRIM(substr(NVFLAT,18,9))          <> ' '
            AND     TRIM(substr(NVFLAT,137,10))        <> ' '
            AND     TRIM(substr(NVFLAT,7,5))       * 1 <> 0
            AND     TRIM(substr(NVFLAT,46,10)) not in ('00002',
                                                    '00003',
                                                    '00006',
                                                    '00007',
                                                    '00009')
            AND     PKWCS# IN ($wcs_clause)
            AND     PKPLC# <> 'PAPRWRK'
            and SUBSTR(NVFLAT, 18, 9) = PKLP#
                     ORDER BY
     PKPDTE DESC"
        );
        
        $as400_query->execute();
        $batch_results = $as400_query->fetchAll(PDO::FETCH_ASSOC);
        $total_as400_records += count($batch_results);

        // **IMPROVED: Direct matching instead of pattern matching**
        $picker_lookup = array();
        foreach ($batch_results as $row) {
            // Use exact match keys
            $exact_key = $row['PKWCS#'] . '|' . $row['PKWKNO'] . '|' . $row['PKBOX#'] . '|' . $row['PKITEM'];
            if (!isset($picker_lookup[$exact_key])) {
                $picker_lookup[$exact_key] = array(
                    'picker' => $row['PKPEMP'],
                    'pick_date' => $row['PKPDTE'],
                    'pick_whse' => $row['PKWHSE'],
                    'pick_location' => $row['PICK_LOCATION'],
                    'itemcode' => $row['PKITEM'],
                    'lpnum' => $row['PKLP#'],
                    'batch' => $row['BATCH']
                );
            }
        }

        // **IMPROVED: Exact matching logic**
        foreach ($batch as $record) {
            $exact_pattern = $record['WCSNUM'] . '|' . $record['WONUM'] . '|' . $record['BOXNUM'] . '|' . $record['ITEMCODE'];

            if (isset($picker_lookup[$exact_pattern])) {
                $picker_data = $picker_lookup[$exact_pattern];
                $picker_updates[] = array(
                    'wcs' => $record['WCSNUM'],
                    'wo' => $record['WONUM'],
                    'box' => $record['BOXNUM'],
                    'lpnum' => $record['LPNUM'],
                    'picker' => $picker_data['picker'],
                    'pick_date' => $picker_data['pick_date'],
                    'pick_whse' => $picker_data['pick_whse'],
                    'pick_location' => $picker_data['pick_location'],
                    'batch' => $picker_data['batch']
                );
                $total_matches++;
            }
        }
    }

    // Update complaint_detail with picker information
    if (!empty($picker_updates)) {
        foreach ($picker_updates as $update) {
            $update_sql = "
                UPDATE custaudit.complaint_detail 
                SET PICK_TSMNUM = :picker,
                    PICK_DATE = :pick_date,
                    PICK_WHSE = :pick_whse,
                    PICK_LOCATION = :pick_location,
                    BATCH_NUM = :batch,
                    PICK_TSM = (SELECT DISTINCT Name FROM nahsi.locus_tags WHERE UserId = :picker LIMIT 1)
                WHERE WCSNUM = :wcs 
                    AND WONUM = :wo 
                    AND BOXNUM = :box
                    AND LPNUM = :lpnum
            ";
            $update_query = $conn1->prepare($update_sql);
            $update_query->execute([
                ':picker' => $update['picker'],
                ':pick_date' => $update['pick_date'],
                ':pick_whse' => $update['pick_whse'],
                ':pick_location' => $update['pick_location'],
                ':batch' => $update['batch'],
                ':wcs' => $update['wcs'],
                ':wo' => $update['wo'],
                ':box' => $update['box'],
                ':lpnum' => $update['lpnum']
            ]);
        }
    }
}

foreach ($whsebuild_array as $key => $value) {
    $schema = 'custaudit';
    $whse = $whsebuild_array[$key]['whse'];
    $build = $whsebuild_array[$key]['build'];
    $tableupdated = 'complaint_detail';
    include '../heatmap_logic/tbl_update_NAHSI_table_updatetimes.php';
}

// **FINAL STEP: Update packer data for AS400 picks using batch numbers**
// Get records that have batch numbers but missing packer data
$missing_packer_query = $conn1->prepare("
    SELECT DISTINCT BATCH_NUM, PICK_WHSE
    FROM custaudit.complaint_detail 
    WHERE BATCH_NUM IS NOT NULL 
        AND BATCH_NUM != '-'
        AND PACK_TSM IS NULL
        AND BOXSIZE <> 'CSE'
        AND ORD_RETURNDATE >= '$startdate'
");
$missing_packer_query->execute();
$missing_packer_records = $missing_packer_query->fetchAll(PDO::FETCH_ASSOC);

if (!empty($missing_packer_records)) {
    // Collect unique batch numbers and warehouses
    $batch_numbers = array();
    $whse_numbers = array();

    foreach ($missing_packer_records as $record) {
        $batch_numbers[] = "'" . $record['BATCH_NUM'] . "'";
        $whse_numbers[] = $record['PICK_WHSE'];
    }

    $batch_clause = implode(',', array_unique($batch_numbers));
    $whse_clause = "'" . implode("','", array_unique($whse_numbers)) . "'";

    // Query allcart_history_hist for packer data using batch numbers
    $packer_query = $conn1->prepare("
        SELECT DISTINCT
            cartstart_batch,
            cartstart_whse,
            cartstart_tsm,
            cartstart_starttime,
            cartstart_packstation
        FROM printvis.allcart_history_hist 
        WHERE cartstart_batch IN ($batch_clause)
            AND cartstart_whse IN ($whse_clause)
            AND cartstart_tsm IS NOT NULL
        ORDER BY cartstart_starttime DESC
    ");
    $packer_query->execute();
    $packer_results = $packer_query->fetchAll(PDO::FETCH_ASSOC);

    // Create lookup table for packer data
    $packer_lookup = array();
    foreach ($packer_results as $row) {
        $lookup_key = $row['cartstart_batch'] . '|' . $row['cartstart_whse'];
        if (!isset($packer_lookup[$lookup_key])) {
            $packer_lookup[$lookup_key] = array(
                'pack_tsm' => $row['cartstart_tsm'],
                'pack_date' => $row['cartstart_starttime'],
                'pack_station' => $row['cartstart_packstation']
            );
        }
    }

    // Update complaint_detail with packer information
    if (!empty($packer_lookup)) {
        foreach ($packer_lookup as $batch_whse_key => $packer_data) {
            list($batch, $whse) = explode('|', $batch_whse_key);

            $packer_update_sql = "
                UPDATE custaudit.complaint_detail 
                SET PACK_TSM = :pack_tsm,
                    PACK_DATE = :pack_date,
                    PACK_STATION = :pack_station,
                    PACK_TSMNAME = (SELECT DISTINCT tsm_name FROM printvis.tsm WHERE tsm_num = :pack_tsm)
                WHERE BATCH_NUM = :batch 
                    AND PICK_WHSE = :whse
                    AND PACK_TSM IS NULL
                    AND BOXSIZE <> 'CSE'
                    AND ORD_RETURNDATE >= '$startdate'
            ";
            $packer_update_query = $conn1->prepare($packer_update_sql);
            $packer_update_query->execute([
                ':pack_tsm' => $packer_data['pack_tsm'],
                ':pack_date' => $packer_data['pack_date'],
                ':pack_station' => $packer_data['pack_station'],
                ':batch' => $batch,
                ':whse' => $whse
            ]);
        }
    }
}
