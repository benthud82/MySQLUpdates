<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
//include_once '../globalincludes/ustxgpslotting_mysql.php';
include_once '../globalincludes/usa_asys.php';
//include_once '../globalincludes/newcanada_asys.php';


$sqldelete = "TRUNCATE custaudit.whslines";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'whslines_whse, whslines_item, whslines_lines30, whslines_lines90, whslines_lines365, whslines_qty30, whslines_qty90, whslines_qty365';

$whsearray = array(2, 3, 6, 7, 9);

foreach ($whsearray as $whssel) {



    $whslinesdata = $aseriesconn->prepare("SELECT PDWHSE, PDITEM, sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 30 Days) then 1 else 0 end) as WHS_LINES_30  ,   sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 90 Days) then 1 else 0 end) as WHS_LINES_90,   sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 365 Days) then 1 else 0 end) as WHS_LINES_365, sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 30 Days) then PDPCKS else 0 end) as SHIP_QTY_30,     sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 90 Days) then PDPCKS else 0 end) as SHIP_QTY_90,  sum(CASE WHEN( CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 365 Days) then PDPCKS else 0 end) as SHIP_QTY_365   FROM HSIPCORDTA.NOTWPT WHERE PDWHSE  = $whssel GROUP BY PDWHSE, PDITEM");
    $whslinesdata->execute();
    $whslinesdataarray = $whslinesdata->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($whslinesdataarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $whslines_whse = intval($whslinesdataarray[$counter]['PDWHSE']);
            $whslines_item = intval($whslinesdataarray[$counter]['PDITEM']);
            $whslines_lines30 = intval($whslinesdataarray[$counter]['WHS_LINES_30']);
            $whslines_lines90 = intval($whslinesdataarray[$counter]['WHS_LINES_90']);
            $whslines_lines365 = intval($whslinesdataarray[$counter]['WHS_LINES_365']);
            $whslines_qty30 = intval($whslinesdataarray[$counter]['SHIP_QTY_30']);
            $whslines_qty90 = intval($whslinesdataarray[$counter]['SHIP_QTY_90']);
            $whslines_qty365 = intval($whslinesdataarray[$counter]['SHIP_QTY_365']);


            $data[] = "($whslines_whse, $whslines_item, $whslines_lines30, $whslines_lines90, $whslines_lines365, $whslines_qty30, $whslines_qty90,$whslines_qty365 )";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO custaudit.whslines ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 10000;
    } while ($counter <= $rowcount);
}