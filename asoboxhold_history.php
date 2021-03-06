<?php

include '../globalincludes/usa_asys.php';
include '../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//put in connection includes (as400 printvis)

$result1 = $aseriesconn->prepare("SELECT PLWHSE, PLWCS#, PLWKNO, PLLP9D, PLBOX#, PLITEM, PLRESN, PLRCJD, PLRCTM, PLRLJD, PLRLTM, PLUSER, PLSTAT, PLLOC#  FROM HSIPCORDTA.NOTWPL WHERE PLRESN = 'ASO' AND PLSTAT = 'C'");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'holdhistory_whse,holdhistory_wcs,holdhistory_wonum,holdhistory_lpnum,holdhistory_boxnum,holdhistory_item,holdhistory_reason,holdhistory_recdate, holdhistory_rectime, holdhistory_releasedate, holdhistory_releasetime, holdhistory_user, holdhistory_status, holdhistory_location';

$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($mindaysarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }
	
$data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
        $holdhistory_whse = $mindaysarray[$counter]['PLWHSE'];
        $holdhistory_wcs = $mindaysarray[$counter]['PLWCS#'];
        $holdhistory_wonum = $mindaysarray[$counter]['PLWKNO'];
        $holdhistory_lpnum = $mindaysarray[$counter]['PLLP9D'];
        $holdhistory_boxnum = $mindaysarray[$counter]['PLBOX#'];
        $holdhistory_item = $mindaysarray[$counter]['PLITEM'];
        $holdhistory_reason = $mindaysarray[$counter]['PLRESN'];
        $holdhistory_recdate = $mindaysarray[$counter]['PLRCJD'];
        $holdhistory_rectime = $mindaysarray[$counter]['PLRCTM'];
        $holdhistory_releasedate = $mindaysarray[$counter]['PLRLJD'];
        $holdhistory_releasetime = $mindaysarray[$counter]['PLRLTM'];
        $holdhistory_user = $mindaysarray[$counter]['PLUSER'];
        $holdhistory_status = $mindaysarray[$counter]['PLSTAT'];
        $holdhistory_location = $mindaysarray[$counter]['PLLOC#'];
		
	$data[] = "($holdhistory_whse, $holdhistory_wcs, $holdhistory_wonum, $holdhistory_lpnum, $holdhistory_boxnum, $holdhistory_item,'$holdhistory_reason', $holdhistory_recdate, $holdhistory_rectime, $holdhistory_releasedate, $holdhistory_releasetime, '$holdhistory_user', '$holdhistory_status', '$holdhistory_location')";
        $counter += 1;
    }
		
	$values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO printvis.asoboxhold_history ($columns) VALUES $values ON DUPLICATE KEY UPDATE
        holdhistory_whse=VALUES(holdhistory_whse),
        holdhistory_wcs=VALUES(holdhistory_wcs),
        holdhistory_wonum= VALUES(holdhistory_wonum),
        holdhistory_lpnum=VALUES(holdhistory_lpnum),
        holdhistory_boxnum=VALUES(holdhistory_boxnum),
        holdhistory_item=VALUES(holdhistory_item),
        holdhistory_reason=VALUES(holdhistory_reason),
        holdhistory_recdate=VALUES(holdhistory_recdate),
        holdhistory_rectime=VALUES(holdhistory_rectime),
        holdhistory_releasedate=VALUES(holdhistory_releasedate),
        holdhistory_releasetime=VALUES(holdhistory_releasetime),
        holdhistory_user=VALUES(holdhistory_user),
        holdhistory_status=VALUES(holdhistory_status),
        holdhistory_location=VALUES(holdhistory_location)";

    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);
	
		



/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

