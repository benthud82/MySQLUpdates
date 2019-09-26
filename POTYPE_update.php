<?php

//code to update PODATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn
//pull in array of key values from PODATE for last 185 days
//this will be used to determine if need to add detail to PODATE_merge to update PODATE table
//$podatekey = $conn1->prepare("SELECT Concat(PORECN,PODCIO,POITEM) from slotting.podate");
//$podatekey->execute();
//$podatekeyarray = $podatekey->fetchAll(pdo::FETCH_COLUMN);



$sql1 = $aseriesconn->prepare("SELECT PONUMB as POTYPEPO,
                            HOWHSE as POTYPEWHS,
                            PQTYP1 as POTYPE1,
                            PQTYP2 as POTYPE2, 
                            (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2)) as POTYPEDATE
                       FROM A.HSIPCORDTA.NPFPHO

                       WHERE PQTYP2 in ('RP', 'FB', 'TR')
                             and CURRENT DATE - 8 Days <= (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2))");
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
        $POTYPEPO = intval($sql1array[$counter]['POTYPEPO']);
        $POTYPEWHS = intval($sql1array[$counter]['POTYPEWHS']);
        $POTYPE1 = $sql1array[$counter]['POTYPE1'];
        $POTYPE2 = $sql1array[$counter]['POTYPE2'];
        $POTYPEDATE = $sql1array[$counter]['POTYPEDATE'];


        $data[] = "($POTYPEPO, $POTYPEWHS, '$POTYPE1', '$POTYPE2', '$POTYPEDATE')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.potype ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);


$sql2 = $aseriesconn->prepare("SELECT PONUMB as POTYPEPO,
                            HHWHSE as POTYPEWHS,
                            PQTYP1 as POTYPE1,
                            PQTYP2 as POTYPE2, 
                            (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2)) as POTYPEDATE
                       FROM A.HSIPCORDTA.NPFPHH

                       WHERE PQTYP2 in ('RP', 'FB', 'TR')
                             and CURRENT DATE - 8 Days <= (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2))");
$sql2->execute();
$sql2array = $sql2->fetchAll(pdo::FETCH_ASSOC);


$values = [];

$maxrange = 4999;
$counter = 0;
$rowcount = count($sql2array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = [];
    $values = [];
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $POTYPEPO = intval($sql2array[$counter]['POTYPEPO']);
        $POTYPEWHS = intval($sql2array[$counter]['POTYPEWHS']);
        $POTYPE1 = $sql2array[$counter]['POTYPE1'];
        $POTYPE2 = $sql2array[$counter]['POTYPE2'];
        $POTYPEDATE = $sql2array[$counter]['POTYPEDATE'];


        $data[] = "($POTYPEPO, $POTYPEWHS, '$POTYPE1', '$POTYPE2', '$POTYPEDATE')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.potype ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);


