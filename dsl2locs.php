<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';



$sqldelete = "TRUNCATE TABLE slotting.dsl2locs";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$result1 = $aseriesconn->prepare("select LMWHSE, LMITEM, LMPKGU, substr(LMTIER,1,1) as LMCSLS from HSIPCORDTA.NPFLSM  where LMSLR# = '2' and LMWHSE in (2, 3, 6, 7, 9)");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'dsl2whs, dsl2item, dsl2pkgu, dsl2csls';


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
        $LMWHSE = $mindaysarray[$counter]['LMWHSE'];
        $LMITEM = $mindaysarray[$counter]['LMITEM'];
        $LMPKGU = $mindaysarray[$counter]['LMPKGU'];
        $LMCSLS = $mindaysarray[$counter]['LMCSLS'];

        $data[] = "($LMWHSE, $LMITEM, $LMPKGU, '$LMCSLS')";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.dsl2locs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);



$result1 = $aseriesconn->prepare("select LMWHSE, LMITEM, LMPKGU, substr(LMTIER,1,1) as LMCSLS from ARCPCORDTA.NPFLSM  where LMSLR# = '2' and LMWHSE in (11, 12, 16)");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'dsl2whs, dsl2item, dsl2pkgu, dsl2csls';


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
        $LMWHSE = $mindaysarray[$counter]['LMWHSE'];
        $LMITEM = $mindaysarray[$counter]['LMITEM'];
        $LMPKGU = $mindaysarray[$counter]['LMPKGU'];
        $LMCSLS = $mindaysarray[$counter]['LMCSLS'];

        $data[] = "($LMWHSE, $LMITEM, $LMPKGU, '$LMCSLS')";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.dsl2locs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);


