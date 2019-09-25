<?php

//Current Open PO Lines
//Truncates table openpo and combines adds to table openpo insert ignore update


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //conn1


$sqltruncate = "TRUNCATE custaudit.openpo";
$querydelete = $conn1->prepare($sqltruncate);
$querydelete->execute();

//SQL to pull open pos by item
$sql1 = $aseriesconn->prepare("SELECT DISTINCT NPFPHO.SUPPLR as OPENSUPP, 
                                HOWHSE as OPENWHSE, 
                                ITMCDE as OPENITEM, 
                                PURQTY as OPENPURQTY,
                                LINENO as OPENPOLINE,
                                NPFPHO.PQVAN8 as OPENVENDADD,
                                NPFPHO.PONUMB as OPENPONUM,
                                TIMESTAMP( (SUBSTRING(PQCDAT, 1, 4) || '-' || SUBSTRING(PQCDAT, 5, 2) || '-' || SUBSTRING(PQCDAT, 7, 2)) || ' ' || (CASE WHEN PQCTIM> 99999 then SUBSTRING(PQCTIM, 1, 2) || ':' || SUBSTRING(PQCTIM, 3, 2) || ':' || SUBSTRING(PQCTIM, 5, 2) else SUBSTRING(PQCTIM, 1, 1) || ':' || SUBSTRING(PQCTIM, 2, 2) || ':' || SUBSTRING(PQCTIM, 4, 2) end)) as PODATE,
                                DATE('20'||DUEYR||'-'||DUEMO||'-'||DUEDY) as DUEDATE
                         FROM A.HSIPCORDTA.NPFPHO NPFPHO, 
                              A.HSIPCORDTA.NPFPDO NPFPDO
                         WHERE HOWHSE = DOWHSE 
                               and NPFPHO.PONUMB = NPFPDO.PONUMB
                               and PODSTS <> 'C'
                               and QTYREC < PURQTY     
                               and DUEYR > 0 and DUEMO > 0 and DUEDY > 0
                               and PQCDAT > 20150101");
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
        $OPENSUPP = $sql1array[$counter]['OPENSUPP'];
        $OPENWHSE = intval($sql1array[$counter]['OPENWHSE']);
        $OPENITEM = intval($sql1array[$counter]['OPENITEM']);
        $OPENPURQTY = intval($sql1array[$counter]['OPENPURQTY']);
        $OPENPOLINE = intval($sql1array[$counter]['OPENPOLINE']);
        $OPENVENDADD = intval($sql1array[$counter]['OPENVENDADD']);
        $OPENPONUM = intval($sql1array[$counter]['OPENPONUM']);
        $PODATE = $sql1array[$counter]['PODATE'];
        $DUEDATE = date('Y-m-d', strtotime($sql1array[$counter]['DUEDATE']));
        $data[] = "('$OPENSUPP', $OPENWHSE, $OPENITEM, $OPENPURQTY, $OPENPOLINE, $OPENVENDADD, $OPENPONUM, '$PODATE','$DUEDATE')";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.openpo ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);



