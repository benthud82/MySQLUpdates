<?php

//For Sparks, do I need to restrict excluded items at this point or still find optimal slot size and address restriction at optimal bay calculation

$slowdownsizecutoff = 999999;

$daystostock = 15;  //stock 10 shipping occurences as max

switch ($whssel) {
    case 2:  //testing standardized locations
        $ConveyGridsArray = array();
        $ConveyGridsArray[0]['LMGRD5'] = '28D16';
        $ConveyGridsArray[0]['LMHIGH'] = 28;
        $ConveyGridsArray[0]['LMDEEP'] = 44;
        $ConveyGridsArray[0]['LMWIDE'] = 16;
        $ConveyGridsArray[0]['LMVOL9'] = 19712;
        $ConveyGridsArray[0]['GRID_COUNT'] = 3123;

        $ConveyGridsArray[1]['LMGRD5'] = '28D24';
        $ConveyGridsArray[1]['LMHIGH'] = 28;
        $ConveyGridsArray[1]['LMDEEP'] = 44;
        $ConveyGridsArray[1]['LMWIDE'] = 24;
        $ConveyGridsArray[1]['LMVOL9'] = 29568;
        $ConveyGridsArray[1]['GRID_COUNT'] = 2082;

        $ConveyGridsArray[2]['LMGRD5'] = '58P48';
        $ConveyGridsArray[2]['LMHIGH'] = 58;
        $ConveyGridsArray[2]['LMDEEP'] = 44;
        $ConveyGridsArray[2]['LMWIDE'] = 48;
        $ConveyGridsArray[2]['LMVOL9'] = 122496;
        $ConveyGridsArray[2]['GRID_COUNT'] = 2082;
        break;

//    case 7:  //testing standardized locations
//        $ConveyGridsArray = array();
//
//        $ConveyGridsArray[0]['LMGRD5'] = '28D24';
//        $ConveyGridsArray[0]['LMHIGH'] = 28;
//        $ConveyGridsArray[0]['LMDEEP'] = 44;
//        $ConveyGridsArray[0]['LMWIDE'] = 24;
//        $ConveyGridsArray[0]['LMVOL9'] = 29568;
//        $ConveyGridsArray[0]['GRID_COUNT'] = 2704;
//
//        $ConveyGridsArray[1]['LMGRD5'] = '58P48';
//        $ConveyGridsArray[1]['LMHIGH'] = 58;
//        $ConveyGridsArray[1]['LMDEEP'] = 44;
//        $ConveyGridsArray[1]['LMWIDE'] = 48;
//        $ConveyGridsArray[1]['LMVOL9'] = 122496;
//        $ConveyGridsArray[1]['GRID_COUNT'] = 1352;
//        break;

    case 11:  //testing standardized locations
        $ConveyGridsArray = array();
        $ConveyGridsArray[0]['LMGRD5'] = '28D16';
        $ConveyGridsArray[0]['LMHIGH'] = 28;
        $ConveyGridsArray[0]['LMDEEP'] = 44;
        $ConveyGridsArray[0]['LMWIDE'] = 16;
        $ConveyGridsArray[0]['LMVOL9'] = 19712;
        $ConveyGridsArray[0]['GRID_COUNT'] = 474;

        $ConveyGridsArray[1]['LMGRD5'] = '28D24';
        $ConveyGridsArray[1]['LMHIGH'] = 28;
        $ConveyGridsArray[1]['LMDEEP'] = 44;
        $ConveyGridsArray[1]['LMWIDE'] = 24;
        $ConveyGridsArray[1]['LMVOL9'] = 29568;
        $ConveyGridsArray[1]['GRID_COUNT'] = 316;

        $ConveyGridsArray[2]['LMGRD5'] = '58P48';
        $ConveyGridsArray[2]['LMHIGH'] = 58;
        $ConveyGridsArray[2]['LMDEEP'] = 44;
        $ConveyGridsArray[2]['LMWIDE'] = 48;
        $ConveyGridsArray[2]['LMVOL9'] = 122496;
        $ConveyGridsArray[2]['GRID_COUNT'] = 158;
        break;

//    case 3:  //testing standardized locations
//        $ConveyGridsArray[0]['LMGRD5'] = '28D24';
//        $ConveyGridsArray[0]['LMHIGH'] = 28;
//        $ConveyGridsArray[0]['LMDEEP'] = 44;
//        $ConveyGridsArray[0]['LMWIDE'] = 24;
//        $ConveyGridsArray[0]['LMVOL9'] = 29568;
//        $ConveyGridsArray[0]['GRID_COUNT'] = 4056;  //1320 in main, 2736 in case
//
//        $ConveyGridsArray[1]['LMGRD5'] = '58P48';
//        $ConveyGridsArray[1]['LMHIGH'] = 58;
//        $ConveyGridsArray[1]['LMDEEP'] = 44;
//        $ConveyGridsArray[1]['LMWIDE'] = 48;
//        $ConveyGridsArray[1]['LMVOL9'] = 122496;
//        $ConveyGridsArray[1]['GRID_COUNT'] = 2028;  //660 in main, 1368 in case
//
//        break;

    default:
include '../connections/conn_slotting.php';
        $conveyGridsSQL = $conn1->prepare("SELECT 
                                                LMGRD5,
                                                LMHIGH,
                                                LMDEEP,
                                                LMWIDE,
                                                LMVOL9,
                                                count(LMGRD5) as GRID_COUNT
                                            FROM
                                                slotting.mysql_npflsm
                                            WHERE
                                                LMWHSE = $whssel
                                                    and LMTIER in ('C03' , 'C05', 'C06', 'C19', 'C20', 'C21')
                                                    and LMGRD5 <> ''
                                                    and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT 
                                                        gridexcl_key
                                                    from
                                                        slotting.gridexclusions
                                                    WHERE
                                                        gridexcl_whse = $whssel)
                                            GROUP BY LMGRD5 , LMVOL9
                                            ORDER BY LMVOL9 asc");
        $conveyGridsSQL->execute();
        $ConveyGridsArray = $conveyGridsSQL->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        break;
}


if ($whssel == 7) {
    $CSE_pick_limit_noncon = .9;
} elseif ($whssel == 2) {
    $CSE_pick_limit_noncon = .9;
} else {
    $CSE_pick_limit_noncon = .9;
}
include '../connections/conn_slotting.php';
$CSEpicksSQL_noncon = $conn1->prepare("SELECT 
                                            sum($sql_dailypick_case) as TOTPICKS
                                        FROM
                                            mysql_nptsld A
                                                join
                                            slotting.npfcpcsettings C ON C.CPCWHSE = A.WAREHOUSE
                                                and C.CPCITEM = A.ITEM_NUMBER
                                        WHERE
                                            WAREHOUSE = $whssel
                                                and PACKAGE_TYPE not in ('LSE' , 'INP')
                                                and CPCCONV = 'N'");
$CSEpicksSQL_noncon->execute();
$CSEpicksArray_noncon = $CSEpicksSQL_noncon->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;
$CSE_Picks_noncon = intval($CSEpicksArray_noncon[0]['TOTPICKS']);

$cse_low_picks_noncon = intval($CSE_pick_limit_noncon * $CSE_Picks_noncon);

include '../connections/conn_slotting.php';
$case_nonconsql = $conn1->prepare("SELECT DISTINCT
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
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailyunit) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / CPCCPKU
                                    else ($sql_dailyunit) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_CUBE_VEL,
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailypick_case) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
                                    else ($sql_dailypick_case) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick_case as DAILYPICK,
                                $sql_dailypick_case as DAILYUNIT
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
                            WHERE
                                WAREHOUSE = $whssel
                                    and CUR_LOCATION not like 'W00%'
                                    and (A.PACKAGE_TYPE not in ('LSE' , 'INP') or A.CUR_LOCATION like ('Q%'))
                                    and CUR_LOCATION not like 'N%'
                                    and ITEM_TYPE = 'ST'
                                    and CPCCONV = 'N'
                            ORDER BY DAILYPICK desc");
$case_nonconsql->execute();
$case_nonconarray = $case_nonconsql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;



////Standardize grid locs for Dallas, same for other DCs?
//switch ($whssel) {
////    case 7:
////        $NonconGridsArray = array();
////        $NonconGridsArray[0]['LMGRD5'] = '28D24';
////        $NonconGridsArray[0]['LMHIGH'] = 28;
////        $NonconGridsArray[0]['LMDEEP'] = 44;
////        $NonconGridsArray[0]['LMWIDE'] = 24;
////        $NonconGridsArray[0]['LMVOL9'] = 29568;
////        $NonconGridsArray[0]['COUNT'] = 10;
////
////        $NonconGridsArray[1]['LMGRD5'] = '58P48';
////        $NonconGridsArray[1]['LMHIGH'] = 58;
////        $NonconGridsArray[1]['LMDEEP'] = 44;
////        $NonconGridsArray[1]['LMWIDE'] = 48;
////        $NonconGridsArray[1]['LMVOL9'] = 122496;
////        $NonconGridsArray[1]['COUNT'] = 10;
////        break;
//
//    default:
//
//        $NonconGridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) as GRID_COUNT FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER in ('C07', 'C08', 'C09') and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 ORDER BY LMVOL9 asc");
//        $NonconGridsSQL->execute();
//        $NonconGridsArray = $NonconGridsSQL->fetchAll(pdo::FETCH_ASSOC);
//        break;
//}
//slotted picks initialize
$slottedpicks = 0;

foreach ($case_nonconarray as $key => $value) {
    if ($slottedpicks >= $cse_low_picks_noncon) {  //have slotted picks exceeded min quantity, if so put rest to reserve
        $case_nonconarray[$key]['SUGGESTED_TIER'] = 'CSE_PFR_NONCON';
        $case_nonconarray[$key]['SUGGESTED_GRID5'] = 'C_PFR';
        $case_nonconarray[$key]['SUGGESTED_DEPTH'] = 0;
        $case_nonconarray[$key]['SUGGESTED_MAX'] = 0;
        $case_nonconarray[$key]['SUGGESTED_MIN'] = 0;
        $case_nonconarray[$key]['SUGGESTED_SLOTQTY'] = 0;
        $case_nonconarray[$key]['SUGGESTED_IMPMOVES'] = 0;


        $ITEM_NUMBER = intval($case_nonconarray[$key]['ITEM_NUMBER']);

        if (substr($case_nonconarray[$key]['CUR_LOCATION'], 0, 1) == 'Q' || $case_nonconarray[$key]['PACKAGE_TYPE'] == 'PFR') {
            $case_nonconarray[$key]['CURRENT_IMPMOVES'] = 0;
        } else {
            $case_nonconarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($case_nonconarray[$key]['CURMAX'], $case_nonconarray[$key]['CURMIN'], $case_nonconarray[$key]['DAILYUNIT'], $case_nonconarray[$key]['AVG_INV_OH'], $case_nonconarray[$key]['SHIP_QTY_MN'], $case_nonconarray[$key]['AVGD_BTW_SLE']);
        }

        $case_nonconarray[$key]['SUGGESTED_NEWLOCVOL'] = 0;
        $case_nonconarray[$key]['SUGGESTED_DAYSTOSTOCK'] = 0;
    } else {

        $var_AVGSHIPQTY = $case_nonconarray[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($case_nonconarray[$key]['AVGD_BTW_SLE']);
        $var_AVGINV = intval($case_nonconarray[$key]['AVG_INV_OH']);
        $avgdailyshipqty = $case_nonconarray[$key]['DAILYUNIT'];
        $var_PCLIQU = $case_nonconarray[$key]['CPCLIQU'];

        $var_PCEHEIin = $case_nonconarray[$key]['CPCCHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $case_nonconarray[$key]['CPCEHEI'] * 0.393701;
        }

        $var_PCELENin = $case_nonconarray[$key]['CPCCLEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $case_nonconarray[$key]['CPCELEN'] * 0.393701;
        }

        $var_PCEWIDin = $case_nonconarray[$key]['CPCCWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $case_nonconarray[$key]['CPCEWID'] * 0.393701;
        }

        $var_caseqty = $case_nonconarray[$key]['CPCCPKU'];
        if ($var_caseqty == 0) {
            $var_caseqty = 1;
        }
        $PKGU_PERC_Restriction = $case_nonconarray[$key]['PERC_PERC'];
        $ITEM_NUMBER = intval($case_nonconarray[$key]['ITEM_NUMBER']);



//call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($case_nonconarray[$key]['PACKAGE_UNIT']);
            $var_pkty = $case_nonconarray[$key]['PACKAGE_TYPE'];
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

//append data to array for writing to my_npfmvc table
        $case_nonconarray[$key]['SUGGESTED_TIER'] = 'CSE_NONCON';
        $case_nonconarray[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $case_nonconarray[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $case_nonconarray[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $case_nonconarray[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $case_nonconarray[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $case_nonconarray[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $case_nonconarray[$key]['SHIP_QTY_MN'], $case_nonconarray[$key]['AVGD_BTW_SLE']);

        if (substr($case_nonconarray[$key]['CUR_LOCATION'], 0, 1) == 'Q' || $case_nonconarray[$key]['PACKAGE_TYPE'] == 'PFR') {
            $case_nonconarray[$key]['CURRENT_IMPMOVES'] = 0;
        } else {
            $case_nonconarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($case_nonconarray[$key]['CURMAX'], $case_nonconarray[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $case_nonconarray[$key]['SHIP_QTY_MN'], $case_nonconarray[$key]['AVGD_BTW_SLE']);
        }

        $case_nonconarray[$key]['SUGGESTED_NEWLOCVOL'] = intval($ConveyGridsArray[$key2]['LMVOL9']);
        $case_nonconarray[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);

        $slottedpicks += $case_nonconarray[$key]['DAILYPICK'];
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
$rowcount = count($case_nonconarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($case_nonconarray[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($case_nonconarray[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($case_nonconarray[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $case_nonconarray[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $case_nonconarray[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $case_nonconarray[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($case_nonconarray[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($case_nonconarray[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($case_nonconarray[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($case_nonconarray[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($case_nonconarray[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $case_nonconarray[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($case_nonconarray[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $case_nonconarray[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $case_nonconarray[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($case_nonconarray[$counter]['CPCEPKU']);
        $CPCIPKU = intval($case_nonconarray[$counter]['CPCIPKU']);
        $CPCCPKU = intval($case_nonconarray[$counter]['CPCCPKU']);
        $CPCFLOW = $case_nonconarray[$counter]['CPCFLOW'];
        $CPCTOTE = $case_nonconarray[$counter]['CPCTOTE'];
        $CPCSHLF = $case_nonconarray[$counter]['CPCSHLF'];
        $CPCROTA = $case_nonconarray[$counter]['CPCROTA'];
        $CPCESTK = intval($case_nonconarray[$counter]['CPCESTK']);
        $CPCLIQU = $case_nonconarray[$counter]['CPCLIQU'];
        $CPCELEN = $case_nonconarray[$counter]['CPCELEN'];
        $CPCEHEI = $case_nonconarray[$counter]['CPCEHEI'];
        $CPCEWID = $case_nonconarray[$counter]['CPCEWID'];
        $CPCCLEN = $case_nonconarray[$counter]['CPCCLEN'];
        $CPCCHEI = $case_nonconarray[$counter]['CPCCHEI'];
        $CPCCWID = $case_nonconarray[$counter]['CPCCWID'];
        $LMFIXA = $case_nonconarray[$counter]['LMFIXA'];
        $LMFIXT = $case_nonconarray[$counter]['LMFIXT'];
        $LMSTGT = $case_nonconarray[$counter]['LMSTGT'];
        $LMHIGH = intval($case_nonconarray[$counter]['LMHIGH']);
        $LMDEEP = intval($case_nonconarray[$counter]['LMDEEP']);
        $LMWIDE = intval($case_nonconarray[$counter]['LMWIDE']);
        $LMVOL9 = intval($case_nonconarray[$counter]['LMVOL9']);
        $LMTIER = rtrim($case_nonconarray[$counter]['LMTIER']);
        $LMGRD5 = $case_nonconarray[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $case_nonconarray[$counter]['DLY_CUBE_VEL'];
        if ($DLY_CUBE_VEL == NULL) {
            $DLY_CUBE_VEL = 0;
        }
        $DLY_PICK_VEL = $case_nonconarray[$counter]['DLY_PICK_VEL'];
        if ($DLY_PICK_VEL == NULL) {
            $DLY_PICK_VEL = 0;
        }
        $SUGGESTED_TIER = $case_nonconarray[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $case_nonconarray[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $case_nonconarray[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($case_nonconarray[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($case_nonconarray[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($case_nonconarray[$counter]['SUGGESTED_SLOTQTY']);
        $SUGGESTED_IMPMOVES = ($case_nonconarray[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($case_nonconarray[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($case_nonconarray[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($case_nonconarray[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $case_nonconarray[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $case_nonconarray[$counter]['DAILYUNIT'];

        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
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