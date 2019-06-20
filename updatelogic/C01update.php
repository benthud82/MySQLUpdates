<?php


$slowdownsizecutoff = 999999;

$daystostock = 15;  //stock 10 shipping occurences as max
//count L01 grids available
//$C01Count = $alltierarray[$C01key]['TIER_COUNT'];
//if ($whssel == 7) {
//    $L01Count += 62;
//}
include '../../CustomerAudit/connection/connection_details.php';
$C01sql = $conn1->prepare("SELECT DISTINCT
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
                            WHERE
                                WAREHOUSE = $whssel
                                    and (A.PACKAGE_TYPE not in ('LSE' , 'INP') or A.CUR_LOCATION like ('Q%'))
                                    and CUR_LOCATION not like 'N%'
                                    and ITEM_TYPE = 'ST'
                                    and CPCCONV <> 'N'
                            ORDER BY DAILYPICK desc
                            ");
$C01sql->execute();
$C01array = $C01sql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;


//$C01GridsArray = array();
switch ($whssel) {  //TODO: Find a way to pull this in manually?  Tier is not listed in location file
    case 1:
        $C01GridsArray[0]['LMGRD5'] = '40P48';
        $C01GridsArray[0]['LMHIGH'] = 140;
        $C01GridsArray[0]['LMDEEP'] = 288;
        $C01GridsArray[0]['LMWIDE'] = 144;
        $C01GridsArray[0]['LMVOL9'] = 1935360;
        $C01GridsArray[0]['GRID_COUNT'] = 14;

        $C01GridsArray[1]['LMGRD5'] = '20P60';
        $C01GridsArray[1]['LMHIGH'] = 120;
        $C01GridsArray[1]['LMDEEP'] = 110;
        $C01GridsArray[1]['LMWIDE'] = 48;
        $C01GridsArray[1]['LMVOL9'] = 792000;
        $C01GridsArray[1]['GRID_COUNT'] = 21;
        break;
    default:
        include '../../CustomerAudit/connection/connection_details.php';
        //Pull in all available C01 locations sorted descending by location volume  *** THIS DOES NOT WORK BECAUSE OF LOCATION DOES NOT HAVE ITEM ASSIGNED, THE LOCATION IS NOT RETURNED!! ***
        $C01GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) as GRID_COUNT FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'C01' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel) GROUP BY LMGRD5, LMVOL9 ORDER BY LMVOL9 desc");
        $C01GridsSQL->execute();
        $C01GridsArray = $C01GridsSQL->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
}


foreach ($C01array as $key => $value) {

    //Loop through $C01GridsArray, calculate true fit, and deduct one from the count until all available grids are filled.
    //Already sorted in descending volume to match descending volume of C01 recs

    if (count($C01GridsArray) == 0) {
        break;
    }

    $var_grid5 = $C01GridsArray[0]['LMGRD5'];  //pull in first grid 5.  Will decrement one from available grids at end of foreach loop to avoid over assigning grids
    $var_gridheight = $C01GridsArray[0]['LMHIGH'];
    $var_griddepth = $C01GridsArray[0]['LMDEEP'];
    $var_gridwidth = $C01GridsArray[0]['LMWIDE'];

    $var_AVGSHIPQTY = $C01array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($C01array[$key]['AVGD_BTW_SLE']);
    $var_AVGINV = intval($C01array[$key]['AVG_INV_OH']);
//    $avgdailyshipqty = round($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
//    if ($avgdailyshipqty == 0) {
//        $avgdailyshipqty = .000000001;
//    }

    $avgdailyshipqty = $C01array[$key]['DAILYUNIT'];
    $var_PCLIQU = $C01array[$key]['CPCLIQU'];

    $var_PCEHEIin = $C01array[$key]['CPCCHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $C01array[$key]['CPCEHEI'] * 0.393701;
    }

    $var_PCELENin = $C01array[$key]['CPCCLEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $C01array[$key]['CPCELEN'] * 0.393701;
    }

    $var_PCEWIDin = $C01array[$key]['CPCCWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $C01array[$key]['CPCEWID'] * 0.393701;
    }

    $var_caseqty = $C01array[$key]['CPCCPKU'];
    if ($var_caseqty == 0) {
        $var_caseqty = 1;
    }
    $PKGU_PERC_Restriction = $C01array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($C01array[$key]['ITEM_NUMBER']);



    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($C01array[$key]['PACKAGE_UNIT']);
        $var_pkty = $C01array[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];
        //write to table inventory_restricted
        include '../../CustomerAudit/connection/connection_details.php';
        $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
        $result2->execute();
        $conn1 = null;
    } else {
        $slotqty = $slotqty_return_array['OPTQTY'];
    }

    //Call the case true fit for L01
    $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
    $SUGGESTED_MAX = $SUGGESTED_MAX_array[1];
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

    //append data to array for writing to my_npfmvc table
    $C01array[$key]['SUGGESTED_TIER'] = 'C01';
    $C01array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $C01array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $C01array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $C01array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $C01array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $C01array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $C01array[$key]['SHIP_QTY_MN'], $C01array[$key]['AVGD_BTW_SLE']);
    $C01array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($C01array[$key]['CURMAX'], $C01array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $C01array[$key]['SHIP_QTY_MN'], $C01array[$key]['AVGD_BTW_SLE']);
    $C01array[$key]['SUGGESTED_NEWLOCVOL'] = intval($C01GridsArray[0]['LMVOL9']);
    $C01array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);

    $C01GridsArray[0]['GRID_COUNT'] -= 1;  //subtract used grid from array as no longer available
    if ($C01GridsArray[0]['GRID_COUNT'] <= 0) {
        unset($C01GridsArray[0]);
        $C01GridsArray = array_values($C01GridsArray);  //reset array
    }
}

array_splice($C01array, ($key));

$C02array = array_values($C01array);  //reset array

$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($C01array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($C01array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($C01array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($C01array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $C01array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $C01array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $C01array[$counter]['CUR_LOCATION'];
        $DAYS_FRM_SLE = intval($C01array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($C01array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($C01array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($C01array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($C01array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $C01array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($C01array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $C01array[$counter]['SHIP_QTY_SD'];
        $ITEM_TYPE = $C01array[$counter]['ITEM_TYPE'];
        $CPCEPKU = intval($C01array[$counter]['CPCEPKU']);
        $CPCIPKU = intval($C01array[$counter]['CPCIPKU']);
        $CPCCPKU = intval($C01array[$counter]['CPCCPKU']);
        $CPCFLOW = $C01array[$counter]['CPCFLOW'];
        $CPCTOTE = $C01array[$counter]['CPCTOTE'];
        $CPCSHLF = $C01array[$counter]['CPCSHLF'];
        $CPCROTA = $C01array[$counter]['CPCROTA'];
        $CPCESTK = intval($C01array[$counter]['CPCESTK']);
        $CPCLIQU = $C01array[$counter]['CPCLIQU'];
        $CPCELEN = $C01array[$counter]['CPCELEN'];
        $CPCEHEI = $C01array[$counter]['CPCEHEI'];
        $CPCEWID = $C01array[$counter]['CPCEWID'];
        $CPCCLEN = $C01array[$counter]['CPCCLEN'];
        $CPCCHEI = $C01array[$counter]['CPCCHEI'];
        $CPCCWID = $C01array[$counter]['CPCCWID'];
        $LMFIXA = $C01array[$counter]['LMFIXA'];
        $LMFIXT = $C01array[$counter]['LMFIXT'];
        $LMSTGT = $C01array[$counter]['LMSTGT'];
        $LMHIGH = intval($C01array[$counter]['LMHIGH']);
        $LMDEEP = intval($C01array[$counter]['LMDEEP']);
        $LMWIDE = intval($C01array[$counter]['LMWIDE']);
        $LMVOL9 = intval($C01array[$counter]['LMVOL9']);
        $LMTIER = $C01array[$counter]['LMTIER'];
        $LMGRD5 = $C01array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $C01array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $C01array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $C01array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $C01array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $C01array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($C01array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($C01array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($C01array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($C01array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($C01array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = intval($C01array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($C01array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $C01array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $C01array[$counter]['DAILYUNIT'];

        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    include '../../CustomerAudit/connection/connection_details.php';
    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $conn1 = null;
    $maxrange +=1000;
} while ($counter <= $rowcount);

$conn1 = null;
