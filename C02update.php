<?php

//initiate any exclusions to not slot to C02 locations
switch ($whssel) {
    case 3:
        $exclusionfilter = " and A.ITEM_NUMBER not in (SELECT exclude_item FROM slotting.slotting_exclusions WHERE exclude_whse = 3 and exclude_type = 'mainbuilding')";

        break;

    default:
        $exclusionfilter = " ";

        break;
}


$slowdownsizecutoff = 999999;

$daystostock = 15;  //stock 10 shipping occurences as max
//count L01 grids available
include '../connections/conn_slotting.php';
$C02sql = $conn1->prepare("SELECT DISTINCT
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
                                    $exclusionfilter
                            ORDER BY DAILYPICK desc");
$C02sql->execute();
$C02array = $C02sql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;


//Pull in all available C02 locations sorted descending by location volume  *** THIS DOES NOT WORK BECAUSE OF LOCATION DOES NOT HAVE ITEM ASSIGNED, THE LOCATION IS NOT RETURNED!! ***
include '../connections/conn_slotting.php';
$C02GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) as GRID_COUNT FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'C02' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel) GROUP BY LMGRD5, LMVOL9 ORDER BY LMVOL9 asc");
$C02GridsSQL->execute();
$C02GridsArray = $C02GridsSQL->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;

foreach ($C02array as $key => $value) {

    if (count($C02GridsArray) == 0) {
        break;
    }

    $var_AVGSHIPQTY = $C02array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($C02array[$key]['AVGD_BTW_SLE']);
    $var_AVGINV = intval($C02array[$key]['AVG_INV_OH']);
//    $avgdailyshipqty = round($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
//    if ($avgdailyshipqty == 0) {
//        $avgdailyshipqty = .000000001;
//    }
    $avgdailyshipqty = $C02array[$key]['DAILYUNIT'];
    $var_PCLIQU = $C02array[$key]['CPCLIQU'];

    $var_PCEHEIin = $C02array[$key]['CPCCHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $C02array[$key]['CPCEHEI'] * 0.393701;
    }

    $var_PCELENin = $C02array[$key]['CPCCLEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $C02array[$key]['CPCELEN'] * 0.393701;
    }

    $var_PCEWIDin = $C02array[$key]['CPCCWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $C02array[$key]['CPCEWID'] * 0.393701;
    }

    $var_caseqty = $C02array[$key]['CPCCPKU'];
    if ($var_caseqty == 0) {
        $var_caseqty = 1;
    }
    $PKGU_PERC_Restriction = $C02array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($C02array[$key]['ITEM_NUMBER']);



    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($C02array[$key]['PACKAGE_UNIT']);
        $var_pkty = $C02array[$key]['PACKAGE_TYPE'];
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


    foreach ($C02GridsArray as $key2 => $value) {

        $var_grid5 = $C02GridsArray[$key2]['LMGRD5'];  //pull in first grid 5.  Will decrement one from available grids at end of foreach loop to avoid over assigning grids
        $var_gridheight = $C02GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $C02GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $C02GridsArray[$key2]['LMWIDE'];

        //Call the case true fit for L01
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
    $C02array[$key]['SUGGESTED_TIER'] = 'C02';
    $C02array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $C02array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $C02array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $C02array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $C02array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;

    if (substr($C02array[$key]['CUR_LOCATION'], 0, 1) == 'Q' || $C02array[$key]['PACKAGE_TYPE'] == 'PFR') {
        $C02array[$key]['CURRENT_IMPMOVES'] = 0;
    } else {
        $C02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($C02array[$key]['CURMAX'], $C02array[$key]['CURMIN'], $C02array[$key]['DAILYUNIT'], $C02array[$key]['AVG_INV_OH'], $C02array[$key]['SHIP_QTY_MN'], $C02array[$key]['AVGD_BTW_SLE']);
    }


    $C02array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $C02array[$key]['SHIP_QTY_MN'], $C02array[$key]['AVGD_BTW_SLE']);
    $C02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($C02array[$key]['CURMAX'], $C02array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $C02array[$key]['SHIP_QTY_MN'], $C02array[$key]['AVGD_BTW_SLE']);
    $C02array[$key]['SUGGESTED_NEWLOCVOL'] = intval($C02GridsArray[0]['LMVOL9']);
    $C02array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);

    $C02GridsArray[$key2]['GRID_COUNT'] -= 1;  //subtract used grid from array as no longer available
    if ($C02GridsArray[$key2]['GRID_COUNT'] <= 0) {
        unset($C02GridsArray[$key2]);
        $C02GridsArray = array_values($C02GridsArray);  //reset array
    }
}

array_splice($C02array, ($key));

$C02array = array_values($C02array);  //reset array

$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($C02array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($C02array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($C02array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($C02array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $C02array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $C02array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $C02array[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($C02array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($C02array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($C02array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($C02array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($C02array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $C02array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($C02array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $C02array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $C02array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($C02array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($C02array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($C02array[$counter]['CPCCPKU']);
        $CPCFLOW = $C02array[$counter]['CPCFLOW'];
        $CPCTOTE = $C02array[$counter]['CPCTOTE'];
        $CPCSHLF = $C02array[$counter]['CPCSHLF'];
        $CPCROTA = $C02array[$counter]['CPCROTA'];
        $CPCESTK = intval($C02array[$counter]['CPCESTK']);
        $CPCLIQU = $C02array[$counter]['CPCLIQU'];
        $CPCELEN = $C02array[$counter]['CPCELEN'];
        $CPCEHEI = $C02array[$counter]['CPCEHEI'];
        $CPCEWID = $C02array[$counter]['CPCEWID'];
        $CPCCLEN = $C02array[$counter]['CPCCLEN'];
        $CPCCHEI = $C02array[$counter]['CPCCHEI'];
        $CPCCWID = $C02array[$counter]['CPCCWID'];
        $LMFIXA = $C02array[$counter]['LMFIXA'];
        $LMFIXT = $C02array[$counter]['LMFIXT'];
        $LMSTGT = $C02array[$counter]['LMSTGT'];
        $LMHIGH = intval($C02array[$counter]['LMHIGH']);
        $LMDEEP = intval($C02array[$counter]['LMDEEP']);
        $LMWIDE = intval($C02array[$counter]['LMWIDE']);
        $LMVOL9 = intval($C02array[$counter]['LMVOL9']);
        $LMTIER = $C02array[$counter]['LMTIER'];
        $LMGRD5 = $C02array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $C02array[$counter]['DLY_CUBE_VEL'];
        if ($DLY_CUBE_VEL == NULL) {
            $DLY_CUBE_VEL = 0;
        }
        $DLY_PICK_VEL = $C02array[$counter]['DLY_PICK_VEL'];
        if ($DLY_PICK_VEL == NULL) {
            $DLY_PICK_VEL = 0;
        }
        $SUGGESTED_TIER = $C02array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $C02array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $C02array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($C02array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($C02array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($C02array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($C02array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($C02array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($C02array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($C02array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $C02array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $C02array[$counter]['DAILYUNIT'];

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
