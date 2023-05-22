<?php

require_once 'funct_array_column.php';

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//main core file to update slotting recommendation file --MY_NPFMVC--
//global includes

include_once '../globalfunctions/slottingfunctions.php';
include_once '../globalfunctions/newitem.php';

include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//assign columns variable for slotmodel_my_npfmvc table
$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCEPKU,CPCIPKU,CPCCPKU,CPCFLOW,CPCTOTE,CPCSHLF,CPCROTA,CPCESTK,CPCLIQU,CPCELEN,CPCEHEI,CPCEWID,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK, AVG_DAILY_PICK, AVG_DAILY_UNIT, VCBAY, JAX_ENDCAP, ITEM_MC';

include '../connections/conn_slotting.php';
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//Delete inventory restricted items
$sqldelete3 = "DELETE FROM slotting.inventory_restricted WHERE WHSE_INV_REST = $whssel;";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete = "DELETE FROM nahsi.slotmodel_fixture_my_npfmvc WHERE WAREHOUSE = $whssel and PACKAGE_TYPE in ('LSE', 'INP')";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//--pull in available tiers--
//$alltiersql = $conn1->prepare("SELECT * FROM slotting.tiercounts WHERE TIER_WHS = $whssel");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
//$alltiersql->execute();
//$alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);

//--pull in volume by tier--
//$allvolumesql = $conn1->prepare("SELECT LMWHSE, LMTIER, sum(LMVOL9) as TIER_VOL FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel GROUP BY LMWHSE, LMTIER");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
//$allvolumesql->execute();
//$allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);

include 'slotmodel_fixture_L06update.php';

include 'slotmodel_fixture_L01update.php';

include 'slotmodel_fixture_L02update.php';

include 'slotmodel_fixture_L05update.php';

//include 'slotmodel_fixture_L04update.php;  //don't need to run L04 for the fixture model.  This will be assigned using the LP model.

//include 'slotmodel_results.php';
