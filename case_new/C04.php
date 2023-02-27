<?php

//*****************************
//EXTERNALIZED VARIABLES
$maxmoves = 5;
$avginvmultiplier = 1.5;
$SUGG_EQUIP = 'PTB_FLOW';
//*****************************

$sql_decks = $conn1->prepare("SELECT 
                                grid AS LMGRD5,
                                grid_height AS LMHIGH,
                                grid_length AS LMDEEP,
                                grid_width AS LMWIDE,
                                (grid_height * grid_length * grid_width) AS LMVOL9,
                                COUNT(*) AS GRIDCOUNT
                            FROM
                                nahsi.grids
                            WHERE
                                grid_tier = 'C04'
                            GROUP BY grid , grid_height , grid_length , grid_width , (grid_height * grid_length * grid_width)
                            ORDER BY (grid_height * grid_length * grid_width) ASC");
$sql_decks->execute();
$array_decks = $sql_decks->fetchAll(pdo::FETCH_ASSOC);

$array_sqlpush = array();

$sql_deckitems = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    'CSE' as PACKAGE_TYPE,
    A.DSL_TYPE,
    'CASEPICK' as CUR_LOCATION,
    0 as REPLEN, 
    min(A.DAYS_FRM_SLE) as DAYS_FRM_SLE,
    min(A.AVGD_BTW_SLE) as AVGD_BTW_SLE,
    max(A.AVG_INV_OH) as AVG_INV_OH,
    max(A.NBR_SHIP_OCC) as NBR_SHIP_OCC,
    max(A.TRUE_PCK_MN) as PICK_QTY_MN,
    max(A.PICK_QTY_SD) as PICK_QTY_SD,
    max(A.TRUE_SLS_MN) as SHIP_QTY_MN,
    max(A.SHIP_QTY_SD) as SHIP_QTY_SD,
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
    CASE
        WHEN D.LMFIXA IS NULL THEN 'PFR'
        ELSE D.LMFIXA
    END AS LMFIXA,
    CASE
        WHEN D.LMFIXT IS NULL THEN 'PFR'
        ELSE D.LMFIXT
    END AS LMFIXT,
    CASE
        WHEN D.LMSTGT IS NULL THEN 'PFR'
        ELSE D.LMSTGT
    END AS LMSTGT,
    CASE
        WHEN D.LMHIGH IS NULL THEN 'PFR'
        ELSE D.LMHIGH
    END AS LMHIGH,
    CASE
        WHEN D.LMDEEP IS NULL THEN 'PFR'
        ELSE D.LMDEEP
    END AS LMDEEP,
    CASE
        WHEN D.LMWIDE IS NULL THEN 'PFR'
        ELSE D.LMWIDE
    END AS LMWIDE,
    CASE
        WHEN D.LMVOL9 IS NULL THEN 'PFR'
        ELSE D.LMVOL9
    END AS LMVOL9,
    CASE
        WHEN D.LMTIER IS NULL THEN 'PFR'
        ELSE D.LMTIER
    END AS LMTIER,
    CASE
        WHEN D.LMGRD5 IS NULL THEN 'PFR'
        ELSE D.LMGRD5
    END AS LMGRD5,
    CASE
        WHEN D.CURMAX IS NULL THEN 'PFR'
        ELSE D.CURMAX
    END AS CURMAX,
    CASE
        WHEN D.CURMIN IS NULL THEN 'PFR'
        ELSE D.CURMIN
    END AS CURMIN,
    MAX(PERC_SHIPQTY) as PERC_SHIPQTY,
    MAX(PERC_PERC) as PERC_PERC,
    MAX(TRUE_PCK_MN) AS DAILYPICK,
    MAX(TRUE_SLS_MN) AS DAILYUNIT
FROM
    nahsi.demand A
        LEFT JOIN
    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
        AND B.ITEM = A.ITEM_NUMBER
        LEFT JOIN
    slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
        AND X.CPCITEM = A.ITEM_NUMBER
        LEFT JOIN
    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
        AND D.LMITEM = A.ITEM_NUMBER
        AND A.PACKAGE_UNIT = D.LMPKGU
        LEFT JOIN
    slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
        AND E.PERC_ITEM = A.ITEM_NUMBER
        AND E.PERC_PKGU = A.PACKAGE_UNIT
        LEFT JOIN
    slotting.my_npfmvc_cse F ON F.WAREHOUSE = A.WAREHOUSE
        AND F.ITEM_NUMBER = A.ITEM_NUMBER
        AND F.PACKAGE_UNIT = A.PACKAGE_UNIT
WHERE
    A.WAREHOUSE = $whse
		
        AND A.PACKAGE_TYPE IN ('CSE' , 'PFR')
        AND B.ITEM_TYPE = 'ST'
        AND A.NBR_SHIP_OCC >= 4
        AND A.AVGD_BTW_SLE > 0
        AND A.AVG_INV_OH > 0
        AND F.ITEM_NUMBER IS NULL
        AND CPCCONV <> 'N'
        AND A.DSL_TYPE NOT IN (1,2 , 3, 4,5)
GROUP BY A.WAREHOUSE , A.ITEM_NUMBER , A.PACKAGE_UNIT ,'CSE' , A.DSL_TYPE , 'CASEPICK'  , B.ITEM_TYPE , X.CPCEPKU , X.CPCIPKU , X.CPCCPKU , X.CPCFLOW , X.CPCTOTE , X.CPCSHLF , X.CPCROTA , X.CPCESTK , X.CPCLIQU , X.CPCELEN , X.CPCEHEI , X.CPCEWID , X.CPCCLEN , X.CPCCHEI , X.CPCCWID , CASE
    WHEN D.LMFIXA IS NULL THEN 'PFR'
    ELSE D.LMFIXA
END , CASE
    WHEN D.LMFIXT IS NULL THEN 'PFR'
    ELSE D.LMFIXT
END , CASE
    WHEN D.LMSTGT IS NULL THEN 'PFR'
    ELSE D.LMSTGT
END , CASE
    WHEN D.LMHIGH IS NULL THEN 'PFR'
    ELSE D.LMHIGH
END , CASE
    WHEN D.LMDEEP IS NULL THEN 'PFR'
    ELSE D.LMDEEP
END , CASE
    WHEN D.LMWIDE IS NULL THEN 'PFR'
    ELSE D.LMWIDE
END , CASE
    WHEN D.LMVOL9 IS NULL THEN 'PFR'
    ELSE D.LMVOL9
END , CASE
    WHEN D.LMTIER IS NULL THEN 'PFR'
    ELSE D.LMTIER
END , CASE
    WHEN D.LMGRD5 IS NULL THEN 'PFR'
    ELSE D.LMGRD5
END , CASE
    WHEN D.CURMAX IS NULL THEN 'PFR'
    ELSE D.CURMAX
END , CASE
    WHEN D.CURMIN IS NULL THEN 'PFR'
    ELSE D.CURMIN
END
HAVING  min(A.DAYS_FRM_SLE) <= 180
ORDER BY MAX(TRUE_PCK_MN) DESC");
$sql_deckitems->execute();
$array_deckitems = $sql_deckitems->fetchAll(pdo::FETCH_ASSOC);

//loop through items and determine if can average inventory can fit in deck location
foreach ($array_deckitems as $key => $value) {

    $item = $array_deckitems[$key]['ITEM_NUMBER'];
    $CPCELEN = $array_deckitems[$key]['CPCELEN'];
    $CPCEHEI = $array_deckitems[$key]['CPCEHEI'];
    $CPCEWID = $array_deckitems[$key]['CPCEWID'];
    $CPCCLEN = $array_deckitems[$key]['CPCCLEN'];
    $CPCCHEI = $array_deckitems[$key]['CPCCHEI'];
    $CPCCWID = $array_deckitems[$key]['CPCCWID'];
    $PACKAGE_UNIT = $array_deckitems[$key]['PACKAGE_UNIT'];
    $AVG_INV_OH = $array_deckitems[$key]['AVG_INV_OH'];
    $DSL_TYPE = $array_deckitems[$key]['DSL_TYPE'];
    $PICK_QTY_MN = $array_deckitems[$key]['PICK_QTY_MN'];
    $PICK_QTY_SD = $array_deckitems[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = $array_deckitems[$key]['SHIP_QTY_MN'];
    $SHIP_QTY_SD = $array_deckitems[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $array_deckitems[$key]['ITEM_TYPE'];
    $LMFIXA = $array_deckitems[$key]['LMFIXA'];
    $LMFIXT = $array_deckitems[$key]['LMFIXT'];
    $LMSTGT = $array_deckitems[$key]['LMSTGT'];
    $LMTIER = $array_deckitems[$key]['LMTIER'];
    $LMGRD5 = $array_deckitems[$key]['LMGRD5'];
    $LMHIGH = $array_deckitems[$key]['LMHIGH'];
    $LMDEEP = $array_deckitems[$key]['LMDEEP'];
    $LMVOL9 = $array_deckitems[$key]['LMVOL9'];
    $LMWIDE = $array_deckitems[$key]['LMWIDE'];
    $PERC_PERC = $array_deckitems[$key]['PERC_PERC'];

    $DAYS_FRM_SLE = $array_deckitems[$key]['DAYS_FRM_SLE'];
    $CURR_EQUIP = 'NA';

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
    $DLY_CUBE_VEL = $SHIP_QTY_MN * $item_len * $item_hei * $item_wid;
    $DLY_PICK_VEL = $PICK_QTY_MN * $item_len * $item_hei * $item_wid;
    $var_PCLIQU = ' ';
    if ($item_len * $item_hei * $item_wid == 0) {
        continue;
    }

    foreach ($array_decks as $key2 => $value) {
        $var_grid5 = $array_decks[$key2]['LMGRD5'];
        $var_gridheight = $array_decks[$key2]['LMHIGH'];
        $var_griddepth = $array_decks[$key2]['LMDEEP'];
        $var_gridwidth = $array_decks[$key2]['LMWIDE'];
        $LMVOL9_new = $array_decks[$key2]['LMVOL9'];

        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $item_hei, $item_len, $item_wid, $PACKAGE_UNIT);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= ($AVG_INV_OH * $avginvmultiplier)) {
            $SUGGESTED_TIER = 'C04';
            $SUGGESTED_GRID5 = $var_grid5;
            $SUGGESTED_DEPTH = $var_griddepth;
            $SUGGESTED_MAX = $SUGGESTED_MAX_test;
            $SUGGESTED_MIN = 1;
            $SUGGESTED_SLOTQTY = $SUGGESTED_MAX_test;
            $SUGGESTED_IMPMOVES = 0;
            $SUGG_LEVEL = 0;
            $AVG_DAILY_PICK = $array_deckitems[$key]['DAILYPICK'];
            $AVG_DAILY_UNIT = $array_deckitems[$key]['DAILYUNIT'];
            $adbs = $array_deckitems[$key]['AVGD_BTW_SLE'];
            $NBR_SHIP_OCC = $array_deckitems[$key]['NBR_SHIP_OCC'];
            if ($LMTIER == 'PFR') {
                $CURRENT_IMPMOVES = 0;
            } else {
                $CURRENT_IMPMOVES = _implied_daily_moves($array_deckitems[$key]['CURMAX'], $array_deckitems[$key]['CURMIN'], $AVG_DAILY_UNIT, $AVG_INV_OH, $array_deckitems[$key]['SHIP_QTY_MN'], $adbs);
            }
            $SUGGESTED_NEWLOCVOL = $LMVOL9;
            $SUGGESTED_DAYSTOSTOCK = 999;
            $CUR_LOCATION = $array_deckitems[$key]['CUR_LOCATION'];
            $VCBAY = substr($CUR_LOCATION, 0, 5);

            $array_sqlpush[] = "($whse, $building, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', '$LMHIGH', '$LMDEEP', '$LMWIDE', '$LMVOL9', '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'C04', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY' ,'$SUGG_EQUIP','$CURR_EQUIP',$SUGG_LEVEL )";

//            $array_decks[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
//            if ($array_decks[$key2]['GRIDCOUNT'] <= 0) {
//                unset($array_decks[$key2]);
//                $array_decks = array_values($array_decks);  //reset array
//            }
            break;
        }
    }
    if (count($array_decks) == 0) {
        break;
    }
}

//after all items or no more deck positions, write to my_npfmvc_cse table
if (!empty($array_sqlpush)) {
    $values = implode(',', $array_sqlpush);

    $sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
}