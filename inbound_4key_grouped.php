<?php

//code to update group inbound_5key by unique ID and calc population count, mean, std for time elapsed between PODATE, URFDATE, RECDATE, PUTDATE
//The following tables must be updated before running code: inbound_5key


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
//SQL to pull date/times by 5key

$sqltruncate = "TRUNCATE custaudit.inbound_4key_grouped";
$querydelete = $conn1->prepare($sqltruncate);
$querydelete->execute();


$sql1 = $conn1->prepare("SELECT 
    5KEYVEND as 4KEYVEND_group,
    5KEYDC as 4KEYDC_group,
    5KEYITEM as 4KEYITEM_group,
    5KEYVNADD as 4KEYVNADD_group,
    COUNT(5KEYITEM) as 4KEYCOUNT_group,
    AVG(5KEYPOtoURF) as 4KEYPOtoURFAVG_group,
    STD(5KEYPOtoURF) as 4KEYPOtoURFSTD_group,
    AVG(5KEYURFtoREC) as 4KEYURFtoRECAVG_group,
    STD(5KEYURFtoREC) as 4KEYURFtoRECSTD_group,
    AVG(5KEYRECtoPUT) as 4KEYRECtoPUTAVG_group,
    STD(5KEYRECtoPUT) as 4KEYRECtoPUTSTD_group
from
    inbound_5key
WHERE
    5KEYTYPE = 'RP'
GROUP BY 5KEYVEND , 5KEYDC , 5KEYITEM , 5KEYVNADD;");
$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);


$columns = implode(", ", array_keys($sql1array[0]));
$values = [];
$maxrange = 9999;
$counter = 0;
$rowcount = count($sql1array);


do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = [];
    $values = [];
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $KEYVEND_group = $sql1array[$counter]['4KEYVEND_group'];
        $KEYDC_group = intval($sql1array[$counter]['4KEYDC_group']);
        $KEYITEM_group = intval($sql1array[$counter]['4KEYITEM_group']);
        $KEYVNADD_group = intval($sql1array[$counter]['4KEYVNADD_group']);
        $KEYCOUNT_group = intval($sql1array[$counter]['4KEYCOUNT_group']);
        $KEYPOtoURFAVG_group = number_format($sql1array[$counter]['4KEYPOtoURFAVG_group'], 4);
        $KEYPOtoURFSTD_group = number_format($sql1array[$counter]['4KEYPOtoURFSTD_group'], 4);
        $KEYURFtoRECAVG_group = number_format($sql1array[$counter]['4KEYURFtoRECAVG_group'], 4);
        $KEYURFtoRECSTD_group = number_format($sql1array[$counter]['4KEYURFtoRECSTD_group'], 4);
        $KEYRECtoPUTAVG_group = number_format($sql1array[$counter]['4KEYRECtoPUTAVG_group'], 4);
        $KEYRECtoPUTSTD_group = number_format($sql1array[$counter]['4KEYRECtoPUTSTD_group'], 4);
        
        $data[] = "('$KEYVEND_group', $KEYDC_group, $KEYITEM_group, $KEYVNADD_group, $KEYCOUNT_group,$KEYPOtoURFAVG_group, $KEYPOtoURFSTD_group, $KEYURFtoRECAVG_group, $KEYURFtoRECSTD_group, $KEYRECtoPUTAVG_group, $KEYRECtoPUTSTD_group)";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.inbound_4key_grouped ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);






$sqltruncate2 = "TRUNCATE custaudit.edi_4key_grouped";
$querydelete2 = $conn1->prepare($sqltruncate2);
$querydelete2->execute();


$sql2 = $conn1->prepare("SELECT 
    5KEYVEND as 4KEYVEND_group,
    5KEYDC as 4KEYDC_group,
    5KEYITEM as 4KEYITEM_group,
    5KEYVNADD as 4KEYVNADD_group,
    COUNT(5KEYITEM) as 4KEYCOUNT_group,
    AVG(5KEYEDItoURF) as 4KEYEDItoURFAVG_group,
    STD(5KEYEDItoURF) as 4KEYEDItoURFSTD_group
from
    inbound_5key
WHERE
    5KEYTYPE = 'RP' and 5KEYEDIDATE > 0
GROUP BY 5KEYVEND , 5KEYDC , 5KEYITEM , 5KEYVNADD;");
$sql2->execute();
$sql2array = $sql2->fetchAll(pdo::FETCH_ASSOC);


$columns = implode(", ", array_keys($sql2array[0]));
$values = [];
$maxrange = 9999;
$counter = 0;
$rowcount = count($sql2array);


do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = [];
    $values = [];
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $KEYVEND_group = $sql2array[$counter]['4KEYVEND_group'];
        $KEYDC_group = intval($sql2array[$counter]['4KEYDC_group']);
        $KEYITEM_group = intval($sql2array[$counter]['4KEYITEM_group']);
        $KEYVNADD_group = intval($sql2array[$counter]['4KEYVNADD_group']);
        $KEYCOUNT_group = intval($sql2array[$counter]['4KEYCOUNT_group']);
        $KEYEDItoURFAVG_group = number_format($sql2array[$counter]['4KEYEDItoURFAVG_group'], 4);
        $KEYEDItoURFSTD_group = number_format($sql2array[$counter]['4KEYEDItoURFSTD_group'], 4);

        
        $data[] = "('$KEYVEND_group', $KEYDC_group, $KEYITEM_group, $KEYVNADD_group, $KEYCOUNT_group,$KEYEDItoURFAVG_group, $KEYEDItoURFSTD_group)";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.edi_4key_grouped ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);
