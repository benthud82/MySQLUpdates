<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include '../connections/conn_printvis.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';



$sqldelete = "TRUNCATE TABLE printvis.tsm";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$result1 = $aseriesconn->prepare("SELECT trim(EMPLNO) as EMPLNO, trim(EMPNME) as EMPNME FROM HSIPCORDTA.EMNMLF ");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'tsm_num, tsm_name';


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
        $tsmnum = intval($mindaysarray[$counter]['EMPLNO']);
        $tsmname =  trim(preg_replace('/[^ \w]+/', '', ($mindaysarray[$counter]['EMPNME'])));
       

        $data[] = "($tsmnum, '$tsmname')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.tsm ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);

