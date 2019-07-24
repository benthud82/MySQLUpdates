<?php

//available hp count grouped by size

//$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCCPKU,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK,AVG_DAILY_PICK,AVG_DAILY_UNIT,VCBAY';

//*****************************
//EXTERNALIZED VARIABLES
$maxmoves = 5;
$avginvmultiplier = 1.5;
$SUGG_EQUIP = 'ORDERPICKER';
//*****************************

    


$sql_hp = $conn1->prepare("SELECT 
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
                                                                LMWHSE = $whse AND LMTIER = 'C05'
                                                                    AND LMLOC NOT LIKE 'Q%'
                                                                    $lmsql
                                                            GROUP BY LMGRD5 , SUBSTRING(LMLOC, 6, 1) , LMHIGH , LMDEEP , LMHIGH
                                                            ORDER BY SHELF_LEV ASC , LMVOL9 ASC");
$sql_hp->execute();
$array_hp = $sql_hp->fetchAll(pdo::FETCH_ASSOC);

//Pull in item candidates.  Weekly cubic volume must be less than largest hp size to limit candidates
$sql_hpmax = $conn1->prepare("SELECT 
                                                            MAX(LMVOL9) AS MAXVOL
                                                        FROM
                                                            slotting.mysql_npflsm
                                                        WHERE
                                                            LMWHSE = $whse AND LMTIER = 'C05'
                                                                $lmsql
                                                                AND LMLOC NOT LIKE 'Q%'");
$sql_hpmax->execute();
$array_hpmax = $sql_hpmax->fetchAll(pdo::FETCH_ASSOC);
$maxhpvol = $array_hpmax[0]['MAXVOL'];

$array_sqlpush = array();


$sql_hpitems = $conn1->prepare("SELECT DISTINCT
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
                                    $locationsql
                                    $sql_inp_pfr
                                    and CPCCONV <> 'N'
                                    and F.ITEM_NUMBER is null
                               --     and A.ITEM_NUMBER = 3250303
                            ORDER BY DAILYPICK desc");
$sql_hpitems->execute();
$array_hpitems = $sql_hpitems->fetchAll(pdo::FETCH_ASSOC);

//loop through items and determine if can average inventory can fit in hp location
$count = 0;
foreach ($array_hpitems as $key => $value) {
    $replens = $array_hpitems[$key]['REPLENS'];
    if ($replens > $maxmoves) {
        continue;
    }

    $item = $array_hpitems[$key]['ITEM_NUMBER'];
    $CPCELEN = $array_hpitems[$key]['CPCELEN'];
    $CPCEHEI = $array_hpitems[$key]['CPCEHEI'];
    $CPCEWID = $array_hpitems[$key]['CPCEWID'];
    $CPCCLEN = $array_hpitems[$key]['CPCCLEN'];
    $CPCCHEI = $array_hpitems[$key]['CPCCHEI'];
    $CPCCWID = $array_hpitems[$key]['CPCCWID'];
    $PACKAGE_UNIT = $array_hpitems[$key]['PACKAGE_UNIT'];
    $AVG_INV_OH = $array_hpitems[$key]['AVG_INV_OH'];
    $DSL_TYPE = $array_hpitems[$key]['DSL_TYPE'];
    $PICK_QTY_MN = $array_hpitems[$key]['PICK_QTY_MN'];
    $PICK_QTY_SD = $array_hpitems[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = $array_hpitems[$key]['SHIP_QTY_MN'];
    $SHIP_QTY_SD = $array_hpitems[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $array_hpitems[$key]['ITEM_TYPE'];
    $LMFIXA = $array_hpitems[$key]['LMFIXA'];
    $LMFIXT = $array_hpitems[$key]['LMFIXT'];
    $LMSTGT = $array_hpitems[$key]['LMSTGT'];
    $LMTIER = $array_hpitems[$key]['LMTIER'];
    $LMGRD5 = $array_hpitems[$key]['LMGRD5'];
    $LMHIGH = $array_hpitems[$key]['LMHIGH'];
    $LMDEEP = $array_hpitems[$key]['LMDEEP'];
    $LMVOL9 = $array_hpitems[$key]['LMVOL9'];
    $LMWIDE = $array_hpitems[$key]['LMWIDE'];
    $DLY_CUBE_VEL = $array_hpitems[$key]['DLY_CUBE_VEL'];
    $DLY_PICK_VEL = $array_hpitems[$key]['DLY_PICK_VEL'];
    $DAYS_FRM_SLE = $array_hpitems[$key]['DAYS_FRM_SLE'];
    $CURR_EQUIP = $array_hpitems[$key]['CURR_EQUIP'];


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

    foreach ($array_hp as $key2 => $value) {
        $var_grid5 = $array_hp[$key2]['LMGRD5'];
        $var_gridheight = $array_hp[$key2]['LMHIGH'];
        $var_griddepth = $array_hp[$key2]['LMDEEP'];
        $var_gridwidth = $array_hp[$key2]['LMWIDE'];
        $LMVOL9_new = $array_hp[$key2]['LMVOL9'];
          $var_level = $array_hp[$key2]['SHELF_LEV'];

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
            $SUGG_LEVEL = intval($var_level);
            $AVG_DAILY_PICK = $array_hpitems[$key]['DAILYPICK'];
            $AVG_DAILY_UNIT = $array_hpitems[$key]['DAILYUNIT'];
            $adbs = $array_hpitems[$key]['AVGD_BTW_SLE'];
            $NBR_SHIP_OCC = $array_hpitems[$key]['NBR_SHIP_OCC'];
            if ($LMTIER == 'PFR') {
                $CURRENT_IMPMOVES = 0;
            } else {
                $CURRENT_IMPMOVES = _implied_daily_moves($array_hpitems[$key]['CURMAX'], $array_hpitems[$key]['CURMIN'], $AVG_DAILY_UNIT, $AVG_INV_OH, $array_hpitems[$key]['SHIP_QTY_MN'], $adbs);
            }
            $SUGGESTED_NEWLOCVOL = $LMVOL9;
            $SUGGESTED_DAYSTOSTOCK = 999;
            $CUR_LOCATION = $array_hpitems[$key]['CUR_LOCATION'];
            $VCBAY = substr($CUR_LOCATION, 0, 5);

            $array_sqlpush[] = "($whse, $building, $item, $PACKAGE_UNIT, 'CSE', '$DSL_TYPE', '$CUR_LOCATION', $DAYS_FRM_SLE, '$adbs',$AVG_INV_OH, $NBR_SHIP_OCC,$PICK_QTY_MN,'$PICK_QTY_SD', $SHIP_QTY_MN, '$SHIP_QTY_SD', '$ITEM_TYPE',$PACKAGE_UNIT, '$item_len', '$item_hei', '$item_wid', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, '$LMTIER', '$LMGRD5', '$DLY_CUBE_VEL', '$DLY_PICK_VEL', 'C05', '$var_grid5', $var_griddepth, $SUGGESTED_MAX, $SUGGESTED_MIN, $SUGGESTED_MAX, '$SUGGESTED_IMPMOVES', '$CURRENT_IMPMOVES', $LMVOL9_new, $SUGGESTED_DAYSTOSTOCK, '$AVG_DAILY_PICK','$AVG_DAILY_UNIT',  '$VCBAY' ,'$SUGG_EQUIP','$CURR_EQUIP',$SUGG_LEVEL )";

            $array_hp[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($array_hp[$key2]['GRIDCOUNT'] <= 0) {
                unset($array_hp[$key2]);
                $array_hp = array_values($array_hp);  //reset array
            }
            break;
        }
    }
    if (count($array_hp) == 0) {
        break;
    }
}

//after all items or no more hp positions, write to my_npfmvc_cse table
$values = implode(',', $array_sqlpush);

$sql = "INSERT IGNORE INTO slotting.my_npfmvc_cse ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();
