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



$sql1 = $aseriesconn->prepare("SELECT DISTINCT 
                            EDITEM as POITEM, 
                            SUPPLR as POVEND, 
                            PQVAN8 as POVNAD, 
                            EHCARR as POCARR, 
                            EDWHSE as POTODC, 
                            EDPONM as POPONM, 
                            EDPOLN as POPOLN, 
                            EDERCN as PORECN, 
                            case when EHORDC > 0 then EHORDC else EHRECN end as PODCIO, 
                            TIMESTAMP( (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2)) || ' ' || (CASE WHEN PQCTIM> 99999 then SUBSTRING(PQCTIM, 1, 2) || ':' || SUBSTRING(PQCTIM, 3, 2) || ':' || SUBSTRING(PQCTIM, 5, 2) else SUBSTRING(PQCTIM, 1, 1) || ':' || SUBSTRING(PQCTIM, 2, 2) || ':' || SUBSTRING(PQCTIM, 4, 2) end)) as POTMST

                       FROM HSIPCORDTA.NPFPHO, 
                            HSIPCORDTA.NPFERD, 
                            HSIPCORDTA.NPFERH, 
                            HSIPCORDTA.NPFERA

                       WHERE HOWHSE = EDWHSE 
                             AND EDPONM = PONUMB 
                             and EHWHSE = EDWHSE 
                             and EDERCN = EHERCN 
                             and EDRECQ > 0 
                             and EDWHSE = EAWHSE 
                             AND EDERCN = EAERCN 
                             AND EAITEM = EDITEM 
                             AND EDLIN# = EALIN# 
                             and CURRENT DATE - 8 Days <= date('20' || SUBSTRING(EHRCDT, 2, 2) || '-' || SUBSTRING(EHRCDT, 4, 2) || '-' || SUBSTRING(EHRCDT, 6, 2))");
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
        $POITEM = intval($sql1array[$counter]['POITEM']);
        $POVEND = $sql1array[$counter]['POVEND'];
        $POVNAD = intval($sql1array[$counter]['POVNAD']);
        $POCARR = str_replace("'", "", $sql1array[$counter]['POCARR']);
        $POTODC = intval($sql1array[$counter]['POTODC']);
        $POPONM = intval($sql1array[$counter]['POPONM']);
        $POPOLN = intval($sql1array[$counter]['POPOLN']);
        $PORECN = intval($sql1array[$counter]['PORECN']);
        $PODCIO = intval($sql1array[$counter]['PODCIO']);
        $POTMST = $sql1array[$counter]['POTMST'];

        $data[] = "($POITEM, '$POVEND', $POVNAD, '$POCARR', $POTODC, $POPONM, $POPOLN, $PORECN, $PODCIO, '$POTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.podate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);



$sql2 = $aseriesconn->prepare("SELECT DISTINCT 
                            EDITEM as POITEM, 
                            SUPPLR as POVEND, 
                            PQVAN8 as POVNAD, 
                            EHCARR as POCARR, 
                            EDWHSE as POTODC, 
                            EDPONM as POPONM, 
                            EDPOLN as POPOLN, 
                            EDERCN as PORECN, 
                            case when EHORDC > 0 then EHORDC else EHRECN end as PODCIO, 
                            TIMESTAMP( (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2)) || ' ' || (CASE WHEN PQCTIM> 99999 then SUBSTRING(PQCTIM, 1, 2) || ':' || SUBSTRING(PQCTIM, 3, 2) || ':' || SUBSTRING(PQCTIM, 5, 2) else SUBSTRING(PQCTIM, 1, 1) || ':' || SUBSTRING(PQCTIM, 2, 2) || ':' || SUBSTRING(PQCTIM, 4, 2) end)) as POTMST

                       FROM HSIPCORDTA.NPFPHH, 
                            HSIPCORDTA.NPFERD, 
                            HSIPCORDTA.NPFERH, 
                            HSIPCORDTA.NPFERA

                       WHERE HHWHSE = EDWHSE 
                             AND EDPONM = PONUMB 
                             and EHWHSE = EDWHSE 
                             and EDERCN = EHERCN 
                             and EDRECQ > 0 
                             and EDWHSE = EAWHSE 
                             AND EDERCN = EAERCN 
                             AND EAITEM = EDITEM 
                             AND EDLIN# = EALIN# 
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
        $POITEM = intval($sql2array[$counter]['POITEM']);
        $POVEND = $sql2array[$counter]['POVEND'];
        $POVNAD = intval($sql2array[$counter]['POVNAD']);
        $POCARR = str_replace("'", "", $sql2array[$counter]['POCARR']);
        $POTODC = intval($sql2array[$counter]['POTODC']);
        $POPONM = intval($sql2array[$counter]['POPONM']);
        $POPOLN = intval($sql2array[$counter]['POPOLN']);
        $PORECN = intval($sql2array[$counter]['PORECN']);
        $PODCIO = intval($sql2array[$counter]['PODCIO']);
        $POTMST = $sql2array[$counter]['POTMST'];

        $data[] = "($POITEM, '$POVEND', $POVNAD, '$POCARR', $POTODC, $POPONM, $POPOLN, $PORECN, $PODCIO, '$POTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.podate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);


