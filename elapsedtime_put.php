<?php

ini_set('memory_limit', '-1');
set_time_limit(99999);
include_once '../connections/conn_printvis.php';
$mintime = 5;
$maxtime = 30;
$columns = 'etput_whse, etput_id, etput_tsm, etput_curbatch, etput_curloc, etput_curqty, etput_caseqty, etput_eachqty, etput_curtime, etput_prevbatch, etput_prevloc, etput_prevqty, etput_prevtime, etput_timedif, etput_difbatch, etput_breaklunch, etput_equip';
$today = date('Y-m-d', strtotime(' -5 days'));


$data = array();

$batches = $conn1->prepare("SELECT 
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
    tsm_name,
    put_obtainall,
    put_placeall,
    comp_put_equip,
    (SELECT 
            MAX(CASE
                    WHEN blcomb_type = 'BREAK' THEN 15
                    WHEN blcomb_type = 'LUNCH' THEN 30
                    ELSE 0
                END)
        FROM
            printvis.breaklunch_combined
        WHERE
            blcomb_tsm = comp_put_tsm
                AND DATE(comp_put_datetime) = DATE(blcomb_datetime)
                AND blcomb_datetime BETWEEN @prevtime AND comp_put_datetime) AS BREAKLUNCH,
    @prevtime AS PREVTIME,
    (SELECT 
            @prevtime:=comp_put_datetime
        FROM
            printvis.completed_putaway t
        WHERE
            A.comp_put_trans = t.comp_put_trans) AS CURRTIME
FROM
    printvis.completed_putaway A
        LEFT JOIN
    printvis.tsm ON comp_put_tsm = tsm_num
        JOIN
    printvis.pm_putawaytimes ON comp_put_whse = put_whse
        AND put_function = comp_put_equip
ORDER BY comp_put_tsm , comp_put_datetime");
$batches->execute();
$batches_array2 = $batches->fetchAll(pdo::FETCH_ASSOC);


$batches = $conn1->prepare("SELECT 
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
    tsm_name,
    put_obtainall,
    put_placeall,
    comp_put_equip,
    (SELECT 
            MAX(CASE
                    WHEN blcomb_type = 'BREAK' THEN 15
                    WHEN blcomb_type = 'LUNCH' THEN 30
                    ELSE 0
                END)
        FROM
            printvis.breaklunch_combined
        WHERE
            blcomb_tsm = comp_put_tsm
                AND DATE(comp_put_datetime) = DATE(blcomb_datetime)
                AND blcomb_datetime BETWEEN @prevtime AND comp_put_datetime) AS BREAKLUNCH,
    @prevtime AS PREVTIME,
    (SELECT 
            @prevtime:=comp_put_datetime
        FROM
            printvis.completed_putaway t
        WHERE
            A.comp_put_trans = t.comp_put_trans) AS CURRTIME
FROM
    printvis.completed_putaway A
        LEFT JOIN
    printvis.tsm ON comp_put_tsm = tsm_num
        JOIN
    printvis.pm_putawaytimes ON comp_put_whse = put_whse
        AND put_function = comp_put_equip
ORDER BY comp_put_tsm , comp_put_datetime");
$batches->execute();
$batches_array = $batches->fetchAll(pdo::FETCH_ASSOC);

$previd = 0;
$prevtime = 0;
$prevdate = 0;

foreach ($batches_array as $key => $value) {
    $difbatch = 0;
    $curdate = date('Y-m-d', strtotime($batches_array[$key]['comp_put_datetime']));

    $currid = intval($batches_array[$key]['comp_put_tsm']);
    $currbatch = intval($batches_array[$key]['comp_put_log']);
    if ($currid !== $previd || $prevdate !== $curdate) {
        $currenttime = ($batches_array[$key]['comp_put_datetime']);
        $currtimestamp = strtotime($currenttime);
        $prevtimestamp = $currtimestamp;
        $prevbatch = $currbatch;
        $prevdate = $curdate;
//first pick for user id.  Do not calculate time difference
        $previd = $currid;
        continue;
    }
    $whse = ($batches_array[$key]['comp_put_whse']);
    $equip = ($batches_array[$key]['comp_put_equip']);
    $put_obtainall = ($batches_array[$key]['put_obtainall']);
    $put_placeall = ($batches_array[$key]['put_placeall']);
    $caseqty = intval($batches_array[$key]['comp_put_caseqty']);
    $eachqty = intval($batches_array[$key]['comp_put_eachqty']);
    $currenttime = ($batches_array[$key]['comp_put_datetime']);
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
        $loc = ($batches_array[$key]['comp_put_loc']);
        $totqty = intval($batches_array[$key]['comp_put_totqty']);
        $caseqty = intval($batches_array[$key]['comp_put_caseqty']);
        $eachqty = intval($batches_array[$key]['comp_put_eachqty']);
        $prevloc = ($batches_array[$key - 1]['comp_put_loc']);
        $prevpickqty = intval($batches_array[$key - 1]['comp_put_totqty']);
        $prevpicktime = ($batches_array[$key - 1]['comp_put_datetime']);
        if ($currbatch !== $prevbatch) {
            $difbatch = 1;
        }
        $data[] = "($whse, $currid, '$TSM', $currbatch, '$loc', $totqty, $caseqty, $eachqty, '$currenttime', $prevbatch,  '$prevloc', $prevpickqty, '$prevpicktime', '$timemin', $difbatch, $BREAKLUNCH_sub, '$equip')";
    }
//set previous time as current time for next loop
    $prevtimestamp = $currtimestamp;
    $previd = $currid;
    $prevbatch = $currbatch;
}

//insert into table
$values = implode(',', $data);

$sql = "INSERT IGNORE INTO printvis.elapsedtime_put ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();
