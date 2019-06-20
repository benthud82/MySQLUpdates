<?php

//if (isset($var_whse)) {
//    $whsefilter = ' and LOWHSE = ' . $var_whse;
//} else {
//    $whsefilter = ' and LOWHSE in (2,3,6,7,9)';
//}

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';


if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.mysql_nptsld WHERE Whse = $var_whse";
    $whsefilter = 'WAREHOUSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.mysql_nptsld";
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'WAREHOUSE, ITEM_NUMBER, PACKAGE_UNIT, PACKAGE_TYPE, DSL_TYPE, CUR_LOCATION, DAYS_FRM_SLE, DAYS_FRM_BKO, AVGD_BTW_SLE, AVG_INV_OH, NBR_SHIP_OCC, PICK_QTY_MN, PICK_QTY_SM, PICK_QTY_SD, PICK_QTY_FC, SLOT_PICKS, SHIP_QTY_MN, SHIP_QTY_SM, SHIP_QTY_SD, SHIP_QTY_FC, SLOT_QTY';


$cpcresult = $aseriesconn->prepare("SELECT WAREHOUSE,
                                            ITEM_NUMBER, 
                                            PACKAGE_UNIT, 
                                            PACKAGE_TYPE, 
                                            DSL_TYPE, 
                                            CUR_LOCATION, 
                                            DAYS_FRM_SLE, 
                                            DAYS_FRM_BKO, 
                                            AVGD_BTW_SLE, 
                                            AVG_INV_OH, 
                                            NBR_SHIP_OCC, 
                                            PICK_QTY_MN, 
                                            PICK_QTY_SM, 
                                            PICK_QTY_SD, 
                                            PICK_QTY_FC, 
                                            SLOT_PICKS, 
                                            SHIP_QTY_MN, 
                                            SHIP_QTY_SM, 
                                            SHIP_QTY_SD, 
                                            SHIP_QTY_FC, 
                                            SLOT_QTY
                                    FROM HSIPCORDTA.NPTSLD
                                    JOIN HSIPCORDTA.NPFWRS on WRSWHS = WAREHOUSE and WRSITM = ITEM_NUMBER
                                    WHERE CUR_LOCATION not like 'Q%' and CUR_LOCATION not like 'N%' and WRSSTK = 'Y'");
$cpcresult->execute();
$NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 999;
$counter = 0;
$rowcount = count($NPFCPC_ALL_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WAREHOUSE = intval($NPFCPC_ALL_array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($NPFCPC_ALL_array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($NPFCPC_ALL_array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $NPFCPC_ALL_array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $NPFCPC_ALL_array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $NPFCPC_ALL_array[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($NPFCPC_ALL_array[$counter]['DAYS_FRM_SLE']);
        $DAYS_FRM_BKO = intval($NPFCPC_ALL_array[$counter]['DAYS_FRM_BKO']);
        $AVGD_BTW_SLE = intval($NPFCPC_ALL_array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($NPFCPC_ALL_array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($NPFCPC_ALL_array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($NPFCPC_ALL_array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SM = number_format($NPFCPC_ALL_array[$counter]['PICK_QTY_SM'], 2, '.', '');
        $PICK_QTY_SD = number_format($NPFCPC_ALL_array[$counter]['PICK_QTY_SD'], 2, '.', '');
        $PICK_QTY_FC = intval($NPFCPC_ALL_array[$counter]['PICK_QTY_FC']);
        $SLOT_PICKS = intval($NPFCPC_ALL_array[$counter]['SLOT_PICKS']);
        $SHIP_QTY_MN = intval($NPFCPC_ALL_array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SM = number_format($NPFCPC_ALL_array[$counter]['SHIP_QTY_SM'], 2, '.', '');
        $SHIP_QTY_SD = number_format($NPFCPC_ALL_array[$counter]['SHIP_QTY_SD'], 2, '.', '');
        $SHIP_QTY_FC = intval($NPFCPC_ALL_array[$counter]['SHIP_QTY_FC']);
        $SLOT_QTY = number_format($NPFCPC_ALL_array[$counter]['SLOT_QTY'], 2, '.', '');




        $data[] = "($WAREHOUSE, $ITEM_NUMBER, $PACKAGE_UNIT, '$PACKAGE_TYPE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, $DAYS_FRM_BKO, $AVGD_BTW_SLE, $AVG_INV_OH, $NBR_SHIP_OCC, $PICK_QTY_MN, $PICK_QTY_SM, $PICK_QTY_SD, $PICK_QTY_FC, $SLOT_PICKS, $SHIP_QTY_MN, $SHIP_QTY_SM, $SHIP_QTY_SD, $SHIP_QTY_FC, $SLOT_QTY)";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.mysql_nptsld ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=1000;
} while ($counter <= $rowcount); //end of item by whse loop




$cpcresult = $aseriesconn_can->prepare("SELECT WAREHOUSE,
                                            ITEM_NUMBER, 
                                            PACKAGE_UNIT, 
                                            PACKAGE_TYPE, 
                                            DSL_TYPE, 
                                            CUR_LOCATION, 
                                            DAYS_FRM_SLE, 
                                            DAYS_FRM_BKO, 
                                            AVGD_BTW_SLE, 
                                            AVG_INV_OH, 
                                            NBR_SHIP_OCC, 
                                            PICK_QTY_MN, 
                                            PICK_QTY_SM, 
                                            PICK_QTY_SD, 
                                            PICK_QTY_FC, 
                                            SLOT_PICKS, 
                                            SHIP_QTY_MN, 
                                            SHIP_QTY_SM, 
                                            SHIP_QTY_SD, 
                                            SHIP_QTY_FC, 
                                            SLOT_QTY
                                    FROM ARCPCORDTA.NPTSLD");
$cpcresult->execute();
$NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 999;
$counter = 0;
$rowcount = count($NPFCPC_ALL_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WAREHOUSE = intval($NPFCPC_ALL_array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($NPFCPC_ALL_array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($NPFCPC_ALL_array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $NPFCPC_ALL_array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $NPFCPC_ALL_array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $NPFCPC_ALL_array[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($NPFCPC_ALL_array[$counter]['DAYS_FRM_SLE']);
        $DAYS_FRM_BKO = intval($NPFCPC_ALL_array[$counter]['DAYS_FRM_BKO']);
        $AVGD_BTW_SLE = intval($NPFCPC_ALL_array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($NPFCPC_ALL_array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($NPFCPC_ALL_array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($NPFCPC_ALL_array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SM = number_format($NPFCPC_ALL_array[$counter]['PICK_QTY_SM'], 2, '.', '');
        $PICK_QTY_SD = number_format($NPFCPC_ALL_array[$counter]['PICK_QTY_SD'], 2, '.', '');
        $PICK_QTY_FC = intval($NPFCPC_ALL_array[$counter]['PICK_QTY_FC']);
        $SLOT_PICKS = intval($NPFCPC_ALL_array[$counter]['SLOT_PICKS']);
        $SHIP_QTY_MN = intval($NPFCPC_ALL_array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SM = number_format($NPFCPC_ALL_array[$counter]['SHIP_QTY_SM'], 2, '.', '');
        $SHIP_QTY_SD = number_format($NPFCPC_ALL_array[$counter]['SHIP_QTY_SD'], 2, '.', '');
        $SHIP_QTY_FC = intval($NPFCPC_ALL_array[$counter]['SHIP_QTY_FC']);
        $SLOT_QTY = number_format($NPFCPC_ALL_array[$counter]['SLOT_QTY'], 2, '.', '');




        $data[] = "($WAREHOUSE, $ITEM_NUMBER, $PACKAGE_UNIT, '$PACKAGE_TYPE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, $DAYS_FRM_BKO, $AVGD_BTW_SLE, $AVG_INV_OH, $NBR_SHIP_OCC, $PICK_QTY_MN, $PICK_QTY_SM, $PICK_QTY_SD, $PICK_QTY_FC, $SLOT_PICKS, $SHIP_QTY_MN, $SHIP_QTY_SM, $SHIP_QTY_SD, $SHIP_QTY_FC, $SLOT_QTY)";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.mysql_nptsld ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=1000;
} while ($counter <= $rowcount); //end of item by whse loop


$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//delete PFR locations where there is a case primary

foreach ($whsearray as $whse) {
    $sqldelete = "DELETE FROM slotting.mysql_nptsld
WHERE
    WAREHOUSE = $whse and PACKAGE_TYPE = ('PFR')
        and ITEM_NUMBER in (SELECT B.itemnum FROM(SELECT 
            ITEM_NUMBER as itemnum
        FROM
            slotting.mysql_nptsld
        WHERE
            WAREHOUSE = $whse
                and PACKAGE_TYPE in ('CSE' , 'PFR')
        GROUP BY ITEM_NUMBER
        HAVING COUNT(*) > 1) B)";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();
}


