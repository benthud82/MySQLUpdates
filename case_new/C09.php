<?php

//available deck count grouped by size
//*****************************
//EXTERNALIZED VARIABLES
$maxmoves = 10;
$daystostock = 12;
$SUGG_EQUIP = 'ORDERPICKER';
//*****************************

$sql_decks = $conn1->prepare("SELECT 
                                                                LMGRD5,
                                                                SUBSTRING(LMLOC, 6, 1) AS SHELF_LEV,
                                                                LMHIGH,
                                                                LMDEEP,
                                                                LMWIDE,
                                                                LMVOL9,
                                                                COUNT(*) AS GRIDCOUNT
                                                            FROM
                                                                slotting.mysql_npflsm
                                                            WHERE
                                                                LMWHSE = $whse AND LMTIER = 'C09'
                                                                    AND LMLOC NOT LIKE 'Q%'
                                                                    $lmsql
                                                            GROUP BY LMGRD5 , SUBSTRING(LMLOC, 6, 1) , LMHIGH , LMDEEP , LMHIGH
                                                            ORDER BY SHELF_LEV ASC , LMVOL9 ASC");
$sql_decks->execute();
$array_decks = $sql_decks->fetchAll(pdo::FETCH_ASSOC);

//Pull in item candidates.  Weekly cubic volume must be less than largest deck size to limit candidates
$sql_deckmax = $conn1->prepare("SELECT 
                                                            MAX(LMVOL9) AS MAXVOL
                                                        FROM
                                                            slotting.mysql_npflsm
                                                        WHERE
                                                            LMWHSE = $whse AND LMTIER = 'C09'
                                                                $lmsql
                                                                AND LMLOC NOT LIKE 'Q%'");
$sql_deckmax->execute();
$array_deckmax = $sql_deckmax->fetchAll(pdo::FETCH_ASSOC);
$maxdeckvol = $array_deckmax[0]['MAXVOL'];

$array_sqlpush = array();


$sql_deckitems = $conn1->prepare("SELECT DISTINCT
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
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMTIER
                                   end as LMTIER,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 'PFR'
                                       else D.LMGRD5
                                   end as LMGRD5,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 0
                                       else D.CURMAX
                                   end as CURMAX,
                                   CASE
                                       WHEN A.PACKAGE_TYPE = 'PFR' then 0
                                       else D.CURMIN
                                   end as CURMIN,
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
                                (SELECT 
                                        SUM(replen_count)
                                    FROM
                                        slotting.replen_itemcount
                                    WHERE
                                        replen_whse = A.WAREHOUSE
                                            AND replen_item = A.ITEM_NUMBER
                                            AND replen_zone BETWEEN 7 AND 8) AS REPLENS,
                                             CASE WHEN D.LMTIER = 'C01' then  'PALLETJACK' when D.LMTIER = 'C02' then 'BELTLINE' when D.LMTIER in ('C03','C05','C06') and FLOOR = 'Y' then 'PALLETJACK' else 'ORDERPICKER' end as CURR_EQUIP
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
                                slotting.my_npfmvc_cse F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                                LEFT JOIN
                                    slotting.case_floor_locs FL on A.WAREHOUSE = FL.WHSE and LMLOC = FL.LOCATION
                            WHERE
                                A.WAREHOUSE = $whse
                                    and A.CUR_LOCATION not like 'W00%'
                                    and (A.PACKAGE_TYPE not in ('LSE' , 'INP') or A.CUR_LOCATION like ('Q%'))
                                    and A.CUR_LOCATION not like 'N%'
                                    and B.ITEM_TYPE = 'ST'
                                    and CPCCONV = 'N'
                                    $locationsql
                                    $sql_inp_pfr
                                    and F.ITEM_NUMBER is null
                               --     and A.ITEM_NUMBER = 3250303
                            ORDER BY DAILYPICK desc");
$sql_deckitems->execute();
$array_deckitems = $sql_deckitems->fetchAll(pdo::FETCH_ASSOC);

//loop through items and determine if can average inventory can fit in deck location
$count = 0;
foreach ($array_deckitems as $key => $value) {
    $replens = $array_deckitems[$key]['REPLENS'];
    if ($replens > $maxmoves) {
        continue;
    }

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
    $DLY_CUBE_VEL = $array_deckitems[$key]['DLY_CUBE_VEL'];
    $DLY_PICK_VEL = $array_deckitems[$key]['DLY_PICK_VEL'];
    $DAYS_FRM_SLE = $array_deckitems[$key]['DAYS_FRM_SLE'];
    $AVG_DAILY_UNIT = $array_deckitems[$key]['DAILYUNIT'];
    $CURR_EQUIP = $array_deckitems[$key]['CURR_EQUIP'];

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

    foreach ($array_decks as $key2 => $value) {
        $var_grid5 = $array_decks[$key2]['LMGRD5'];
        $var_gridheight = $array_decks[$key2]['LMHIGH'];
        $var_griddepth = $array_decks[$key2]['LMDEEP'];
        $var_gridwidth = $array_decks[$key2]['LMWIDE'];
        $LMVOL9_new = $array_decks[$key2]['LMVOL9'];
        $var_level = $array_decks[$key2]['SHELF_LEV'];

        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $item_hei, $item_len, $item_wid, $PACKAGE_UNIT);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= ($AVG_DAILY_UNIT * $daystostock)) {
            $SUGGESTED_TIER = 'C09';
            $SUGGESTED_GRID5 = $var_grid5;
            $SUGGESTED_DEPTH = $var_griddepth;
            $SUGGESTED_MAX = $SUGGESTED_MAX_test;
            $SUGGESTED_MIN = 1;
            $SUGGESTED_SLOTQTY = $SUGGESTED_MAX_test;
            $SUGGESTED_IMPMOVES = 0;
            $AVG_DAILY_PICK = $array_deckitems[$key]['DAILYPICK'];
            $SUGG_LEVEL = intval($var_level);

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

            $array_sqlpush[] = "($whse, $building, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'C09', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY','$SUGG_EQUIP' ,'$CURR_EQUIP',$SUGG_LEVEL )";

            $array_decks[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($array_decks[$key2]['GRIDCOUNT'] <= 0) {
                unset($array_decks[$key2]);
                $array_decks = array_values($array_decks);  //reset array
            }
            break;
        }
    }
    if (count($array_decks) == 0) {
        break;
    }
}

//after all items or no more deck positions, write to my_npfmvc_cse table
if (count($array_sqlpush) > 0) {
    $values = implode(',', $array_sqlpush);


    $sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
}