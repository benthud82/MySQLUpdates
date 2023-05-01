<?php

//include '../connections/conn_slotting.php';
date_default_timezone_set('America/New_York');
$today = date("Y-m-d H:i:s");
$inculde = intval(1); //set default to include data model run in analysis
$sql_modelresults = $conn1->prepare("SELECT 
    CASE
        WHEN
            suggested_tier = 'L04'
                AND AVGD_BTW_SLE < 2
        THEN
            'L04_A'
        WHEN
            suggested_tier = 'L04'
                AND AVGD_BTW_SLE BETWEEN 2 AND 4
        THEN
            'L04_B'
        WHEN
            suggested_tier = 'L04'
                AND AVGD_BTW_SLE BETWEEN 4 AND 7
        THEN
            'L04_C'
        WHEN
            suggested_tier = 'L04'
                AND AVGD_BTW_SLE > 7
        THEN
            'L04_D'
        WHEN
            suggested_tier = 'L02'
                AND AVGD_BTW_SLE < 2
        THEN
            'L02_A'
        WHEN
            suggested_tier = 'L02'
                AND AVGD_BTW_SLE BETWEEN 2 AND 3
        THEN
            'L02_B'
        WHEN
            suggested_tier = 'L02'
                AND AVGD_BTW_SLE BETWEEN 3 AND 4
        THEN
            'L02_C'
        WHEN
            suggested_tier = 'L02'
                AND AVGD_BTW_SLE > 4
        THEN
            'L02_D'
        ELSE suggested_tier
    END AS SUGG_TIER,
    COUNT(*) AS ITEM_COUNT,
    SUM(SUGGESTED_IMPMOVES) AS SUGG_REPLEN,
    SUM(CURRENT_IMPMOVES) AS CURR_REPLEN,
    SUM(SUGGESTED_NEWLOCVOL) / 10000 AS NEWLOC_VOL
FROM
    slotting.slotmodel_my_npfmvc
WHERE
    WAREHOUSE = $whssel
GROUP BY SUGG_TIER
ORDER BY SUGG_TIER");
$sql_modelresults->execute();
$array_modelresults = $sql_modelresults->fetchAll(pdo::FETCH_ASSOC);

$result_L01_curr_replen = $result_L01_sugg_replen = $result_L01_sugg_locvol = $result_L01_items = $result_L02_A_curr_replen = $result_L02_A_sugg_replen = $result_L02_A_sugg_locvol = $result_L02_A_items = $result_L02_B_curr_replen = $result_L02_B_sugg_replen = $result_L02_B_sugg_locvol = $result_L02_B_items = $result_L02_C_curr_replen = $result_L02_C_sugg_replen = $result_L02_C_sugg_locvol = $result_L02_C_items = $result_L02_D_curr_replen = $result_L02_D_sugg_replen = $result_L02_D_sugg_locvol = $result_L02_D_items = $result_L04_A_curr_replen = $result_L04_A_sugg_replen = $result_L04_A_sugg_locvol = $result_L04_A_items = $result_L04_B_curr_replen = $result_L04_B_sugg_replen = $result_L04_B_sugg_locvol = $result_L04_B_items = $result_L04_C_curr_replen = $result_L04_C_sugg_replen = $result_L04_C_sugg_locvol = $result_L04_C_items = $result_L04_D_curr_replen = $result_L04_D_sugg_replen = $result_L04_D_sugg_locvol = $result_L04_D_items = $result_L05_curr_replen = $result_L05_sugg_replen = $result_L05_sugg_locvol = $result_L05_items = $result_L06_curr_replen = $result_L06_sugg_replen = $result_L06_sugg_locvol = $result_L06_items = 0;

foreach ($array_modelresults as $key => $value) {

    $tier = $array_modelresults[$key]['SUGG_TIER'];
    switch ($tier) {
        case 'L01':
            $result_L01_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L01_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L01_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L01_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L02_A':
            $result_L02_A_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L02_A_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L02_A_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L02_A_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L02_B':
            $result_L02_B_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L02_B_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L02_B_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L02_B_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L02_C':
            $result_L02_C_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L02_C_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L02_C_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L02_C_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L02_D':
            $result_L02_D_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L02_D_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L02_D_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L02_D_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L04_A':
            $result_L04_A_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L04_A_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L04_A_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L04_A_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L04_B':
            $result_L04_B_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L04_B_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L04_B_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L04_B_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L04_C':
            $result_L04_C_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L04_C_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L04_C_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L04_C_items = $array_modelresults[$key]['ITEM_COUNT'];

            break;
        case 'L04_D':
            $result_L04_D_curr_replen = $array_modelresults[$key]['CURR_REPLEN'];
            $result_L04_D_sugg_replen = $array_modelresults[$key]['SUGG_REPLEN'];
            $result_L04_D_sugg_locvol = $array_modelresults[$key]['NEWLOC_VOL'];
            $result_L04_D_items = $array_modelresults[$key]['ITEM_COUNT'];

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

$result2 = $conn1->prepare("INSERT INTO slotting.slotmodel_result (result_datetime,
                                                                    result_whse,
                                                                    result_L01_curr_replen,
                                                                    result_L01_sugg_replen,
                                                                    result_L01_sugg_locvol,
                                                                    result_L01_items,
                                                                    result_L02_A_curr_replen,
                                                                    result_L02_A_sugg_replen,
                                                                    result_L02_A_sugg_locvol,
                                                                    result_L02_A_items,
                                                                    result_L02_B_curr_replen,
                                                                    result_L02_B_sugg_replen,
                                                                    result_L02_B_sugg_locvol,
                                                                    result_L02_B_items,
                                                                    result_L02_C_curr_replen,
                                                                    result_L02_C_sugg_replen,
                                                                    result_L02_C_sugg_locvol,
                                                                    result_L02_C_items,
                                                                    result_L02_D_curr_replen,
                                                                    result_L02_D_sugg_replen,
                                                                    result_L02_D_sugg_locvol,
                                                                    result_L02_D_items,
                                                                    result_L04_A_curr_replen,
                                                                    result_L04_A_sugg_replen,
                                                                    result_L04_A_sugg_locvol,
                                                                    result_L04_A_items,
                                                                    result_L04_B_curr_replen,
                                                                    result_L04_B_sugg_replen,
                                                                    result_L04_B_sugg_locvol,
                                                                    result_L04_B_items,
                                                                    result_L04_C_curr_replen,
                                                                    result_L04_C_sugg_replen,
                                                                    result_L04_C_sugg_locvol,
                                                                    result_L04_C_items,
                                                                    result_L04_D_curr_replen,
                                                                    result_L04_D_sugg_replen,
                                                                    result_L04_D_sugg_locvol,
                                                                    result_L04_D_items,
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
                                                                        '$result_L02_A_curr_replen', '$result_L02_A_sugg_replen', '$result_L02_A_sugg_locvol',$result_L02_A_items,
                                                                        '$result_L02_B_curr_replen', '$result_L02_B_sugg_replen', '$result_L02_B_sugg_locvol',$result_L02_B_items,
                                                                        '$result_L02_C_curr_replen', '$result_L02_C_sugg_replen', '$result_L02_C_sugg_locvol',$result_L02_C_items,
                                                                        '$result_L02_D_curr_replen', '$result_L02_D_sugg_replen', '$result_L02_D_sugg_locvol',$result_L02_D_items,
                                                                        '$result_L04_A_curr_replen', '$result_L04_A_sugg_replen', '$result_L04_A_sugg_locvol',$result_L04_A_items,
                                                                        '$result_L04_B_curr_replen', '$result_L04_B_sugg_replen', '$result_L04_B_sugg_locvol',$result_L04_B_items,
                                                                        '$result_L04_C_curr_replen', '$result_L04_C_sugg_replen', '$result_L04_C_sugg_locvol',$result_L04_C_items,
                                                                        '$result_L04_D_curr_replen', '$result_L04_D_sugg_replen', '$result_L04_D_sugg_locvol',$result_L04_D_items,
                                                                        '$result_L05_curr_replen', '$result_L05_sugg_replen', '$result_L05_sugg_locvol',$result_L05_items,
                                                                        '$result_L06_curr_replen', '$result_L06_sugg_replen', '$result_L06_sugg_locvol',$result_L06_items,
                                                                         '$encoded_data', $inculde)");
$result2->execute();
