<?php

ini_set('memory_limit', '-1');
set_time_limit(99999);
include_once '../connections/conn_printvis.php';
$whsearray = array(2, 3, 6, 7, 9);
$mintime = 5;
$maxtime = 13;
$columns = 'etpick_whse, etpick_id, etpick_tsm, etpick_curbatch, etpick_curloc, etpick_curqty, etpick_curtime, etpick_prevbatch, etpick_prevloc, etpick_prevqty, etpick_prevtime, etpick_timedif,  etpick_difbatch';
$today = date('Y-m-d', strtotime(' -5 days'));

foreach ($whsearray as $whsesel) {
    $whse = intval($whsesel);
    $data = array();
    include '../globalincludes/voice_' . $whse . '.php';

    $batches = $dbh->prepare("SELECT t2.UserDescription AS UserDescription, 
                                                            t1.ReserveUSerID AS ReserveUserID, 
                                                            t1.batch_num AS Batch_Num, 
                                                            convert(varchar(25), t1.DateTimeFirstPick, 120) AS DateTimeFirstPick ,
                                                            t1.Location,
                                                            t1.QtyPick, 
                                                            t1.PackageUnit
                                            FROM Henryschein.dbo.Pick AS t1 WITH (NOLOCK)
	INNER JOIN JenX.dbo.Users AS t2 WITH (NOLOCK) ON t1.ReserveUserID = t2.UserName
	WHERE (t1.DateTimefirstPick >= '$today') 
	ORDER BY t1.ReserveUSerID, t1.DateTimeFirstPick");
    $batches->execute();
    $batches_array = $batches->fetchAll(pdo::FETCH_ASSOC);

    $previd = 0;
    $prevtime = 0;
    $prevdate = 0;

    foreach ($batches_array as $key => $value) {
        $difbatch = 0;
        $curdate = date('Y-m-d', strtotime($batches_array[$key]['DateTimeFirstPick']));

        $currid = intval($batches_array[$key]['ReserveUserID']);
        $currbatch = intval($batches_array[$key]['Batch_Num']);
        if ($currid !== $previd || $prevdate !== $curdate) {
            $currenttime = ($batches_array[$key]['DateTimeFirstPick']);
            $currtimestamp = strtotime($currenttime);
            $prevtimestamp = $currtimestamp;
            $prevbatch = $currbatch;
            $prevdate = $curdate;
            //first pick for user id.  Do not calculate time difference
            $previd = $currid;
            continue;
        }

        $currenttime = ($batches_array[$key]['DateTimeFirstPick']);
        $currtimestamp = strtotime($currenttime);
        $timediff = $currtimestamp - $prevtimestamp;
        $timemin = round($timediff / 60, 2);
        if ($timemin >= $mintime && $timemin <= $maxtime) {
            //push records to data array for inserting into 

            $TSM = str_replace("'", " ", $batches_array[$key]['UserDescription']);
//            $TSM = preg_replace('/[^ \w]+/', '', $batches_array[$key]['UserDescription']);
            $loc = ($batches_array[$key]['Location']);
            $pickqty = intval($batches_array[$key]['QtyPick']);
            $pkgu = intval($batches_array[$key]['PackageUnit']);
            $prevloc = ($batches_array[$key - 1]['Location']);
            $prevpickqty = intval($batches_array[$key - 1]['QtyPick']);
            $prevpicktime = ($batches_array[$key - 1]['DateTimeFirstPick']);
            if ($currbatch !== $prevbatch) {
                $difbatch = 1;
            }
            $data[] = "($whse, $currid, '$TSM', $currbatch, '$loc', $pickqty, '$currenttime', $prevbatch,  '$prevloc', $prevpickqty, '$prevpicktime', '$timemin', $difbatch)";
        }
        //set previous time as current time for next loop
        $prevtimestamp = $currtimestamp;
        $previd = $currid;
        $prevbatch = $currbatch;
    }

    //insert into table
    $values = implode(',', $data);
    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.elapsedtime_pick ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
}
