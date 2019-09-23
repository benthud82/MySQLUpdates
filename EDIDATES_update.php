<?php

//code to update edidates table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include_once '../globalincludes/usa_asys.php';  //$aseriesconn




$sql1 = $aseriesconn->prepare("SELECT PONUMBER AS EDIPONUMB,
                                      HSIITEM AS EDIITEM, 
                                      SCACCODE AS EDISCAC, 
                                      CARRIERREF AS EDICARRIER, 
                                      SHIPFRIDCODE AS EDISHIPID, 
                                      EDFI856I.RECEIVEDATE AS EDIRECDATE,
                                      TIMESTAMP( (SUBSTRING(EDFI856I.DATEUPDATED, 1, 4) || '-' || SUBSTRING(EDFI856I.DATEUPDATED, 5, 2) || '-' || SUBSTRING(EDFI856I.DATEUPDATED, 7, 2)) || ' ' || (CASE WHEN EDFI856I.TIMEUPDATED> 99999 then SUBSTRING(EDFI856I.TIMEUPDATED, 1, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 3, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 5, 2) else SUBSTRING(EDFI856I.TIMEUPDATED, 1, 1) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 2, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 4, 2) end)) as EDIUPDATETMST, 
                                      CASE WHEN SHIPFRIDCODE IN ('02','03','06','07','09') THEN SUM(SHIPQTY) ELSE SUM (WCSQTY) END AS EDISHIPQTY
                               
                               FROM A.HSIPCORDTA.EDFI856S EDFI856S, 
                                    A.HSIPCORDTA.EDFI856I EDFI856I
                                    
                               WHERE EDFI856S.MAILBOX = EDFI856I.MAILBOX 
                                    AND EDFI856I.TIMEUPDATED > 10000               
                               and CURRENT DATE - 8 Days <= date(SUBSTRING(EDFI856I.RECEIVEDATE,1,4) || '-' || SUBSTRING(EDFI856I.RECEIVEDATE, 5, 2) || '-' || SUBSTRING(EDFI856I.RECEIVEDATE, 7, 2))
                               GROUP BY PONUMBER, HSIITEM, SCACCODE, CARRIERREF, SHIPFRIDCODE, EDFI856I.RECEIVEDATE,TIMESTAMP( (SUBSTRING(EDFI856I.DATEUPDATED, 1, 4) || '-' || SUBSTRING(EDFI856I.DATEUPDATED, 5, 2) || '-' || SUBSTRING(EDFI856I.DATEUPDATED, 7, 2)) || ' ' || (CASE WHEN EDFI856I.TIMEUPDATED> 99999 then SUBSTRING(EDFI856I.TIMEUPDATED, 1, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 3, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 5, 2) else SUBSTRING(EDFI856I.TIMEUPDATED, 1, 1) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 2, 2) || ':' || SUBSTRING(EDFI856I.TIMEUPDATED, 4, 2) end))");


$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);

$columns = implode(", ", array_keys($sql1array[0]));

$values = array();

$maxrange = 4999;
$counter = 0;
$rowcount = count($sql1array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $EDIPONUMB = intval($sql1array[$counter]['EDIPONUMB']);
        $EDIITEM = intval($sql1array[$counter]['EDIITEM']);
        $EDISCAC = $sql1array[$counter]['EDISCAC'];
        $EDICARRIER = $sql1array[$counter]['EDICARRIER'];
        $EDISHIPID = $sql1array[$counter]['EDISHIPID'];
        $EDIRECDATE = $sql1array[$counter]['EDIRECDATE'];
        $EDIUPDATETMST = $sql1array[$counter]['EDIUPDATETMST'];
        $EDISHIPQTY = intval($sql1array[$counter]['EDISHIPQTY']);

        $data[] = "($EDIPONUMB, $EDIITEM, '$EDISCAC', '$EDICARRIER', '$EDISHIPID', '$EDIRECDATE', '$EDIUPDATETMST', $EDISHIPQTY)";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.edidates ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);
