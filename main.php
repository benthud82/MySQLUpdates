<?php

if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//main core file to update slotting recommendation file --MY_NPFMVC--
//global includes

include_once '../globalfunctions/slottingfunctions.php';
include_once '../globalfunctions/newitem.php';

include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//assign columns variable for my_npfmvc table
$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCEPKU,CPCIPKU,CPCCPKU,CPCFLOW,CPCTOTE,CPCSHLF,CPCROTA,CPCESTK,CPCLIQU,CPCELEN,CPCEHEI,CPCEWID,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK, AVG_DAILY_PICK, AVG_DAILY_UNIT, VCBAY, JAX_ENDCAP';

include '../connections/conn_slotting.php';
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//Delete inventory restricted items
$sqldelete3 = "DELETE FROM slotting.inventory_restricted WHERE WHSE_INV_REST = $whssel;";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete = "DELETE FROM slotting.my_npfmvc WHERE WAREHOUSE = $whssel and PACKAGE_TYPE in ('LSE', 'INP')";
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


//Assign items on hold
include_once 'itemsonhold.php';



//call L06 update logic  ***Is not needed for Canada.  Will have to add for US based slotting.  Should try to keep L06 (PICK_QTY_MN / AVGD_BTW_SLE) less that 1% of total
//what is total L06 volume available.
$L06key = array_search('L06', array_column($allvolumearray, 'LMTIER')); //Find 'L06' associated key
$L06onholdkey = array_search('L06', array_column($holdvolumearray, 'SUGGESTED_TIER')); //Find 'L06' associated key in items on hold array to subtract from available volume
if ($L06onholdkey !== FALSE) {
    $L06holdvol = intval($holdvolumearray[$L06onholdkey]['ASSVOL']);
} else {
    $L06holdvol = 0;
}
$L06Vol = intval($allvolumearray[$L06key]['TIER_VOL']) - $L06holdvol;

if ($L06key !== FALSE) {
    include 'L06update.php';
}


//call L01 Update logic
$L01key = array_search('L01', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
$L01onholdkey = array_search('L01', array_column($holdvolumearray, 'SUGGESTED_TIER'));
if ($L01onholdkey !== FALSE) {
    $L01onholdcount = intval($holdvolumearray[$L01onholdkey]['ASSCOUNT']);
} else {
    $L01onholdcount = 0;
}
if ($L01key !== FALSE) {
    include 'L01update.php';
}





//call L02 Update logic
include 'L02update.php';
//Call L05 logic if drawers exist
$L05key = array_search('L05', array_column($allvolumearray, 'LMTIER')); //Find 'L05' associated key
//    if ($L05key !== FALSE) {

if ($whssel !== 6 && $whssel !== 9) {
    include 'L05update.php';
}
//Call L04 update logic
//Kill sleeping connections
//For Jax, have to recommend different sizes for endcaps with 48 in wide shelfs.  Call L04 endcaps first, and assign based off available volume for endcaps
if ($whssel == 9) {
    include 'L04jaxendcap.php';
}

include 'L04update.php';
