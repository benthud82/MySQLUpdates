<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//main core file to update slotting recommendation file --MY_NPFMVC--
//global includes


include_once '../globalfunctions/slottingfunctions.php';
include_once '../globalfunctions/newitem.php';

include_once 'sql_dailypick_case.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//assign columns variable for my_npfmvc table
$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCEPKU,CPCIPKU,CPCCPKU,CPCFLOW,CPCTOTE,CPCSHLF,CPCROTA,CPCESTK,CPCLIQU,CPCELEN,CPCEHEI,CPCEWID,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK, AVG_DAILY_PICK, AVG_DAILY_UNIT';


//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//    if ($whssel == 32) {
//        $sparksbuild2filter = " >= 'W400000'";
//        $tierwhse = 32;
//        $whssel = 3;
//    } else {
//        $sparksbuild2filter = " >= ''";
//        $tierwhse = $whssel;
//    }
include '../connections/conn_slotting.php';
$sqldelete = "DELETE FROM slotting.my_npfmvc WHERE WAREHOUSE = $whssel and SUGGESTED_TIER like 'C%'";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//--pull in available tiers--
$alltiersql = $conn1->prepare("SELECT * FROM slotting.tiercounts WHERE TIER_WHS = $whssel");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$alltiersql->execute();
$alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);

//--pull in volume by tier--
$allvolumesql = $conn1->prepare("SELECT LMWHSE, LMTIER, sum(LMVOL9) as TIER_VOL FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel GROUP BY LMWHSE, LMTIER");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$allvolumesql->execute();
$allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;
//call C01 Update logic (BP)
$C01key = array_search('C01', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
if ($C01key !== FALSE && $whssel <> 3) {

    include 'C01update.php';
}

//call C02 Update logic (PTB)

include 'C02update.php';

//call noncon logic (C07-C09)

include 'case_noncon_update.php';

//call convey logic (C03, C05, C06)


include 'case_convey_update.php';
