<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';
include_once '../heatmap_logic/functions/funct.php';




$result1 = $aseriesconn->prepare("SELECT
                                    LOWHSE as locoh_whse,
                                    LOITEM as locoh_item,
                                    LOLOC# as locoh_loc ,
                                    LOONHD as locoh_onhand,
                                    LOOPNA as locoh_openalloc,
                                    LOPRTA as locoh_printalloc                                    
                                FROM
                                    HSIPCORDTA.NPFLOC
                                WHERE
                                    LENGTH(RTRIM(TRANSLATE(LOITEM, '*', ' 0123456789'))) = 0");
$result1->execute();
$result = $result1->fetchAll(pdo::FETCH_ASSOC);
$schema = 'slotting';
$arraychunk = 10000;
$mysqltable = 'loc_oh';


$updatecols = array('locoh_onhand','locoh_openalloc','locoh_printalloc');
//insert into table
pdoMultiInsert_duplicate($mysqltable, $schema, $result, $conn1, $arraychunk,$updatecols);


