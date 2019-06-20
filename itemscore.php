<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
date_default_timezone_set('America/New_York');
$datetime = date('Y-m-d');
$previous7days = date('Y-m-d', strtotime('-7 days'));


$sqldelete = "TRUNCATE TABLE slotting.slottingscore";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'SCORE_WHSE, SCORE_ITEM, SCORE_PKGU, SCORE_ZONE, SCORE_TOTALSCORE, SCORE_REPLENSCORE, SCORE_WALKSCORE, SCORE_TOTALSCORE_OPT, SCORE_REPLENSCORE_OPT, SCORE_WALKSCORE_OPT, SCORE_BOTTOM100, SCORE_BOTTOM1000';

$scoresql = $conn1->prepare("SELECT 
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    CASE
        WHEN 1 - (((abs(A.CURRENT_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.CURRENT_IMPMOVES) / 15) / .052632))
    end * CASE
        WHEN 1 - (((abs(B.OPT_ADDTLFTPERDAY) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(B.OPT_ADDTLFTPERDAY) / 5280 / 3.1) / .052632))
    end as SCORE_TOTALSCORE,
    CASE
        WHEN 1 - (((abs(A.CURRENT_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.CURRENT_IMPMOVES) / 15) / .052632))
    end as SCORE_REPLENSCORE,
    CASE
        WHEN 1 - (((abs(B.OPT_ADDTLFTPERDAY) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(B.OPT_ADDTLFTPERDAY) / 5280 / 3.1) / .052632))
    end as SCORE_WALKSCORE,
    CASE
        WHEN 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632))
    end * CASE
        WHEN 1 - (((abs(0) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(0) / 5280 / 3.1) / .052632))
    end as SCORE_TOTALSCORE_OPT,
    CASE
        WHEN 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632))
    end as SCORE_REPLENSCORE_OPT,
    CASE
        WHEN 1 - (((abs(0) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(0) / 5280 / 3.1) / .052632))
    end as SCORE_WALKSCORE_OPT
FROM
    slotting.my_npfmvc A
        join
    slotting.optimalbay B ON A.WAREHOUSE = B.OPT_WHSE
        and A.ITEM_NUMBER = B.OPT_ITEM
        and A.PACKAGE_UNIT = B.OPT_PKGU
        and A.PACKAGE_TYPE = B.OPT_CSLS
        WHERE
   PACKAGE_TYPE in ('LSE', 'INP')");
$scoresql->execute();
$scoresqlarray = $scoresql->fetchAll(pdo::FETCH_ASSOC);



$maxrange = 999;
$counter = 0;
$rowcount = count($scoresqlarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $SCORE_WHSE = intval($scoresqlarray[$counter]['WAREHOUSE']);
        $SCORE_ITEM = intval($scoresqlarray[$counter]['ITEM_NUMBER']);
        $SCORE_PKGU = intval($scoresqlarray[$counter]['PACKAGE_UNIT']);
        $SCORE_ZONE = ($scoresqlarray[$counter]['PACKAGE_TYPE']);
        $SCORE_TOTALSCORE = ($scoresqlarray[$counter]['SCORE_TOTALSCORE']);
        $SCORE_REPLENSCORE = ($scoresqlarray[$counter]['SCORE_REPLENSCORE']);
        $SCORE_WALKSCORE = ($scoresqlarray[$counter]['SCORE_WALKSCORE']);
        $SCORE_TOTALSCORE_OPT = ($scoresqlarray[$counter]['SCORE_TOTALSCORE_OPT']);
        $SCORE_REPLENSCORE_OPT = ($scoresqlarray[$counter]['SCORE_REPLENSCORE_OPT']);
        $SCORE_WALKSCORE_OPT = ($scoresqlarray[$counter]['SCORE_WALKSCORE_OPT']);


        $data[] = "($SCORE_WHSE, $SCORE_ITEM, $SCORE_PKGU, '$SCORE_ZONE', '$SCORE_TOTALSCORE', '$SCORE_REPLENSCORE', '$SCORE_WALKSCORE', '$SCORE_TOTALSCORE_OPT', '$SCORE_REPLENSCORE_OPT', '$SCORE_WALKSCORE_OPT', 0, 0)";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.slottingscore ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount);

//update the bottom100
foreach ($whsearray as $whse) {
    $sql = "UPDATE slottingscore dest,
                                (SELECT 
                                    *
                                FROM
                                    slotting.slottingscore
                                WHERE
                                    SCORE_WHSE = $whse
                                        AND SCORE_ZONE IN ('LSE' , 'INP')
                                ORDER BY SCORE_TOTALSCORE , SCORE_REPLENSCORE , SCORE_WALKSCORE
                                LIMIT 100) src 
                            SET 
                                dest.SCORE_BOTTOM100 = 1
                            WHERE
                                dest.SCORE_WHSE = src.SCORE_WHSE
                                    AND dest.SCORE_ITEM = src.SCORE_ITEM
                                    AND dest.SCORE_ZONE = src.SCORE_ZONE;";
    $query = $conn1->prepare($sql);
    $query->execute();
}

//update the bottom1000
foreach ($whsearray as $whse) {
    $sql = "UPDATE slottingscore dest,
                                (SELECT 
                                    *
                                FROM
                                    slotting.slottingscore
                                WHERE
                                    SCORE_WHSE = $whse
                                        AND SCORE_ZONE IN ('LSE' , 'INP')
                                ORDER BY SCORE_TOTALSCORE , SCORE_REPLENSCORE , SCORE_WALKSCORE
                                LIMIT 1000) src 
                            SET 
                                dest.SCORE_BOTTOM1000 = 1
                            WHERE
                                dest.SCORE_WHSE = src.SCORE_WHSE
                                    AND dest.SCORE_ITEM = src.SCORE_ITEM
                                    AND dest.SCORE_ZONE = src.SCORE_ZONE;";
    $query = $conn1->prepare($sql);
    $query->execute();
}






//logic to calculate case score

$scoresql = $conn1->prepare("SELECT 
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    CASE
        WHEN 1 - (((abs(A.CURRENT_IMPMOVES) / 12) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.CURRENT_IMPMOVES) / 12) / .052632))
    end * CASE
        WHEN
            A.LMTIER in ('C01' , 'C02')
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            A.LMTIER in ('C01' , 'C02')
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 50)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 50)) / .052632)
            end
        when
            B.FLOOR = 'Y'
                and A.LMTIER not in ('C01' , 'C02')
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            B.FLOOR = 'Y'
                and A.LMTIER not in ('C01' , 'C02')
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 50)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 50)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            (FLOOR = 'N' or FLOOR is null)
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            (FLOOR = 'N' or FLOOR is null)
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            1
        else 1
    end as SCORE_TOTALSCORE,
    CASE
        WHEN 1 - (((abs(A.CURRENT_IMPMOVES) / 12) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.CURRENT_IMPMOVES) / 12) / .052632))
    end as SCORE_REPLENSCORE,
    CASE
        WHEN
            A.LMTIER in ('C01' , 'C02')
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            A.LMTIER in ('C01' , 'C02')
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 50)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 200) - (A.AVG_DAILY_PICK / 50)) / .052632)
            end
        when
            B.FLOOR = 'Y'
                and A.LMTIER not in ('C01' , 'C02')
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            B.FLOOR = 'Y'
                and A.LMTIER not in ('C01' , 'C02')
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 50)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 90) - (A.AVG_DAILY_PICK / 50)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            (FLOOR = 'N' or FLOOR is null)
                and A.SUGGESTED_TIER in ('CSE_CONVEY' , 'CSE_NONCON')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 90)) / .052632)
            end
        when
            (FLOOR = 'N' or FLOOR is null)
                and A.SUGGESTED_TIER in ('C01' , 'C02')
        then
            case
                when 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632) < 0 then 0
                else 1 - (abs((A.AVG_DAILY_PICK / 50) - (A.AVG_DAILY_PICK / 200)) / .052632)
            end
        when
            PACKAGE_TYPE = 'PFR'
                and A.SUGGESTED_GRID5 in ('C_PFR')
        then
            1
        else 1
    end as SCORE_WALKSCORE,
    CASE
        WHEN 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632))
    end * CASE
        WHEN 1 - (((abs(0) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(0) / 5280 / 3.1) / .052632))
    end as SCORE_TOTALSCORE_OPT,
    CASE
        WHEN 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(A.SUGGESTED_IMPMOVES) / 15) / .052632))
    end as SCORE_REPLENSCORE_OPT,
    CASE
        WHEN 1 - (((abs(0) / 5280 / 3.1) / .052632)) < 0 THEN 0
        ELSE 1 - (((abs(0) / 5280 / 3.1) / .052632))
    end as SCORE_WALKSCORE_OPT
FROM
    slotting.my_npfmvc A
        left join
    slotting.case_floor_locs B ON A.WAREHOUSE = B.WHSE
        and A.CUR_LOCATION = B.LOCATION
WHERE
    PACKAGE_TYPE not in ('LSE' , 'INP')");
$scoresql->execute();
$scoresqlarray = $scoresql->fetchAll(pdo::FETCH_ASSOC);



$maxrange = 999;
$counter = 0;
$rowcount = count($scoresqlarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $SCORE_WHSE = intval($scoresqlarray[$counter]['WAREHOUSE']);
        $SCORE_ITEM = intval($scoresqlarray[$counter]['ITEM_NUMBER']);
        $SCORE_PKGU = intval($scoresqlarray[$counter]['PACKAGE_UNIT']);
        $SCORE_ZONE = ($scoresqlarray[$counter]['PACKAGE_TYPE']);
        $SCORE_TOTALSCORE = ($scoresqlarray[$counter]['SCORE_TOTALSCORE']);
        $SCORE_REPLENSCORE = ($scoresqlarray[$counter]['SCORE_REPLENSCORE']);
        $SCORE_WALKSCORE = ($scoresqlarray[$counter]['SCORE_WALKSCORE']);
        $SCORE_TOTALSCORE_OPT = ($scoresqlarray[$counter]['SCORE_TOTALSCORE_OPT']);
        $SCORE_REPLENSCORE_OPT = ($scoresqlarray[$counter]['SCORE_REPLENSCORE_OPT']);
        $SCORE_WALKSCORE_OPT = ($scoresqlarray[$counter]['SCORE_WALKSCORE_OPT']);


        $data[] = "($SCORE_WHSE, $SCORE_ITEM, $SCORE_PKGU, '$SCORE_ZONE', '$SCORE_TOTALSCORE', '$SCORE_REPLENSCORE', '$SCORE_WALKSCORE', '$SCORE_TOTALSCORE_OPT', '$SCORE_REPLENSCORE_OPT', '$SCORE_WALKSCORE_OPT',0,0)";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.slottingscore ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount); //end of item by whse loop
//***Write the relevant scores to the historcal table for tracking and trend analysis***

//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16, 32);

foreach ($whsearray as $whse) {

    //score_loose100
    $loosescore_100data = $conn1->prepare("SELECT 
                                avg(items.SCORE_TOTALSCORE) as loosescore_bottom100
                            FROM
                                (SELECT 
                                    B.SCORE_TOTALSCORE
                                from
                                    slotting.slottingscore B
                                WHERE
                                    B.SCORE_WHSE = $whse
                                        and B.SCORE_ZONE in ('LSE' , 'INP')
                                ORDER BY B.SCORE_TOTALSCORE asc
                                LIMIT 100) items");
    $loosescore_100data->execute();
    $loosescore_100dataarray = $loosescore_100data->fetchAll(pdo::FETCH_ASSOC);
    $loosescore_bottom100 = number_format($loosescore_100dataarray[0]['loosescore_bottom100'] * 100, 1);

    //score_loose1000
    $loosescore_1000data = $conn1->prepare("SELECT 
                                avg(items.SCORE_TOTALSCORE) as loosescore_bottom1000
                            FROM
                                (SELECT 
                                    B.SCORE_TOTALSCORE
                                from
                                    slotting.slottingscore B
                                WHERE
                                    B.SCORE_WHSE = $whse
                                        and B.SCORE_ZONE in ('LSE' , 'INP')
                                ORDER BY B.SCORE_TOTALSCORE asc
                                LIMIT 1000) items");
    $loosescore_1000data->execute();
    $loosescore_1000dataarray = $loosescore_1000data->fetchAll(pdo::FETCH_ASSOC);
    $loosescore_bottom1000 = number_format($loosescore_1000dataarray[0]['loosescore_bottom1000'] * 100, 1);

    //score_looseall
    $loosescore_alldata = $conn1->prepare("SELECT 
                                avg(items.SCORE_TOTALSCORE) as loosescore_bottomall
                            FROM
                                (SELECT 
                                    B.SCORE_TOTALSCORE
                                from
                                    slotting.slottingscore B
                                WHERE
                                    B.SCORE_WHSE = $whse
                                        and B.SCORE_ZONE in ('LSE' , 'INP')
                                ORDER BY B.SCORE_TOTALSCORE asc) items");
    $loosescore_alldata->execute();
    $loosescore_alldataarray = $loosescore_alldata->fetchAll(pdo::FETCH_ASSOC);
    $loosescore_bottomall = number_format($loosescore_alldataarray[0]['loosescore_bottomall'] * 100, 1);


    //determine if Sparks building one or two
    if ($whse == 32) {
        $sparksbuild2filter = " >= 'W300000'";
        $whsecase = 3;
    } elseif ($whse == 3) {
        $sparksbuild2filter = " <= 'W299999'";
        $whsecase = $whse;
    } else {
        $sparksbuild2filter = " >= ' '";
        $whsecase = $whse;
    }

    //score_case100
    $casescore_100data = $conn1->prepare("SELECT 
                                        avg(items.SCORE_TOTALSCORE) as casescore_bottom100
                                    FROM
                                        (SELECT 
                                            B.SCORE_TOTALSCORE, B.SCORE_WHSE, B.SCORE_ITEM, B.SCORE_PKGU, B.SCORE_ZONE
                                        from
                                            slotting.slottingscore B
                                            join
                                        slotting.my_npfmvc C ON C.WAREHOUSE = B.SCORE_WHSE
                                            and C.ITEM_NUMBER = B.SCORE_ITEM
                                            and C.PACKAGE_UNIT = B.SCORE_PKGU
                                            and C.PACKAGE_TYPE = B.SCORE_ZONE
                                        WHERE
                                            B.SCORE_WHSE = $whsecase
                                                and B.SCORE_ZONE in ('CSE' , 'PFR')
                                        ORDER BY B.SCORE_TOTALSCORE asc
                                        LIMIT 100) items");
    $casescore_100data->execute();
    $casescore_100dataarray = $casescore_100data->fetchAll(pdo::FETCH_ASSOC);
    $casescore_bottom100 = number_format($casescore_100dataarray[0]['casescore_bottom100'] * 100, 1);

    //score_case1000
    $casescore_1000data = $conn1->prepare("SELECT 
                                        avg(items.SCORE_TOTALSCORE) as casescore_bottom1000
                                    FROM
                                        (SELECT 
                                            B.SCORE_TOTALSCORE, B.SCORE_WHSE, B.SCORE_ITEM, B.SCORE_PKGU, B.SCORE_ZONE
                                        from
                                            slotting.slottingscore B
                                            join
                                        slotting.my_npfmvc C ON C.WAREHOUSE = B.SCORE_WHSE
                                            and C.ITEM_NUMBER = B.SCORE_ITEM
                                            and C.PACKAGE_UNIT = B.SCORE_PKGU
                                            and C.PACKAGE_TYPE = B.SCORE_ZONE
                                        WHERE
                                            B.SCORE_WHSE = $whsecase
                                                and B.SCORE_ZONE in ('CSE' , 'PFR')
                                        ORDER BY B.SCORE_TOTALSCORE asc
                                        LIMIT 1000) items");
    $casescore_1000data->execute();
    $casescore_1000dataarray = $casescore_1000data->fetchAll(pdo::FETCH_ASSOC);
    $casescore_bottom1000 = number_format($casescore_1000dataarray[0]['casescore_bottom1000'] * 100, 1);


    //score_caseall
    $casescore_alldata = $conn1->prepare("SELECT 
                                        avg(items.SCORE_TOTALSCORE) as casescore_bottomall
                                    FROM
                                        (SELECT 
                                            B.SCORE_TOTALSCORE, B.SCORE_WHSE, B.SCORE_ITEM, B.SCORE_PKGU, B.SCORE_ZONE
                                        from
                                            slotting.slottingscore B
                                            join
                                        slotting.my_npfmvc C ON C.WAREHOUSE = B.SCORE_WHSE
                                            and C.ITEM_NUMBER = B.SCORE_ITEM
                                            and C.PACKAGE_UNIT = B.SCORE_PKGU
                                            and C.PACKAGE_TYPE = B.SCORE_ZONE
                                        WHERE
                                            B.SCORE_WHSE = $whsecase
                                                and B.SCORE_ZONE in ('CSE' , 'PFR')
                                        ORDER BY B.SCORE_TOTALSCORE asc) items");
    $casescore_alldata->execute();
    $casescore_alldataarray = $casescore_alldata->fetchAll(pdo::FETCH_ASSOC);
    $casescore_bottomall = number_format($casescore_alldataarray[0]['casescore_bottomall'] * 100, 1);

    //loose walk reduction
    $walkred_loose = $conn1->prepare("SELECT 
                                        SUM(OPT_ADDTLFTPERDAY) / 5280 as WALKTIMEREDLOOSE
                                    FROM
                                        slotting.optimalbay
                                    WHERE
                                        OPT_WHSE = $whse
                                            and OPT_CSLS in ('LSE' , 'INP')");
    $walkred_loose->execute();
    $walkred_loosearray = $walkred_loose->fetchAll(pdo::FETCH_ASSOC);
    $walkred_loose_miles = number_format($walkred_loosearray[0]['WALKTIMEREDLOOSE'], 1);

    //loose replen reduction
    $replenred_loose = $conn1->prepare("SELECT 
                                SUM(CURRENT_IMPMOVES) - 
                                    SUM(SUGGESTED_IMPMOVES) as REPLENREDLOOSE
                            FROM
                                slotting.my_npfmvc
                            WHERE
                                WAREHOUSE = $whse
                                    and PACKAGE_TYPE in ('LSE' , 'INP')");
    $replenred_loose->execute();
    $replenred_loosearray = $replenred_loose->fetchAll(pdo::FETCH_ASSOC);

    $replenred_loose_moves = number_format($replenred_loosearray[0]['REPLENREDLOOSE'], 1);

    //case hour reduction
    $walkred_case = $conn1->prepare("SELECT 
                                        SUM(OPT_ADDTLFTPERDAY) / 60 as WALKTIMEREDCASE
                                    FROM
                                        slotting.optimalbay
                                    WHERE
                                        OPT_WHSE = $whsecase
                                            and OPT_CSLS in ('CSE' , 'PFR')");
    $walkred_case->execute();
    $walkred_casearray = $walkred_case->fetchAll(pdo::FETCH_ASSOC);

    $walkred_casearray_hours = ($walkred_casearray[0]['WALKTIMEREDCASE']);

    if ($walkred_casearray_hours == NULL) {
        $walkred_casearray_hours = 0;
    }

    //case replen reduction
    $replenred_case = $conn1->prepare("SELECT 
                                SUM(CURRENT_IMPMOVES) - 
                                    SUM(SUGGESTED_IMPMOVES) as REPLENREDCASE
                            FROM
                                slotting.my_npfmvc
                            WHERE
                                WAREHOUSE = $whsecase
                                    and PACKAGE_TYPE in ('CSE' , 'PFR')");
    $replenred_case->execute();
    $replenred_casearray = $replenred_case->fetchAll(pdo::FETCH_ASSOC);

    $replenred_casearray_moves = ($replenred_casearray[0]['REPLENREDCASE']);
    if ($replenred_casearray_moves == NULL) {
        $replenred_casearray_moves = 0;
    }

    //insert into table slottingscore_hist

    $result1 = $conn1->prepare("INSERT INTO slotting.slottingscore_hist(slottingscore_hist_WHSE, slottingscore_hist_DATE, slottingscore_hist_LSE100, slottingscore_hist_LSE1000, slottingscore_hist_LSEALL, slottingscore_hist_CSE100, slottingscore_hist_CSE1000, slottingscore_hist_CSEALL, slottingscore_hist_LSEWALK, slottingscore_hist_LSEMOVES, slottingscore_hist_CSEHOURS, slottingscore_hist_CSEMOVES)
                                VALUES ($whse, '$datetime', '$loosescore_bottom100', '$loosescore_bottom1000', '$loosescore_bottomall', '$casescore_bottom100', '$casescore_bottom1000', '$casescore_bottomall', '$walkred_loose_miles', '$replenred_loose_moves', '$walkred_casearray_hours', '$replenred_casearray_moves')
                                ON DUPLICATE KEY UPDATE slottingscore_hist_LSE100=VALUES(slottingscore_hist_LSE100), slottingscore_hist_LSE1000=VALUES(slottingscore_hist_LSE1000), slottingscore_hist_LSEALL=VALUES(slottingscore_hist_LSEALL), slottingscore_hist_CSE100=VALUES(slottingscore_hist_CSE100), slottingscore_hist_CSE1000=VALUES(slottingscore_hist_CSE1000), slottingscore_hist_CSEALL=VALUES(slottingscore_hist_CSEALL), slottingscore_hist_LSEWALK=VALUES(slottingscore_hist_LSEWALK), slottingscore_hist_LSEMOVES=VALUES(slottingscore_hist_LSEMOVES), slottingscore_hist_CSEHOURS=VALUES(slottingscore_hist_CSEHOURS), slottingscore_hist_CSEMOVES=VALUES(slottingscore_hist_CSEMOVES)");
    $result1->execute();
}

//replen history by bay
$result2 = $conn1->prepare("INSERT INTO slotting.replen_hist (replen_whse, replen_date, replen_bay, replen_replens) 
                            SELECT 
                                WAREHOUSE,
                                curdate(),
                                substring(CUR_LOCATION, 1, 5) as BAY,
                                sum(CURRENT_IMPMOVES - SUGGESTED_IMPMOVES) * 253 as YEARLYMOVES
                            FROM
                                slotting.my_npfmvc
                            GROUP BY WAREHOUSE , curdate() , substring(CUR_LOCATION, 1, 5)
                            ON DUPLICATE KEY UPDATE replen_replens=VALUES(replen_replens)");
$result2->execute();


//Walk feet history by bay


$result3 = $conn1->prepare("INSERT INTO slotting.walk_hist (walk_whse, walk_date, walk_bay, walk_walkfeet) 
                            SELECT 
                                OPT_WHSE,
                                curdate(),
                                substring(OPT_LOC, 1, 5) as BAY,
                                sum(OPT_ADDTLFTPERDAY) * 253 as YEARLYFEET
                            FROM
                                slotting.optimalbay
                            GROUP BY OPT_WHSE , curdate() , substring(OPT_LOC, 1, 5)
                            ON DUPLICATE KEY UPDATE walk_walkfeet=VALUES(walk_walkfeet)");
$result3->execute();


//historical feet summary graph update

$result4 = $conn1->prepare("INSERT INTO slotting.feetperpick_summary (fpp_whse, fpp_date, fpp_totalfeet, fpp_fpp) 
                            SELECT 
                                picksbybay_WHSE,
                                picksbybay_DATE,
                                sum(picksbybay_PICKS * WALKFEET) as fpp_totalfeet,
                                sum(picksbybay_PICKS * WALKFEET) / sum(picksbybay_PICKS) as fpp_fpp
                            FROM
                                slotting.picksbybay
                                    left join
                                slotting.vectormap ON picksbybay_BAY = BAY
                                    and VECTWHSE = picksbybay_WHSE
                            WHERE
                                picksbybay_DATE >= '$previous7days'
                            GROUP BY picksbybay_DATE , picksbybay_WHSE
                            ON DUPLICATE KEY UPDATE fpp_totalfeet=VALUES(fpp_totalfeet), fpp_fpp=VALUES(fpp_fpp)");
$result4->execute();


//map errors for locations in slot master not mapped
$sqldelete2 = "TRUNCATE TABLE slotting.vectormaperrors";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

$result5 = $conn1->prepare("INSERT IGNORE INTO slotting.vectormaperrors (maperror_whse, maperror_bay, maperror_tier)
                                                         SELECT DISTINCT
                                                                LMWHSE, LMBAY, LMTIER
                                                            FROM
                                                                slotting.mysql_npflsm
                                                                    LEFT JOIN
                                                                slotting.vectormap ON BAY = LMBAY AND LMWHSE = VECTWHSE
                                                            WHERE
                                                              WALKFEET is null and LMTIER <> ' '");
$result5->execute();


//update slotting historical scores by item.

$result6 = $conn1->prepare("INSERT IGNORE into slotting.slottingscore_hist_item
                                                        SELECT 
                                                            WAREHOUSE,
                                                            ITEM_NUMBER,
                                                            PACKAGE_UNIT,
                                                            CUR_LOCATION,
                                                            LMTIER,
                                                            SUGGESTED_TIER,
                                                            LMGRD5,
                                                            SUGGESTED_GRID5,
                                                            SUGGESTED_DEPTH,
                                                            SUGGESTED_SLOTQTY,
                                                            SUGGESTED_MAX,
                                                            CURRENT_IMPMOVES,
                                                            SUGGESTED_IMPMOVES,
                                                            AVG_DAILY_PICK,
                                                            AVG_DAILY_UNIT,
                                                            SCORE_TOTALSCORE,
                                                            SCORE_TOTALSCORE_OPT,
                                                            SCORE_REPLENSCORE,
                                                            SCORE_REPLENSCORE_OPT,
                                                            SCORE_WALKSCORE,
                                                            SCORE_WALKSCORE_OPT,
                                                            SCORE_BOTTOM100,
                                                            SCORE_BOTTOM1000,
                                                            CURDATE()
                                                        FROM
                                                            slotting.slottingscore
                                                                JOIN
                                                            slotting.my_npfmvc ON SCORE_WHSE = WAREHOUSE
                                                                AND SCORE_ITEM = ITEM_NUMBER
                                                                AND SCORE_PKGU = PACKAGE_UNIT
                                                                AND SCORE_ZONE = PACKAGE_TYPE");
$result6->execute();
