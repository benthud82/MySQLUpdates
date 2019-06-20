<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';


if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.tiercounts WHERE Whse = $var_whse";
    $whsefilter = 'LOWHSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.tiercounts";
    $whsefilter = 'LOWHSE in (2,3,6,7,9,11,12,16)';
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'TIER_WHS, TIER_TIER, TIER_COUNT, TIER_DESCRIPTION';

$whsearray = array(2);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT LMWHSE, LMTIER, count(LMWHSE) as TIERCOUNT, LMFIXT||'-'||LMSTGT as TIERDESC FROM HSIPCORDTA.NPFLSM WHERE LMWHSE in (2,6,7,9) and LMTIER <> ' '  and LMLOC# not like 'Q%' and LMLOC# not like 'N%' and LMSTGT <> 'DV' GROUP BY LMWHSE, LMTIER,  LMFIXT||'-'||LMSTGT");
    $tierresult->execute();
    $tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($tierarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $TIER_WHS = intval($tierarray[$counter]['LMWHSE']);
            $TIER_TIER = $tierarray[$counter]['LMTIER'];
            $TIER_COUNT = intval($tierarray[$counter]['TIERCOUNT']);
            $TIER_DESCRIPTION = $tierarray[$counter]['TIERDESC'];

            $data[] = "($TIER_WHS, '$TIER_TIER', $TIER_COUNT, '$TIER_DESCRIPTION')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.tiercounts ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}

//update for sparks for case building 2 where location is > W30
foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT 32 as LMWHSE, LMTIER, count(LMWHSE) as TIERCOUNT, LMFIXT||'-'||LMSTGT as TIERDESC FROM HSIPCORDTA.NPFLSM WHERE LMWHSE in (3) and LMTIER <> ' '  and LMLOC# not like 'Q%' and LMLOC# not like 'N%' and LMLOC# >= 'W300000' GROUP BY LMWHSE, LMTIER,  LMFIXT||'-'||LMSTGT");
    $tierresult->execute();
    $tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($tierarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $TIER_WHS = intval($tierarray[$counter]['LMWHSE']);
            $TIER_TIER = $tierarray[$counter]['LMTIER'];
            $TIER_COUNT = intval($tierarray[$counter]['TIERCOUNT']);
            $TIER_DESCRIPTION = $tierarray[$counter]['TIERDESC'];

            $data[] = "($TIER_WHS, '$TIER_TIER', $TIER_COUNT, '$TIER_DESCRIPTION')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.tiercounts ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}

//update for sparks for main building 1 in sparks
foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT LMWHSE, LMTIER, count(LMWHSE) as TIERCOUNT, LMFIXT||'-'||LMSTGT as TIERDESC FROM HSIPCORDTA.NPFLSM WHERE LMWHSE in (3) and LMTIER <> ' '  and LMLOC# not like 'Q%' and LMLOC# not like 'N%' and LMLOC# <= 'W300000' GROUP BY LMWHSE, LMTIER,  LMFIXT||'-'||LMSTGT");
    $tierresult->execute();
    $tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($tierarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $TIER_WHS = intval($tierarray[$counter]['LMWHSE']);
            $TIER_TIER = $tierarray[$counter]['LMTIER'];
            $TIER_COUNT = intval($tierarray[$counter]['TIERCOUNT']);
            $TIER_DESCRIPTION = $tierarray[$counter]['TIERDESC'];

            $data[] = "($TIER_WHS, '$TIER_TIER', $TIER_COUNT, '$TIER_DESCRIPTION')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.tiercounts ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}


$whsearray = array(11);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn_can->prepare("SELECT LMWHSE, LMTIER, count(LMWHSE) as TIERCOUNT, LMFIXT||'-'||LMSTGT as TIERDESC FROM ARCPCORDTA.NPFLSM WHERE LMWHSE in (11,12,16) and LMTIER <> ' ' GROUP BY LMWHSE, LMTIER,  LMFIXT||'-'||LMSTGT");
    $tierresult->execute();
    $tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($tierarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $TIER_WHS = intval($tierarray[$counter]['LMWHSE']);
            $TIER_TIER = $tierarray[$counter]['LMTIER'];
            $TIER_COUNT = intval($tierarray[$counter]['TIERCOUNT']);
            $TIER_DESCRIPTION = $tierarray[$counter]['TIERDESC'];

            $data[] = "($TIER_WHS, '$TIER_TIER', $TIER_COUNT, '$TIER_DESCRIPTION')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.tiercounts ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}