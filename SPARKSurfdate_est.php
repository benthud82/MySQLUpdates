<?php

//estimate the PO delivery date by item
//Start at 5 key and traverse down to 2 key
//Run openpo_update first to ensure most up to date data

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../connections/conn_custaudit.php';  //conn1
//include '../../globalincludes/usa_asys.php';  //conn1
//include '../../globalincludes/ustxgpslotting_mysql.php';  //conn1
include 'globalfunctions.php';

$whse = "('3')"; //declare warehouse for inclusion on urfdate_est

include 'urfdate_est.php';  //this will call urfdate_est.php master file to help manage changes to how the urf date is estimated.  Don't have to update each DCs file.

