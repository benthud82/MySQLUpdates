<?php

//code to update PUTDATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn


$sql1 = $aseriesconn->prepare("SELECT EDITEM as PUTITEM, 
                                      SUPPLR as PUTVEND, 
                                      PQVAN8 as PUTVNAD, 
                                      EHCARR as PUTCARR, 
                                      EDWHSE as PUTTODC, 
                                      EDPONM as PUTPONM,
                                      EDPOLN as PUTPOLN,
                                      EDERCN as PUTRECN,  
                                      case when EHORDC > 0 then EHORDC else EHRECN end as PUTDCIN,
                                      max(TIMESTAMP( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)) || ' ' ||  (CASE WHEN EATRNT  > 99999 then SUBSTRING(EATRNT, 1, 2) || ':' || SUBSTRING(EATRNT, 3, 2) || ':' || SUBSTRING(EATRNT, 5, 2) else SUBSTRING(EATRNT, 1, 1) || ':' || SUBSTRING(EATRNT, 2, 2) || ':' || SUBSTRING(EATRNT, 4, 2) end))) as PUTTMST 
                                      

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

                               GROUP BY EDITEM, SUPPLR, PQVAN8, EDWHSE, EDPONM, EDPOLN,  EDERCN, EHCARR, case when EHORDC > 0 then EHORDC else EHRECN end

                               HAVING max(( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)))) >= CURRENT DATE - 45 Days");

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
        $PUTITEM = intval($sql1array[$counter]['PUTITEM']);
        $PUTVEND = $sql1array[$counter]['PUTVEND'];
        $PUTVNAD = intval($sql1array[$counter]['PUTVNAD']);
        $PUTCARR = str_replace("'", "", $sql1array[$counter]['PUTCARR']);
        $PUTTODC = intval($sql1array[$counter]['PUTTODC']);
        $PUTPONM = intval($sql1array[$counter]['PUTPONM']);
        $PUTDCIN = intval($sql1array[$counter]['PUTDCIN']);
        $PUTPOLN = intval($sql1array[$counter]['PUTPOLN']);
        $PUTRECN = intval($sql1array[$counter]['PUTRECN']);
        $PUTTMST = $sql1array[$counter]['PUTTMST'];

        $data[] = "($PUTITEM, '$PUTVEND', $PUTVNAD, '$PUTCARR', $PUTTODC, $PUTPONM, $PUTPOLN, $PUTRECN, $PUTDCIN, '$PUTTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.putdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);



$sql2 = $aseriesconn->prepare("SELECT EDITEM as PUTITEM, 
                                      SUPPLR as PUTVEND, 
                                      PQVAN8 as PUTVNAD, 
                                      EHCARR as PUTCARR, 
                                      EDWHSE as PUTTODC, 
                                      EDPONM as PUTPONM,
                                      EDPOLN as PUTPOLN,
                                      EDERCN as PUTRECN, 
                                      case when EHORDC > 0 then EHORDC else EHRECN end as PUTDCIN,
                                      max(TIMESTAMP( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)) || ' ' ||  (CASE WHEN EATRNT  > 99999 then SUBSTRING(EATRNT, 1, 2) || ':' || SUBSTRING(EATRNT, 3, 2) || ':' || SUBSTRING(EATRNT, 5, 2) else SUBSTRING(EATRNT, 1, 1) || ':' || SUBSTRING(EATRNT, 2, 2) || ':' || SUBSTRING(EATRNT, 4, 2) end))) as PUTTMST 
                                      
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

                               GROUP BY EDITEM, SUPPLR, PQVAN8, EDWHSE, EDPONM, EDPOLN,  EDERCN, EHCARR, case when EHORDC > 0 then EHORDC else EHRECN end

                               HAVING max(( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)))) >= CURRENT DATE - 45 Days");
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
        $PUTITEM = intval($sql2array[$counter]['PUTITEM']);
        $PUTVEND = $sql2array[$counter]['PUTVEND'];
        $PUTVNAD = intval($sql2array[$counter]['PUTVNAD']);
        $PUTCARR = str_replace("'", "", $sql2array[$counter]['PUTCARR']);
        $PUTTODC = intval($sql2array[$counter]['PUTTODC']);
        $PUTPONM = intval($sql2array[$counter]['PUTPONM']);
        $PUTDCIN = intval($sql2array[$counter]['PUTDCIN']);
        $PUTPOLN = intval($sql2array[$counter]['PUTPOLN']);
        $PUTRECN = intval($sql2array[$counter]['PUTRECN']);
        $PUTTMST = $sql2array[$counter]['PUTTMST'];

        $data[] = "($PUTITEM, '$PUTVEND', $PUTVNAD, '$PUTCARR', $PUTTODC, $PUTPONM, $PUTPOLN, $PUTRECN, $PUTDCIN, '$PUTTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.putdate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);


 



