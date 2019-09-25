<?php

//code to update PUTDATE table

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn
include '../globalfunctions/custdbfunctions.php';

$today = date('Y-m-d', strtotime('-5 days'));
$startdate = _gregdateto1yyddd($today);

$sql1 = $aseriesconn->prepare("SELECT 
                                    BILL_TO,
                                    CUSTOMER,
                                    ITEM,
                                    ORD_NUM,
                                    ORD_TYP,
                                    SHIP_DC,
                                    INV_PWHS,
                                    CUSTOMER,
                                    OR_DATE,
                                    ORD_QTY,
                                    SHP_QTY,
                                    BCK_QTY,
                                    BUYER,
                                    CUS_DIVC,
                                    IP_FIL_TYP,
                                    AVAIL_FLG,
                                    ITM_SUPP,
                                    IM_BRN_TYP
                                FROM
                                    A.HSIPCORDTA.IM0011
                                WHERE
                                     OR_DATE >= $startdate and IP_FIL_TYP <> ' '");

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
        $BILL_TO = intval($sql1array[$counter]['BILL_TO']);
        $CUSTOMER = intval($sql1array[$counter]['CUSTOMER']);
        $ITEM = intval($sql1array[$counter]['ITEM']);
        $ORD_NUM = intval($sql1array[$counter]['ORD_NUM']);
        $ORD_TYP = ($sql1array[$counter]['ORD_TYP']);
        $SHIP_DC = intval($sql1array[$counter]['SHIP_DC']);
        $INV_PWHS = intval($sql1array[$counter]['INV_PWHS']);
        $OR_DATE = intval($sql1array[$counter]['OR_DATE']);
        $ORD_QTY = intval($sql1array[$counter]['ORD_QTY']);
        $SHP_QTY = intval($sql1array[$counter]['SHP_QTY']);
        $BCK_QTY = intval($sql1array[$counter]['BCK_QTY']);
        $BUYER = intval($sql1array[$counter]['BUYER']);
        $CUS_DIVC = ($sql1array[$counter]['CUS_DIVC']);
        $IP_FIL_TYP = ($sql1array[$counter]['IP_FIL_TYP']);
        $AVAIL_FLG = ($sql1array[$counter]['AVAIL_FLG']);
        $ITM_SUPP = ($sql1array[$counter]['ITM_SUPP']);
        $IM_BRN_TYP = ($sql1array[$counter]['IM_BRN_TYP']);

        $data[] = "($BILL_TO, $CUSTOMER, $ITEM, $ORD_NUM, '$ORD_TYP', $SHIP_DC, $INV_PWHS, $OR_DATE, $ORD_QTY, $SHP_QTY, $BCK_QTY, $BUYER, '$CUS_DIVC', '$IP_FIL_TYP', '$AVAIL_FLG', '$ITM_SUPP', '$IM_BRN_TYP')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.im0011_frissues ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=5000;
} while ($counter <= $rowcount);

