<?php

//include '../connections/conn_slotting.php';
date_default_timezone_set('America/New_York');
$today = date("Y-m-d H:i:s");
$inculde = intval(1); //set default to include data model run in analysis
$sql_modelresults = $conn1->prepare("SELECT 
                                        suggested_tier AS SUGG_TIER,
                                        COUNT(*) AS ITEM_COUNT,
                                        SUM(SUGGESTED_IMPMOVES) AS SUGG_REPLEN,
                                        SUM(CURRENT_IMPMOVES) AS CURR_REPLEN,
                                        SUM(SUGGESTED_NEWLOCVOL) / 10000 AS NEWLOC_VOL
                                    FROM
                                        slotting.slotmodel_my_npfmvc
                                    WHERE WAREHOUSE = $whssel
                                    GROUP BY suggested_tier");
$sql_modelresults->execute();
$array_modelresults = $sql_modelresults->fetchAll(pdo::FETCH_ASSOC);

foreach ($array_modelresults as $key => $value) {

    $tier = $array_modelresults[$key]['SUGG_TIER'];
    switch ($tier) {
        case 'L01':
            $result_L01_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L01_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L01_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L01_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L02':
            $result_L02_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L02_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L02_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L02_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L04':
            $result_L04_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L04_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L04_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L04_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L05':
            $result_L05_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L05_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L05_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L05_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L06':
            $result_L06_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L06_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L06_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L06_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;

        default:
            break;
    }
}

$result2 = $conn1->prepare("INSERT INTO slotting.slotmodel_results (result_datetime,
                                                                    result_whse,
                                                                    result_L01_curr_replen,
                                                                    result_L01_sugg_replen,
                                                                    result_L01_sugg_locvol,
                                                                    result_L01_items,
                                                                    result_L02_curr_replen,
                                                                    result_L02_sugg_replen,
                                                                    result_L02_sugg_locvol,
                                                                    result_L02_items,
                                                                    result_L04_curr_replen,
                                                                    result_L04_sugg_replen,
                                                                    result_L04_sugg_locvol,
                                                                    result_L04_items,
                                                                    result_L05_curr_replen,
                                                                    result_L05_sugg_replen,
                                                                    result_L05_sugg_locvol,
                                                                    result_L05_items,
                                                                    result_L06_curr_replen,
                                                                    result_L06_sugg_replen,
                                                                    result_L06_sugg_locvol,
                                                                    result_L06_items,
                                                                    result_variables,
                                                                    result_include) 
                                                                values ('$today', $whssel, 
                                                                        '$result_L01_curr_replen', '$result_L01_sugg_replen', '$result_L01_sugg_locvol',$result_L01_items,
                                                                        '$result_L02_curr_replen', '$result_L02_sugg_replen', '$result_L02_sugg_locvol',$result_L02_items,
                                                                        '$result_L04_curr_replen', '$result_L04_sugg_replen', '$result_L04_sugg_locvol',$result_L04_items,
                                                                        '$result_L05_curr_replen', '$result_L05_sugg_replen', '$result_L05_sugg_locvol',$result_L05_items,
                                                                        '$result_L06_curr_replen', '$result_L06_sugg_replen', '$result_L06_sugg_locvol',$result_L06_items,
                                                                         '$encoded_data', $inculde)");
$result2->execute();
