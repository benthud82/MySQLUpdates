<?php

$post_array = [];
$JAX_ENDCAP = 0;
$slowdownsizecutoff = 999999;  //min ADBS to only stock to 2 ship occurences as Max.  Not used right now till capacity is determined
require_once 'funct_array_column.php';

$skippedkeycount = 0;
include '../connections/conn_slotting.php';

//Delete Restricted flow Locs
$SQLDelete = $conn1->prepare("DELETE FROM slotting.items_restricted WHERE REST_WHSE = $whssel and REST_SHOULD = 'FLOW'");
$SQLDelete->execute();

//build the $L02GridsArray based off selection
$imploded_L02_grids = '("' . implode('" , "', str_replace('grid_L02_', '', $array_checked_grids_L02)) . '")';

$sql_L02grids = $conn1->prepare("SELECT 
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
                                        AND sm_grids_tier = 'L02' and sm_grids_grid5 in $imploded_L02_grids
                                ORDER BY sm_grids_vol asc");
$sql_L02grids->execute();

$L02GridsArray = $sql_L02grids->fetchAll(pdo::FETCH_ASSOC);

// <editor-fold desc="L02 Main Data Pull">
$L02sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                D.LMLOC,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                 case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                JOIN
                                    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                and D.LMITEM = A.ITEM_NUMBER
                                and D.LMPKGU = A.PACKAGE_UNIT
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                    LEFT JOIN
                                slotting.slotmodel_my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    $slotmodel_standard_wheres
                                    and A.AVGD_BTW_SLE <= $L02_min_adbs
                                    and A.DAYS_FRM_SLE <= $L02_min_dsls
                                    and F.ITEM_NUMBER IS NULL
                                  --  and A.ITEM_NUMBER = 1000055
                                    AND (case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end) > $L02_min_cubevel
                                    
                            ORDER BY DLY_CUBE_VEL desc");
$L02sql->execute();
$L02array = $L02sql->fetchAll(pdo::FETCH_ASSOC);
// </editor-fold>
// <editor-fold desc="L02 Main Loop">
foreach ($L02array as $key => $value) {

    // <editor-fold desc="Loop - Variable Defs">
    $var_item = intval($L02array[$key]['ITEM_NUMBER']);

    $var_caseqty = $L02array[$key]['CPCCPKU'];

    //is the require vendor case setting set?
    if ($var_L02_vendorcase == 1 && $var_caseqty == 0) {
        continue;
    }


    $DLY_CUBE_VEL = intval($L02array[$key]['DLY_CUBE_VEL']);

    if ($DLY_CUBE_VEL < $L02_min_cubevel) {
        continue;
    }

    if ($L02Vol < 0) {
        break;  //if all available L02 volume has been used, exit
    }

    //Check OK in Flow Setting
    $var_OKINFLOW = $L02array[$key]['CPCFLOW'];
    if ($var_OKINFLOW == 'N' && $var_L02_okinflow == 1) {

        $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
        $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
        $var_should = 'FLOW';

        //write to table that should have gone to flow and was restricted
        $result2 = $conn1->prepare("INSERT INTO slotting.items_restricted (REST_ID, REST_WHSE, REST_ITEM, REST_PKGU, REST_PKTY, REST_SHOULD) values (0,$whssel, $var_item ,$var_pkgu,'" . $var_pkty . "','" . $var_should . "')");
        $result2->execute();

        $skippedkeycount += 1;
        unset($L02array[$key]);
        continue;
    }

    $var_AVGSHIPQTY = $L02array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = ($L02array[$key]['AVGD_BTW_SLE']);
    $var_AVGINV = intval($L02array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $L02array[$key]['CPCLIQU'];

    $var_PCEHEIin = $L02array[$key]['CPCCHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $L02array[$key]['CPCEHEI'] * 0.393701;
    }

    $var_PCELENin = $L02array[$key]['CPCCLEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $L02array[$key]['CPCELEN'] * 0.393701;
    }

    $var_PCEWIDin = $L02array[$key]['CPCCWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $L02array[$key]['CPCEWID'] * 0.393701;
    }

    //Determine how many days to stock based on ADBS $daystostock
    //For L02 
    //key0-> 1 ADBS $L02_adbs_key0
    //key1-> BTW 1 and 2 ADBS $L02_adbs_key1
    //key2-> BTW 2 and 3 ADBS $L02_adbs_key2
    //key3-> BTW 3 and 4 ADBS $L02_adbs_key3
    //key4-> BTW 4 and 5 ADBS $L02_adbs_key4
    switch (true) {
        case in_array($AVGD_BTW_SLE, range(0, 1)): //the range from range of 0-20:
            $daystostock = $L02_adbs_key0;
            break;
        case in_array($AVGD_BTW_SLE, range(1, 2)): //the range from range of 0-20:
            $daystostock = $L02_adbs_key1;
            break;
        case in_array($AVGD_BTW_SLE, range(2, 3)): //the range from range of 0-20:
            $daystostock = $L02_adbs_key2;
            break;
        case in_array($AVGD_BTW_SLE, range(3, 4)): //the range from range of 0-20:
            $daystostock = $L02_adbs_key3;
            break;
        case in_array($AVGD_BTW_SLE, range(4, 5)): //the range from range of 0-20:
            $daystostock = $L02_adbs_key4;
            break;

        default:
            break;
    }

    $PKGU_PERC_Restriction = $L02array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($L02array[$key]['ITEM_NUMBER']);

    // </editor-fold>
    // <editor-fold desc="Loop - Slot Qty">
    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
        $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];

        //write to table inventory_restricted
        $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
        $result2->execute();
    } else {
        $slotqty = $slotqty_return_array['OPTQTY'];
    }
    // </editor-fold>
    // <editor-fold desc="Loop - True Fit Calc">
    //calculate total slot valume to determine what grid to start
    $totalslotvol = ($slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin) / $var_caseqty;
    $gridkeycount = count($L02GridsArray) - 1; //count to ensure that at least 1 true fit is run
    //loop through available L02 grids to determine smallest location to accomodate slot quantity
    foreach ($L02GridsArray as $key2 => $value) {
        //if total slot volume is less than location volume, then continue
        if ($totalslotvol > $L02GridsArray[$key2]['LMVOL9'] && $gridkeycount <> $key2) {
            continue;
        }

        $var_grid5 = $L02GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L02GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L02GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L02GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L02GridsArray[$key2]['LMVOL9'];

        //Call the case true fit for L02
        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= $slotqty) {
            break;
        }
    }

    //Does slot qty to max of lane satisfy min lane calc requirement
    $pkgu_avail_inv = $PKGU_PERC_Restriction * $var_AVGINV;
    if (($pkgu_avail_inv / $SUGGESTED_MAX_test ) <= $L02_min_lanecalc) {
        continue;
    }

// </editor-fold>
// 
// 
    //<editor-fold desc="Loop - Set Min/Max">
    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));
    // </editor-fold>
    // //<editor-fold desc="Loop - Append Calculated Data">
    //append data to array for writing to my_npfmvc table
    $L02array[$key]['SUGGESTED_TIER'] = 'L02';
    $L02array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L02array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L02array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L02array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L02array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L02array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
    $L02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L02array[$key]['CURMAX'], $L02array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
    $L02array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($var_grid5, 0, 2)) * intval(substr($var_grid5, 3, 2)) * intval($var_griddepth);
    $L02array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

    //add L02array($key] to postarray for posting to mysql table
    $post_array[$key] = $L02array[$key];

    $L02Vol -= $var_locvol;
    // </editor-fold>
}
// </editor-fold>
// <editor-fold desc="Array Splice and Reset">
//L02 items have been designated.  Loop through L02 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L02array, ($key - $skippedkeycount + 1));

$L02array = array_values($L02array);  //reset array
$post_array = array_values($post_array);  //reset array
// </editor-fold>
// <editor-fold desc="Write to table slotting.my_npfmvc">
$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($post_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($post_array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($post_array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($post_array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $post_array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $post_array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $post_array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($post_array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($post_array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($post_array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($post_array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($post_array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $post_array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($post_array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $post_array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $post_array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($post_array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($post_array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($post_array[$counter]['CPCCPKU']);
        $CPCFLOW = $post_array[$counter]['CPCFLOW'];
        $CPCTOTE = $post_array[$counter]['CPCTOTE'];
        $CPCSHLF = $post_array[$counter]['CPCSHLF'];
        $CPCROTA = $post_array[$counter]['CPCROTA'];
        $CPCESTK = intval($post_array[$counter]['CPCESTK']);
        $CPCLIQU = $post_array[$counter]['CPCLIQU'];
        $CPCELEN = $post_array[$counter]['CPCELEN'];
        $CPCEHEI = $post_array[$counter]['CPCEHEI'];
        $CPCEWID = $post_array[$counter]['CPCEWID'];
        $CPCCLEN = $post_array[$counter]['CPCCLEN'];
        $CPCCHEI = $post_array[$counter]['CPCCHEI'];
        $CPCCWID = $post_array[$counter]['CPCCWID'];
        $LMFIXA = $post_array[$counter]['LMFIXA'];
        $LMFIXT = $post_array[$counter]['LMFIXT'];
        $LMSTGT = $post_array[$counter]['LMSTGT'];
        $LMHIGH = intval($post_array[$counter]['LMHIGH']);
        $LMDEEP = intval($post_array[$counter]['LMDEEP']);
        $LMWIDE = intval($post_array[$counter]['LMWIDE']);
        $LMVOL9 = intval($post_array[$counter]['LMVOL9']);
        $LMTIER = $post_array[$counter]['LMTIER'];
        $LMGRD5 = $post_array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $post_array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $post_array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $post_array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $post_array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $post_array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($post_array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($post_array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($post_array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($post_array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($post_array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($post_array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($post_array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $post_array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $post_array[$counter]['DAILYUNIT'];
        if ($LMTIER == 'L01' || $LMTIER == 'L15') {
            $VCBAY = $CUR_LOCATION;
        } else if ($LMTIER == 'L05' && $WAREHOUSE == 3) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '12';
        } else if ($LMTIER == 'L05') {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '01';
        } else {
            $VCBAY = substr($CUR_LOCATION, 0, 5);
        }
        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP)";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO slotting.slotmodel_my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $maxrange += 1000;
} while ($counter <= $rowcount);
// </editor-fold>
