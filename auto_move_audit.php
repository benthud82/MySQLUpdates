<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';
include_once '../heatmap_logic/functions/funct.php';
include_once '../CustomerAudit/functions/customer_audit_functions.php';




$result1 = $aseriesconn->prepare("SELECT
       AUCOMP,
       AUWHSE,
       AUBLD# as AUBLD,
       AUITEM,
       AUSQTY,
       AUFLOC,
       AUTLOC,
       AUDS2F,
       AUDS4F,
       AUDSL2,
       AUDSL4,
       AUMTYP,
       EQUIPMENT_TYPE,      
       ALL_MOVES,
       AUESTS,
       AUEDSP,
       AUCRDT,
       AUCRTM,
       AUPRGM
FROM
       HSIPCORDTA.HWAMAUD");
$result1->execute();
$result = $result1->fetchAll(pdo::FETCH_ASSOC);
$schema = 'nahsi';
$arraychunk = 10000;
$mysqltable = 'auto_move_audit';

foreach ($result as $key => $value) {
    $result[$key]['AUCRDT'] = _YYYYMMDDtomysqldate($result[$key]['AUCRDT']);

    $cncltime = str_pad($result[$key]['AUCRTM'], 6, '0', STR_PAD_LEFT);
    $result[$key]['AUCRTM'] = date('H:i:s', strtotime($cncltime));
}


//$updatecols = array('locoh_onhand','locoh_openalloc','locoh_printalloc');
//insert into table
pdoMultiInsert($mysqltable, $schema, $result, $conn1, $arraychunk);


