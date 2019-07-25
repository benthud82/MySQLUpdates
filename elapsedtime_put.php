<?php

ini_set('memory_limit', '-1');
set_time_limit(99999);
include_once '../connections/conn_printvis.php';
$mintime = 5;
$maxtime = 13;
$columns = 'etput_whse, etput_id, etput_tsm, etput_curbatch, etput_curloc, etput_curqty, etput_caseqty, etput_eachqty, etput_curtime, etput_prevbatch, etput_prevloc, etput_prevqty, etput_prevtime, etput_timedif,  etput_difbatch, etput_equip';
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
                                                            comp_put_equip
                                                        FROM
                                                            printvis.completed_putaway
                                                                LEFT JOIN
                                                            printvis.tsm ON comp_put_tsm = tsm_num
                                                            JOIN printvis.pm_putawaytimes on comp_put_whse = put_whse and put_function = comp_put_equip
                                                        ORDER BY comp_put_tsm ASC , comp_put_datetime ASC");
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
    $timetosubtract = (($caseqty * $put_obtainall) + ($caseqty * $put_placeall ) + ($eachqty * $put_obtainall) + ($eachqty * $put_placeall ) );


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
        $data[] = "($whse, $currid, '$TSM', $currbatch, '$loc', $totqty, $caseqty, $eachqty, '$currenttime', $prevbatch,  '$prevloc', $prevpickqty, '$prevpicktime', '$timemin', $difbatch, '$equip')";
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
