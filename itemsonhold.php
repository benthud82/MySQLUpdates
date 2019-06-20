
<?php
$JAX_ENDCAP = 0;
$data = array();
if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}


include '../connections/conn_slotting.php';
$itemsonhold = $conn1->prepare("SELECT DISTINCT
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
                                $sql_dailyunit as DAILYUNIT,
                               S.CASETF,
                               HOLDTIER,
                               HOLDGRID,
                               HOLDLOCATION
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
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
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
                                    and A.PACKAGE_TYPE = ('LSE')
                                    and A.NBR_SHIP_OCC >= 4
                                    AND D.LMSLR NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and (HOLDLOCATION <> '')
                            ORDER BY DLY_CUBE_VEL desc");
$itemsonhold->execute();
$itemsonholdarray = $itemsonhold->fetchAll(pdo::FETCH_ASSOC);


foreach ($itemsonholdarray as $key => $value) {

    $ITEM_NUMBER = intval($itemsonholdarray[$key]['ITEM_NUMBER']);
    //Check OK in Shelf Setting
    $var_OKINSHLF = $itemsonholdarray[$key]['CPCSHLF'];
    $var_stacklimit = $itemsonholdarray[$key]['CPCESTK'];
    $var_casetf = $itemsonholdarray[$key]['CASETF'];
    $var_gridheight = $itemsonholdarray[$key]['LMHIGH'];
    $var_griddepth = $itemsonholdarray[$key]['LMDEEP'];
    $var_gridwidth = $itemsonholdarray[$key]['LMWIDE'];

    $var_AVGSHIPQTY = $itemsonholdarray[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($itemsonholdarray[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($itemsonholdarray[$key]['AVG_INV_OH']);

    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $itemsonholdarray[$key]['CPCLIQU'];

    $var_PCEHEIin = $itemsonholdarray[$key]['CPCEHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $itemsonholdarray[$key]['CPCCHEI'] * 0.393701;
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $itemsonholdarray[$key]['CPCELEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $itemsonholdarray[$key]['CPCCLEN'] * 0.393701;
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $itemsonholdarray[$key]['CPCEWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $itemsonholdarray[$key]['CPCCWID'] * 0.393701;
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_PCCHEIin = $itemsonholdarray[$key]['CPCCHEI'] * 0.393701;
    $var_PCCLENin = $itemsonholdarray[$key]['CPCCLEN'] * 0.393701;
    $var_PCCWIDin = $itemsonholdarray[$key]['CPCCWID'] * 0.393701;

    $var_eachqty = $itemsonholdarray[$key]['CPCEPKU'];
    $var_caseqty = $itemsonholdarray[$key]['CPCCPKU'];
    if ($var_eachqty == 0) {
        $var_eachqty = 1;
    }

    switch ($whssel) {
        case 2:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 18;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 13;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 4;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 1;
            } else {
                $daystostock = 1;
            }
            break;
        case 11:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 60;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 22;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 2;
            } else {
                $daystostock = 1;
            }
            break;
        case 12:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 60;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 22;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 2;
            } else {
                $daystostock = 1;
            }
            break;
        case 16:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 75;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 35;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 25;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 4;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 2;
            } else {
                $daystostock = 1;
            }
            break;
        case 7:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 24;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 9;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 7;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 4;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 1;
            } else {
                $daystostock = 1;
            }
            break;
        case 9:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 40;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 2;
            } else {
                $daystostock = 2;
            }
            break;
        case 6:
            //Pass 3
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 43;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 25;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 18;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 2;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 2;
            } else {
                $daystostock = 2;
            }
            break;
        case 3:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 26;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 4;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 40) {
                $daystostock = 1;
            } elseif ($AVGD_BTW_SLE <= 50) {
                $daystostock = 1;
            } else {
                $daystostock = 1;
            }
            break;
        default:
            break;
    }

    $PKGU_PERC_Restriction = $itemsonholdarray[$key]['PERC_PERC'];

    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, 999999, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($itemsonholdarray[$key]['PACKAGE_UNIT']);
        $var_pkty = $itemsonholdarray[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];
        //write to table inventory_restricted
        //include '../connections/conn_slotting.php';
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

    $var_grid5 = $itemsonholdarray[$key]['LMGRD5'];

    if ($var_casetf == 'Y' && substr($var_grid5, 2, 1) == 'S' && ($var_PCCHEIin * $var_PCCLENin * $var_PCCWIDin * $var_caseqty > 0)) {
        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCCHEIin, $var_PCCLENin, $var_PCCWIDin, $var_caseqty);
    } else if ($var_stacklimit > 0) {
        $SUGGESTED_MAX_array = _truefit($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, 0, $var_stacklimit);
    } else {
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
    }
    $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];


    $SUGGESTED_MAX = $SUGGESTED_MAX_test;

    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

    //append data to array for writing to my_npfmvc table
    $itemsonholdarray[$key]['SUGGESTED_TIER'] = $itemsonholdarray[$key]['HOLDTIER'];
    $itemsonholdarray[$key]['SUGGESTED_GRID5'] = $itemsonholdarray[$key]['HOLDGRID'];
    $itemsonholdarray[$key]['SUGGESTED_DEPTH'] = $itemsonholdarray[$key]['LMDEEP'];
    $itemsonholdarray[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $itemsonholdarray[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $itemsonholdarray[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $itemsonholdarray[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $itemsonholdarray[$key]['SHIP_QTY_MN'], $itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $itemsonholdarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($itemsonholdarray[$key]['CURMAX'], $itemsonholdarray[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $itemsonholdarray[$key]['SHIP_QTY_MN'], $itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $itemsonholdarray[$key]['SUGGESTED_NEWLOCVOL'] = intval($itemsonholdarray[$key]['LMVOL9']);
    $itemsonholdarray[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

    //********** START of SQL to ADD TO TABLE **********
    $WAREHOUSE = intval($itemsonholdarray[$key]['WAREHOUSE']);
    $ITEM_NUMBER = intval($itemsonholdarray[$key]['ITEM_NUMBER']);
    $PACKAGE_UNIT = intval($itemsonholdarray[$key]['PACKAGE_UNIT']);
    $PACKAGE_TYPE = $itemsonholdarray[$key]['PACKAGE_TYPE'];
    $DSL_TYPE = $itemsonholdarray[$key]['DSL_TYPE'];
    $CUR_LOCATION = $itemsonholdarray[$key]['LMLOC'];
    $DAYS_FRM_SLE = intval($itemsonholdarray[$key]['DAYS_FRM_SLE']);
    $AVGD_BTW_SLE = intval($itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $AVG_INV_OH = intval($itemsonholdarray[$key]['AVG_INV_OH']);
    $NBR_SHIP_OCC = intval($itemsonholdarray[$key]['NBR_SHIP_OCC']);
    $PICK_QTY_MN = intval($itemsonholdarray[$key]['PICK_QTY_MN']);
    $PICK_QTY_SD = $itemsonholdarray[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = intval($itemsonholdarray[$key]['SHIP_QTY_MN']);
    $SHIP_QTY_SD = $itemsonholdarray[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $itemsonholdarray[$key]['ITEM_TYPE'];
    $CPCEPKU = intval($itemsonholdarray[$key]['CPCEPKU']);
    $CPCIPKU = intval($itemsonholdarray[$key]['CPCIPKU']);
    $CPCCPKU = intval($itemsonholdarray[$key]['CPCCPKU']);
    $CPCFLOW = $itemsonholdarray[$key]['CPCFLOW'];
    $CPCTOTE = $itemsonholdarray[$key]['CPCTOTE'];
    $CPCSHLF = $itemsonholdarray[$key]['CPCSHLF'];
    $CPCROTA = $itemsonholdarray[$key]['CPCROTA'];
    $CPCESTK = intval($itemsonholdarray[$key]['CPCESTK']);
    $CPCLIQU = $itemsonholdarray[$key]['CPCLIQU'];
    $CPCELEN = $itemsonholdarray[$key]['CPCELEN'];
    $CPCEHEI = $itemsonholdarray[$key]['CPCEHEI'];
    $CPCEWID = $itemsonholdarray[$key]['CPCEWID'];
    $CPCCLEN = $itemsonholdarray[$key]['CPCCLEN'];
    $CPCCHEI = $itemsonholdarray[$key]['CPCCHEI'];
    $CPCCWID = $itemsonholdarray[$key]['CPCCWID'];
    $LMFIXA = $itemsonholdarray[$key]['LMFIXA'];
    $LMFIXT = $itemsonholdarray[$key]['LMFIXT'];
    $LMSTGT = $itemsonholdarray[$key]['LMSTGT'];
    $LMHIGH = intval($itemsonholdarray[$key]['LMHIGH']);
    $LMDEEP = intval($itemsonholdarray[$key]['LMDEEP']);
    $LMWIDE = intval($itemsonholdarray[$key]['LMWIDE']);
    $LMVOL9 = intval($itemsonholdarray[$key]['LMVOL9']);
    $LMTIER = $itemsonholdarray[$key]['LMTIER'];
    $LMGRD5 = $itemsonholdarray[$key]['LMGRD5'];
    $DLY_CUBE_VEL = intval($itemsonholdarray[$key]['DLY_CUBE_VEL']);
    $DLY_PICK_VEL = intval($itemsonholdarray[$key]['DLY_PICK_VEL']);
    $SUGGESTED_TIER = $itemsonholdarray[$key]['SUGGESTED_TIER'];
    $SUGGESTED_GRID5 = $itemsonholdarray[$key]['SUGGESTED_GRID5'];
    $SUGGESTED_DEPTH = $itemsonholdarray[$key]['SUGGESTED_DEPTH'];
    $SUGGESTED_MAX = intval($itemsonholdarray[$key]['SUGGESTED_MAX']);
    $SUGGESTED_MIN = intval($itemsonholdarray[$key]['SUGGESTED_MIN']);
    $SUGGESTED_SLOTQTY = intval($itemsonholdarray[$key]['SUGGESTED_SLOTQTY']);

    $SUGGESTED_IMPMOVES = ($itemsonholdarray[$key]['SUGGESTED_IMPMOVES']);
    $CURRENT_IMPMOVES = ($itemsonholdarray[$key]['CURRENT_IMPMOVES']);
    $SUGGESTED_NEWLOCVOL = intval($itemsonholdarray[$key]['SUGGESTED_NEWLOCVOL']);
    $SUGGESTED_DAYSTOSTOCK = intval($itemsonholdarray[$key]['SUGGESTED_DAYSTOSTOCK']);
    $AVG_DAILY_PICK = $itemsonholdarray[$key]['DAILYPICK'];
    $AVG_DAILY_UNIT = $itemsonholdarray[$key]['DAILYUNIT'];
        if ($LMTIER == 'L01' || $LMTIER == 'L15') {
            $VCBAY = $CUR_LOCATION;
        } else if ($LMTIER == 'L05' && $WAREHOUSE == 3) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '12';
        } else if ($LMTIER == 'L05' ) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '01';
        } else {
            $VCBAY = substr($CUR_LOCATION, 0, 5);
        }
    $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP)";

    if ($key % 100 == 0 && $key <> 0) {
        $values = implode(',', $data);



        //include '../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();

        $data = array();
    }
    //********** END of SQL to ADD TO TABLE **********
}

If (count($data) > 0) {
    $values = implode(',', $data);


    //include '../CustomerAudit/connection/connection_details.php';
    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $data = array();
}


//Add SQL to group assigned volume by tier for items on hold.  This will be removed from available volume going forward
//include '../CustomerAudit/connection/connection_details.php';
$holdvolume = $conn1->prepare("SELECT SUGGESTED_TIER, sum(SUGGESTED_NEWLOCVOL) as ASSVOL, count(*) as ASSCOUNT from slotting.my_npfmvc WHERE WAREHOUSE = $whssel GROUP BY SUGGESTED_TIER");
$holdvolume->execute();
$holdvolumearray = $holdvolume->fetchAll(pdo::FETCH_ASSOC);

$holdgrid = $conn1->prepare("SELECT SUGGESTED_GRID5, sum(SUGGESTED_NEWLOCVOL) as ASSVOL, count(*) as ASSCOUNT from slotting.my_npfmvc WHERE WAREHOUSE = $whssel GROUP BY SUGGESTED_GRID5");
$holdgrid->execute();
$holdgridarray = $holdgrid->fetchAll(pdo::FETCH_ASSOC);




