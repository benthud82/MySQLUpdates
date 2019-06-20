<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include_once '../globalincludes/usa_asys.php';
include_once '../globalfunctions/slottingfunctions.php';


$sqldelete = "TRUNCATE custaudit.massalgorithm_shipacc_recs";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'shipacc_id, shipacc_whse, shipacc_item, shipacc_desc, shipacc_30day, shipacc_90day, shipacc_365day, shipacc_lines30day, shipacc_lines90day, shipacc_lines365day, shipacc_acc30day, shipacc_acc90day, shipacc_acc365day';

$shipacc = $conn1->prepare("SELECT 
                                                        WHSE,
                                                        ITEMCODE,
                                                        SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 30 DAY) THEN 1
                                                            ELSE 0
                                                        END) AS SHIPACC_30,
                                                        SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 90 DAY) THEN 1
                                                            ELSE 0
                                                        END) AS SHIPACC_90,
                                                        SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 365 DAY) THEN 1
                                                            ELSE 0
                                                        END) AS SHIPACC_365,
                                                        whslines_lines30,
                                                        whslines_lines90,
                                                        whslines_lines365,
                                                        1 - (SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 30 DAY) THEN 1
                                                            ELSE 0
                                                        END) / whslines_lines30) AS SHIPPERCACC_30,
                                                        1 - (SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 90 DAY) THEN 1
                                                            ELSE 0
                                                        END) / whslines_lines90) AS SHIPPERCACC_90,
                                                        1 - (SUM(CASE
                                                            WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 365 DAY) THEN 1
                                                            ELSE 0
                                                        END) / whslines_lines365) AS SHIPPERCACC_365
                                                    FROM
                                                        custaudit.custreturns
                                                            JOIN
                                                        custaudit.whslines ON whslines_whse = WHSE
                                                            AND whslines_item = ITEMCODE
                                                    WHERE
                                                        RETURNCODE IN ('WQSP' , 'WISP', 'IBNS')
                                                            AND whslines_lines30 > 0
                                                    GROUP BY WHSE , ITEMCODE
                                                    HAVING (SHIPPERCACC_365 <= .97 OR SHIPPERCACC_90 <= .97
                                                        OR SHIPPERCACC_30 <= .97)
                                                        AND whslines_lines365 >= 100");
$shipacc->execute();
$shipaccarray = $shipacc->fetchAll(pdo::FETCH_ASSOC);

$recentactioned = $conn1->prepare("SELECT concat(ma_whse, ma_item) as LOOKUPKEY FROM custaudit.massalgorithm_actions WHERE ma_date >= DATE_ADD(CURDATE(), INTERVAL - 90 DAY)and ma_algorithm = 'SHIPACC';");
$recentactioned->execute();
$recentactionedarray = $recentactioned->fetchAll(pdo::FETCH_ASSOC);

$shipacc_id = 0;



$maxrange = 20000;
$counter = 0;
$rowcount = count($shipaccarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $shipacc_whse = intval($shipaccarray[$counter]['WHSE']);
        $shipacc_item = intval($shipaccarray[$counter]['ITEMCODE']);

        $itemdescsql = $aseriesconn->prepare("SELECT IMDESC from HSIPCORDTA.NPFIMS WHERE IMITEM = '$shipacc_item'");
        $itemdescsql->execute();
        $itemdescarray = $itemdescsql->fetchAll(pdo::FETCH_ASSOC);

        $itemdesc = trim(preg_replace('/[^ \w]+/', '', ($itemdescarray[0]['IMDESC'])));


//if item/whse combination is in recently actioned items, continue to next item as to not skew count
        $lookupval = $shipacc_whse . $shipacc_item;
        $lookupkey = array_search($lookupval, array_column($recentactionedarray, 'LOOKUPKEY')); //Find 'L06' associated key
        if ($lookupkey !== FALSE) {
            $counter += 1;
            continue;
        }
        $shipacc_30day = intval($shipaccarray[$counter]['SHIPACC_30']);
        $shipacc_90day = intval($shipaccarray[$counter]['SHIPACC_90']);
        $shipacc_365day = intval($shipaccarray[$counter]['SHIPACC_365']);
        $shipacc_lines30day = intval($shipaccarray[$counter]['whslines_lines30']);
        $shipacc_lines90day = intval($shipaccarray[$counter]['whslines_lines90']);
        $shipacc_lines365day = intval($shipaccarray[$counter]['whslines_lines365']);
        $shipacc_acc30day = ($shipaccarray[$counter]['SHIPPERCACC_30']);
        $shipacc_acc90day = ($shipaccarray[$counter]['SHIPPERCACC_90']);
        $shipacc_acc365day = ($shipaccarray[$counter]['SHIPPERCACC_365']);




        $data[] = "($shipacc_id, $shipacc_whse, $shipacc_item,'$itemdesc',  $shipacc_30day, $shipacc_90day, $shipacc_365day, $shipacc_lines30day,$shipacc_lines90day, $shipacc_lines365day,'$shipacc_acc30day', '$shipacc_acc90day', '$shipacc_acc365day')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.massalgorithm_shipacc_recs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 20000;
} while ($counter <= $rowcount); //end of item by whse loop
//populate skuopt summary table by whse/date
$sql2 = "INSERT IGNORE INTO custaudit.massalgorithm_shipacc_summary
                         SELECT 
                                CURDATE(),
                                shipacc_whse,
                                COUNT(*) AS whscount,
                                SUM(shipacc_30day) AS monthunits,
                                SUM(shipacc_365day) AS yearunits
                            FROM
                                custaudit.massalgorithm_shipacc_recs
                            GROUP BY CURDATE() , shipacc_whse";
$query2 = $conn1->prepare($sql2);
$query2->execute();







