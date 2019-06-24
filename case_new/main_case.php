<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include_once '../../globalfunctions/slottingfunctions.php';
include_once '../../globalfunctions/newitem.php';
include_once '../sql_dailypick_case.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
include '../../connections/conn_slotting.php';

//assign decks that can fit average inventory
include 'C06.php';

//--pull in available tiers--
$alltiersql = $conn1->prepare("SELECT 
                                                            TIER_WHS, TIER_TIER, TIER_COUNT, TIER_DESCRIPTION
                                                        FROM
                                                            slotting.tiercounts
                                                        WHERE
                                                            TIER_WHS = 7 AND TIER_TIER LIKE 'C%'");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$alltiersql->execute();
$alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);




//--pull in volume by tier--
$allvolumesql = $conn1->prepare("SELECT LMWHSE, LMTIER, sum(LMVOL9) as TIER_VOL FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel GROUP BY LMWHSE, LMTIER");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$allvolumesql->execute();
$allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);
