<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';
$itemdesignation_id = 0;

if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.itemdesignation WHERE Whse = $var_whse";
    $whsefilter = 'LOWHSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.itemdesignation";
    $whsefilter = 'LOWHSE in (2,3,6,7,9,11,12,16)';
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'itemdesignation_id, WHSE, ITEM,  ITEM_TYPE, ITEM_DESC';

$whsearray = array(2,3,6,7,9);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT DISTINCT LMWHSE, LMITEM,  case when (IMCLSC in ('1','2','3','4') or IMCLSC like '%1%' or IMCLSC like '%2%' or IMCLSC like '%3%' or IMCLSC like '%4%' or IMCLSC like '%5%') and IMCLSC <> 'L2' and IMCLSC <> 'S3'  then 'DR' when IMLOCT like 'R%' or LMLOC# like 'I%' then 'FR' when IMHAZC like 'F%' then 'FL' when LMSLR# = '1' then 'CB' else 'ST' end AS ITEM_TYPE, IMDESC FROM HSIPCORDTA.NPFLSM, HSIPCORDTA.NPFIMS, HSIPCORDTA.NPFWRS WHERE IMITEM = LMITEM and WRSWHS = LMWHSE and WRSITM = LMITEM and IMAVLC in (' ','B', 'R')  and LMWHSE = $whsval");
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
            $WHSE = intval($tierarray[$counter]['LMWHSE']);
            $ITEM = intval($tierarray[$counter]['LMITEM']);
//            
//            $LOCATION = ($tierarray[$counter]['LMLOC#']);
            $ITEM_TYPE = $tierarray[$counter]['ITEM_TYPE'];
            $ITEM_DESC = trim(preg_replace('/[^ \w]+/', '', ($tierarray[$counter]['IMDESC'])));

            $data[] = "($itemdesignation_id, $WHSE, $ITEM,  '$ITEM_TYPE', '$ITEM_DESC')";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.itemdesignation ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}




$whsearray = array(11);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn_can->prepare("SELECT DISTINCT LMWHSE, LMITEM,  case when (IMCLSC in ('1','2','3','4') or IMCLSC like '%1%' or IMCLSC like '%2%' or IMCLSC like '%3%' or IMCLSC like '%4%' or IMCLSC like '%5%') and IMCLSC <> 'L2' and IMCLSC <> 'S3'  then 'DR' when IMLOCT like 'R%' then 'FR' when IMHAZC like 'F%' then 'FL' when LMSLR# = '1' then 'CB' else 'ST' end AS ITEM_TYPE, IMDESC FROM ARCPCORDTA.NPFLSM, ARCPCORDTA.NPFIMS, ARCPCORDTA.NPFWRS WHERE IMITEM = LMITEM and WRSWHS = LMWHSE and WRSITM = LMITEM and IMAVLC in (' ','B', 'R')  and LMWHSE in (11,12,16)");
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
            $WHSE = intval($tierarray[$counter]['LMWHSE']);
            $ITEM = intval($tierarray[$counter]['LMITEM']);
//            $LOCATION = ($tierarray[$counter]['LMLOC#']);
            $ITEM_TYPE = $tierarray[$counter]['ITEM_TYPE'];
            $ITEM_DESC = trim(preg_replace('/[^ \w]+/', '', ($tierarray[$counter]['IMDESC'])));

            $data[] = "($itemdesignation_id, $WHSE, $ITEM,  '$ITEM_TYPE', '$ITEM_DESC')";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.itemdesignation ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 10000;
    } while ($counter <= $rowcount); //end of item by whse loop
}