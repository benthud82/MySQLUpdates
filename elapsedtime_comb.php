<?php

//!!Must be run after php update files completed_putaway and completed_replen!!
//!!Setup a control file to ensure this happens!!

ini_set('memory_limit', '-1');
set_time_limit(99999);
include_once '../connections/conn_printvis.php';
$mintime = 5;
$maxtime = 30;
$columns = 'etcomb_whse, etcomb_id, etcomb_tsm, etcomb_curbatch, etcomb_curloc, etcomb_curqty, etcomb_caseqty, etcomb_eachqty, etcomb_curtime, etcomb_prevbatch, etcomb_prevloc, etcomb_prevqty, etcomb_prevtime, etcomb_timedif, etcomb_difbatch, etcomb_breaklunch, etcomb_equip, etcomb_path';
$today = date('Y-m-d', strtotime(' -5 days'));

/*
 * 2020-01-21 Update
 * printvis.completed_replen table is now being populated
 * combine the two tables (printvis.completed_putaway and completed_replen) into one table
 * called printvis.completed_materials_comb.
 * Use this table to perform analysis on elapsed time.
 */

$sqldelete = "TRUNCATE TABLE printvis.completed_materials_comb";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();



//insert putaway transactions into combined table
$sqlput = "INSERT INTO printvis.completed_materials_comb (SELECT 0,
                                                            comp_put_whse,  
                                                            comp_put_trans, 
                                                            comp_put_item, 
                                                            comp_put_totqty,
                                                            comp_put_caseqty, 
                                                            comp_put_eachqty,
                                                            comp_put_loc, 
                                                            comp_put_log, 
                                                            comp_put_datetime,
                                                            comp_put_lot, 
                                                            comp_put_expiry,
                                                            comp_put_tsm, 
                                                            comp_put_equip, 
                                                            comp_put_path  FROM printvis.completed_putaway)";
$queryput = $conn1->prepare($sqlput);
$queryput->execute();

//insert replen transactions into combined table
$sqlreplen = "INSERT INTO printvis.completed_materials_comb (SELECT 0,
                                                            comp_replen_whse,  
                                                            comp_replen_trans, 
                                                            comp_replen_item, 
                                                            comp_replen_totqty,
                                                            comp_replen_caseqty, 
                                                            comp_replen_eachqty,
                                                            comp_replen_loc, 
                                                            comp_replen_log, 
                                                            comp_replen_datetime,
                                                            comp_replen_lot, 
                                                            comp_replen_expiry,
                                                            comp_replen_tsm, 
                                                            comp_replen_equip, 
                                                            comp_replen_path  FROM printvis.completed_replen)";
$queryreplen = $conn1->prepare($sqlreplen);
$queryreplen->execute();

$data = array();

$batches = $conn1->prepare("SELECT 
                                comp_mat_comb_whse,
                                comp_mat_comb_trans,
                                comp_mat_comb_item,
                                comp_mat_comb_totqty,
                                comp_mat_comb_caseqty,
                                comp_mat_comb_eachqty,
                                comp_mat_comb_loc,
                                comp_mat_comb_log,
                                comp_mat_comb_datetime,
                                comp_mat_comb_lot,
                                comp_mat_comb_expiry,
                                comp_mat_comb_tsm,
                                tsm_name,
                                .0143 as put_obtainall,
                                .05 as put_placeall,
                                comp_mat_comb_equip,
                                comp_mat_comb_path,
                                (SELECT 
                                        MAX(CASE
                                                WHEN blcomb_type = 'BREAK' THEN 15
                                                WHEN blcomb_type = 'LUNCH' THEN 30
                                                ELSE 0
                                            END)
                                    FROM
                                        printvis.breaklunch_combined
                                    WHERE
                                        blcomb_tsm = comp_mat_comb_tsm
                                            AND DATE(comp_mat_comb_datetime) = DATE(blcomb_datetime)
                                            AND blcomb_datetime BETWEEN @prevtime AND comp_mat_comb_datetime) AS BREAKLUNCH,
                                @prevtime AS PREVTIME,
                                (SELECT 
                                        @prevtime:=comp_mat_comb_datetime
                                    FROM
                                        printvis.completed_materials_comb t
                                    WHERE
                                        A.comp_mat_comb_id = t.comp_mat_comb_id) AS CURRTIME
                            FROM
                                printvis.completed_materials_comb A
                                    LEFT JOIN
                                printvis.tsm ON comp_mat_comb_tsm = tsm_num
                                    ORDER BY comp_mat_comb_tsm , comp_mat_comb_datetime");
$batches->execute();
$batches_array = $batches->fetchAll(pdo::FETCH_ASSOC);

$previd = 0;
$prevtime = 0;
$prevdate = 0;

foreach ($batches_array as $key => $value) {
    $difbatch = 0;
    $curdate = date('Y-m-d', strtotime($batches_array[$key]['comp_mat_comb_datetime']));

    $currid = intval($batches_array[$key]['comp_mat_comb_tsm']);
    $currbatch = intval($batches_array[$key]['comp_mat_comb_log']);
    if ($currid !== $previd || $prevdate !== $curdate) {
        $currenttime = ($batches_array[$key]['comp_mat_comb_datetime']);
        $currtimestamp = strtotime($currenttime);
        $prevtimestamp = $currtimestamp;
        $prevbatch = $currbatch;
        $prevdate = $curdate;
//first pick for user id.  Do not calculate time difference
        $previd = $currid;
        continue;
    }
    $whse = ($batches_array[$key]['comp_mat_comb_whse']);
    $equip = ($batches_array[$key]['comp_mat_comb_equip']);
    $put_obtainall = ($batches_array[$key]['put_obtainall']);
    $put_placeall = ($batches_array[$key]['put_placeall']);
    $caseqty = intval($batches_array[$key]['comp_mat_comb_caseqty']);
    $eachqty = intval($batches_array[$key]['comp_mat_comb_eachqty']);
    $currenttime = ($batches_array[$key]['comp_mat_comb_datetime']);
    $BREAKLUNCH = intval($batches_array[$key]['BREAKLUNCH']);
    if($BREAKLUNCH > 0){
        $BREAKLUNCH_sub = $BREAKLUNCH;
    }else{
        $BREAKLUNCH_sub = 0;
    }
    $timetosubtract = (($caseqty * $put_obtainall) + ($caseqty * $put_placeall ) + ($eachqty * $put_obtainall) + ($eachqty * $put_placeall ) + $BREAKLUNCH_sub);


    $currtimestamp = strtotime($currenttime);


    $timediff = $currtimestamp - $prevtimestamp;
    $timemin = round($timediff / 60, 2) - $timetosubtract;

    if ($timemin >= $mintime && $timemin <= $maxtime) {
//push records to data array for inserting into 

        $TSM = str_replace("'", " ", $batches_array[$key]['tsm_name']);
//            $TSM = preg_replace('/[^ \w]+/', '', $batches_array[$key]['UserDescription']);
        $loc = ($batches_array[$key]['comp_mat_comb_loc']);
        $totqty = intval($batches_array[$key]['comp_mat_comb_totqty']);
        $caseqty = intval($batches_array[$key]['comp_mat_comb_caseqty']);
        $eachqty = intval($batches_array[$key]['comp_mat_comb_eachqty']);
        $prevloc = ($batches_array[$key - 1]['comp_mat_comb_loc']);
        $prevpickqty = intval($batches_array[$key - 1]['comp_mat_comb_totqty']);
        $prevpicktime = ($batches_array[$key - 1]['comp_mat_comb_datetime']);
        $putpath = ($batches_array[$key]['comp_mat_comb_path']);
        if ($currbatch !== $prevbatch) {
            $difbatch = 1;
        }
        $data[] = "($whse, $currid, '$TSM', $currbatch, '$loc', $totqty, $caseqty, $eachqty, '$currenttime', $prevbatch,  '$prevloc', $prevpickqty, '$prevpicktime', '$timemin', $difbatch, $BREAKLUNCH_sub, '$equip', '$putpath')";
    }
//set previous time as current time for next loop
    $prevtimestamp = $currtimestamp;
    $previd = $currid;
    $prevbatch = $currbatch;
}

//insert into table
$values = implode(',', $data);

$sql = "INSERT IGNORE INTO printvis.elapsedtime_comb ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();
