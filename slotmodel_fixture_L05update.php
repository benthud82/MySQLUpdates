<?php

$post_array = [];
$JAX_ENDCAP = 0;
$slowdownsizecutoff = 999999;  //min ADBS to only stock to 2 ship occurences as Max.  Not used right now till capacity is determined
require_once 'funct_array_column.php';

include '../connections/conn_slotting.php';

//build the $L02GridsArray based off selection
$imploded_L05_grids = '("' . implode('" , "', str_replace('grid_L05_', '', $array_checked_grids_L05)) . '")';

$sql_L05grids = $conn1->prepare("SELECT 
                                    sm_grids_grid5 AS LMGRD5,
                                    sm_grids_high AS LMHIGH,
                                    sm_grids_deep AS LMDEEP,
                                    sm_grids_wide AS LMWIDE,
                                    sm_grids_vol AS LMVOL9,
                                    sm_grids_count AS GRID_COUNT
                                FROM
                                    slotting.slotmodel_grids
                                WHERE
                                    sm_grids_whse = $whssel
                                        AND sm_grids_tier = 'L05' and sm_grids_grid5 in $imploded_L05_grids
                                ORDER BY sm_grids_vol asc");
$sql_L05grids->execute();
$L05GridsArray = $sql_L05grids->fetchAll(pdo::FETCH_ASSOC);

$L05sql = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    A.DSL_TYPE,
    L.location AS LMLOC,
    A.DAYS_FRM_SLE,
    A.AVGD_BTW_SLE,
    A.AVG_INV_OH,
    A.NBR_SHIP_OCC,
    A.PICK_QTY_MN,
    A.PICK_QTY_SD,
    A.SHIP_QTY_MN,
    A.SHIP_QTY_SD,
    I.item_type AS ITEM_TYPE,
    item_eapkgu AS CPCEPKU,
    item_ippkgu AS CPCIPKU,
    item_capkgu AS CPCCPKU,
    item_okflow AS CPCFLOW,
    'Y' AS CPCTOTE,
    item_okshelf AS CPCSHLF,
    item_okrotate AS CPCROTA,
    item_stacklim AS CPCESTK,
    item_liquid AS CPCLIQU,
    item_ealength AS CPCELEN,
    item_eaheight AS CPCEHEI,
    item_eawidth AS CPCEWID,
    item_calength AS CPCCLEN,
    item_caheight AS CPCCHEI,
    item_cawidth AS CPCCWID,
    loc_fixt AS LMFIXA,
    loc_storage AS LMFIXT,
    loc_storage AS LMSTGT,
    grid_useheight AS LMHIGH,
    grid_uselength AS LMDEEP,
    grid_usewidth AS LMWIDE,
    (grid_useheight * grid_uselength * grid_usewidth) AS LMVOL9,
    loc_tier AS LMTIER,
    loc_grid AS LMGRD5,
    itemloc_max AS CURMAX,
    itemloc_min AS CURMIN,
    CASE
        WHEN item_ealength * item_eaheight * item_eawidth > 0 THEN ((A.TRUE_SLS_MN) * item_ealength * item_eaheight * item_eawidth)
        ELSE (A.TRUE_SLS_MN) * item_calength * item_caheight * item_cawidth / item_capkgu
    END AS DLY_CUBE_VEL,
    CASE
        WHEN item_ealength * item_eaheight * item_eawidth > 0 THEN ((A.TRUE_PCK_MN) * item_ealength * item_eaheight * item_eawidth)
        ELSE (A.TRUE_PCK_MN) * item_calength * item_caheight * item_cawidth / item_capkgu
    END AS DLY_PICK_VEL,
    PERC_SHIPQTY,
    PERC_PERC,
    A.TRUE_PCK_MN AS DAILYPICK,
    A.TRUE_SLS_MN AS DAILYUNIT,
    CASE
        WHEN A.AVGD_BTW_SLE < 2 THEN 'A'
        WHEN A.AVGD_BTW_SLE < 4 THEN 'B'
        WHEN A.AVGD_BTW_SLE < 7 THEN 'C'
        ELSE 'D'
    END AS ITEM_MC
FROM
    nahsi.items I
        JOIN
    nahsi.demand A ON warehouse = item_whse
        AND BUILDING = item_build
        AND item = ITEM_NUMBER
        AND PACKAGE_UNIT = item_eapkgu
        JOIN
    nahsi.item_locs IL ON IL.itemloc_whse = item_whse
        AND IL.itemloc_build = item_build
        AND IL.item = I.item
        JOIN
    nahsi.locations L ON L.loc_whse = item_whse
        AND L.loc_build = item_build
        AND IL.location = L.location
        LEFT JOIN
    nahsi.grids ON grid_whse = item_whse
        AND grid_build = item_build
        AND grid_tier = loc_tier
        AND loc_grid = grid
        AND loc_griddep = grid_length
        JOIN
    slotting.pkgu_percent E ON E.PERC_WHSE = WAREHOUSE
        AND E.PERC_ITEM = ITEM_NUMBER
        AND E.PERC_PKGU = PACKAGE_UNIT
        AND E.PERC_PKGTYPE = PACKAGE_TYPE
        LEFT JOIN
    nahsi.slotmodel_fixture_my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
        AND F.ITEM_NUMBER = A.ITEM_NUMBER
        AND F.PACKAGE_TYPE = A.PACKAGE_TYPE
        AND F.PACKAGE_UNIT = A.PACKAGE_UNIT
WHERE
    A.WAREHOUSE = $whssel
        AND A.PACKAGE_TYPE = 'LSE'
        AND L.location NOT LIKE 'D%'
        AND L.location NOT LIKE 'Y04%'
        AND A.DSL_TYPE NOT IN (1 , 2, 4)
        AND I.item_type = 'ST'
        AND A.NBR_SHIP_OCC >= 4
        and loc_tier like ('L%')
        AND A.AVG_INV_OH > 0
        AND F.ITEM_NUMBER IS NULL
ORDER BY A.TRUE_PCK_MN DESC;");

$L05sql->execute();
$L05array = $L05sql->fetchAll(pdo::FETCH_ASSOC);

foreach ($L05array as $key => $value) {

    $ITEM_NUMBER = intval($L05array[$key]['ITEM_NUMBER']);
    $PKGU_PERC_Restriction = $L05array[$key]['PERC_PERC'];

    $var_AVGSHIPQTY = $L05array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L05array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($L05array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $L05array[$key]['CPCLIQU'];

    $var_PCEHEIin = $L05array[$key]['CPCEHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $L05array[$key]['CPCCHEI'] * 0.393701;
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $L05array[$key]['CPCELEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $L05array[$key]['CPCCLEN'] * 0.393701;
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $L05array[$key]['CPCEWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $L05array[$key]['CPCCWID'] * 0.393701;
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_eachqty = $L05array[$key]['CPCEPKU'];
    if ($var_eachqty == 0) {
        $var_eachqty = 1;
    }

    //for drawers, slot to average inventory plus 20% excess
    if ($var_AVGINV == 0) {
        $slotqty = 1;
    } else {
        $slotqty = intval(ceil($var_AVGINV * (1 + $L05_stockbuffer) * $PKGU_PERC_Restriction));
    }


    //loop through available L05 grids to determine smallest location to accomodate slot quantity
    foreach ($L05GridsArray as $key2 => $value) {
        $var_grid5 = $L05GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L05GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L05GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L05GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L05GridsArray[$key2]['LMVOL9'];

        //Call the case true fit for L04
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= $slotqty) {
            $L05GridsArray[$key2]['GRID_COUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L05GridsArray[$key2]['GRID_COUNT'] <= 0) {
                unset($L05GridsArray[$key2]);
                $L05GridsArray = array_values($L05GridsArray);  //reset array
            }
            break;
        }
    }

    if ($SUGGESTED_MAX_test < $slotqty) {
        $skippedkeycount += 1;
        unset($L05array[$key]);
        continue;
    }




    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

    //append data to array for writing to my_npfmvc table
    $L05array[$key]['SUGGESTED_TIER'] = 'L05';
    $L05array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L05array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L05array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L05array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L05array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L05array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
    $L05array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L05array[$key]['CURMAX'], $L05array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
    $L05array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L05array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

    if (count($L05GridsArray) <= 0) {
        break;
    }
}


//L04 items have been designated.  Loop through L04 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L05array, ($key - $skippedkeycount));

$L05array = array_values($L05array);  //reset array



$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L05array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($L05array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L05array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L05array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L05array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $L05array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $L05array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L05array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($L05array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L05array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L05array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L05array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L05array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L05array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L05array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $L05array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($L05array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($L05array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($L05array[$counter]['CPCCPKU']);
        $CPCFLOW = $L05array[$counter]['CPCFLOW'];
        $CPCTOTE = $L05array[$counter]['CPCTOTE'];
        $CPCSHLF = $L05array[$counter]['CPCSHLF'];
        $CPCROTA = $L05array[$counter]['CPCROTA'];
        $CPCESTK = intval($L05array[$counter]['CPCESTK']);
        $CPCLIQU = $L05array[$counter]['CPCLIQU'];
        $CPCELEN = $L05array[$counter]['CPCELEN'];
        $CPCEHEI = $L05array[$counter]['CPCEHEI'];
        $CPCEWID = $L05array[$counter]['CPCEWID'];
        $CPCCLEN = $L05array[$counter]['CPCCLEN'];
        $CPCCHEI = $L05array[$counter]['CPCCHEI'];
        $CPCCWID = $L05array[$counter]['CPCCWID'];
        $LMFIXA = $L05array[$counter]['LMFIXA'];
        $LMFIXT = $L05array[$counter]['LMFIXT'];
        $LMSTGT = $L05array[$counter]['LMSTGT'];
        $LMHIGH = intval($L05array[$counter]['LMHIGH']);
        $LMDEEP = intval($L05array[$counter]['LMDEEP']);
        $LMWIDE = intval($L05array[$counter]['LMWIDE']);
        $LMVOL9 = intval($L05array[$counter]['LMVOL9']);
        $LMTIER = $L05array[$counter]['LMTIER'];
        $LMGRD5 = $L05array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = intval($L05array[$counter]['DLY_CUBE_VEL']);
        $DLY_PICK_VEL = intval($L05array[$counter]['DLY_PICK_VEL']);
        $SUGGESTED_TIER = $L05array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L05array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L05array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L05array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L05array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L05array[$counter]['SUGGESTED_SLOTQTY']);
        $ITEM_MC = ($L05array[$counter]['ITEM_MC']);

        $SUGGESTED_IMPMOVES = ($L05array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($L05array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($L05array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($L05array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L05array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L05array[$counter]['DAILYUNIT'];
        if ($LMTIER == 'L01' || $LMTIER == 'L15') {
            $VCBAY = $CUR_LOCATION;
        } else if ($LMTIER == 'L05' && $WAREHOUSE == 3) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '12';
        } else if ($LMTIER == 'L05') {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '01';
        } else {
            $VCBAY = substr($CUR_LOCATION, 0, 5);
        }
        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP,'$ITEM_MC')";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO nahsi.slotmodel_fixture_my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $maxrange += 1000;
} while ($counter <= $rowcount);

