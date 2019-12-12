<?php

//code to update PODATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn
include '../globalfunctions/custdbfunctions.php';


$sql1 = $aseriesconn->prepare("SELECT 
                                PBWHTO,
                                PBPTJD,
                                PBPTHM,
                                PBRLJD,
                                PBRLHM,
                                PBBXSZ,
                                PBBOXL,
                                PBBOXV,
                                PBBOXW,
                                PBWCS#,
                                PBLP9D,
                                PBTRC#,
                                PBSHAN,
                                PBAN8,
                                PBBOX#,
                                PBCART,
                                PBBIN#,
                                PBPRIO,
                                PBSHPZ,
                                PBSHPC
                               FROM HSIPCORDTA.NOTWPS
                                WHERE PBRLJD between 19168 and 19169
                                    and PBWHTO in (2,3,6,7,9)
                                    and PBTRC# like '1Z%'");
$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);

$columns = 'ml_base_whse,
            ml_base_printjdate,
            ml_base_printhourmin,
            ml_base_reljdate,
            ml_base_relhourmin,
            ml_base_boxsize,
            ml_base_boxlines,
            ml_base_boxvol,
            ml_base_boxweight,
            ml_base_wcsnum,
            ml_base_lpnum,
            ml_base_tracer,
            ml_base_shipto,
            ml_base_billto,
            ml_base_boxnum,
            ml_base_batch,
            ml_base_bin,
            ml_base_priority,
            ml_base_shipzone,
            ml_base_shipclass';

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
        $PBWHTO = intval($sql1array[$counter]['PBWHTO']);
        $PBPTJD = intval($sql1array[$counter]['PBPTJD']);
        $printdate = date('Y-m-d', strtotime(_yydddtogregdate($PBPTJD)));
        $PBPTHM = intval($sql1array[$counter]['PBPTHM']);
        $PBRLJD = intval($sql1array[$counter]['PBRLJD']);
        $reldate = date('Y-m-d', strtotime(_yydddtogregdate($PBRLJD)));
        $PBRLHM = intval($sql1array[$counter]['PBRLHM']);
        $PBBXSZ = ($sql1array[$counter]['PBBXSZ']);
        $PBBOXL = intval($sql1array[$counter]['PBBOXL']);
        $PBBOXV = intval($sql1array[$counter]['PBBOXV']);
        $PBBOXW = ($sql1array[$counter]['PBBOXW']);
        $PBWCS = intval($sql1array[$counter]['PBWCS#']);
        $PBLP9D = intval($sql1array[$counter]['PBLP9D']);
        $PBTRC = ($sql1array[$counter]['PBTRC#']);
        $PBSHAN = intval($sql1array[$counter]['PBSHAN']);
        $PBAN8 = intval($sql1array[$counter]['PBAN8']);
        $PBBOX = intval($sql1array[$counter]['PBBOX#']);
        $PBCART = intval($sql1array[$counter]['PBCART']);
        $PBBIN = intval($sql1array[$counter]['PBBIN#']);
        $PBPRIO = intval($sql1array[$counter]['PBPRIO']);
        $PBSHPZ = substr($sql1array[$counter]['PBSHPZ'],0,2);
        $PBSHPC = ($sql1array[$counter]['PBSHPC']);


        $data[] = "($PBWHTO, '$printdate', $PBPTHM, '$reldate', $PBRLHM, '$PBBXSZ', $PBBOXL, $PBBOXV, '$PBBOXW', $PBWCS, $PBLP9D, '$PBTRC', "
                . "$PBSHAN, $PBAN8, $PBBOX, $PBCART, $PBBIN, $PBPRIO, '$PBSHPZ','$PBSHPC')";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.ml_custcomp_basedata ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 5000;
} while ($counter <= $rowcount);




