<?php

if (isset($whssel)) {
    $whsefilter = ' and LOWHSE = ' . $whssel;
} else {
    $whsefilter = '';
}

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
//include_once '../globalincludes/usa_asys.php';
//include_once '../globalincludes/newcanada_asys.php';
include_once '../globalfunctions/slottingfunctions.php';
include_once 'sql_dailypick_case.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);


if ($whssel == 3) {
    $OPT_BUILDING = intval(2);
} else {
    $OPT_BUILDING = intval(1);
}

include_once 'case_standardizedbays.php';  //pulls in count of bays and zone designation by aisle.  Can obtain pallet/deck count from count of bays.  Not stored implicitly in table.


if (count($casestandardbays_palletsarray) > 0) {

    if (isset($whssel)) {
        $sqldelete = "DELETE FROM slotting.optimalbay WHERE OPT_WHSE = $whssel and OPT_CSLS not in ('LSE', 'INP')";
    } else {
        $sqldelete = "TRUNCATE TABLE slotting.optimalbay";
    }
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    $ppc = $conn1->prepare("SELECT 
                                WAREHOUSE as OPT_WHSE,
                                ITEM_NUMBER as OPT_ITEM,
                                PACKAGE_UNIT as OPT_PKGU,
                                -- CASE WHEN LMTIER = ' ' then 'PFR' else CUR_LOCATION end as OPT_LOC,
                                CUR_LOCATION as OPT_LOC,
                                AVGD_BTW_SLE as OPT_ADBS,
                                PACKAGE_TYPE as OPT_CSLS,
                                case
                                    when (A.CPCELEN * A.CPCEHEI * A.CPCEWID) > 0 then (A.CPCELEN * A.CPCEHEI * A.CPCEWID)
                                    else (A.CPCCLEN * A.CPCCHEI * A.CPCCWID)
                                end as OPT_CUBE,
                                CASE WHEN LMTIER = ' ' then 'PFR' else LMTIER end as OPT_CURTIER,
                                SUGGESTED_TIER as OPT_TOTIER,
                                SUGGESTED_GRID5 as OPT_NEWGRID,
                                SUGGESTED_DEPTH as OPT_NDEP,
                                PICK_QTY_MN as OPT_AVGPICK,
                                $sql_dailypick_case as OPT_DAILYPICKS,
                                case when SUGGESTED_GRID5 = 'C_PFR' then 0 else SUGGESTED_NEWLOCVOL end as OPT_NEWGRIDVOL,
                                case when SUGGESTED_GRID5 = 'C_PFR' then 0 else ($sql_dailypick_case) / (SUGGESTED_NEWLOCVOL) * 1000 end as OPT_PPCCALC,
                                D.FLOOR,
                                case
                                    when LMTIER in ('C01' , 'C02') then 1
                                            when LMTIER = ' ' then 999
                                            when CUR_LOCATION between 'W200000' and 'W390000' and WAREHOUSE = 3 then 999
                                    else S.case_standardizedbays_zone
                                end as OPT_CURRBAY,
                                exclude_type
                            FROM
                                slotting.my_npfmvc A
                             JOIN
                                slotting.npfcpcsettings C ON C.CPCWHSE = A.WAREHOUSE
                                    AND C.CPCITEM = A.ITEM_NUMBER
                             LEFT JOIN
                                slotting.case_floor_locs D ON D.WHSE = A.WAREHOUSE
                                    AND D.LOCATION = A.CUR_LOCATION
                             LEFT JOIN
                                slotting.case_standardizedbays S ON A.WAREHOUSE = S.case_standardizedbays_whse
                                    and substring(A.CUR_LOCATION, 1, 3) = case_standardizedbays_aisle
                             LEFT JOIN slotting.slotting_exclusions on exclude_whse = A.WAREHOUSE and exclude_item = A.ITEM_NUMBER
                            WHERE
                                WAREHOUSE = $whssel
                                    and PACKAGE_TYPE not in ('LSE', 'INP')
                                    and SUGGESTED_GRID5 <> ' '
                                    and $sql_dailypick_case >= 0  -- added = sign because some item were being excluded on 10-19-16
                            ORDER BY ($sql_dailypick_case) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) DESC, $sql_dailypick_case desc");
    $ppc->execute();
    $ppcarray = $ppc->fetchAll(pdo::FETCH_ASSOC);

    $conn1 = null;

    $columns = 'OPT_WHSE, OPT_ITEM, OPT_PKGU, OPT_LOC, OPT_ADBS, OPT_CSLS, OPT_CUBE, OPT_CURTIER, OPT_TOTIER, OPT_NEWGRID, OPT_NDEP, OPT_AVGPICK, OPT_DAILYPICKS, OPT_NEWGRIDVOL, OPT_PPCCALC, OPT_OPTBAY, OPT_CURRBAY, OPT_CURRDAILYFT, OPT_SHLDDAILYFT, OPT_ADDTLFTPERPICK, OPT_ADDTLFTPERDAY, OPT_WALKCOST, OPT_BUILDING';
    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($ppcarray);

    $palletkey = $deckkey = $palletkeydog = $deckkeydog = 0;
    $palletkeycount = count($casestandardbays_palletsarray);
    $deckkeycount = count($casestandardbays_decksarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) {
            $OPT_TOTIER = $ppcarray[$counter]['OPT_TOTIER'];
            $OPT_WHSE = intval($ppcarray[$counter]['OPT_WHSE']);
            $OPT_ITEM = intval($ppcarray[$counter]['OPT_ITEM']);
            $OPT_PKGU = intval($ppcarray[$counter]['OPT_PKGU']);
            $OPT_LOC = $ppcarray[$counter]['OPT_LOC'];
            $OPT_ADBS = intval($ppcarray[$counter]['OPT_ADBS']);
            $OPT_CSLS = $ppcarray[$counter]['OPT_CSLS'];
            $OPT_CUBE = intval($ppcarray[$counter]['OPT_CUBE']);
            $OPT_CURTIER = $ppcarray[$counter]['OPT_CURTIER'];
            $OPT_NEWGRID = $ppcarray[$counter]['OPT_NEWGRID'];
            $OPT_NDEP = intval($ppcarray[$counter]['OPT_NDEP']);
            $OPT_AVGPICK = number_format($ppcarray[$counter]['OPT_AVGPICK'],2);
            $OPT_DAILYPICKS = number_format($ppcarray[$counter]['OPT_DAILYPICKS'],2);
            $OPT_NEWGRIDVOL = intval($ppcarray[$counter]['OPT_NEWGRIDVOL']);
            $OPT_PPCCALC = str_replace(",", "", $ppcarray[$counter]['OPT_PPCCALC']);
            $OPT_FLOOR = $ppcarray[$counter]['FLOOR'];
            $OPT_CURRBAY = intval($ppcarray[$counter]['OPT_CURRBAY']);

            //optimal bay logic
            if ($OPT_TOTIER == 'C01' || $OPT_TOTIER == 'C02') {
                $OPT_OPTBAY = 1;
            } elseif ($OPT_TOTIER == 'CSE_NONCON') {
                //for sparks, determine building
                if ($ppcarray[$counter]['exclude_type'] == 'mainbuilding') {
                    $OPT_BUILDING = intval(1);
                }
                $OPT_OPTBAY = 99;
            } elseif ($OPT_NEWGRID == 'C_PFR') {
                if ($ppcarray[$counter]['exclude_type'] == 'mainbuilding') {
                    $OPT_BUILDING = intval(1);
                }
                $OPT_OPTBAY = 999;
            } elseif ($OPT_NEWGRID == '58P48') {
                //account for 2 buildings in Sparks.  If all locations in case have been used, flip to main building.
                if ($palletkey == $palletkeycount || $ppcarray[$counter]['exclude_type'] == 'mainbuilding') {  //if this is true, all pallets have been exhausted in case building
                    $OPT_OPTBAY = $casestandardbays_dogs_palletsarray[$palletkeydog]['case_standardizedbays_zone'];
                    $OPT_BUILDING = intval(1);
                    $casestandardbays_dogs_palletsarray[$palletkeydog]['case_standardizedbays_pallets'] -= 1;  //subtract used grid from array as no longer available
                    if ($casestandardbays_dogs_palletsarray[$palletkeydog]['case_standardizedbays_pallets'] <= 0) {
                        $palletkeydog +=1;
                    }
                } else {


                    $OPT_OPTBAY = $casestandardbays_palletsarray[$palletkey]['case_standardizedbays_zone'];
                    $casestandardbays_palletsarray[$palletkey]['case_standardizedbays_pallets'] -= 1;  //subtract used grid from array as no longer available
                    if ($casestandardbays_palletsarray[$palletkey]['case_standardizedbays_pallets'] <= 0) {
                        $palletkey +=1;
                    }
                }
            } elseif ($OPT_NEWGRID == '28D24') {

                //account for 2 buildings in Sparks.  If all locations in case have been used, flip to main building.
                if ($deckkey == $deckkeycount || $ppcarray[$counter]['exclude_type'] == 'mainbuilding') {  //if this is true, all decks have been exhausted in case building
                    $OPT_OPTBAY = $casestandardbays_dogs_decksarray[$deckkeydog]['case_standardizedbays_zone'];
                    $OPT_BUILDING = intval(1);
                    $casestandardbays_dogs_decksarray[$deckkeydog]['case_standardizedbays_decks'] -= 1;  //subtract used grid from array as no longer available
                    if ($casestandardbays_dogs_decksarray[$deckkeydog]['case_standardizedbays_decks'] <= 0) {
                        $deckkeydog +=1;
                    }
                } else {

                    $OPT_OPTBAY = $casestandardbays_decksarray[$deckkey]['case_standardizedbays_zone'];
                    $casestandardbays_decksarray[$deckkey]['case_standardizedbays_decks'] -= 1;  //subtract used grid from array as no longer available
                    if ($casestandardbays_decksarray[$deckkey]['case_standardizedbays_decks'] <= 0) {
                        $deckkey+=1;
                    }
                }
            } else {
                $OPT_OPTBAY = 9999;
            }

            $OPT_OPTBAY = intval($OPT_OPTBAY);

            $walkcostarray = _walkcost_case($OPT_CURTIER, $OPT_TOTIER, $OPT_DAILYPICKS, $OPT_FLOOR);

            $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
            $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
            $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
            $OPT_ADDTLFTPERDAY = number_format($walkcostarray['ADDTL_FT_PER_DAY'],2);
            $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
            $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, '$OPT_AVGPICK', '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, $OPT_BUILDING)";
            $counter +=1;

            //Change back to building 2 for sparks
            if ($whssel == 3) {
                $OPT_BUILDING = intval(2);
            }
        }

        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
     include '../connections/conn_slotting.php';
        $sql = "INSERT IGNORE INTO slotting.optimalbay ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $conn1 = null;
        $maxrange +=4000;
    } while ($counter <= $rowcount);
}
//update history table
include '../connections/conn_slotting.php';
$sql_hist = "INSERT IGNORE INTO slotting.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
                 SELECT OPT_WHSE, OPT_CURTIER, CURDATE(), substring(OPT_LOC,1,5) as BAY, sum(OPT_DAILYPICKS), avg(ABS(OPT_WALKCOST)), count(OPT_ITEM) FROM slotting.optimalbay GROUP BY OPT_WHSE, OPT_CURTIER, CURDATE(), substring(OPT_LOC,1,5);";
$query_hist = $conn1->prepare($sql_hist);
$query_hist->execute();

$conn1 = null;
