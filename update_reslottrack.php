<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
$whsearray = array(2, 3, 6, 7, 9);

foreach ($whsearray as $whssel) {
    $whse = $whssel;
    $movestable = $whse . 'moves';

    $itemsql = $conn1->prepare("SELECT 
                                            reslot_item
                                        FROM
                                            slotting.reslot_tracking
                                        WHERE
                                            reslot_whse = 2
                                                AND reslot_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
												");
    $itemsql->execute();
    $itemarray = $itemsql->fetchAll(pdo::FETCH_ASSOC);

    foreach ($itemarray as $key => $value) {

        $itemcode = $itemarray[$key]['reslot_item'];

        $insertsql = $conn1->prepare("INSERT INTO slotting.reslot_tracking_progress
        SELECT 
    A.reslot_whse,
    A.reslot_item,
    A.reslot_date,
    (SELECT 
            COUNT(T.MVITEM) / 90
        FROM
            slotting.$movestable T
        WHERE
            $itemcode = T.MVITEM
                AND A.reslot_pkgu = T.MVTPKG
                AND T.MVDATE BETWEEN DATE_SUB(A.reslot_date, INTERVAL 90 DAY) AND A.reslot_date) AS BEF_MOVES,
    (SELECT 
            COUNT(U.MVITEM) / CASE
                    WHEN DATEDIFF(CURDATE(), A.reslot_date) > 90 THEN 90
                    ELSE DATEDIFF(CURDATE(), A.reslot_date)
                END
        FROM
            slotting.$movestable U
        WHERE
            $itemcode = U.MVITEM
                AND A.reslot_pkgu = U.MVTPKG
                AND U.MVDATE BETWEEN A.reslot_date AND DATE_ADD(A.reslot_date, INTERVAL 90 DAY)) AS AFT_MOVES,
    ((SELECT 
            COUNT(T.MVITEM) / 90
        FROM
            slotting.$movestable T
        WHERE
            $itemcode = T.MVITEM
                AND A.reslot_pkgu = T.MVTPKG
                AND T.MVDATE BETWEEN DATE_SUB(A.reslot_date, INTERVAL 90 DAY) AND A.reslot_date)) - ((SELECT 
            COUNT(U.MVITEM) / CASE
                    WHEN DATEDIFF(CURDATE(), A.reslot_date) > 90 THEN 90
                    ELSE DATEDIFF(CURDATE(), A.reslot_date)
                END
        FROM
            slotting.$movestable U
        WHERE
            $itemcode = U.MVITEM
                AND A.reslot_pkgu = U.MVTPKG
                AND U.MVDATE BETWEEN A.reslot_date AND DATE_ADD(A.reslot_date, INTERVAL 90 DAY))) * 253 AS YEARLY_MOVE_RED,
    AVG_DAILY_PICK,
    CAST(SUBSTRING(histitem_location, 4, 2) AS UNSIGNED) AS OLD_BAY,
    CAST(SUBSTRING(CUR_LOCATION, 4, 2) AS UNSIGNED) AS NEW_BAY,
    AVG_DAILY_PICK * (SELECT 
            FEET
        FROM
            slotting.bay_walkfeet
        WHERE
            BAY = CAST(SUBSTRING(histitem_location, 4, 2) AS UNSIGNED)) AS OLD_WALKFEET,
    AVG_DAILY_PICK * (SELECT 
            FEET
        FROM
            slotting.bay_walkfeet
        WHERE
            BAY = CAST(SUBSTRING(CUR_LOCATION, 4, 2) AS UNSIGNED)) AS NEW_WALKFEET,
    (AVG_DAILY_PICK * (SELECT 
            FEET
        FROM
            slotting.bay_walkfeet
        WHERE
            BAY = CAST(SUBSTRING(histitem_location, 4, 2) AS UNSIGNED)) - (AVG_DAILY_PICK * (SELECT 
            FEET
        FROM
            slotting.bay_walkfeet
        WHERE
            BAY = CAST(SUBSTRING(CUR_LOCATION, 4, 2) AS UNSIGNED)))) * 253 AS YEARLY_WALK_RED,
    reslot_type
FROM
    slotting.reslot_tracking A
        JOIN
    slotting.slottingscore_hist_item B ON B.histitem_whse = A.reslot_whse
        AND A.reslot_item = B.histitem_item
        AND B.histitem_pkgu = A.reslot_pkgu
        AND B.histitem_date = (A.reslot_date)
        JOIN
    slotting.my_npfmvc C ON C.WAREHOUSE = A.reslot_whse
        AND C.ITEM_NUMBER = A.reslot_item
        AND C.PACKAGE_UNIT = A.reslot_pkgu
WHERE
    A.reslot_whse = $whse
        AND B.histitem_item = $itemcode
        AND A.reslot_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
 ON DUPLICATE KEY UPDATE reslotprog_befmoves=values(reslotprog_befmoves), reslotprog_aftmoves=values(reslotprog_aftmoves), reslotprog_movered=values(reslotprog_movered), reslotprog_avgdailypick=values(reslotprog_avgdailypick), reslotprog_oldbay=values(reslotprog_oldbay), reslotprog_newbay=values(reslotprog_newbay), reslotprog_oldfeet=values(reslotprog_oldfeet), reslotprog_newfeet=values(reslotprog_newfeet), reslotprog_walkred=values(reslotprog_walkred)");
        $insertsql->execute();


        echo $key;
    }
}