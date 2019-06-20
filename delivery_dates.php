
<?php

date_default_timezone_set('America/New_York');
set_time_limit(99999);
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../globalincludes/usa_asys.php';
include '../globalfunctions/custdbfunctions.php';
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';  //modelling connection

$rollmonthdate = date('Y-m-d', strtotime('-30 days'));
$rollqtrdate = date('Y-m-d', strtotime('-90 days'));
$rollyeardate = date('Y-m-d', strtotime('-365 days'));

$sqldelete2 = "TRUNCATE TABLE custaudit.delivery_dates_merge";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

$startdate = _roll10dayyyyymmdd();
//$startdate = _roll12yyyymmdd();


$dates = $aseriesconn->prepare("SELECT DISTINCT XHDDAT FROM A.HSIPCORDTA.NOTHDR WHERE  XHDDAT  >= $startdate");
$dates->execute();
$datesarray = $dates->fetchAll(pdo::FETCH_COLUMN);

$columns = 'WHSE, WCSNUM, WONUM, BOXNUM, SHIPZONE, SHIPCLASS, TRACER, BOXSIZE, HAZCLASS, BOXLINES, BOXWEIGHT, ZIPCODE, BOXVALUE, DELIVERDATE, DELIVERTIME, LICENSE, CARRIER, SHIPDATE, SHIPTIME, BILLTO, SHIPTO';



foreach ($datesarray as $value) {
    $sqldelete = "TRUNCATE TABLE custaudit.delivery_dates_only";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    $result1 = $aseriesconn->prepare("SELECT
                                                        PBWHSE as WHSE,
                                                        PBWCS# as WCSNUM,
                                                        PBWKNO as WONUM,
                                                        PBBOX# as BOXNUM,
                                                        PBSHPZ as SHIPZONE,
                                                        PBSHPC as SHIPCLASS,
                                                        PBTRC# as TRACER,
                                                        PBBXSZ as BOXSIZE,
                                                        CPHAZT as HAZCLASS,
                                                        PBBOXL as BOXLINES,
                                                        PBBXAW as BOXWEIGHT,
                                                        GCZIP5 as ZIPCODE,
                                                        PBBXVS as BOXVALUE,
                                                        substring(XHDDAT,1,4) || '-' ||   substring(XHDDAT,5,2) || '-' ||   substring(XHDDAT,7,2) as DELIVERDATE,
                                                        case when  XHDTIM <= 999 then substring(XHDTIM,1,1) || ':' || substring(XHDTIM,2,2)  else  substring(XHDTIM,1,2) || ':' || substring(XHDTIM,3,2) end as DELIVERTIME ,
                                                        XHLP9D as LICENSE,
                                                        XHCRNM as CARRIER,
                                                        substring(XHSDAT,1,4) || '-' ||   substring(XHSDAT,5,2) || '-' ||   substring(XHSDAT,7,2) as SHIPDATE,
                                                        case when XHSTIM <= 99999 then    substring(XHSTIM,1,1) || ':' || substring(XHSTIM,2,2) else  substring(XHSTIM,1,2) || ':' || substring(XHSTIM,3,2) end  as SHIPTIME,
                                                        XHAN8 AS BILLTO,
                                                        XHSHAN AS SHIPTO
                                                      FROM A.HSIPCORDTA.NOTHDR
                                                      WHERE PBTRC# like '1Z%' and XHDDAT  = $value");
    $result1->execute();
    $masterdisplayarray = $result1->fetchAll(pdo::FETCH_ASSOC);

    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($masterdisplayarray);


    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }
        $data = array();
        $values = array();

        while ($counter <= $maxrange) {
            $WHSE = intval($masterdisplayarray[$counter]['WHSE']);
            $WCSNUM = intval($masterdisplayarray[$counter]['WCSNUM']);
            $WONUM = intval($masterdisplayarray[$counter]['WONUM']);
            $BOXNUM = intval($masterdisplayarray[$counter]['BOXNUM']);
            $SHIPZONE = trim($masterdisplayarray[$counter]['SHIPZONE']);
            $SHIPCLASS = trim($masterdisplayarray[$counter]['SHIPCLASS']);
            $TRACER = trim($masterdisplayarray[$counter]['TRACER']);
            $BOXSIZE = trim($masterdisplayarray[$counter]['BOXSIZE']);
            $HAZCLASS = trim($masterdisplayarray[$counter]['HAZCLASS']);
            $BOXLINES = intval($masterdisplayarray[$counter]['BOXLINES']);
            $BOXWEIGHT = $masterdisplayarray[$counter]['BOXWEIGHT'];
            $ZIPCODE = intval($masterdisplayarray[$counter]['ZIPCODE']);
            $BOXVALUE = $masterdisplayarray[$counter]['BOXVALUE'];
            $DELIVERDATE = trim($masterdisplayarray[$counter]['DELIVERDATE']);
            $DELIVERTIME = trim($masterdisplayarray[$counter]['DELIVERTIME']);
            $LICENSE = intval($masterdisplayarray[$counter]['LICENSE']);
            $CARRIER = trim(preg_replace('/[^ \w]+/', '', $masterdisplayarray[$counter]['CARRIER']));
            $SHIPDATE = trim($masterdisplayarray[$counter]['SHIPDATE']);
            $SHIPTIME = trim($masterdisplayarray[$counter]['SHIPTIME']);
            $BILLTO = intval($masterdisplayarray[$counter]['BILLTO']);
            $SHIPTO = intval($masterdisplayarray[$counter]['SHIPTO']);

            $data[] = "($WHSE, $WCSNUM, $WONUM, $BOXNUM, '$SHIPZONE', '$SHIPCLASS', '$TRACER', '$BOXSIZE', '$HAZCLASS', $BOXLINES, '$BOXWEIGHT', $ZIPCODE, '$BOXVALUE', '$DELIVERDATE', '$DELIVERTIME', $LICENSE, '$CARRIER', '$SHIPDATE', '$SHIPTIME', $BILLTO, $SHIPTO)";
            $counter += 1;
        }

        $values = implode(',', $data);
        if (empty($values)) {
            break;
        }

        $sql = "INSERT IGNORE INTO custaudit.delivery_dates_only ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();


//select all from the merge table and join with the standard times in transit table to determine if ontime
        $dayscalc = $conn1->prepare("INSERT IGNORE INTO delivery_dates_merge
                                                                             SELECT 
                                                                                A.*,
                                                                                DAYS + (SELECT 
                                                                                        COUNT(*)
                                                                                    FROM
                                                                                        custaudit.ups_holiday
                                                                                    WHERE
                                                                                        (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE)) AS SHOULDDAYS,
                                                                                5 * (DATEDIFF(DELIVERDATE, SHIPDATE) DIV 7) + MID('0123444401233334012222340111123400001234000123440',
                                                                                    7 * WEEKDAY(SHIPDATE) + WEEKDAY(DELIVERDATE) + 1,
                                                                                    1) AS ACTUALDAYS,
                                                                                CASE
                                                                                    WHEN
                                                                                        DAYS + (SELECT 
                                                                                                COUNT(*)
                                                                                            FROM
                                                                                                custaudit.ups_holiday
                                                                                            WHERE
                                                                                                (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE)) < 5 * (DATEDIFF(DELIVERDATE, SHIPDATE) DIV 7) + MID('0123444401233334012222340111123400001234000123440',
                                                                                            7 * WEEKDAY(SHIPDATE) + WEEKDAY(DELIVERDATE) + 1,
                                                                                            1)
                                                                                    THEN
                                                                                        1
                                                                                    ELSE 0
                                                                                END AS LATE
                                                                            FROM
                                                                                custaudit.delivery_dates_only A
                                                                                    JOIN
                                                                                custaudit.transit_times B ON A.WHSE = B.SHIPDC
                                                                                    AND A.ZIPCODE = B.ZIPCODE");
                                                                                    $dayscalc->execute();


        //once merge table has been populated, insert on duplicate key update here.
        $sqlmerge2 = "INSERT INTO custaudit.delivery_dates (WHSE, WCSNUM, WONUM, BOXNUM, SHIPZONE, SHIPCLASS, TRACER, BOXSIZE, HAZCLASS, BOXLINES, BOXWEIGHT, ZIPCODE, BOXVALUE, DELIVERDATE, DELIVERTIME, LICENSE, CARRIER, SHIPDATE, SHIPTIME, BILLTO, SHIPTO, SHOULDDAYS, ACTUALDAYS, LATE)
                                    SELECT A.WHSE, A.WCSNUM, A.WONUM, A.BOXNUM, A.SHIPZONE, A.SHIPCLASS, A.TRACER, A.BOXSIZE, A.HAZCLASS, A.BOXLINES, A.BOXWEIGHT, A.ZIPCODE, A.BOXVALUE, A.DELIVERDATE, A.DELIVERTIME, A.LICENSE, A.CARRIER, A.SHIPDATE, A.SHIPTIME, A.BILLTO, A.SHIPTO, A.SHOULDDAYS, A.ACTUALDAYS, A.LATE FROM custaudit.delivery_dates_merge A
                                    ON DUPLICATE KEY UPDATE  DELIVERDATE = A.DELIVERDATE,  DELIVERTIME = A.DELIVERTIME, SHOULDDAYS = A.SHOULDDAYS, ACTUALDAYS = A.ACTUALDAYS, LATE = A.LATE;  ";
        $querymerge2 = $conn1->prepare($sqlmerge2);
        $querymerge2->execute();

        $sqldelete2 = "TRUNCATE TABLE custaudit.delivery_dates_merge";
        $querydelete2 = $conn1->prepare($sqldelete2);
        $querydelete2->execute();

        $sqldelete3 = "TRUNCATE TABLE custaudit.delivery_dates_only";
        $querydelete3 = $conn1->prepare($sqldelete3);
        $querydelete3->execute();


        $maxrange += 10000;
    } while ($counter <= $rowcount);
}


//populate tnt_summary table

$sqldelete5 = "TRUNCATE TABLE custaudit.tnt_summary";
$querydelete5 = $conn1->prepare($sqldelete5);
$querydelete5->execute();

$sqlmerge2 = "INSERT INTO custaudit.tnt_summary (tnt_billto, tnt_shipto, tnt_boxes_mnt, tnt_late_mnt, tnt_mnt_ontime, tnt_boxes_qtr, tnt_late_qtr, tnt_qtr_ontime, tnt_boxes_r12, tnt_late_r12, tnt_r12_ontime, tnt_avg_mnt, tnt_avg_qtr, tnt_avg_r12)
SELECT 
    BILLTO, SHIPTO,
        SUM(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' THEN 1
        ELSE 0
    END) AS BOXES_MNT,
        SUM(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' AND LATE > 0 THEN 1
        ELSE 0
    END) AS LATE_MNT,
    (SUM(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' THEN 1
        ELSE 0
    END) - SUM(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' AND LATE > 0 THEN 1
        ELSE 0
    END)) / SUM(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' THEN 1
        ELSE 0
    END) AS PERC_ONTIME_MNT,
    SUM(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' THEN 1
        ELSE 0
    END) AS BOXES_QTR,
    SUM(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' AND LATE > 0 THEN 1
        ELSE 0
    END) AS LATE_QTR,
    (SUM(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' THEN 1
        ELSE 0
    END) - SUM(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' AND LATE > 0 THEN 1
        ELSE 0
    END)) / SUM(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' THEN 1
        ELSE 0
    END) AS PERC_ONTIME_QTR,
            SUM(CASE
        WHEN DELIVERDATE >= '$rollyeardate' THEN 1
        ELSE 0
    END) AS BOXES_R12,
        SUM(CASE
        WHEN DELIVERDATE >= '$rollyeardate' AND LATE > 0 THEN 1
        ELSE 0
    END) AS LATE_R12,
    (SUM(CASE
        WHEN DELIVERDATE >= '$rollyeardate' THEN 1
        ELSE 0
    END) - SUM(CASE
        WHEN DELIVERDATE >= '$rollyeardate' AND LATE > 0 THEN 1
        ELSE 0
    END)) / SUM(CASE
        WHEN DELIVERDATE >= '$rollyeardate' THEN 1
        ELSE 0
    END) AS PERC_ONTIME_R12,
    AVG(CASE
        WHEN DELIVERDATE >= '$rollmonthdate' THEN ACTUALDAYS
        ELSE NULL
    END) AS AVG_TNT_MNT,
    AVG(CASE
        WHEN DELIVERDATE >= '$rollqtrdate' THEN ACTUALDAYS
        ELSE NULL
    END) AS AVG_TNT_QTR,
    AVG(CASE
        WHEN DELIVERDATE >= '$rollyeardate' THEN ACTUALDAYS
        ELSE NULL
    END) AS AVG_TNT_R12
FROM
    custaudit.delivery_dates 
GROUP BY BILLTO, SHIPTO
";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();

