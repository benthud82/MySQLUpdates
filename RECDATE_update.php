<?php

//code to update RECDATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn


$sql1 = $aseriesconn->prepare("SELECT EDITEM as RECITEM, 
                                      SUPPLR as RECVEND, 
                                      PQVAN8 as RECVNAD, 
                                      EHCARR as RECCARR, 
                                      EDWHSE as RECTODC, 
                                      EDPONM as RECPONM, 
                                      EDPOLN as RECPOLN, 
                                      EDERCN as RECRECN, 
                                      case when EHORDC > 0 then EHORDC else EHRECN end as RECDCIN,
                                      min(TIMESTAMP( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)) || ' ' ||  (CASE WHEN EATRNT  > 99999 then SUBSTRING(EATRNT, 1, 2) || ':' || SUBSTRING(EATRNT, 3, 2) || ':' || SUBSTRING(EATRNT, 5, 2) else SUBSTRING(EATRNT, 1, 1) || ':' || SUBSTRING(EATRNT, 2, 2) || ':' || SUBSTRING(EATRNT, 4, 2) end))) as RECTMST
                               
                               FROM 
                                    HSIPCORDTA.NPFPHO,
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
                                     and EASEQ3 = 1
                                     
                               GROUP BY EDITEM, SUPPLR, PQVAN8, EHCARR, EDWHSE, EDPONM, EDPOLN, EDERCN, case when EHORDC > 0 then EHORDC else EHRECN end

                               HAVING min(( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)))) >= CURRENT DATE - 45 Days");
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
        $RECITEM = intval($sql1array[$counter]['RECITEM']);
        $RECVEND = $sql1array[$counter]['RECVEND'];
        $RECVNAD = intval($sql1array[$counter]['RECVNAD']);
        $RECCARR = str_replace("'", "", $sql1array[$counter]['RECCARR']);
        $RECTODC = intval($sql1array[$counter]['RECTODC']);
        $RECPONM = intval($sql1array[$counter]['RECPONM']);
        $RECPOLN = intval($sql1array[$counter]['RECPOLN']);
        $RECRECN = intval($sql1array[$counter]['RECRECN']);
        $RECDCIN = intval($sql1array[$counter]['RECDCIN']);
        $RECTMST = $sql1array[$counter]['RECTMST'];

        $data[] = "($RECITEM, '$RECVEND', $RECVNAD, '$RECCARR', $RECTODC, $RECPONM, $RECPOLN, $RECRECN, $RECDCIN, '$RECTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.recdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);



$sql2 = $aseriesconn->prepare("SELECT EDITEM as RECITEM, 
                                      SUPPLR as RECVEND, 
                                      PQVAN8 as RECVNAD, 
                                      EHCARR as RECCARR, 
                                      EDWHSE as RECTODC, 
                                      EDPONM as RECPONM, 
                                      EDPOLN as RECPOLN, 
                                      EDERCN as RECRECN, 
                                      case when EHORDC > 0 then EHORDC else EHRECN end as RECDCIN,
                                      min(TIMESTAMP( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)) || ' ' ||  (CASE WHEN EATRNT  > 99999 then SUBSTRING(EATRNT, 1, 2) || ':' || SUBSTRING(EATRNT, 3, 2) || ':' || SUBSTRING(EATRNT, 5, 2) else SUBSTRING(EATRNT, 1, 1) || ':' || SUBSTRING(EATRNT, 2, 2) || ':' || SUBSTRING(EATRNT, 4, 2) end))) as RECTMST
                               
                               FROM 
                                    HSIPCORDTA.NPFPHH,
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
                                     and EASEQ3 = 1
                                     
                               GROUP BY EDITEM, SUPPLR, PQVAN8, EHCARR, EDWHSE, EDPONM, EDPOLN, EDERCN, case when EHORDC > 0 then EHORDC else EHRECN end

                               HAVING min(( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)))) >= CURRENT DATE - 8 Days");
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
        $RECITEM = intval($sql2array[$counter]['RECITEM']);
        $RECVEND = $sql2array[$counter]['RECVEND'];
        $RECVNAD = intval($sql2array[$counter]['RECVNAD']);
        $RECCARR = str_replace("'", "", $sql2array[$counter]['RECCARR']);
        $RECTODC = intval($sql2array[$counter]['RECTODC']);
        $RECPONM = intval($sql2array[$counter]['RECPONM']);
        $RECPOLN = intval($sql2array[$counter]['RECPOLN']);
        $RECRECN = intval($sql2array[$counter]['RECRECN']);
        $RECDCIN = intval($sql2array[$counter]['RECDCIN']);
        $RECTMST = $sql2array[$counter]['RECTMST'];

        $data[] = "($RECITEM, '$RECVEND', $RECVNAD, '$RECCARR', $RECTODC, $RECPONM, $RECPOLN, $RECRECN, $RECDCIN, '$RECTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.recdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);


 



