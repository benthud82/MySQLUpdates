<?php

//available deck count grouped by size

$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCCPKU,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK,AVG_DAILY_PICK,AVG_DAILY_UNIT,VCBAY';

//*****************************
//EXTERNALIZED VARIABLES
$casebreakeven = 15;  //number of cases per pallet to allow picks to outweigh the replen cost
$stdpallet_height = 58;
$stdpallet_depth = 48;
$stdpallet_width = 48;
//$avginvmultiplier = 2;
//*****************************

$sql_pallets = $conn1->prepare("SELECT 
                                                            LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, COUNT(*) AS GRIDCOUNT
                                                        FROM
                                                            slotting.mysql_npflsm
                                                        WHERE
                                                            LMWHSE = $whse AND LMTIER = 'C03'
                                                                AND LMLOC NOT LIKE 'Q%'
                                                        GROUP BY LMGRD5 , LMHIGH , LMDEEP , LMHIGH
                                                        ORDER BY LMVOL9 ASC");
$sql_pallets->execute();
//may not need this depending on how many items want to go to a full pallet
$array_pallets = $sql_pallets->fetchAll(pdo::FETCH_ASSOC);

$array_sqlpush = array();

$sql_palletitems = $conn1->prepare("SELECT DISTINCT
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
                                   D.LMHIGH,
                                   D.LMDEEP,
                                   D.LMWIDE,
                                   D.LMVOL9,
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
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailyunit) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / C.CPCCPKU
                                    else ($sql_dailyunit) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_CUBE_VEL,
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then (($sql_dailypick_case) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
                                    else ($sql_dailypick_case) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_PICK_VEL,
                                $sql_dailypick_case as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT,
                                (SELECT 
                                        SUM(replen_count)
                                    FROM
                                        slotting.replen_itemcount
                                    WHERE
                                        replen_whse = A.WAREHOUSE
                                            AND replen_item = A.ITEM_NUMBER
                                            AND replen_zone BETWEEN 7 AND 8) AS REPLENS
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
                            WHERE
                                A.WAREHOUSE = $whse
                                    and A.CUR_LOCATION not like 'W00%'
                                    and (A.PACKAGE_TYPE not in ('LSE' , 'INP') or A.CUR_LOCATION like ('Q%'))
                                    and A.CUR_LOCATION not like 'N%'
                                    and B.ITEM_TYPE = 'ST'
                                    and CPCCONV <> 'N'
                                    and F.ITEM_NUMBER is null
                               --     and A.ITEM_NUMBER = 3250303
                            ORDER BY DAILYPICK desc");
$sql_palletitems->execute();
print_r($sql_palletitems);
$array_palletitems = $sql_palletitems->fetchAll(pdo::FETCH_ASSOC);

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

        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $item_hei, $item_len, $item_wid, $PACKAGE_UNIT);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= ($AVG_INV_OH * $avginvmultiplier)) {
            $SUGGESTED_TIER = 'C06';
            $SUGGESTED_GRID5 = $var_grid5;
            $SUGGESTED_DEPTH = $var_griddepth;
            $SUGGESTED_MAX = $SUGGESTED_MAX_test;
            $SUGGESTED_MIN = 1;
            $SUGGESTED_SLOTQTY = $SUGGESTED_MAX_test;
            $SUGGESTED_IMPMOVES = 0;
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

            $array_sqlpush[] = "($whse, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'C06', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY'  )";

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
$values = implode(',', $array_sqlpush);

$sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();
