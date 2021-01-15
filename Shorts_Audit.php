<?php

include '../globalincludes/usa_asys.php';
include '../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//put in connection includes (as400 printvis)

$today = date('Y-m-d');

$result1 = $aseriesconn->prepare("SELECT ADWHSE, ADITEM, ADPKGU, ADLOC#, ADDSLT, ADWCS#, ADWKNO, ADBOX#, ADCART, ADRCDT, ADRCTM, ADUSER, ADPCKS, ADHAND, ADRCVQ, ADPMTQ, ADRMTQ, ADOPNA, ADPRTA, ADBXHD, ADBXRL FROM HSIPCORDTA.NPTSHTAUD WHERE ADRCDT = '$today'");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'shortsaudit_whse, shortsaudit_item, shortsaudit_pkgu, shortsaudit_location, shortsaudit_dsl, shortsaudit_wcsnum, shortsaudit_workorder, shortsaudit_boxnum, shortsaudit_batch, shortsaudit_date, shortsaudit_time, shortsaudit_picker, shortsaudit_pickqty, shortsaudit_onhand, shortsaudit_recqty, shortsaudit_moveprimary, shortsaudit_movereserve, shortsaudit_openalloc, shortsaudit_printedalloc, shortsaudit_boxhold, shortsaudit_boxreleaseuser';

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
        $shortsaudit_whse = $mindaysarray[$counter]['ADWHSE'];
        $shortsaudit_item = INTVAL($mindaysarray[$counter]['ADITEM']);
        $shortsaudit_pkgu = $mindaysarray[$counter]['ADPKGU'];
        $shortsaudit_location = $mindaysarray[$counter]['ADLOC#'];
        $shortsaudit_dsl = INTVAL($mindaysarray[$counter]['ADDSLT']);
        $shortsaudit_wcsnum = $mindaysarray[$counter]['ADWCS#'];
        $shortsaudit_workorder = $mindaysarray[$counter]['ADWKNO'];
        $shortsaudit_boxnum = $mindaysarray[$counter]['ADBOX#'];
        $shortsaudit_batch = $mindaysarray[$counter]['ADCART'];
        $shortsaudit_date = $mindaysarray[$counter]['ADRCDT'];
        $shortsaudit_time = $mindaysarray[$counter]['ADRCTM'];
        $shortsaudit_picker = $mindaysarray[$counter]['ADUSER'];
        $shortsaudit_pickqty = $mindaysarray[$counter]['ADPCKS'];
        $shortsaudit_onhand = $mindaysarray[$counter]['ADHAND'];
        $shortsaudit_recqty = $mindaysarray[$counter]['ADRCVQ'];
        $shortsaudit_moveprimary = $mindaysarray[$counter]['ADPMTQ'];
        $shortsaudit_movereserve = $mindaysarray[$counter]['ADRMTQ'];
        $shortsaudit_openalloc = $mindaysarray[$counter]['ADOPNA'];
        $shortsaudit_printedalloc = $mindaysarray[$counter]['ADPRTA'];
        $shortsaudit_boxhold = $mindaysarray[$counter]['ADBXHD'];
        $shortsaudit_boxreleaseuser = $mindaysarray[$counter]['ADBXRL'];
		
	$data[] = "($shortsaudit_whse, $shortsaudit_item, $shortsaudit_pkgu, '$shortsaudit_location', $shortsaudit_dsl, $shortsaudit_wcsnum, $shortsaudit_workorder, $shortsaudit_boxnum, $shortsaudit_batch, '$shortsaudit_date', '$shortsaudit_time', $shortsaudit_picker, $shortsaudit_pickqty, $shortsaudit_onhand, $shortsaudit_recqty, $shortsaudit_moveprimary, $shortsaudit_movereserve, $shortsaudit_openalloc, $shortsaudit_printedalloc, '$shortsaudit_boxhold', '$shortsaudit_boxreleaseuser')";
        $counter += 1;
    }
		
	$values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO nahsi.shortsaudit_history ($columns) VALUES $values ON DUPLICATE KEY UPDATE
        shortsaudit_whse=VALUES(shortsaudit_whse),
        shortsaudit_item=VALUES(shortsaudit_item),
        shortsaudit_pkgu= VALUES(shortsaudit_pkgu),
        shortsaudit_location=VALUES(shortsaudit_location),
        shortsaudit_dsl=VALUES(shortsaudit_dsl),
        shortsaudit_wcsnum=VALUES(shortsaudit_wcsnum),
        shortsaudit_workorder=VALUES(shortsaudit_workorder),
        shortsaudit_boxnum=VALUES(shortsaudit_boxnum),
        shortsaudit_batch=VALUES(shortsaudit_batch),
        shortsaudit_date=VALUES(shortsaudit_date),
        shortsaudit_time=VALUES(shortsaudit_time),
        shortsaudit_picker=VALUES(shortsaudit_picker),
        shortsaudit_pickqty=VALUES(shortsaudit_pickqty),
        shortsaudit_onhand=VALUES(shortsaudit_onhand),            
        shortsaudit_recqty=VALUES(shortsaudit_recqty),
        shortsaudit_moveprimary=VALUES(shortsaudit_moveprimary),
        shortsaudit_movereserve=VALUES(shortsaudit_movereserve),
        shortsaudit_openalloc=VALUES(shortsaudit_openalloc),
        shortsaudit_printedalloc=VALUES(shortsaudit_printedalloc),   
        shortsaudit_boxhold=VALUES(shortsaudit_boxhold),
        shortsaudit_boxreleaseuser=VALUES(shortsaudit_boxreleaseuser)";

    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);
	
		



/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

