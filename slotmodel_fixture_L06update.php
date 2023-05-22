<?php

$JAX_ENDCAP = 0;

require_once 'funct_array_column.php';

$LSEpicksSQL = $conn1->prepare("SELECT 
                                    SUM(TRUE_PCK_MN) as TOTPICKS
                                FROM
                                    nahsi.demand
                                WHERE
                                    warehouse = $whssel AND PACKAGE_TYPE = 'LSE'");
$LSEpicksSQL->execute();

$LSEpicksArray = $LSEpicksSQL->fetchAll(pdo::FETCH_ASSOC);
$LSE_Picks = intval($LSEpicksArray[0]['TOTPICKS']);
$Max_L06_picks = $L06_pick_limit * $LSE_Picks;  //maximum number of picks to reside in L06 based off daily pick forecast
//Pull in available L06 Grid5s by volume ascending order

include '../connections/conn_slotting.php';

//build the $L04GridsArray based off selection
$imploded_L06_grids = '("' . implode('" , "', str_replace('grid_L04_', '', $array_checked_grids_L04)) . '")';

//use the same grids as L04 for the L06 area
$sql_L06grids = $conn1->prepare("SELECT 
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
                                        AND sm_grids_tier = 'L04' and sm_grids_grid5 in $imploded_L06_grids
                                ORDER BY sm_grids_vol asc");
$sql_L06grids->execute();
$L06GridsArray = $sql_L06grids->fetchAll(pdo::FETCH_ASSOC);

$max_grid_vol = 0;
foreach ($L06GridsArray as $item) {
    $LMVOL9 = $item["LMVOL9"];
    if ($LMVOL9 > $max_grid_vol) {
        $max_grid_vol = $LMVOL9;
    }
}

$L06sql = $conn1->prepare(" SELECT 
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
    'N' AS CASETF
FROM
    nahsi.items I
        JOIN
    nahsi.demand A ON A.warehouse = item_whse
        AND A.BUILDING = item_build
        AND item = A.ITEM_NUMBER
        AND A.PACKAGE_UNIT = item_eapkgu
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
    slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
        AND E.PERC_ITEM = A.ITEM_NUMBER
        AND E.PERC_PKGU = A.PACKAGE_UNIT
        AND E.PERC_PKGTYPE = A.PACKAGE_TYPE
        LEFT JOIN
    nahsi.slotmodel_fixture_my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
        AND F.ITEM_NUMBER = A.ITEM_NUMBER
        AND F.PACKAGE_TYPE = A.PACKAGE_TYPE
        AND F.PACKAGE_UNIT = A.PACKAGE_UNIT
WHERE
    A.warehouse = 7
        AND A.PACKAGE_TYPE = 'LSE'
        AND L.location <> 'PFR'
        AND I.item_type = 'ST'
        and loc_tier like 'L%' 
        AND F.ITEM_NUMBER IS NULL
ORDER BY A.TRUE_PCK_MN ASC");

$L06sql->execute();
$L06array = $L06sql->fetchAll(pdo::FETCH_ASSOC);

$running_L06_picks = 0; //initilize picks
$running_L06_volume = 0; //initialize volume variable
foreach ($L06array as $key => $value) {
    if ($running_L06_picks >= $Max_L06_picks || $running_L06_volume >= $L06Vol) {
        break;  //if exceeded pre-determined max picks from L06
    }

    // <editor-fold desc="Variable Assignment">
    $var_OKINSHLF = $L06array[$key]['CPCSHLF'];

    $var_AVGSHIPQTY = $L06array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L06array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($L06array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }

    $avgdailypickqty = $L06array[$key]['DAILYPICK'];

    $var_PCLIQU = $L06array[$key]['CPCLIQU'];

    $var_PCEHEIin = $L06array[$key]['CPCEHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $L06array[$key]['CPCCHEI'] * 0.393701;
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $L06array[$key]['CPCELEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $L06array[$key]['CPCCLEN'] * 0.393701;
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $L06array[$key]['CPCEWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $L06array[$key]['CPCCWID'] * 0.393701;
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_eachqty = $L06array[$key]['CPCEPKU'];
    if ($var_eachqty == 0) {
        $var_eachqty = 1;
    }

    $PKGU_PERC_Restriction = $L06array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($L06array[$key]['ITEM_NUMBER']);
// </editor-fold>
    // <editor-fold desc="L06 Slot Qty Calc">
    $slotqty = intval(ceil($var_AVGINV * $PKGU_PERC_Restriction));
    if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
        $slotqty = 1;
    }
    // </editor-fold>
    // <editor-fold desc="Grid Assignment - True Fit">
    $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

    if ($totalslotvol > $max_grid_vol) {
        //too much inventory onhand.  Reduce the slot qty to 50% of the largest grid size. This should be about a half shelf worth of product.
        $slotqty = intval(($max_grid_vol * .5) / ($var_PCEHEIin * $var_PCELENin * $var_PCEWIDin));
        $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;
    }

    //loop through available L06 grids to determine smallest location to accomodate slot quantity
    foreach ($L06GridsArray as $key2 => $value) {
        //if total slot volume is less than location volume, then continue
        if ($totalslotvol > $L06GridsArray[$key2]['LMVOL9']) {
            continue;
        }

        $var_grid5 = $L06GridsArray[$key2]['LMGRD5'];
        if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
            continue;
        }
        $var_gridheight = $L06GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L06GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L06GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L06GridsArray[$key2]['LMVOL9'];

        //Call the  true fit for L06
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= $slotqty) {
            $lastusedgrid5 = $var_grid5;
            break;
        }
        $lastusedgrid5 = $var_grid5;
    }

    //</editor-fold>
    // <editor-fold desc="Set Min/Max">
    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));
    if ($SUGGESTED_MIN == 0) {
        $SUGGESTED_MIN = 1;
    }
    //</editor-fold>
    // <editor-fold desc="Append Variables to Main Array">
    $L06array[$key]['SUGGESTED_TIER'] = 'L06';
    $L06array[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
    $L06array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L06array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L06array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L06array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L06array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L06array[$key]['CURMAX'], $L06array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L06array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);
    $L06array[$key]['ITEM_MC'] = 'D';

    $running_L06_picks += $avgdailypickqty;
    $running_L06_volume += $var_locvol;
    // </editor-fold>
}



//L06 items have been designated.  Loop through L06 array to add to slotmodel_my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L06array, ($key));

$L06array = array_values($L06array);  //reset array
// <editor-fold desc="Write to table slotting.slotmodel_my_npfmvc">
$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L06array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table slotmodel_my_npfmvc
        if (!isset($L06array[$counter]['WAREHOUSE'])) {
            $counter += 1;
            continue;
        }
        $WAREHOUSE = intval($L06array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L06array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L06array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L06array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $L06array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $L06array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L06array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($L06array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L06array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L06array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L06array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L06array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L06array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L06array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $L06array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($L06array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($L06array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($L06array[$counter]['CPCCPKU']);
        $CPCFLOW = $L06array[$counter]['CPCFLOW'];
        $CPCTOTE = $L06array[$counter]['CPCTOTE'];
        $CPCSHLF = $L06array[$counter]['CPCSHLF'];
        $CPCROTA = $L06array[$counter]['CPCROTA'];
        $CPCESTK = intval($L06array[$counter]['CPCESTK']);
        $CPCLIQU = $L06array[$counter]['CPCLIQU'];
        $CPCELEN = $L06array[$counter]['CPCELEN'];
        $CPCEHEI = $L06array[$counter]['CPCEHEI'];
        $CPCEWID = $L06array[$counter]['CPCEWID'];
        $CPCCLEN = $L06array[$counter]['CPCCLEN'];
        $CPCCHEI = $L06array[$counter]['CPCCHEI'];
        $CPCCWID = $L06array[$counter]['CPCCWID'];
        $LMFIXA = $L06array[$counter]['LMFIXA'];
        $LMFIXT = $L06array[$counter]['LMFIXT'];
        $LMSTGT = $L06array[$counter]['LMSTGT'];
        $LMHIGH = intval($L06array[$counter]['LMHIGH']);
        $LMDEEP = intval($L06array[$counter]['LMDEEP']);
        $LMWIDE = intval($L06array[$counter]['LMWIDE']);
        $LMVOL9 = intval($L06array[$counter]['LMVOL9']);
        $LMTIER = $L06array[$counter]['LMTIER'];
        $LMGRD5 = $L06array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = intval($L06array[$counter]['DLY_CUBE_VEL']);
        $DLY_PICK_VEL = intval($L06array[$counter]['DLY_PICK_VEL']);
        $SUGGESTED_TIER = $L06array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L06array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L06array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L06array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L06array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L06array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = number_format($L06array[$counter]['SUGGESTED_IMPMOVES'], 4);
        $CURRENT_IMPMOVES = number_format($L06array[$counter]['CURRENT_IMPMOVES'], 4);
        $SUGGESTED_NEWLOCVOL = intval($L06array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($L06array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L06array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L06array[$counter]['DAILYUNIT'];
        $ITEM_MC = $L06array[$counter]['ITEM_MC'];

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
    //include '../CustomerAudit/connection/connection_details.php';
    $sql = "INSERT IGNORE INTO nahsi.slotmodel_fixture_my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $maxrange += 1000;
} while ($counter <= $rowcount);

// </editor-fold>