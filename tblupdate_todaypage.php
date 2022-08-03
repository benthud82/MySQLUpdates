<a href="../heatmap_logic/functions/funct.php"></a>
<?php
require_once '../globalincludes/usa_asys.php';
require_once '../global_dash/connections/connection.php';
require '../heatmap_logic/functions/funct.php';

$today = date('Ymd');
$formatted_end = date('Ymd', strtotime($today . ' + 20 days'));
$formatted_start = date('Ymd', strtotime($today . ' - 2 days'));
$mysqltable = 'todaypage';
$schema = 'nahsi';

$columns = 'today_whse, today_appt, today_type, today_sched_date, today_pallets, today_po_count, today_carton_count, today_fridge_count, today_drug_count';
$updatecols = array('today_sched_date', 'today_pallets', 'today_po_count', 'today_carton_count', 'today_fridge_count', 'today_drug_count');
$arraychunk = 1000;
$whsearray = array(7,2, 3, 6, 9);

foreach ($whsearray as $whse) {
    switch ($whse) {
        case 2:
            $var_whse = '02';
            break;
        case 3:
            $var_whse = '03';
            break;
        case 6:
            $var_whse = '06';
            break;
        case 7:
            $var_whse = '07';
            break;
        case 9:
            $var_whse = '09';
            break;

        default:
            break;
    }


    $sql_doors_ltl = $conn1->prepare("SELECT 
                                descartes_doornum, descartes_loadtype
                            FROM
                                nahsi.descartes_loadtype
                            WHERE
                                descartes_loadtype = 'LTL'
                                and descartes_dc = $whse");
    $sql_doors_ltl->execute();
    $array_doors_ltl = $sql_doors_ltl->fetchAll(pdo::FETCH_ASSOC);

    $door_ltl = "('" . implode("','", array_column($array_doors_ltl, "descartes_doornum")) . "')";
    $sql_ltl = $aseriesconn->prepare("SELECT DISTINCT
                                            $whse                       as TODAY_WHSE,                                           
                                            DADAPPNB                    as TODAY_APPT,
                                            'LTL'                       as TODAY_TYPE, 
                                            substring(D.DADRASDT,1,8)   as TODAY_SCHED_DATE,
                                            min(DADTOTPL)               as TODAY_PALLETS,
                                            count(DISTINCT DADPONUM)    as TODAY_PO_COUNT,
                                            min(DADTOTCT)               as TODAY_CARTON_COUNT,
                                            sum
                                                      (
                                                                case
                                                                          when EDSP02 = 'X' or EDSP07 = 'X'
                                                                                    then 1
                                                                                    else 0
                                                                end
                                                      )
                                            as TODAY_FRIDGE_COUNT,
                                                              sum
                                                      (
                                                                case
                                                                          when EDSP03 = 'X'
                                                                                    then 1
                                                                                    else 0
                                                                end
                                                      )
                                            as TODAY_DRUG_COUNT
                                   FROM
                                        HSIPCORDTA.HWFDAD D
                                        LEFT JOIN
                                                  HSIPCORDTA.NPFERD
                                                  on
                                                            DADPONUM = EDPONM
                                   WHERE
                                            D.DADDOOR# in $door_ltl
                                            and substring(D.DADDCCOD,1,2) = '$var_whse'
                                            and substring(D.DADRASDT,1,8) between '$formatted_start' and '$formatted_end'
                                   GROUP BY
                                            substring(D.DADRASDT,1,8) ,
                                            D.DADAPPNB
                                   ORDER BY
                                            substring(D.DADRASDT,1,8)");
    $sql_ltl->execute();
    $array_ltl = $sql_ltl->fetchAll(pdo::FETCH_ASSOC);

    foreach ($array_ltl as $key => $value) {
        $array_ltl[$key]['TODAY_SCHED_DATE'] = date('Y-m-d', strtotime($array_ltl[$key]['TODAY_SCHED_DATE']));      
    }

    //insert into table for LTL loads
    pdoMultiInsert_duplicate($mysqltable, $schema, $array_ltl, $conn1, $arraychunk, $updatecols);


    $sql_doors_bulk = $conn1->prepare("SELECT 
                                descartes_doornum, descartes_loadtype
                            FROM
                                nahsi.descartes_loadtype
                            WHERE
                                descartes_loadtype = 'BULK'
                                and descartes_dc = $whse");
    $sql_doors_bulk->execute();
    $array_doors_bulk = $sql_doors_bulk->fetchAll(pdo::FETCH_ASSOC);

    $door_bulk = "('" . implode("','", array_column($array_doors_bulk, "descartes_doornum")) . "')";
    $sql_bulk = $aseriesconn->prepare("SELECT DISTINCT
                                            $whse                       as TODAY_WHSE,                                           
                                            DADAPPNB                    as TODAY_APPT,
                                            'BULK'                      as TODAY_TYPE, 
                                            substring(D.DADRASDT,1,8)   as TODAY_SCHED_DATE,
                                            min(DADTOTPL)               as TODAY_PALLETS,
                                            count(DISTINCT DADPONUM)    as TODAY_PO_COUNT,
                                            min(DADTOTCT)               as TODAY_CARTON_COUNT,
                                            sum
                                                      (
                                                                case
                                                                          when EDSP02 = 'X' or EDSP07 = 'X'
                                                                                    then 1
                                                                                    else 0
                                                                end
                                                      )
                                            as TODAY_FRIDGE_COUNT,
                                                              sum
                                                      (
                                                                case
                                                                          when EDSP03 = 'X'
                                                                                    then 1
                                                                                    else 0
                                                                end
                                                      )
                                            as TODAY_DRUG_COUNT
                                   FROM
                                        HSIPCORDTA.HWFDAD D
                                        LEFT JOIN
                                                  HSIPCORDTA.NPFERD
                                                  on
                                                            DADPONUM = EDPONM
                                   WHERE
                                            D.DADDOOR# in $door_bulk
                                            and substring(D.DADDCCOD,1,2) = '$var_whse'
                                            and substring(D.DADRASDT,1,8) between '$formatted_start' and '$formatted_end'
                                   GROUP BY
                                            substring(D.DADRASDT,1,8) ,
                                            D.DADAPPNB
                                   ORDER BY
                                            substring(D.DADRASDT,1,8)");
    $sql_bulk->execute();
    $array_bulk = $sql_bulk->fetchAll(pdo::FETCH_ASSOC);

    foreach ($array_bulk as $key => $value) {
        $array_bulk[$key]['TODAY_SCHED_DATE'] = date('Y-m-d', strtotime($array_bulk[$key]['TODAY_SCHED_DATE']));
    }

    //insert into table for LTL loads
    pdoMultiInsert_duplicate($mysqltable, $schema, $array_bulk, $conn1, $arraychunk, $updatecols);
}