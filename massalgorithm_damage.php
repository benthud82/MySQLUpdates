<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_custaudit.php';  //conn1
include_once '../globalincludes/usa_asys.php';
include_once '../globalfunctions/slottingfunctions.php';


$sqldelete = "TRUNCATE custaudit.massalgorithm_damage_recs";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'damage_id, damage_whse, damage_item, damage_desc, damage_30day, damage_90day, damage_365day, damage_lines30day, damage_lines90day, damage_lines365day, damage_acc30day, damage_acc90day, damage_acc365day';

$damage = $conn1->prepare("SELECT 
    WHSE,
    ITEMCODE,
    SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 30 DAY) THEN 1
        ELSE 0
    END) AS DAMAGES_30,
    SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 90 DAY) THEN 1
        ELSE 0
    END) AS DAMAGES_90,
    SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 365 DAY) THEN 1
        ELSE 0
    END) AS DAMAGES_365,
    whslines_lines30,
    whslines_lines90,
    whslines_lines365,
    1 - (SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 30 DAY) THEN 1
        ELSE 0
    END) / whslines_lines30) AS DMGACC_30,
    1 - (SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 90 DAY) THEN 1
        ELSE 0
    END) / whslines_lines90) AS DMGACC_90,
    1 - (SUM(CASE
        WHEN ORD_RETURNDATE >= (CURRENT_DATE - INTERVAL 365 DAY) THEN 1
        ELSE 0
    END) / whslines_lines365) AS DMGACC_365
FROM
    custaudit.custreturns
        JOIN
    custaudit.whslines ON whslines_whse = WHSE
        AND whslines_item = ITEMCODE
WHERE
    RETURNCODE IN ('TDNR' , 'CRID')
        AND whslines_lines30 > 0
GROUP BY WHSE , ITEMCODE
HAVING (DMGACC_365 <= .97 OR DMGACC_90 <= .97
    OR DMGACC_30 <= .97)
    AND whslines_lines365 >= 100");
$damage->execute();
$damagearray = $damage->fetchAll(pdo::FETCH_ASSOC);

$recentactioned = $conn1->prepare("SELECT concat(ma_whse, ma_item) as LOOKUPKEY FROM custaudit.massalgorithm_actions WHERE ma_date >= DATE_ADD(CURDATE(), INTERVAL - 90 DAY)and ma_algorithm = 'DAMAGE';");
$recentactioned->execute();
$recentactionedarray = $recentactioned->fetchAll(pdo::FETCH_ASSOC);

$damage_id = 0;



$maxrange = 20000;
$counter = 0;
$rowcount = count($damagearray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $damage_whse = intval($damagearray[$counter]['WHSE']);
        $damage_item = intval($damagearray[$counter]['ITEMCODE']);


        $itemdescsql = $aseriesconn->prepare("SELECT IMDESC from HSIPCORDTA.NPFIMS WHERE IMITEM = '$damage_item'");
        $itemdescsql->execute();
        $itemdescarray = $itemdescsql->fetchAll(pdo::FETCH_ASSOC);

        $itemdesc = trim(preg_replace('/[^ \w]+/', '', ($itemdescarray[0]['IMDESC'])));
//if item/whse combination is in recently actioned items, continue to next item as to not skew count
        $lookupval = $damage_whse . $damage_item;
        $lookupkey = array_search($lookupval, array_column($recentactionedarray, 'LOOKUPKEY')); //Find 'L06' associated key
        if ($lookupkey !== FALSE) {
            $counter += 1;
            continue;
        }
        $damage_30day = intval($damagearray[$counter]['DAMAGES_30']);
        $damage_90day = intval($damagearray[$counter]['DAMAGES_90']);
        $damage_365day = intval($damagearray[$counter]['DAMAGES_365']);
        $damage_lines30day = intval($damagearray[$counter]['whslines_lines30']);
        $damage_lines90day = intval($damagearray[$counter]['whslines_lines90']);
        $damage_lines365day = intval($damagearray[$counter]['whslines_lines365']);
        $damage_acc30day = ($damagearray[$counter]['DMGACC_30']);
        $damage_acc90day = ($damagearray[$counter]['DMGACC_90']);
        $damage_acc365day = ($damagearray[$counter]['DMGACC_365']);




        $data[] = "($damage_id, $damage_whse, $damage_item, '$itemdesc', $damage_30day, $damage_90day, $damage_365day, $damage_lines30day,$damage_lines90day, $damage_lines365day,'$damage_acc30day', '$damage_acc90day', '$damage_acc365day')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.massalgorithm_damage_recs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 20000;
} while ($counter <= $rowcount); //end of item by whse loop
//populate skuopt summary table by whse/date
$sql2 = "INSERT IGNORE INTO custaudit.massalgorithm_damage_summary
                         SELECT 
                                CURDATE(),
                                damage_whse,
                                COUNT(*) AS whscount,
                                SUM(damage_30day) AS monthunits,
                                SUM(damage_365day) AS yearunits
                            FROM
                                custaudit.massalgorithm_damage_recs
                            GROUP BY CURDATE() , damage_whse";
$query2 = $conn1->prepare($sql2);
$query2->execute();







