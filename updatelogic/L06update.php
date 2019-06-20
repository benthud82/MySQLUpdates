<?php

//Pull in total picks by day.  Want to keep less than 1% of picks in slow move zone??
if ($whssel == 3) {
    $L06_pick_limit = .03;
} elseif ($whssel == 2) {
    $L06_pick_limit = .03;
} else {
    $L06_pick_limit = .01;
}

include '../../CustomerAudit/connection/connection_details.php';
$LSEpicksSQL = $conn1->prepare("SELECT 
                                    sum(case
                                    when AVGD_BTW_SLE >= 365 then 0
                                    when DAYS_FRM_SLE >= 180 then 0
                                    when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                    when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then PICK_QTY_MN
                                    when AVGD_BTW_SLE = 0 then (PICK_QTY_MN / DAYS_FRM_SLE)
                                    else (PICK_QTY_MN / AVGD_BTW_SLE)
                                end) as TOTPICKS
                                FROM
                                    mysql_nptsld
                                WHERE
                                    WAREHOUSE = $whssel and PACKAGE_TYPE = 'LSE'");
$LSEpicksSQL->execute();
$conn1 = null;
$LSEpicksArray = $LSEpicksSQL->fetchAll(pdo::FETCH_ASSOC);
$LSE_Picks = intval($LSEpicksArray[0]['TOTPICKS']);
$Max_L06_picks = $L06_pick_limit * $LSE_Picks;  //maximum number of picks to reside in L06 based off daily pick forecast
//Pull in available L06 Grid5s by volume ascending order

include '../../CustomerAudit/connection/connection_details.php';
$L06GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L06' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
$L06GridsSQL->execute();
$L06GridsArray = $L06GridsSQL->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;
include '../../CustomerAudit/connection/connection_details.php';
$L06sql = $conn1->prepare("SELECT DISTINCT
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
        when
            X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0
        then
            (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
        else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
    end as DLY_CUBE_VEL,
    case
        when
            X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0
        then
            ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID
        else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID
    end as DLY_PICK_VEL,
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
        AND $sql_dailypick <= 1
        and A.PACKAGE_TYPE = ('LSE')
        and B.ITEM_TYPE = 'ST'
        and F.ITEM_NUMBER IS NULL
ORDER BY $sql_dailypick asc");


$L06sql->execute();
$L06array = $L06sql->fetchAll(pdo::FETCH_ASSOC);
$conn1 = null;
$running_L06_picks = 0; //initilize picks
foreach ($L06array as $key => $value) {
    if ($running_L06_picks > $Max_L06_picks) {
        break;  //if exceeded pre-determined max picks from L06
    }

    //Check OK in Shelf Setting
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


    $slotqty = intval(ceil($var_AVGINV * $PKGU_PERC_Restriction)); //does it make sense to slot slow movers to average inventory?

    if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
        $slotqty = 1;
    }

    //calculate total slot valume to determine what grid to start
    $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

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
            break;
        }
    }


    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));
    if ($SUGGESTED_MIN == 0) {
        $SUGGESTED_MIN = 1;
    }

    //append data to array for writing to my_npfmvc table
    $L06array[$key]['SUGGESTED_TIER'] = 'L06';
    $L06array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L06array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L06array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L06array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L06array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L06array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L06array[$key]['CURMAX'], $L06array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L06array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

    $running_L06_picks +=$avgdailypickqty;
}


//L06 items have been designated.  Loop through L06 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L06array, ($key));

$L06array = array_values($L06array);  //reset array



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
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = intval($L06array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L06array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L06array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L06array[$counter]['PACKAGE_TYPE'];
        $DSL_TYPE = $L06array[$counter]['DSL_TYPE'];
        $CUR_LOCATION = $L06array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L06array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = intval($L06array[$counter]['AVGD_BTW_SLE']);
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
        $VCBAY = substr($CUR_LOCATION, 0, 5);
        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY')";
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
