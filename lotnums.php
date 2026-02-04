<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';



$sqldelete = "TRUNCATE TABLE slotting.npflot";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$result1 = $aseriesconn->prepare("SELECT IMITEM, IMLCTL FROM HSIPCORDTA.NPFIMS WHERE IMLCTL <> ' ' and IMITEM >= '1000000' AND TRANSLATE(IMITEM, ' ', '0123456789') = ''");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'lot_item, lot_lot';


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
        $LTITEM = $mindaysarray[$counter]['LTITEM'];
        $LTFLAG = $mindaysarray[$counter]['LTFLAG'];

        $data[] = "($LTITEM, '$LTFLAG')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.npflot ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);

