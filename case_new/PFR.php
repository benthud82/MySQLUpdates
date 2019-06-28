<?php

//available deck count grouped by size

$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCCPKU,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK,AVG_DAILY_PICK,AVG_DAILY_UNIT,VCBAY';

$array_sqlpush = array();
$sql_pfr = $conn1->prepare("SELECT DISTINCT
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
        when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailyunit) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / C.CPCCPKU
        else ($sql_dailyunit) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
    end as DLY_CUBE_VEL,
    case
        when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailypick_case) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
        else ($sql_dailypick_case) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
    end as DLY_PICK_VEL,
    $sql_dailypick_case as DAILYPICK,
    $sql_dailyunit as DAILYUNIT
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
WHERE
    A.WAREHOUSE = $whse
        AND A.CUR_LOCATION NOT LIKE 'W00%'
        AND (A.PACKAGE_TYPE NOT IN ('LSE' , 'INP')
        OR A.CUR_LOCATION LIKE ('Q%'))
        AND A.CUR_LOCATION NOT LIKE 'N%'
        AND B.ITEM_TYPE = 'ST'
        AND CPCCONV <> 'N'
        AND F.ITEM_NUMBER IS NULL");
$sql_pfr->execute();
$array_pfr = $sql_pfr->fetchAll(pdo::FETCH_ASSOC);

//loop through items and determine if can average inventory can fit in deck location
$count = 0;
foreach ($array_pfr as $key => $value) {

    $item = $array_pfr[$key]['ITEM_NUMBER'];
    $CPCELEN = $array_pfr[$key]['CPCELEN'];
    $CPCEHEI = $array_pfr[$key]['CPCEHEI'];
    $CPCEWID = $array_pfr[$key]['CPCEWID'];
    $CPCCLEN = $array_pfr[$key]['CPCCLEN'];
    $CPCCHEI = $array_pfr[$key]['CPCCHEI'];
    $CPCCWID = $array_pfr[$key]['CPCCWID'];
    $PACKAGE_UNIT = $array_pfr[$key]['PACKAGE_UNIT'];
    $AVG_INV_OH = $array_pfr[$key]['AVG_INV_OH'];
    $DSL_TYPE = $array_pfr[$key]['DSL_TYPE'];
    $PICK_QTY_MN = $array_pfr[$key]['PICK_QTY_MN'];
    $PICK_QTY_SD = $array_pfr[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = $array_pfr[$key]['SHIP_QTY_MN'];
    $SHIP_QTY_SD = $array_pfr[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $array_pfr[$key]['ITEM_TYPE'];
    $LMFIXA = $array_pfr[$key]['LMFIXA'];
    $LMFIXT = $array_pfr[$key]['LMFIXT'];
    $LMSTGT = $array_pfr[$key]['LMSTGT'];
    $LMTIER = $array_pfr[$key]['LMTIER'];
    $LMGRD5 = $array_pfr[$key]['LMGRD5'];
    $LMHIGH = $array_pfr[$key]['LMHIGH'];
    $LMDEEP = $array_pfr[$key]['LMDEEP'];
    $LMVOL9 = $array_pfr[$key]['LMVOL9'];
    $LMWIDE = $array_pfr[$key]['LMWIDE'];
    $DLY_CUBE_VEL = $array_pfr[$key]['DLY_CUBE_VEL'];
    $DLY_PICK_VEL = $array_pfr[$key]['DLY_PICK_VEL'];
    $DAYS_FRM_SLE = $array_pfr[$key]['DAYS_FRM_SLE'];


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


    $LMVOL9_new = 999;

    $SUGGESTED_TIER = 'C03';
    $SUGGESTED_GRID5 = $var_grid5;
    $SUGGESTED_DEPTH = $var_griddepth;
    $SUGGESTED_MAX = 999;
    $SUGGESTED_MIN = 999;
    $SUGGESTED_SLOTQTY = 999;
    $AVG_DAILY_UNIT = $array_pfr[$key]['DAILYUNIT'];
    $SUGGESTED_IMPMOVES = 0;
    $AVG_DAILY_PICK = $array_pfr[$key]['DAILYPICK'];

    $adbs = $array_pfr[$key]['AVGD_BTW_SLE'];
    $NBR_SHIP_OCC = $array_pfr[$key]['NBR_SHIP_OCC'];
    if ($LMTIER == 'PFR') {
        $CURRENT_IMPMOVES = 0;
    } else {
        $CURRENT_IMPMOVES = _implied_daily_moves($array_pfr[$key]['CURMAX'], $array_pfr[$key]['CURMIN'], $AVG_DAILY_UNIT, $AVG_INV_OH, $array_pfr[$key]['SHIP_QTY_MN'], $adbs);
    }
    $SUGGESTED_NEWLOCVOL = 999;
    $SUGGESTED_DAYSTOSTOCK = 999;
    $CUR_LOCATION = $array_pfr[$key]['CUR_LOCATION'];
    $VCBAY = substr($CUR_LOCATION, 0, 5);

    $array_sqlpush[] = "($whse, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'PFR', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY'  )";
}

//after all items or no more deck positions, write to my_npfmvc_cse table
$values = implode(',', $array_sqlpush);

$sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();
