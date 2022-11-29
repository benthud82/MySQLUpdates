
<?php

$whsearray = array(7);

set_time_limit(99999);
ini_set('max_execution_time', 99999);
ini_set('set_time_limit', 99999);
ini_set('memory_limit', '-1');
ini_set('request_terminate_timeout', 99999);
$encoded_data = json_encode($_POST);
//assigned posted variables
$L04vol = $_POST['varL04_vol'];
$L02Vol = $_POST['varL02_vol'];
$array_checked_grids_L04 = $_POST['array_checked_grids_L04'];
$array_checked_grids_L05 = $_POST['array_checked_grids_L05'];

$array_stock_days_L02 = $_POST['array_stock_days_L02'];
$array_stock_days_L04 = $_POST['array_stock_days_L04'];
$L06_pick_limit = $_POST['varL06_pickpercent'];
$L01count_single = $_POST['varL01_L01count_single'];
$L01count_double = $_POST['varL01_L01count_double'];
$L02_min_adbs = $_POST['varL02_min_adbs'];
$L02_min_dsls = $_POST['varL02_min_dsls'];
$L02_min_cubevel = $_POST['varL02_cubevel'];
$L02_min_lanecalc = $_POST['varL02_min_lanecalc'];
$var_L02_vendorcase = $_POST['var_L02_vendorcase'];
$var_L02_okinflow = $_POST['var_L02_okinflow'];
$L05_stockbuffer = $_POST['varL05_stockbuffer'];
$L05_vol = $_POST['varL05_vol'];
$L02_stockdays = $_POST['varL02_stockdays'];
$L01_stockdays = $_POST['varL01_stockdays'];

$array_checked_grids_L02 = $_POST['array_checked_grids_L02']; //variable is $L02GridsArray
$L02_adbs_key0 = $array_stock_days_L02[0];
$L02_adbs_key1 = $array_stock_days_L02[1];
$L02_adbs_key2 = $array_stock_days_L02[2];
$L02_adbs_key3 = $array_stock_days_L02[3];
$L02_adbs_key4 = $array_stock_days_L02[4];

$L04_adbs_key0 = $array_stock_days_L04[0];
$L04_adbs_key1 = $array_stock_days_L04[1];
$L04_adbs_key2 = $array_stock_days_L04[2];
$L04_adbs_key3 = $array_stock_days_L04[3];
$L04_adbs_key4 = $array_stock_days_L04[4];
$L04_adbs_key5 = $array_stock_days_L04[5];
$L04_adbs_key6 = $array_stock_days_L04[6];
$L04_adbs_key7 = $array_stock_days_L04[7];
$L04_adbs_key8 = $array_stock_days_L04[8];
$L04_adbs_key9 = $array_stock_days_L04[9];

foreach ($whsearray as $whssel) {
    include 'slotmodel_main.php';
}


