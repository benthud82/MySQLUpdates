
<?php

require_once 'funct_array_column.php';

//build the $L04GridsArray based off selection
$imploded_L04_grids = '("' . implode('" , "', str_replace('grid_L04_', '', $array_checked_grids_L04)) . '")';

$sql_L04grids = $conn1->prepare("SELECT 
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
                                        AND sm_grids_tier = 'L04' and sm_grids_grid5 in $imploded_L04_grids
                                ORDER BY sm_grids_vol asc");
$sql_L04grids->execute();
$L04GridsArray = $sql_L04grids->fetchAll(pdo::FETCH_ASSOC);

$L04sql = $conn1->prepare("SELECT DISTINCT
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
                                D.CURTF,
                                case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT,
                               S.CASETF,
                               case when A.AVGD_BTW_SLE < 2 then 'A'  when A.AVGD_BTW_SLE < 4 then 'B'  when A.AVGD_BTW_SLE < 7 then 'C' else 'D' end as ITEM_MC
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
                                      LEFT JOIN
                                slotting.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                            WHERE
                                A.WAREHOUSE = $whssel
                                    
                                        and D.LMLOC not like 'D%'
                                        and D.LMLOC not like 'Y04%'
                                        AND A.DSL_TYPE NOT IN (1,2,4)
                                    and A.PACKAGE_TYPE = ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.NBR_SHIP_OCC >= 4
                                    AND D.LMSLR NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and F.ITEM_NUMBER IS NULL
                            ORDER BY DLY_CUBE_VEL desc");
$L04sql->execute();
$L04array = $L04sql->fetchAll(pdo::FETCH_ASSOC);
$count = count($L04array);

foreach ($L04array as $key => $value) {

    $ITEM_NUMBER = intval($L04array[$key]['ITEM_NUMBER']);
    //Check OK in Shelf Setting
    $var_OKINSHLF = $L04array[$key]['CPCSHLF'];
    $var_stacklimit = $L04array[$key]['CPCESTK'];
    $var_casetf = $L04array[$key]['CASETF'];
    $var_CURTF = $L04array[$key]['CURTF'];

    $var_AVGSHIPQTY = $L04array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L04array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($L04array[$key]['AVG_INV_OH']);

    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $L04array[$key]['CPCLIQU'];

    $var_PCEHEIin = $L04array[$key]['CPCEHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $L04array[$key]['CPCCHEI'] * 0.393701;
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $L04array[$key]['CPCELEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $L04array[$key]['CPCCLEN'] * 0.393701;
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $L04array[$key]['CPCEWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $L04array[$key]['CPCCWID'] * 0.393701;
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_PCCHEIin = $L04array[$key]['CPCCHEI'] * 0.393701;
    $var_PCCLENin = $L04array[$key]['CPCCLEN'] * 0.393701;
    $var_PCCWIDin = $L04array[$key]['CPCCWID'] * 0.393701;

    $var_eachqty = $L04array[$key]['CPCEPKU'];
    $var_caseqty = $L04array[$key]['CPCCPKU'];
    if ($var_eachqty == 0) {
        $var_eachqty = 1;
    }




    //Determine how many days to stock based on ADBS $daystostock
    //For L04 
    //key0-> 1 ADBS $L02_adbs_key0
    //key1-> BTW 1 and 2 ADBS $L02_adbs_key1
    //key2-> BTW 2 and 3 ADBS $L02_adbs_key2
    //key3-> BTW 3 and 4 ADBS $L02_adbs_key3
    //key4-> BTW 4 and 5 ADBS $L02_adbs_key4
    //key5-> BTW 5 and 7 ADBS $L02_adbs_key1
    //key6-> BTW 7 and 10 ADBS $L02_adbs_key1
    //key7-> BTW 10 and 15 ADBS $L02_adbs_key2
    //key8-> BTW 15 and 25 ADBS $L02_adbs_key3
    //key9-> Greater than 25





    switch (true) {
        case in_array($AVGD_BTW_SLE, range(0, 1)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key0;
            break;
        case in_array($AVGD_BTW_SLE, range(1, 2)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key1;
            break;
        case in_array($AVGD_BTW_SLE, range(2, 3)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key2;
            break;
        case in_array($AVGD_BTW_SLE, range(3, 4)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key3;
            break;
        case in_array($AVGD_BTW_SLE, range(4, 5)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key4;
            break;
        case in_array($AVGD_BTW_SLE, range(5, 7)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key5;
            break;
        case in_array($AVGD_BTW_SLE, range(7, 10)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key6;
            break;
        case in_array($AVGD_BTW_SLE, range(10, 15)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key7;
            break;
        case in_array($AVGD_BTW_SLE, range(15, 25)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key8;
            break;
        case in_array($AVGD_BTW_SLE, range(25, 99999999)): //the range from range of 0-20:
            $daystostock = $L04_adbs_key9;
            break;

        default:
            break;
    }

    $PKGU_PERC_Restriction = $L04array[$key]['PERC_PERC'];

    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($L04array[$key]['PACKAGE_UNIT']);
        $var_pkty = $L04array[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];
        //write to table inventory_restricted

        $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
        $result2->execute();
    } else {
        $slotqty = $slotqty_return_array['OPTQTY'];
    }


    if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
        $slotqty = 1;
    } elseif ($slotqty == 0) {
        $slotqty = $var_AVGINV;
    }

    //calculate total slot valume to determine what grid to start
    $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

//    if ($var_OKINSHLF == 'N') {
//        $lastusedgrid5 = '15T11';
//    } else {
//        $lastusedgrid5 = '15S47';
//    }
//    $maxkey = count($L04GridsArray) - 1; //if reach max key and not figured true fit, calc at max
    //loop through available L04 grids to determine smallest location to accomodate slot quantity
    foreach ($L04GridsArray as $key2 => $value) {
        //if total slot volume is less than location volume, then continue
//        if ($totalslotvol > $L04GridsArray[$key2]['LMVOL9']) {
//            continue;
//        }

        $var_grid5 = $L04GridsArray[$key2]['LMGRD5'];
        if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
            continue;
        }
        $var_gridheight = $L04GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L04GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L04GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L04GridsArray[$key2]['LMVOL9'];

        //Call the true fit for L04`
        if ($var_casetf == 'Y' && substr($var_grid5, 2, 1) == 'S' && ($var_PCCHEIin * $var_PCCLENin * $var_PCCWIDin * $var_caseqty > 0)) {
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCCHEIin, $var_PCCLENin, $var_PCCWIDin, $var_caseqty);
        } else if ($var_stacklimit > 0) {
            $SUGGESTED_MAX_array = _truefit($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, 0, $var_stacklimit);
        } else {
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        }
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($var_locvol < 100) {  //location is a drawer
            if ($SUGGESTED_MAX_test >= $var_AVGINV) {
                break;
            }
        } elseif ($var_locvol >= 100) {
            if ($SUGGESTED_MAX_test >= $slotqty) {
                $lastusedgrid5 = $var_grid5;
                break;
            }
        }
        //to prevent issue of suggesting a shelf when not accpetable according to OK in flag
        $lastusedgrid5 = $var_grid5;
    }


    $SUGGESTED_MAX = $SUGGESTED_MAX_test;

    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

    //append data to array for writing to slotmodel_my_npfmvc table
    $L04array[$key]['SUGGESTED_TIER'] = 'L04';
    $L04array[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
    $L04array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L04array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L04array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L04array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L04array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
    $L04array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves_withcurrentTF($L04array[$key]['CURMAX'], $L04array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE'], $var_CURTF);
    $L04array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($lastusedgrid5, 0, 2)) * intval(substr($lastusedgrid5, 3, 2)) * intval($var_griddepth);
    $L04array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L04array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

    //********** START of SQL to ADD TO TABLE **********


    $WAREHOUSE = intval($L04array[$key]['WAREHOUSE']);
    $ITEM_NUMBER = intval($L04array[$key]['ITEM_NUMBER']);
    $PACKAGE_UNIT = intval($L04array[$key]['PACKAGE_UNIT']);
    $PACKAGE_TYPE = $L04array[$key]['PACKAGE_TYPE'];
    $DSL_TYPE = $L04array[$key]['DSL_TYPE'];
    $CUR_LOCATION = $L04array[$key]['LMLOC'];
    $DAYS_FRM_SLE = intval($L04array[$key]['DAYS_FRM_SLE']);
    $AVGD_BTW_SLE = ($L04array[$key]['AVGD_BTW_SLE']);
    $AVG_INV_OH = intval($L04array[$key]['AVG_INV_OH']);
    $NBR_SHIP_OCC = intval($L04array[$key]['NBR_SHIP_OCC']);
    $PICK_QTY_MN = intval($L04array[$key]['PICK_QTY_MN']);
    $PICK_QTY_SD = $L04array[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = intval($L04array[$key]['SHIP_QTY_MN']);
    $SHIP_QTY_SD = $L04array[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $L04array[$key]['ITEM_TYPE'];
    $CPCEPKU = intval($L04array[$key]['CPCEPKU']);
    $CPCIPKU = intval($L04array[$key]['CPCIPKU']);
    $CPCCPKU = intval($L04array[$key]['CPCCPKU']);
    $CPCFLOW = $L04array[$key]['CPCFLOW'];
    $CPCTOTE = $L04array[$key]['CPCTOTE'];
    $CPCSHLF = $L04array[$key]['CPCSHLF'];
    $CPCROTA = $L04array[$key]['CPCROTA'];
    $CPCESTK = intval($L04array[$key]['CPCESTK']);
    $CPCLIQU = $L04array[$key]['CPCLIQU'];
    $CPCELEN = $L04array[$key]['CPCELEN'];
    $CPCEHEI = $L04array[$key]['CPCEHEI'];
    $CPCEWID = $L04array[$key]['CPCEWID'];
    $CPCCLEN = $L04array[$key]['CPCCLEN'];
    $CPCCHEI = $L04array[$key]['CPCCHEI'];
    $CPCCWID = $L04array[$key]['CPCCWID'];
    $LMFIXA = $L04array[$key]['LMFIXA'];
    $LMFIXT = $L04array[$key]['LMFIXT'];
    $LMSTGT = $L04array[$key]['LMSTGT'];
    $LMHIGH = intval($L04array[$key]['LMHIGH']);
    $LMDEEP = intval($L04array[$key]['LMDEEP']);
    $LMWIDE = intval($L04array[$key]['LMWIDE']);
    $LMVOL9 = intval($L04array[$key]['LMVOL9']);
    $LMTIER = $L04array[$key]['LMTIER'];
    $LMGRD5 = $L04array[$key]['LMGRD5'];
    $DLY_CUBE_VEL = intval($L04array[$key]['DLY_CUBE_VEL']);
    $DLY_PICK_VEL = intval($L04array[$key]['DLY_PICK_VEL']);
    $SUGGESTED_TIER = $L04array[$key]['SUGGESTED_TIER'];
    $SUGGESTED_GRID5 = $L04array[$key]['SUGGESTED_GRID5'];
    $SUGGESTED_DEPTH = $L04array[$key]['SUGGESTED_DEPTH'];
    $SUGGESTED_MAX = intval($L04array[$key]['SUGGESTED_MAX']);
    $SUGGESTED_MIN = intval($L04array[$key]['SUGGESTED_MIN']);
    $SUGGESTED_SLOTQTY = intval($L04array[$key]['SUGGESTED_SLOTQTY']);
    $ITEM_MC = ($L04array[$key]['ITEM_MC']);

    $SUGGESTED_IMPMOVES = ($L04array[$key]['SUGGESTED_IMPMOVES']);
    $CURRENT_IMPMOVES = ($L04array[$key]['CURRENT_IMPMOVES']);
    $SUGGESTED_NEWLOCVOL = intval($L04array[$key]['SUGGESTED_NEWLOCVOL']);
    $SUGGESTED_DAYSTOSTOCK = intval($L04array[$key]['SUGGESTED_DAYSTOSTOCK']);
    $AVG_DAILY_PICK = $L04array[$key]['DAILYPICK'];
    $AVG_DAILY_UNIT = $L04array[$key]['DAILYUNIT'];
    if ($LMTIER == 'L01' || $LMTIER == 'L15') {
        $VCBAY = $CUR_LOCATION;
    } else if ($LMTIER == 'L05' && $WAREHOUSE == 3) {
        $VCBAY = substr($CUR_LOCATION, 0, 3) . '12';
    } else if ($LMTIER == 'L05') {
        $VCBAY = substr($CUR_LOCATION, 0, 3) . '01';
    } else {
        $VCBAY = substr($CUR_LOCATION, 0, 5);
    }
    $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,'$AVGD_BTW_SLE',$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP,'$ITEM_MC')";

    if ($key % 100 == 0 && $key <> 0) {
        $values = implode(',', $data);

        $sql = "INSERT IGNORE INTO slotting.slotmodel_my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();

        $data = array();
    }

    //********** END of SQL to ADD TO TABLE **********


    $L04vol -= $var_locvol;
}
$values = implode(',', $data);

if ($values) {
    $sql = "INSERT IGNORE INTO slotting.slotmodel_my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
}

