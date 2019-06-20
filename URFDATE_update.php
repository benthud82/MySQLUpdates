<?php

//code to update PODATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn


$sql1 = $aseriesconn->prepare("SELECT SUPPLR as URFVEND, 
                                      PQVAN8 as URFVNAD, 
                                      EHCARR as URFCARR, 
                                      EHWHSE as URFTODC, 
                                      PONUMB as URFPONM, 
                                      EHERCN as URFRECN, 
                                      EHRECN as URFDCIN , 
                                      EHORDC as URFDCIO, 
                                      TIMESTAMP( ('20' || SUBSTRING(EHRCDT, 2, 2) || '-' || SUBSTRING(EHRCDT, 4, 2) || '-' || SUBSTRING(EHRCDT, 6, 2)) || ' ' ||  (CASE WHEN EHRCTM > 999 then SUBSTRING(EHRCTM, 1, 2) || ':' || SUBSTRING(EHRCTM, 3, 2) || ':' || '00' else SUBSTRING(EHRCTM, 1, 1) || ':' || SUBSTRING(EHRCTM, 2, 2)  || ':' || '00' end)) as URFTMST
                                      
                               FROM HSIPCORDTA.NPFPHO, 
                                    HSIPCORDTA.NPFERH
                               
                               WHERE EHWHSE = HOWHSE 
                                     and EHPRPO = PONUMB 
                                     and CURRENT DATE - 45 Days <= date('20' || SUBSTRING(EHRCDT, 2, 2) || '-' || SUBSTRING(EHRCDT, 4, 2) || '-' || SUBSTRING(EHRCDT, 6, 2))");
$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);
$columns = implode(", ", array_keys($sql1array[0]));

$values = [];

$maxrange = 4999;
$counter = 0;
$rowcount = count($sql1array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = [];
    $values = [];
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $URFVEND = $sql1array[$counter]['URFVEND'];
        $URFVNAD = intval($sql1array[$counter]['URFVNAD']);
        $URFCARR = str_replace("'", "", $sql1array[$counter]['URFCARR']);
        $URFTODC = intval($sql1array[$counter]['URFTODC']);
        $URFPONM = intval($sql1array[$counter]['URFPONM']);
        $URFRECN = intval($sql1array[$counter]['URFRECN']);
        $URFDCIN = intval($sql1array[$counter]['URFDCIN']);
        $URFDCIO = intval($sql1array[$counter]['URFDCIO']);
        $URFTMST = $sql1array[$counter]['URFTMST'];

        $data[] = "('$URFVEND', $URFVNAD, '$URFCARR', $URFTODC, $URFPONM, $URFRECN, $URFDCIN, $URFDCIO, '$URFTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.urfdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);


$sql2 = $aseriesconn->prepare("SELECT SUPPLR as URFVEND, 
                                      PQVAN8 as URFVNAD, 
                                      EHCARR as URFCARR, 
                                      EHWHSE as URFTODC, 
                                      PONUMB as URFPONM, 
                                      EHERCN as URFRECN, 
                                      EHRECN as URFDCIN , 
                                      EHORDC as URFDCIO, 
                                      TIMESTAMP( ('20' || SUBSTRING(EHRCDT, 2, 2) || '-' || SUBSTRING(EHRCDT, 4, 2) || '-' || SUBSTRING(EHRCDT, 6, 2)) || ' ' ||  (CASE WHEN EHRCTM > 999 then SUBSTRING(EHRCTM, 1, 2) || ':' || SUBSTRING(EHRCTM, 3, 2) || ':' || '00' else SUBSTRING(EHRCTM, 1, 1) || ':' || SUBSTRING(EHRCTM, 2, 2)  || ':' || '00' end)) as URFTMST
                                      
                               FROM HSIPCORDTA.NPFPHH, 
                                    HSIPCORDTA.NPFERH
                               
                               WHERE EHWHSE = HHWHSE 
                                     and EHPRPO = PONUMB 
                                     and CURRENT DATE - 8 Days <= date('20' || SUBSTRING(EHRCDT, 2, 2) || '-' || SUBSTRING(EHRCDT, 4, 2) || '-' || SUBSTRING(EHRCDT, 6, 2))");
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
        $URFVEND = $sql2array[$counter]['URFVEND'];
        $URFVNAD = intval($sql2array[$counter]['URFVNAD']);
        $URFCARR = str_replace("'", "", $sql2array[$counter]['URFCARR']);
        $URFTODC = intval($sql2array[$counter]['URFTODC']);
        $URFPONM = intval($sql2array[$counter]['URFPONM']);
        $URFRECN = intval($sql2array[$counter]['URFRECN']);
        $URFDCIN = intval($sql2array[$counter]['URFDCIN']);
        $URFDCIO = intval($sql2array[$counter]['URFDCIO']);
        $URFTMST = $sql2array[$counter]['URFTMST'];

        $data[] = "('$URFVEND', $URFVNAD, '$URFCARR', $URFTODC, $URFPONM, $URFRECN, $URFDCIN, $URFDCIO, '$URFTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.urfdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);



 



