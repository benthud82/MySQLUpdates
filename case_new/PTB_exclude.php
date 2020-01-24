<?php

//pull in eric's items listing and write to the npfmvc file for exclusion later

$sql_eric = $conn1->prepare("INSERT INTO slotting.my_npfmvc_cse
                                SELECT DISTINCT
                                A.WAREHOUSE,
                                $building,
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
                                C.CPCCPKU,
                                case when C.CPCCLEN > 0 then C.CPCCLEN else  C.CPCELEN end as CPCCLEN,
                                case when C.CPCCHEI > 0 then C.CPCCHEI else  C.CPCEHEI end as CPCCHEI,
                                case when C.CPCCWID > 0 then C.CPCCWID else  C.CPCEWID end as CPCCWID,
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
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then ((SMTH_SLS_MN) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID) / C.CPCCPKU
                                    else (SMTH_SLS_MN) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_CUBE_VEL,
                                case
                                    when C.CPCCLEN * C.CPCCHEI * C.CPCCWID > 0 then ((SMTH_PCK_MN) * C.CPCCLEN * C.CPCCHEI * C.CPCCWID)
                                    else (SMTH_PCK_MN) * C.CPCELEN * C.CPCEHEI * C.CPCEWID
                                end as DLY_PICK_VEL,
                                eric_rec as SUGGESTED_TIER,
                                eric_rec as SUGGEST_GRID5,
                                999 as SUGGESTED_DEPTH,
                                999 as SUGGESTED_MAX,
                                999 as SUGGESTED_MIN,
                                999 as SUGGESTED_SLOTQTY,
                                0 as SUGGESTED_IMPMOVES,
                                0 as CURRENT_IMPMOVES,
                                999 as SUGGESTED_NEWLOCVOL,
                                999 as SUGGESTED_DAYSTOSTOCK,
                                SMTH_PCK_MN as DAILYPICK,
                                SMTH_SLS_MN as DAILYUNIT,
                                substr(LMLOC,1,5) as VCBAY,
                                CASE WHEN eric_rec = 'BULK' then 'PALLETJACK' when eric_rec = 'PTB' then 'BELTLINE' else 'ORDERPICKER' end as SUGG_EQUIP,
                                CASE WHEN D.LMTIER = 'C01' then  'PALLETJACK' when D.LMTIER = 'C02' then 'BELTLINE' when D.LMTIER in ('C03','C05','C06') and FLOOR = 'Y' then 'PALLETJACK' else 'ORDERPICKER' end as CURR_EQUIP,
                                0
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
                                and LMLOC = A.CUR_LOCATION
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
                               JOIN
                                        slotting.eric_exclude ON eric_whse = A.WAREHOUSE
                                        AND eric_item = A.ITEM_NUMBER
                                        AND eric_pkgu = A.PACKAGE_UNIT                                        
                               LEFT JOIN
                                             slotting.case_floor_locs FL on A.WAREHOUSE = FL.WHSE and LMLOC = FL.LOCATION
                                WHERE
                                    A.WAREHOUSE = $whse
                                    AND A.PACKAGE_TYPE IN ('PFR' , 'CSE')
                                     and B.ITEM_TYPE <> 'CB'
                                     and F.ITEM_NUMBER is null
                                     $locationsql
                                    AND A.DSL_TYPE = ' '");
$sql_eric->execute();

