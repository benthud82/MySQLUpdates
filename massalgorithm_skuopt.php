<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include '../connections/conn_custaudit.php';  //conn1
include_once '../globalincludes/usa_asys.php';
include_once '../globalfunctions/slottingfunctions.php';


$startdate = date('Y-m-d', strtotime('-30 days'));
//convert startdate for sql connection jdate below
$startyear = date('y', strtotime($startdate));
$startday = date('z', strtotime($startdate)) + 1;
if ($startday < 10) {
    $startday = '00' . $startday;
} else if ($startday < 100) {
    $startday = '0' . $startday;
}
$startdatemonth = intval('1' . $startyear . $startday);

$startdate2 = date('Y-m-d', strtotime('-365 days'));
//convert startdate for sql connection jdate below
$startyear2 = date('y', strtotime($startdate2));
$startday2 = date('z', strtotime($startdate2)) + 1;
if ($startday2 < 10) {
    $startday2 = '00' . $startday2;
} else if ($startday2 < 100) {
    $startday2 = '0' . $startday2;
}
$startdateyear = intval('1' . $startyear2 . $startday2);




$sqldelete = "TRUNCATE custaudit.massalgorithm_skuopt_recs";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'skuopt_id, skuopt_whse, skuopt_item, skuopt_desc, skuopt_monthunits, skuopt_yearunits';

$skuopt = $aseriesconn->prepare("SELECT       ORD_PWHS,     ITEM,    IMDESC,  SUM(CASE         WHEN OR_DATE >= $startdatemonth THEN 1         ELSE 0     END) AS MONTHUNITS,     SUM(CASE         WHEN OR_DATE >= $startdateyear THEN 1         ELSE 0     END) AS YEARUNITS FROM     HSIPCORDTA.IM0011         LEFT JOIN     HSIPCORDTA.NPFWRS ON ORD_PWHS = WRSWHS AND ITEM = WRSITM         LEFT JOIN     HSIPCORDTA.NPFIMS ON IMITEM = ITEM WHERE NOT EXISTS(SELECT * FROM HSIPCORDTA.NPFLOC WHERE LOITEM = ITEM and ORD_PWHS = LOWHSE and LOLOC# not like ('N%') )  and ORD_PWHS IN (2 , 3, 6, 7, 9)         AND SHIP_DC IN (2 , 3, 6, 7, 9)         AND (WRSSTK IS NULL OR WRSSTK = 'N')         AND IMCLSC <> '2'  and IMAVLC = ' '   GROUP BY SHIP_DC , ORD_PWHS , ITEM , IMDESC, WRSSTK HAVING SUM(CASE     WHEN OR_DATE >= $startdateyear THEN 1     ELSE 0 END) >= 24 and (CAST(SUM(CASE         WHEN OR_DATE >= $startdatemonth THEN 1         ELSE 0     END) as float) / CAST(SUM(CASE         WHEN OR_DATE >= $startdateyear THEN 1         ELSE 0     END) as float)) >= .05");
$skuopt->execute();
$skuoptarray = $skuopt->fetchAll(pdo::FETCH_ASSOC);

$recentactioned = $conn1->prepare("SELECT concat(ma_whse, ma_item) as LOOKUPKEY FROM custaudit.massalgorithm_actions WHERE ma_date >= DATE_ADD(CURDATE(), INTERVAL - 90 DAY) and ma_algorithm = 'SKUOPT';");
$recentactioned->execute();
$recentactionedarray = $recentactioned->fetchAll(pdo::FETCH_ASSOC);




$maxrange = 20000;
$counter = 0;
$rowcount = count($skuoptarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $skuopt_whse = intval($skuoptarray[$counter]['ORD_PWHS']);
        $skuopt_item = intval($skuoptarray[$counter]['ITEM']);

//if item/whse combination is in recently actioned items, continue to next item as to not skew count
        $lookupval = $skuopt_whse . $skuopt_item;
        $lookupkey = array_search($lookupval, array_column($recentactionedarray, 'LOOKUPKEY')); //Find 'L06' associated key
        if ($lookupkey !== FALSE) {
            $counter += 1;
            continue;
        }

        $skuopt_id = 0;

        $skuopt_monthunits = intval($skuoptarray[$counter]['MONTHUNITS']);
        $skuopt_yearunits = intval($skuoptarray[$counter]['YEARUNITS']);
        $skuopt_desc = trim(preg_replace('/[^ \w]+/', '', ($skuoptarray[$counter]['IMDESC'])));


        if (($skuopt_whse == 2 || $skuopt_whse == 6 || $skuopt_whse == 9) && $skuopt_yearunits < 36) {
            $counter += 1;
            continue;
        }


        $data[] = "($skuopt_id, $skuopt_whse, $skuopt_item,'$skuopt_desc',$skuopt_monthunits, $skuopt_yearunits )";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.massalgorithm_skuopt_recs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 20000;
} while ($counter <= $rowcount); //end of item by whse loop
//populate skuopt summary table by whse/date
$sql2 = "INSERT IGNORE INTO custaudit.massalgorithm_skuopt_summary
                        SELECT 
                            CURDATE(),
                            skuopt_whse,
                            COUNT(*) AS whscount,
                            SUM(skuopt_monthunits) AS monthunits,
                            SUM(skuopt_yearunits) AS yearunits
                        FROM
                            custaudit.massalgorithm_skuopt_recs
                        GROUP BY CURDATE() , skuopt_whse";
$query2 = $conn1->prepare($sql2);
$query2->execute();
