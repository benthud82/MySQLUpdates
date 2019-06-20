<?php

//code to update ITEMFILLRATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn


$sql1 = $aseriesconn->prepare("SELECT EDWHSE AS FRWHSE,
                                      EDITEM AS FRITEM,
                                      EDPONM AS FRPONM,
                                      EDPOLN AS FRPOLN,
                                      EDRECQ AS FRRECQ,
                                      EDPURQ AS FROPENQ,
                                      EDDESC AS FRDESC,
                                      EDASNQ AS FRASNQ,
                                      min(TIMESTAMP( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)) || ' ' ||  (CASE WHEN EATRNT  > 99999 then SUBSTRING(EATRNT, 1, 2) || ':' || SUBSTRING(EATRNT, 3, 2) || ':' || SUBSTRING(EATRNT, 5, 2) else SUBSTRING(EATRNT, 1, 1) || ':' || SUBSTRING(EATRNT, 2, 2) || ':' || SUBSTRING(EATRNT, 4, 2) end))) as FRTMST
                               FROM HSIPCORDTA.NPFERD, 
                                    HSIPCORDTA.NPFERA 

                               WHERE EDRECQ > 0 
                                     and EDWHSE = EAWHSE 
                                     AND EDERCN = EAERCN 
                                     AND EAITEM = EDITEM 
                                     AND EDLIN# = EALIN#

                               GROUP BY EDWHSE, EDITEM, EDPONM, EDPOLN, EDRECQ, EDPURQ, EDDESC, EDASNQ
                               
                               HAVING min(( ('20' || SUBSTRING(EATRND, 2, 2) || '-' || SUBSTRING(EATRND, 4, 2) || '-' || SUBSTRING(EATRND, 6, 2)))) >= CURRENT DATE - 8 Days");


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
        $FRWHSE = intval($sql1array[$counter]['FRWHSE']);
        $FRITEM = intval($sql1array[$counter]['FRITEM']);
        $FRPONM = intval($sql1array[$counter]['FRPONM']);
        $FRPOLN = intval($sql1array[$counter]['FRPOLN']);
        $FRRECQ = intval($sql1array[$counter]['FRRECQ']);
        $FROPENQ = intval($sql1array[$counter]['FROPENQ']);
        $FRDESC = str_replace("'", "", $sql1array[$counter]['FRDESC']);
        $FRASNQ = intval($sql1array[$counter]['FRASNQ']);
        $FRTMST = $sql1array[$counter]['FRTMST'];


        $data[] = "($FRWHSE, $FRITEM, $FRPONM, $FRPOLN, $FRRECQ, $FROPENQ, '$FRDESC', $FRASNQ, '$FRTMST')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.itemfillrate ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);

