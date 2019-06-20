<?php


$slowdownsizecutoff = 999999;

$daystostock = 15;  //stock 10 shipping occurences as max



if ($whssel == 7) {
    $CSE_pick_limit_convey = .9;
} elseif ($whssel == 2) {
    $CSE_pick_limit_convey = .9;
} else {
    $CSE_pick_limit_convey = .9;
}
include '../connections/conn_slotting.php';
$CSEpicksSQL_convey = $conn1->prepare("SELECT 
                                            sum($sql_dailypick_case) as TOTPICKS
                                        FROM
                                            mysql_nptsld A
                                                join
                                            slotting.npfcpcsettings C ON CPCWHSE = A.WAREHOUSE
                                                and CPCITEM = ITEM_NUMBER
                                                LEFT JOIN
                                            slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                                and F.ITEM_NUMBER = A.ITEM_NUMBER
                                                and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                                and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                                        WHERE
                                            A.WAREHOUSE = $whssel
                                                and F.ITEM_NUMBER IS NULL
                                                and A.PACKAGE_TYPE not in ('LSE' , 'INP')
                                                and CPCCONV <> 'N'");
$CSEpicksSQL_convey->execute();
$CSEpicksArray_convey = $CSEpicksSQL_convey->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;
$CSE_Picks_convey = intval($CSEpicksArray_convey[0]['TOTPICKS']);

$cse_low_picks_convey = intval($CSE_pick_limit_convey * $CSE_Picks_convey);

include '../connections/conn_slotting.php';
$case_conveysql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                CASE
                                    WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                    else LMLOC
                                end as CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                C.CPCEPKU,
                                C.CPCIPKU,
                                C.CPCCPKU,
                                C.CPCFLOW,
                                C.CPCTOTE,
                                C.CPCSHLF,
                                C.CPCROTA,
                                C.CPCESTK,
                                C.CPCLIQU,
                                C.CPCELEN,
                                C.CPCEHEI,
                                C.CPCEWID,
                                C.CPCCLEN,
                                C.CPCCHEI,
                                C.CPCCWID,
                                C.CPCNEST,
                                CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMFIXA
                                   end as LMFIXA,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMFIXT
                                   end as LMFIXT,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMSTGT
                                   end as LMSTGT,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMHIGH
                                   end as LMHIGH,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMDEEP
                                   end as LMDEEP,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMWIDE
                                   end as LMWIDE,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMVOL9
                                   end as LMVOL9,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMTIER
                                   end as LMTIER,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMGRD5
                                   end as LMGRD5,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.CURMAX
                                   end as CURMAX,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.CURMIN
                                   end as CURMIN,
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailyunit) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / C.CPCCPKU
                                    else ($sql_dailyunit) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_CUBE_VEL,
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailypick_case) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
                                    else ($sql_dailypick_case) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick_case as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings C ON C.CPCWHSE = A.WAREHOUSE
                                    AND C.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMITEM = A.ITEM_NUMBER
                                    and case
                                    when PACKAGE_TYPE = 'PFR' then A.PACKAGE_UNIT = 0
                                    else A.PACKAGE_UNIT
                                end = LMPKGU
                                    JOIN
                                slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
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
                                    and A.CUR_LOCATION not like 'W00%'
                                    and (A.PACKAGE_TYPE not in ('LSE' , 'INP') or A.CUR_LOCATION like ('Q%'))
                                    and A.CUR_LOCATION not like 'N%'
                                    and F.ITEM_NUMBER IS NULL
                                    and B.ITEM_TYPE = 'ST'
                                    and CPCCONV <> 'N'
                            ORDER BY DAILYPICK desc");
$case_conveysql->execute();
$case_conveyarray = $case_conveysql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;



//Standardize grid locs for Dallas, same for other DCs?
//slotted picks initialize
$slottedpicks = 0;

foreach ($case_conveyarray as $key => $value) {

    if ($slottedpicks >= $cse_low_picks_convey || count($ConveyGridsArray) == 0) {  //have slotted picks exceeded min quantity, if so put rest to reserve
        $case_conveyarray[$key]['SUGGESTED_TIER'] = 'CSE_PFR_CON';
        $case_conveyarray[$key]['SUGGESTED_GRID5'] = 'C_PFR';
        $case_conveyarray[$key]['SUGGESTED_DEPTH'] = 0;
        $case_conveyarray[$key]['SUGGESTED_MAX'] = 0;
        $case_conveyarray[$key]['SUGGESTED_MIN'] = 0;
        $case_conveyarray[$key]['SUGGESTED_SLOTQTY'] = 0;
        $case_conveyarray[$key]['SUGGESTED_IMPMOVES'] = 0;
        if (substr($case_conveyarray[$key]['CUR_LOCATION'], 0, 1) == 'Q' || $case_conveyarray[$key]['PACKAGE_TYPE'] == 'PFR') {
            $case_conveyarray[$key]['CURRENT_IMPMOVES'] = 0;
        } else {
            $case_conveyarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($case_conveyarray[$key]['CURMAX'], $case_conveyarray[$key]['CURMIN'], $case_conveyarray[$key]['DAILYUNIT'], $case_conveyarray[$key]['AVG_INV_OH'],$case_conveyarray[$key]['SHIP_QTY_MN'],$case_conveyarray[$key]['AVGD_BTW_SLE']);
        }
        $case_conveyarray[$key]['SUGGESTED_NEWLOCVOL'] = 0;
        $case_conveyarray[$key]['SUGGESTED_DAYSTOSTOCK'] = 0;
    } else {


//        switch ($whssel) {
//            case 2:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 10;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 6;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 5;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 3;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 3;
//                }
//                break;
//            case 11:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 15;
//                }
//                break;
//            case 12:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 15;
//                }
//                break;
//            case 16:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 15;
//                }
//                break;
//            case 3:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 12;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 8;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 6;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 3;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 3;
//                }
//                break;
//            case 7:
//
//                if ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 50;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 50;
//                } elseif ($AVGD_BTW_SLE <= 10) {
//                    $daystostock = 15;
//                } elseif ($AVGD_BTW_SLE <= 20) {
//                    $daystostock = 10;
//                } elseif ($AVGD_BTW_SLE <= 25) {
//                    $daystostock = 2;
//                }else{
//                    $daystostock = 2;
//                }
//                break;
//            case 6:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 12;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 8;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 6;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 3;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 3;
//                }
//                break;
//
//            case 9:
//                if ($AVGD_BTW_SLE <= 1) {
//                    $daystostock = 12;
//                } elseif ($AVGD_BTW_SLE <= 2) {
//                    $daystostock = 8;
//                } elseif ($AVGD_BTW_SLE <= 3) {
//                    $daystostock = 6;
//                } elseif ($AVGD_BTW_SLE <= 4) {
//                    $daystostock = 3;
//                } elseif ($AVGD_BTW_SLE <= 5) {
//                    $daystostock = 3;
//                }
//                break;
//            default:
//                break;
//        }

        $var_AVGSHIPQTY = $case_conveyarray[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($case_conveyarray[$key]['AVGD_BTW_SLE']);
        $var_AVGINV = intval($case_conveyarray[$key]['AVG_INV_OH']);
        $avgdailyshipqty = $case_conveyarray[$key]['DAILYUNIT'];
        $var_PCLIQU = $case_conveyarray[$key]['CPCLIQU'];

        $var_PCEHEIin = $case_conveyarray[$key]['CPCCHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $case_conveyarray[$key]['CPCEHEI'] * 0.393701;
        }

        $var_PCELENin = $case_conveyarray[$key]['CPCCLEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $case_conveyarray[$key]['CPCELEN'] * 0.393701;
        }

        $var_PCEWIDin = $case_conveyarray[$key]['CPCCWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $case_conveyarray[$key]['CPCEWID'] * 0.393701;
        }

        $var_caseqty = $case_conveyarray[$key]['CPCCPKU'];
        if ($var_caseqty == 0) {
            $var_caseqty = 1;
        }
        $PKGU_PERC_Restriction = $case_conveyarray[$key]['PERC_PERC'];
        $ITEM_NUMBER = intval($case_conveyarray[$key]['ITEM_NUMBER']);



//call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($case_conveyarray[$key]['PACKAGE_UNIT']);
            $var_pkty = $case_conveyarray[$key]['PACKAGE_TYPE'];
            $optqty = $slotqty_return_array['OPTQTY'];
            $slotqty = $slotqty_return_array['CEILQTY'];
            //write to table inventory_restricted
            include '../connections/conn_slotting.php';
            $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
            $result2->execute();
            $conn1 = null;
        } else {
            $slotqty = $slotqty_return_array['OPTQTY'];
        }


        foreach ($ConveyGridsArray as $key2 => $value) {
            $var_grid5 = $ConveyGridsArray[$key2]['LMGRD5'];
            $var_gridheight = $ConveyGridsArray[$key2]['LMHIGH'];
            $var_griddepth = $ConveyGridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $ConveyGridsArray[$key2]['LMWIDE'];

            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($SUGGESTED_MAX_test >= $slotqty) {
                break;
            }
        }

//Call the case true fit for L01

        $SUGGESTED_MAX = $SUGGESTED_MAX_array[1];
//Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));
        if ($var_grid5 == '58P48') {
            $SUGGESTED_MIN = 1;
        }
        
        if(count($ConveyGridsArray) == 0){
            echo 't';
        }

//append data to array for writing to my_npfmvc table
        $case_conveyarray[$key]['SUGGESTED_TIER'] = 'CSE_CONVEY';
        $case_conveyarray[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $case_conveyarray[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $case_conveyarray[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $case_conveyarray[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $case_conveyarray[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $case_conveyarray[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV,$case_conveyarray[$key]['SHIP_QTY_MN'],$case_conveyarray[$key]['AVGD_BTW_SLE']);
        if (substr($case_conveyarray[$key]['CUR_LOCATION'], 0, 1) == 'Q' || $case_conveyarray[$key]['PACKAGE_TYPE'] == 'PFR') {
            $case_conveyarray[$key]['CURRENT_IMPMOVES'] = 0;
        } else {
            $case_conveyarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($case_conveyarray[$key]['CURMAX'], $case_conveyarray[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV,$case_conveyarray[$key]['SHIP_QTY_MN'],$case_conveyarray[$key]['AVGD_BTW_SLE']);
        }
        $case_conveyarray[$key]['SUGGESTED_NEWLOCVOL'] = intval($ConveyGridsArray[$key2]['LMVOL9']);
        $case_conveyarray[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);

        $slottedpicks += $case_conveyarray[$key]['DAILYPICK'];
        $ConveyGridsArray[$key2]['GRID_COUNT'] -= 1;  //subtract used grid from array as no longer available
        if ($ConveyGridsArray[$key2]['GRID_COUNT'] <= 0) {
            unset($ConveyGridsArray[$key2]);
            $ConveyGridsArray = array_values($ConveyGridsArray);  //reset array
        }
    }
}


$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($case_conveyarray);

print_r($ConveyGridsArray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($case_conveyarray[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($case_conveyarray[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($case_conveyarray[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $case_conveyarray[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $case_conveyarray[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $case_conveyarray[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($case_conveyarray[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($case_conveyarray[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($case_conveyarray[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($case_conveyarray[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($case_conveyarray[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $case_conveyarray[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($case_conveyarray[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $case_conveyarray[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $case_conveyarray[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($case_conveyarray[$counter]['CPCEPKU']);
        $CPCIPKU = intval($case_conveyarray[$counter]['CPCIPKU']);
        $CPCCPKU = intval($case_conveyarray[$counter]['CPCCPKU']);
        $CPCFLOW = $case_conveyarray[$counter]['CPCFLOW'];
        $CPCTOTE = $case_conveyarray[$counter]['CPCTOTE'];
        $CPCSHLF = $case_conveyarray[$counter]['CPCSHLF'];
        $CPCROTA = $case_conveyarray[$counter]['CPCROTA'];
        $CPCESTK = intval($case_conveyarray[$counter]['CPCESTK']);
        $CPCLIQU = $case_conveyarray[$counter]['CPCLIQU'];
        $CPCELEN = $case_conveyarray[$counter]['CPCELEN'];
        $CPCEHEI = $case_conveyarray[$counter]['CPCEHEI'];
        $CPCEWID = $case_conveyarray[$counter]['CPCEWID'];
        $CPCCLEN = $case_conveyarray[$counter]['CPCCLEN'];
        $CPCCHEI = $case_conveyarray[$counter]['CPCCHEI'];
        $CPCCWID = $case_conveyarray[$counter]['CPCCWID'];
        $LMFIXA = $case_conveyarray[$counter]['LMFIXA'];
        $LMFIXT = $case_conveyarray[$counter]['LMFIXT'];
        $LMSTGT = $case_conveyarray[$counter]['LMSTGT'];
        $LMHIGH = intval($case_conveyarray[$counter]['LMHIGH']);
        $LMDEEP = intval($case_conveyarray[$counter]['LMDEEP']);
        $LMWIDE = intval($case_conveyarray[$counter]['LMWIDE']);
        $LMVOL9 = intval($case_conveyarray[$counter]['LMVOL9']);
        $LMTIER = rtrim($case_conveyarray[$counter]['LMTIER']);
        $LMGRD5 = $case_conveyarray[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $case_conveyarray[$counter]['DLY_CUBE_VEL'];
        if ($DLY_CUBE_VEL == NULL) {
            $DLY_CUBE_VEL = 0;
        }
        $DLY_PICK_VEL = $case_conveyarray[$counter]['DLY_PICK_VEL'];
        if ($DLY_PICK_VEL == NULL) {
            $DLY_PICK_VEL = 0;
        }
        $SUGGESTED_TIER = $case_conveyarray[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $case_conveyarray[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $case_conveyarray[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($case_conveyarray[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($case_conveyarray[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($case_conveyarray[$counter]['SUGGESTED_SLOTQTY']);
        $SUGGESTED_IMPMOVES = ($case_conveyarray[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($case_conveyarray[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($case_conveyarray[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($case_conveyarray[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $case_conveyarray[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $case_conveyarray[$counter]['DAILYUNIT'];


        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK', '$AVG_DAILY_UNIT')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    include '../connections/conn_slotting.php';
    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $conn1 = null;
    $maxrange +=1000;
} while ($counter <= $rowcount);
$conn1 = null;