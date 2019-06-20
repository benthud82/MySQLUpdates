<?php

set_time_limit(99999);
ini_set('memory_limit', '-1');

include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';


$result = $aseriesconn->prepare("SELECT PDWHSE,(CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) as PICKDATE, CASE WHEN LMTIER = 'L01' then PDLOC#   when LMTIER = 'L05' and LMWHSE = 7 then substring(PDLOC#,1,3) || '01' else substring(PDLOC#,1,5) end as BAY, count(*) as PICKCOUNT FROM A.HSIPCORDTA.NOTWPT JOIN HSIPCORDTA.NPFLSM on LMWHSE = PDWHSE and PDLOC# = LMLOC# WHERE PDLOC# between 'A000000' and 'B999999' and PDBXSZ <> 'CSE' and (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 5 Days) and substring(PDLOC#,1,5) <> ' ' GROUP BY PDWHSE,PDSHPD,CASE WHEN LMTIER = 'L01' then PDLOC#   when LMTIER = 'L05' and LMWHSE = 7 then substring(PDLOC#,1,3) || '01' else substring(PDLOC#,1,5) end ");
$result->execute();
$resultarray = $result->fetchAll(PDO::FETCH_ASSOC);



$columns = 'picksbybay_WHSE, picksbybay_DATE, picksbybay_BAY, picksbybay_PICKS';

$maxrange = 999;
$counter = 0;
$rowcount = count($resultarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }
    $data = array();
    $values = array();

    while ($counter <= $maxrange) {

        $picksbybay_WHSE = $resultarray[$counter]['PDWHSE'];
        $picksbybay_DATE = date('Y-m-d', strtotime($resultarray[$counter]['PICKDATE']));
        $picksbybay_BAY = $resultarray[$counter]['BAY'];
        $picksbybay_PICKS = $resultarray[$counter]['PICKCOUNT'];

        $data[] = "($picksbybay_WHSE, '$picksbybay_DATE', '$picksbybay_BAY', $picksbybay_PICKS)";
        $counter +=1;
    }

    $values = implode(',', $data);
    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO slotting.picksbybay ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=1000;
} while ($counter <= $rowcount);
 
 
 $result = $aseriesconn->prepare("SELECT PDWHSE,(CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) as PICKDATE,substring(PDLOC#,1,5) as BAY, count(*) as PICKCOUNT FROM A.ARCPCORDTA.NOTWPT WHERE PDLOC# between 'A000000' and 'B999999' and PDBXSZ <> 'CSE' and (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 365 Days) and substring(PDLOC#,1,5) <> ' ' GROUP BY PDWHSE,PDSHPD,substring(PDLOC#,1,5)");
 $result->execute();
 $resultarray = $result->fetchAll(PDO::FETCH_ASSOC);
 
 
 
 $columns = 'picksbybay_WHSE, picksbybay_DATE, picksbybay_BAY, picksbybay_PICKS';
 
 $maxrange = 9999;
 $counter = 0;
 $rowcount = count($resultarray);
 
 do {
     if ($maxrange > $rowcount) {   //prevent undefined offset
         $maxrange = $rowcount - 1;
     }
     $data = array();
     $values = array();
 
     while ($counter <= $maxrange) {
 
         $picksbybay_WHSE = $resultarray[$counter]['PDWHSE'];
         $picksbybay_DATE = date('Y-m-d', strtotime($resultarray[$counter]['PICKDATE']));
         $picksbybay_BAY = $resultarray[$counter]['BAY'];
         $picksbybay_PICKS = $resultarray[$counter]['PICKCOUNT'];
 
         $data[] = "($picksbybay_WHSE, '$picksbybay_DATE', '$picksbybay_BAY', $picksbybay_PICKS)";
         $counter +=1;
     }
 
     $values = implode(',', $data);
     if (empty($values)) {
         break;
     }
 
     $sql = "INSERT IGNORE INTO slotting.picksbybay ($columns) VALUES $values";
     $query = $conn1->prepare($sql);
     $query->execute();
     $maxrange +=1000;
 } while ($counter <= $rowcount);




