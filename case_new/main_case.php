<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include_once '../../globalfunctions/slottingfunctions.php';
include_once '../../globalfunctions/newitem.php';
include_once '../sql_dailypick_case.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
include '../../connections/conn_slotting.php';
//$whse_array = array(7, 2, 3, 6, 9);
$whse_array = array(7);

$sqldelete3 = "TRUNCATE slotting.my_npfmvc_cse";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();
foreach ($whse_array as $whse) {
    //exclude PTB and bulk recommendations from Eric's logic
    include 'PTB_exclude.php';
    //assign decks 
    include 'C06.php';
    //assign full pallets
    include 'C03.php';
    //assing everything else
    include 'PFR.php';
    //non-cons
}