<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include_once '../../globalfunctions/slottingfunctions.php';
include_once '../../globalfunctions/newitem.php';
include_once '../sql_dailypick_case.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
include '../../connections/conn_slotting.php';
$whse_array = array(32, 7, 3);

$sqldelete3 = "TRUNCATE slotting.my_npfmvc_cse";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$columns = 'WAREHOUSE,BUILDING, ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCCPKU,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK,AVG_DAILY_PICK,AVG_DAILY_UNIT,VCBAY,SUGG_EQUIP, CURR_EQUIP, SUGG_LEVEL';
//sql to exclude PFR items that have inner pack location.  These items will default to PFR

foreach ($whse_array as $whseval) {
    if ($whseval == 32) {
        $whse = 3;
        $locationsql = " and (CASE
                                    WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                    else LMLOC
                                end > 'W40%' or CASE
                                    WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                    else LMLOC
                                end = 'PFR') ";
        $building = 2;
    } else if ($whseval == 3) {
        $whse = 3;
        $locationsql = " and (CASE
                                    WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                    else LMLOC
                                end <= 'W39%' and CASE
                                    WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                    else LMLOC
                                end <> 'PFR') ";
        $building = 1;
    } else {
        $whse = $whseval;
        $locationsql = " ";
        $building = 1;
    }
    if ($whseval == 32) {
        $lmsql = " and LMLOC >= 'W40%' ";
    } elseif ($whseval == 3) {
        $lmsql = " and LMLOC < 'W40%' ";
    } else {
        $lmsql = ' ';
    }
    $sql_inp_pfr = " and A.ITEM_NUMBER not in (SELECT DISTINCT
    CPCITEM
FROM
    slotting.npfcpcsettings
        JOIN
    slotting.mysql_nptsld ON WAREHOUSE = CPCWHSE
        AND ITEM_NUMBER = CPCITEM
        AND PACKAGE_UNIT = CPCCPKU
WHERE
    CPCWHSE = $whse AND PACKAGE_TYPE = 'INP')";
    //exclude PTB and bulk recommendations from Eric's logic
    include 'PTB_exclude.php';
    
    //assign bulk 
//    include 'BULK.php';
    //assign bulk 
//    include 'PTB.php';
    //assign decks 
    include 'C06.php';
    //assign half pallet 
    include 'C05.php';
    //assign full pallets
    include 'C03.php';
    //assing everything else
    include 'PFR.php';
    //deck non-cons
    include 'C09.php';
    //pallet non-cons
    include 'C07.php';
}