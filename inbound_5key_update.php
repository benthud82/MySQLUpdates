<?php

//code to update inbound_5key summary table (Supplier code, DC, Item, Supplier address, Carrier)
//The following tables must be updated before running code: PODATE, URFDATE, RECDATE, PUTDATE
//Also calulates the work days between each of the time components
//1 of 5 in inbound hierarchy


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';  //conn1
include_once 'globalfunctions.php';

$sqldelete = "TRUNCATE TABLE custaudit.inbound_5key_merge ";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


//SQL to pull date/times by 5key
$sql1 = $conn1->prepare("SELECT 
    POVEND as 5KEYVEND,
    POTODC as 5KEYDC,
    POITEM as 5KEYITEM,
    POVNAD as 5KEYVNADD,
    POCARR as 5KEYCARR,
    POTYPE2 as 5KEYTYPE,
    POPONM as 5KEYPONUM,
    PORECN as 5KEYRECN,
    PODCIO as 5KEYDCIN,
    POTMST as 5KEYPOTMST,
    URFTMST as 5KEYURFTMST,
    RECTMST as 5KEYRECTMST,
    PUTTMST as 5KEYPUTTMST,
EDIRECDATE as 5KEYEDIDATE
FROM
custaudit.putdate 
JOIN custaudit.podate on POITEM = PUTITEM and PORECN = PUTRECN and POPONM = PUTPONM and POPOLN = PUTPOLN  and PODCIO = PUTDCIN
JOIN custaudit.urfdate on URFPONM = POPONM and URFDCIN = PUTDCIN and URFRECN = PUTRECN
JOIN custaudit.recdate on PUTPONM = RECPONM and PUTRECN = RECRECN and PUTITEM = RECITEM and PUTPOLN = RECPOLN and PUTDCIN = RECDCIN

        LEFT OUTER JOIN
    custaudit.edidates ON edidates.EDIPONUMB = putdate.PUTPONM
        and edidates.EDIITEM = putdate.PUTITEM,
    custaudit.potype
 WHERE POPONM = POTYPEPO
         and PUTTMST BETWEEN NOW() - INTERVAL 5 DAY AND NOW()
-- and PUTTMST >= '2017-01-01'
ORDER by PUTTMST;
");
$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);

//pull in holiday dates to array
//$sqlholiday = $conn1->prepare("SELECT * from holidays");
//$sqlholiday->execute();
//$sqlholidayarray = $sqlholiday->fetchAll(pdo::FETCH_COLUMN); //fetch column returns the date in a single dimensional array!

$sqlholidayarray = array();
$columns = implode(", ", array_keys($sql1array[0])) . ', 5KEYPOtoURF, 5KEYURFtoREC, 5KEYRECtoPUT, 5KEYEDItoURF';


$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($sql1array);

do {
if ($maxrange > $rowcount) {  //prevent undefined offset
$maxrange = $rowcount - 1;
}

$data = array();
$values = array();
while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
$EDItoURF =0;
$POVEND = preg_replace('/[^ \w]+/', '', $sql1array[$counter]['5KEYVEND']);
$POTODC = intval($sql1array[$counter]['5KEYDC']);
$POITEM = intval($sql1array[$counter]['5KEYITEM']);
$POVNAD =  preg_replace('/[^ \w]+/', '',  $sql1array[$counter]['5KEYVNADD']);
$POCARR = preg_replace('/[^ \w]+/', '', $sql1array[$counter]['5KEYCARR']);
$POTYPE = preg_replace('/[^ \w]+/', '',  $sql1array[$counter]['5KEYTYPE']);
$POPONM = intval($sql1array[$counter]['5KEYPONUM']);
$PORECN = intval($sql1array[$counter]['5KEYRECN']);
$PODCIO = intval($sql1array[$counter]['5KEYDCIN']);
$POTMST = $sql1array[$counter]['5KEYPOTMST'];
$URFTMST = $sql1array[$counter]['5KEYURFTMST'];
$RECTMST = $sql1array[$counter]['5KEYRECTMST'];
$PUTTMST = $sql1array[$counter]['5KEYPUTTMST'];
$EDIDATE = $sql1array[$counter]['5KEYEDIDATE'];



if (isset($EDIDATE) || !is_null($EDIDATE) ) {
$EDItoURF = number_format(getWorkingDays($EDIDATE, $URFTMST, $sqlholidayarray), 4);
$EDIDATE = "'" . $EDIDATE . "'";
} ELSE{
    $EDIDATE = 'NULL';
}

$POtoURF = number_format(getWorkingDays($POTMST, $URFTMST, $sqlholidayarray), 4);
$URFtoREC = number_format(getWorkingDays($URFTMST, $RECTMST, $sqlholidayarray), 4);
$RECtoPUT = number_format(getWorkingDays($RECTMST, $PUTTMST, $sqlholidayarray), 4);


$data[] = "('$POVEND', $POTODC, $POITEM, $POVNAD, '$POCARR', '$POTYPE', $POPONM, $PORECN, $PODCIO, '$POTMST', '$URFTMST', '$RECTMST', '$PUTTMST', $EDIDATE, '$POtoURF', '$URFtoREC', '$RECtoPUT', '$EDItoURF')";
$counter += 1;
}


$values = implode(',', $data);

if (empty($values)) {
break;
}

$sql = "INSERT INTO custaudit.inbound_5key_merge ($columns) VALUES $values ";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);


//once merge table has been populated, insert on duplicate key update here.
$sqlmerge2 = "INSERT INTO custaudit.inbound_5key (5KEYVEND, 5KEYDC, 5KEYITEM, 5KEYVNADD, 5KEYCARR, 5KEYTYPE, 5KEYPONUM, 5KEYRECN, 5KEYDCIN, 5KEYPOTMST, 5KEYURFTMST, 5KEYRECTMST, 5KEYPUTTMST, 5KEYEDIDATE, 5KEYPOtoURF, 5KEYURFtoREC, 5KEYRECtoPUT, 5KEYEDItoURF)
SELECT A.5KEYVEND, A.5KEYDC, A.5KEYITEM, A.5KEYVNADD, A.5KEYCARR, A.5KEYTYPE, A.5KEYPONUM, A.5KEYRECN, A.5KEYDCIN, A.5KEYPOTMST, A.5KEYURFTMST, A.5KEYRECTMST, A.5KEYPUTTMST, A.5KEYEDIDATE, A.5KEYPOtoURF, A.5KEYURFtoREC, A.5KEYRECtoPUT, A.5KEYEDItoURF FROM custaudit.inbound_5key_merge A
ON DUPLICATE KEY UPDATE  5KEYPOTMST = A.5KEYPOTMST, 5KEYURFTMST = A.5KEYURFTMST, 5KEYRECTMST = A.5KEYRECTMST, 5KEYPUTTMST = A.5KEYPUTTMST, 5KEYEDIDATE = A.5KEYEDIDATE, 5KEYPOtoURF = A.5KEYPOtoURF, 5KEYURFtoREC = A.5KEYURFtoREC, 5KEYRECtoPUT = A.5KEYRECtoPUT, 5KEYEDItoURF = A.5KEYEDItoURF;  ";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();