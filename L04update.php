
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
$slowdownsizecutoff = 99999;


$reconfigured = 10;  //Bay23
$whse11endcapopp = ((32 - $reconfigured) * 6336);

//what is total L04 volume available.  Only used for capacity constraints
$L04onholdkey = array_search('L04', array_column($holdvolumearray, 'SUGGESTED_TIER')); //Find 'L06' associated key in items on hold array to subtract from available volume
$L04key = array_search('L04', array_column($allvolumearray, 'LMTIER')); //Find 'L04' associated key
if ($L04onholdkey !== FALSE) {
    $L04Vol = intval($allvolumearray[$L04key]['TIER_VOL']) - intval($holdvolumearray[$L04onholdkey]['ASSVOL']);
} else {
    $L04Vol = intval($allvolumearray[$L04key]['TIER_VOL']);
}

$sqlexclude = '';

if ($whssel == 11) {
    $L04Vol += $whse11endcapopp;
    $sqlexclude = " and A.CUR_LOCATION not like 'B34%' and A.CUR_LOCATION not like 'B35%'";
//} elseif ($whssel == 7) { //endcap opportunity
//    $L04Vol += 138240;  //two additional endcaps from drug room
//    $L04Vol += 999999999999999999999;
//} elseif ($whssel == 6) {
//    $L04Vol += 3317760;
}




//*** Step 4: L04 Designation ***

include '../CustomerAudit/connection/connection_details.php';
//Pull in available L04 Grid5s by volume ascending order


if ($whssel == 9) {
    $L04GridsArray = array();

    $L04GridsArray[0]['LMGRD5'] = '06T04';
    $L04GridsArray[0]['LMHIGH'] = 6;
    $L04GridsArray[0]['LMDEEP'] = 6;
    $L04GridsArray[0]['LMWIDE'] = 4;
    $L04GridsArray[0]['LMVOL9'] = 144;
    $L04GridsArray[0]['GRIDCOUNT'] = 99999;

    $L04GridsArray[1]['LMGRD5'] = '06T06';
    $L04GridsArray[1]['LMHIGH'] = 6;
    $L04GridsArray[1]['LMDEEP'] = 6;
    $L04GridsArray[1]['LMWIDE'] = 6;
    $L04GridsArray[1]['LMVOL9'] = 216;
    $L04GridsArray[1]['GRIDCOUNT'] = 99999;

    $L04GridsArray[2]['LMGRD5'] = '06T04';
    $L04GridsArray[2]['LMHIGH'] = 6;
    $L04GridsArray[2]['LMDEEP'] = 24;
    $L04GridsArray[2]['LMWIDE'] = 4;
    $L04GridsArray[2]['LMVOL9'] = 576;
    $L04GridsArray[2]['GRIDCOUNT'] = 99999;

    $L04GridsArray[3]['LMGRD5'] = '06T06';
    $L04GridsArray[3]['LMHIGH'] = 6;
    $L04GridsArray[3]['LMDEEP'] = 24;
    $L04GridsArray[3]['LMWIDE'] = 6;
    $L04GridsArray[3]['LMVOL9'] = 864;
    $L04GridsArray[3]['GRIDCOUNT'] = 99999;

    $L04GridsArray[4]['LMGRD5'] = '06T11';
    $L04GridsArray[4]['LMHIGH'] = 6;
    $L04GridsArray[4]['LMDEEP'] = 24;
    $L04GridsArray[4]['LMWIDE'] = 11;
    $L04GridsArray[4]['LMVOL9'] = 1584;
    $L04GridsArray[4]['GRIDCOUNT'] = 99999;

    $L04GridsArray[5]['LMGRD5'] = '11T11';
    $L04GridsArray[5]['LMHIGH'] = 11;
    $L04GridsArray[5]['LMDEEP'] = 24;
    $L04GridsArray[5]['LMWIDE'] = 11;
    $L04GridsArray[5]['LMVOL9'] = 2904;
    $L04GridsArray[5]['GRIDCOUNT'] = 99999;

    $L04GridsArray[6]['LMGRD5'] = '15T11';
    $L04GridsArray[6]['LMHIGH'] = 15;
    $L04GridsArray[6]['LMDEEP'] = 24;
    $L04GridsArray[6]['LMWIDE'] = 11;
    $L04GridsArray[6]['LMVOL9'] = 3960;
    $L04GridsArray[6]['GRIDCOUNT'] = 99999;

    $L04GridsArray[7]['LMGRD5'] = '11S17';
    $L04GridsArray[7]['LMHIGH'] = 11;
    $L04GridsArray[7]['LMDEEP'] = 24;
    $L04GridsArray[7]['LMWIDE'] = 17;
    $L04GridsArray[7]['LMVOL9'] = 4488;
    $L04GridsArray[7]['GRIDCOUNT'] = 99999;

    $L04GridsArray[8]['LMGRD5'] = '11S35';
    $L04GridsArray[8]['LMHIGH'] = 11;
    $L04GridsArray[8]['LMDEEP'] = 24;
    $L04GridsArray[8]['LMWIDE'] = 35;
    $L04GridsArray[8]['LMVOL9'] = 9240;
    $L04GridsArray[8]['GRIDCOUNT'] = 99999;

    $L04GridsArray[9]['LMGRD5'] = '15S35';
    $L04GridsArray[9]['LMHIGH'] = 11;
    $L04GridsArray[9]['LMDEEP'] = 24;
    $L04GridsArray[9]['LMWIDE'] = 35;
    $L04GridsArray[9]['LMVOL9'] = 12600;
    $L04GridsArray[9]['GRIDCOUNT'] = 99999;
} else {
    $L04GridsSQL = $conn1->prepare("SELECT 
                                    LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5)
                                FROM
                                    slotting.mysql_npflsm
                                WHERE
                                    LMWHSE = $whssel and LMTIER = 'L04' and LMGRD5 <> ' '
                                        and CONCAT(LMWHSE, LMTIER, LMGRD5, LMDEEP) not in (SELECT 
                                            gridexcl_key
                                        from
                                            slotting.gridexclusions
                                        WHERE
                                            gridexcl_whse = $whssel)
                                GROUP BY LMGRD5 , LMHIGH , LMDEEP , LMWIDE , LMVOL9
                                HAVING count(LMGRD5) >= 10
                                ORDER BY LMVOL9");
    $L04GridsSQL->execute();
    $L04GridsArray = $L04GridsSQL->fetchAll(pdo::FETCH_ASSOC);
}

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
                               S.CASETF
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
                                    and B.ITEM_TYPE = 'ST'
                                    and A.NBR_SHIP_OCC >= 4
                                    AND D.LMSLR NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and F.ITEM_NUMBER IS NULL
                                    $sqlexclude
                            ORDER BY DLY_CUBE_VEL desc");
$L04sql->execute();
$L04array = $L04sql->fetchAll(pdo::FETCH_ASSOC);
$count = count($L04array);


foreach ($L04array as $key => $value) {
    if ($L04Vol < 0) {
        echo $key . '<br>';
        echo $count. '<br>';
        break;  //if all available L04 volume has been used, exit
    }
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

    switch ($whssel) {
        case 2:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 5;
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
        case 11:
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 11;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 9;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
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
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
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
                $daystostock = 35;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 23;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 18;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 18;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 20) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 5;
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
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 18;
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
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 25) {
                $daystostock = 6;
            } elseif ($AVGD_BTW_SLE <= 30) {
                $daystostock = 5;
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
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 6;
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

        if ($whssel == 11) {
            $SUGGESTED_MAX_test = intval(floor($SUGGESTED_MAX_test * .95));  //take down suggested max by 5% to correct true fit issues for NOTL
        }

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

    //append data to array for writing to my_npfmvc table
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
    $AVGD_BTW_SLE = intval($L04array[$key]['AVGD_BTW_SLE']);
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
    $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP)";

    if ($key % 100 == 0 && $key <> 0) {
        $values = implode(',', $data);




        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();


        $data = array();
    }

    //********** END of SQL to ADD TO TABLE **********


    $L04Vol -= $var_locvol;
}
$values = implode(',', $data);

$sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();

$data = array();
echo $whssel . ' available volume is ' . $L04Vol;
