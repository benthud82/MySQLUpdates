<?php
$JAX_ENDCAP = 0;

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

//*** RESTRICTION VARIABLES ***
$minadbs = 5;  //need to have at least 15% of the available items.  For NOTL, 5 ADBS represents 4678 of 31000 items or 15%
$mindsls = 14; //sold in the last two weeks
//$daystostock = 15;  //stock 10 shipping occurences as max
$slowdownsizecutoff = 999999;  //min ADBS to only stock to 2 ship occurences as Max.  Not used right now till capacity is determined
$skippedkeycount = 0;
include '../connections/conn_slotting.php';
//what is total L02 volume available
$L02onholdkey = array_search('L02', array_column($holdvolumearray, 'SUGGESTED_TIER')); //Find 'L06' associated key in items on hold array to subtract from available volume
$L02key = array_search('L02', array_column($allvolumearray, 'LMTIER')); //Find 'L02' associated key
if ($L02onholdkey !== FALSE) {
    $L02Vol = intval($allvolumearray[$L02key]['TIER_VOL']) - intval($holdvolumearray[$L02onholdkey]['ASSVOL']);
} else {
    $L02Vol = intval($allvolumearray[$L02key]['TIER_VOL']);
}

//*** Step 2: L02 Designation ***
//Delete Restricted flow Locs
$SQLDelete = $conn1->prepare("DELETE FROM slotting.items_restricted WHERE REST_WHSE = $whssel and REST_SHOULD = 'FLOW'");
$SQLDelete->execute();




//Pull in available L02 Grid5s by volume ascending order
$L02GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L02' GROUP BY LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
$L02GridsSQL->execute();
$L02GridsArray = $L02GridsSQL->fetchAll(pdo::FETCH_ASSOC);


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
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE = ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.NBR_SHIP_OCC >= 4
                                    and A.AVGD_BTW_SLE > 0
                                    and A.AVGD_BTW_SLE <= $minadbs
                                    and A.DAYS_FRM_SLE <= $mindsls
                                    and F.ITEM_NUMBER IS NULL
                                    AND A.DSL_TYPE NOT IN (2,4)
                            ORDER BY DLY_CUBE_VEL desc");
$L02sql->execute();
$L02array = $L02sql->fetchAll(pdo::FETCH_ASSOC);

foreach ($L02array as $key => $value) {
    $var_item = intval($L02array[$key]['ITEM_NUMBER']);

    if ($L02Vol < 0) {
        break;  //if all available L02 volume has been used, exit
    }

    //Check OK in Flow Setting
    $var_OKINFLOW = $L02array[$key]['CPCFLOW'];
    if ($var_OKINFLOW == 'N') {

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
    $AVGD_BTW_SLE = intval($L02array[$key]['AVGD_BTW_SLE']);
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

    $var_caseqty = $L02array[$key]['CPCCPKU'];
    if ($var_caseqty == 0) {
        $var_caseqty = 1;
    }

    switch ($whssel) {
        case 2:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 3;
            }
            break;
        case 11:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            }
            break;
        case 12:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            }
            break;
        case 16:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            }
            break;
        case 3:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 7;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 5;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 3;
            }
            break;
        case 7:

            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 7;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 4;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 2;
            }
            break;

//below is optimal if additional L01 could be added to match Jax
//            if ($AVGD_BTW_SLE <= 1) {
//                $daystostock = 10;
//            } elseif ($AVGD_BTW_SLE <= 2) {
//                $daystostock = 6;
//            } elseif ($AVGD_BTW_SLE <= 3) {
//                $daystostock = 5;
//            } elseif ($AVGD_BTW_SLE <= 4) {
//                $daystostock = 3;
//            } elseif ($AVGD_BTW_SLE <= 5) {
//                $daystostock = 3;
//            }
//            break;

        case 6:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 3;
            }
            break;

        case 9:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 3;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 3;
            }
            break;
        default:
            break;
    }

    $PKGU_PERC_Restriction = $L02array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($L02array[$key]['ITEM_NUMBER']);

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




    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

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

    $L02Vol -= $var_locvol;
}

//L02 items have been designated.  Loop through L02 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L02array, ($key - $skippedkeycount - 1));

$L02array = array_values($L02array);  //reset array



$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L02array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($L02array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L02array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L02array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L02array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $L02array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $L02array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L02array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($L02array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L02array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L02array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L02array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L02array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L02array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L02array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $L02array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($L02array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($L02array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($L02array[$counter]['CPCCPKU']);
        $CPCFLOW = $L02array[$counter]['CPCFLOW'];
        $CPCTOTE = $L02array[$counter]['CPCTOTE'];
        $CPCSHLF = $L02array[$counter]['CPCSHLF'];
        $CPCROTA = $L02array[$counter]['CPCROTA'];
        $CPCESTK = intval($L02array[$counter]['CPCESTK']);
        $CPCLIQU = $L02array[$counter]['CPCLIQU'];
        $CPCELEN = $L02array[$counter]['CPCELEN'];
        $CPCEHEI = $L02array[$counter]['CPCEHEI'];
        $CPCEWID = $L02array[$counter]['CPCEWID'];
        $CPCCLEN = $L02array[$counter]['CPCCLEN'];
        $CPCCHEI = $L02array[$counter]['CPCCHEI'];
        $CPCCWID = $L02array[$counter]['CPCCWID'];
        $LMFIXA = $L02array[$counter]['LMFIXA'];
        $LMFIXT = $L02array[$counter]['LMFIXT'];
        $LMSTGT = $L02array[$counter]['LMSTGT'];
        $LMHIGH = intval($L02array[$counter]['LMHIGH']);
        $LMDEEP = intval($L02array[$counter]['LMDEEP']);
        $LMWIDE = intval($L02array[$counter]['LMWIDE']);
        $LMVOL9 = intval($L02array[$counter]['LMVOL9']);
        $LMTIER = $L02array[$counter]['LMTIER'];
        $LMGRD5 = $L02array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $L02array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $L02array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $L02array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L02array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L02array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L02array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L02array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L02array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($L02array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($L02array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($L02array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($L02array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L02array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L02array[$counter]['DAILYUNIT'];
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
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    
    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    
    $maxrange += 1000;
} while ($counter <= $rowcount);


