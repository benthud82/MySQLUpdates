<?php
$C03_limit = 300;
//available pallet count grouped by size
//$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCCPKU,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK,AVG_DAILY_PICK,AVG_DAILY_UNIT,VCBAY';
$SUGG_EQUIP = 'PALLETJACK';
//*****************************
//EXTERNALIZED VARIABLES
$casebreakeven = 19;  //number of cases per pallet to allow picks to outweigh the replen cost
$dailypicklimit = .1;
$dslslimit = 45;
$var_gridheight = 58;
$var_griddepth = 48;
$var_gridwidth = 48;
$var_grid5 = '58P48';
$avginvmultiplier = 1.2;
$SUGG_LEVEL = 0;
//*****************************

$array_sqlpush = array();

$sql_palletitems = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    A.DSL_TYPE,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE LMLOC
    END AS CUR_LOCATION,
    A.DAYS_FRM_SLE,
    A.AVGD_BTW_SLE,
    A.AVG_INV_OH,
    A.NBR_SHIP_OCC,
    A.PICK_QTY_MN,
    A.PICK_QTY_SD,
    A.SHIP_QTY_MN,
    A.SHIP_QTY_SD,
    B.ITEM_TYPE,
    C.CPCCPKU,
    C.CPCPPKU / A.PACKAGE_UNIT AS CASES_PER_PALLET,
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
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE D.LMFIXA
    END AS LMFIXA,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE D.LMFIXT
    END AS LMFIXT,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE D.LMSTGT
    END AS LMSTGT,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 999
        ELSE D.LMHIGH
    END AS LMHIGH,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 999
        ELSE D.LMDEEP
    END AS LMDEEP,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 999
        ELSE D.LMWIDE
    END AS LMWIDE,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 999
        ELSE D.LMVOL9
    END AS LMVOL9,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE D.LMTIER
    END AS LMTIER,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 'PFR'
        ELSE D.LMGRD5
    END AS LMGRD5,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 0
        ELSE D.CURMAX
    END AS CURMAX,
    CASE
        WHEN A.PACKAGE_TYPE = 'PFR' THEN 0
        ELSE D.CURMIN
    END AS CURMIN,
    case
        when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then ((SMTH_SLS_MN) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / C.CPCCPKU
        else (SMTH_SLS_MN) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
    end as DLY_CUBE_VEL,
    case
        when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then ((SMTH_PCK_MN) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
        else (SMTH_PCK_MN) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
    end as DLY_PICK_VEL,
    SMTH_PCK_MN as DAILYPICK,
    SMTH_SLS_MN as DAILYUNIT,
         CASE WHEN D.LMTIER = 'C01' then  'PALLETJACK' when D.LMTIER = 'C02' then 'BELTLINE' when D.LMTIER in ('C03','C05','C06') and FLOOR = 'Y' then 'PALLETJACK' else 'ORDERPICKER' end as CURR_EQUIP
                           FROM
    slotting.mysql_nptsld A
        JOIN
    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
        AND B.ITEM = A.ITEM_NUMBER
        JOIN
    slotting.npfcpcsettings C ON C.CPCWHSE = A.WAREHOUSE
        AND C.CPCITEM = A.ITEM_NUMBER
        JOIN
    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
        AND D.LMITEM = A.ITEM_NUMBER
        AND CASE
        WHEN PACKAGE_TYPE = 'PFR' THEN A.PACKAGE_UNIT = 0
        ELSE A.PACKAGE_UNIT
    END = LMPKGU
  --  and LMLOC = A.CUR_LOCATION
        JOIN
    slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
        AND E.PERC_ITEM = A.ITEM_NUMBER
        AND E.PERC_PKGU = A.PACKAGE_UNIT
        AND E.PERC_PKGTYPE = A.PACKAGE_TYPE
        LEFT JOIN
    slotting.my_npfmvc_cse F ON F.WAREHOUSE = A.WAREHOUSE
        AND F.ITEM_NUMBER = A.ITEM_NUMBER
        AND F.PACKAGE_TYPE = A.PACKAGE_TYPE
        AND F.PACKAGE_UNIT = A.PACKAGE_UNIT
        LEFT JOIN
    slotting.case_floor_locs FL on A.WAREHOUSE = FL.WHSE and LMLOC = FL.LOCATION
WHERE
    A.WAREHOUSE = $whse
        AND A.CUR_LOCATION NOT LIKE 'W00%'
        AND (A.PACKAGE_TYPE NOT IN ('LSE' , 'INP')
        OR A.CUR_LOCATION LIKE ('Q%'))
        AND A.CUR_LOCATION NOT LIKE 'N%'
        AND B.ITEM_TYPE = 'ST'
        AND CPCCONV <> 'N'
        $locationsql
        $sql_inp_pfr    
        AND F.ITEM_NUMBER IS NULL
        AND C.CPCPPKU / A.PACKAGE_UNIT >= $casebreakeven
        AND A.AVG_INV_OH * PERC_PERC * $avginvmultiplier >= C.CPCPPKU
        AND SMTH_PCK_MN > $dailypicklimit
        AND A.DAYS_FRM_SLE <= $dslslimit");
$sql_palletitems->execute();
$array_palletitems = $sql_palletitems->fetchAll(pdo::FETCH_ASSOC);

//loop through items and determine if can average inventory can fit in pallet location
$count = 0;
foreach ($array_palletitems as $key => $value) {

    $item = $array_palletitems[$key]['ITEM_NUMBER'];
    $CPCELEN = $array_palletitems[$key]['CPCELEN'];
    $CPCEHEI = $array_palletitems[$key]['CPCEHEI'];
    $CPCEWID = $array_palletitems[$key]['CPCEWID'];
    $CPCCLEN = $array_palletitems[$key]['CPCCLEN'];
    $CPCCHEI = $array_palletitems[$key]['CPCCHEI'];
    $CPCCWID = $array_palletitems[$key]['CPCCWID'];
    $PACKAGE_UNIT = $array_palletitems[$key]['PACKAGE_UNIT'];
    $AVG_INV_OH = $array_palletitems[$key]['AVG_INV_OH'];
    $DSL_TYPE = $array_palletitems[$key]['DSL_TYPE'];
    $PICK_QTY_MN = $array_palletitems[$key]['PICK_QTY_MN'];
    $PICK_QTY_SD = $array_palletitems[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = $array_palletitems[$key]['SHIP_QTY_MN'];
    $SHIP_QTY_SD = $array_palletitems[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $array_palletitems[$key]['ITEM_TYPE'];
    $LMFIXA = $array_palletitems[$key]['LMFIXA'];
    $LMFIXT = $array_palletitems[$key]['LMFIXT'];
    $LMSTGT = $array_palletitems[$key]['LMSTGT'];
    $LMTIER = $array_palletitems[$key]['LMTIER'];
    $LMGRD5 = $array_palletitems[$key]['LMGRD5'];
    $LMHIGH = $array_palletitems[$key]['LMHIGH'];
    $LMDEEP = $array_palletitems[$key]['LMDEEP'];
    $LMVOL9 = $array_palletitems[$key]['LMVOL9'];
    $LMWIDE = $array_palletitems[$key]['LMWIDE'];
    $DLY_CUBE_VEL = $array_palletitems[$key]['DLY_CUBE_VEL'];
    $DLY_PICK_VEL = $array_palletitems[$key]['DLY_PICK_VEL'];
    $DAYS_FRM_SLE = $array_palletitems[$key]['DAYS_FRM_SLE'];
    $CURR_EQUIP = $array_palletitems[$key]['CURR_EQUIP'];

    if ($CPCCLEN > 0) {
        $item_len = $CPCCLEN * 0.393701;
    } else {
        $item_len = $CPCELEN * 0.393701;
    }

    if ($CPCCHEI > 0) {
        $item_hei = $CPCCHEI * 0.393701;
    } else {
        $item_hei = $CPCEHEI * 0.393701;
    }

    if ($CPCCWID > 0) {
        $item_wid = $CPCCWID * 0.393701;
    } else {
        $item_wid = $CPCEWID * 0.393701;
    }
    $var_PCLIQU = ' ';
    if ($item_len * $item_hei * $item_wid == 0) {
        continue;
    }


    $LMVOL9_new = $array_palletitems[$key2]['LMVOL9'];
//change to CPC pallet package unit
    $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $item_hei, $item_len, $item_wid, $PACKAGE_UNIT);
    $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

    $SUGGESTED_TIER = 'C03';
    $SUGGESTED_GRID5 = $var_grid5;
    $SUGGESTED_DEPTH = $var_griddepth;
    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    $SUGGESTED_MIN = 1;
    $SUGGESTED_SLOTQTY = $SUGGESTED_MAX_test;
    $AVG_DAILY_UNIT = $array_palletitems[$key]['DAILYUNIT'];
    $SUGGESTED_IMPMOVES = _implied_daily_moves($SUGGESTED_MAX, 1, $AVG_DAILY_UNIT, $AVG_INV_OH, $array_palletitems[$key]['SHIP_QTY_MN'], $adbs);
    $AVG_DAILY_PICK = $array_palletitems[$key]['DAILYPICK'];

    $adbs = $array_palletitems[$key]['AVGD_BTW_SLE'];
    $NBR_SHIP_OCC = $array_palletitems[$key]['NBR_SHIP_OCC'];
    if ($LMTIER == 'PFR') {
        $CURRENT_IMPMOVES = 0;
    } else {
        $CURRENT_IMPMOVES = _implied_daily_moves($array_palletitems[$key]['CURMAX'], $array_palletitems[$key]['CURMIN'], $AVG_DAILY_UNIT, $AVG_INV_OH, $array_palletitems[$key]['SHIP_QTY_MN'], $adbs);
    }
    $SUGGESTED_NEWLOCVOL = $LMVOL9;
    $SUGGESTED_DAYSTOSTOCK = 999;
    $CUR_LOCATION = $array_palletitems[$key]['CUR_LOCATION'];
    $VCBAY = substr($CUR_LOCATION, 0, 5);

    $array_sqlpush[] = "($whse, $building, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'C03', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY' ,'$SUGG_EQUIP', '$CURR_EQUIP',$SUGG_LEVEL)";

    if (count($array_sqlpush) >= $C03_limit) {
        break;
    }
}

//after all items or no more pallet positions, write to my_npfmvc_cse table
if (!empty($array_sqlpush)) {
    $values = implode(',', $array_sqlpush);

    $sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
}