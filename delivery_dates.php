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

$startdate = date('Ymd', strtotime('-2 days'));
//$startdate = _roll12yyyymmdd();

// --- UPS EXCEPTION DATA PULL AND INSERT ---
try {
    // Pull UPS exceptions from iSeries NOFPOD
    $exceptionQuery = $aseriesconn->prepare("
        SELECT
            POTRK#, PODATE, POTIME, PORSCD, POEXRE, POEXRD, POEXRT, POREDD, POREDT,
            POCARRIER, POAN8, POSHAN, POHSIINV, POHSIWRK, POHSIBOX#, POCBI#, POPFT3,
            PONAME, POADDR, POCITY, POSTE, POZIP, POSTAT
        FROM HSIPCORDTA.NOFPOD
        WHERE POEXRD <> ' ' AND POEXRD IS NOT NULL AND PODATE >= $startdate
        ORDER BY PODATE
    ");
    $exceptionQuery->execute();
    $exceptions = $exceptionQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($exceptions as $ex) {
        // Validate and sanitize numeric fields
        $billto = (isset($ex['POAN8']) && is_numeric($ex['POAN8'])) ? $ex['POAN8'] : null;
        $shipto = (isset($ex['POSHAN']) && is_numeric($ex['POSHAN'])) ? $ex['POSHAN'] : null;
        $wcsnum = (isset($ex['POHSIINV']) && is_numeric($ex['POHSIINV'])) ? $ex['POHSIINV'] : null;
        $wonum = (isset($ex['POHSIWRK']) && is_numeric($ex['POHSIWRK'])) ? $ex['POHSIWRK'] : null;
        $boxnum = (isset($ex['POHSIBOX#']) && is_numeric($ex['POHSIBOX#'])) ? $ex['POHSIBOX#'] : null;

        // Validate and sanitize date fields
        $exception_date = (!empty($ex['PODATE']) && strlen($ex['PODATE']) == 8 && ctype_digit($ex['PODATE'])) ?
            substr($ex['PODATE'],0,4) . '-' . substr($ex['PODATE'],4,2) . '-' . substr($ex['PODATE'],6,2) : null;
        $resched_delivery_date = (!empty($ex['POREDD']) && strlen($ex['POREDD']) == 8 && ctype_digit($ex['POREDD'])) ?
            substr($ex['POREDD'],0,4) . '-' . substr($ex['POREDD'],4,2) . '-' . substr($ex['POREDD'],6,2) : null;

        // Log problematic records
        if (
            (!is_null($ex['POAN8']) && !is_numeric($ex['POAN8']) && $ex['POAN8'] !== '') ||
            (!is_null($ex['POSHAN']) && !is_numeric($ex['POSHAN']) && $ex['POSHAN'] !== '') ||
            (!is_null($ex['POHSIINV']) && !is_numeric($ex['POHSIINV']) && $ex['POHSIINV'] !== '') ||
            (!is_null($ex['POHSIWRK']) && !is_numeric($ex['POHSIWRK']) && $ex['POHSIWRK'] !== '') ||
            (!is_null($ex['POHSIBOX#']) && !is_numeric($ex['POHSIBOX#']) && $ex['POHSIBOX#'] !== '')
        ) {
            error_log('Non-numeric value found in UPS Exception: ' . print_r($ex, true));
        }
        if (
            (!empty($ex['PODATE']) && (strlen($ex['PODATE']) != 8 || !ctype_digit($ex['PODATE']))) ||
            (!empty($ex['POREDD']) && (strlen($ex['POREDD']) != 8 || !ctype_digit($ex['POREDD'])))
        ) {
            error_log('Malformed date value found in UPS Exception: ' . print_r($ex, true));
        }

        $insertException = $conn1->prepare("
            INSERT INTO custaudit.ups_exceptions (
                tracking_number, exception_date, exception_time, exception_code, exception_reason, exception_detail, exception_reason_type, resched_delivery_date, resched_delivery_time, carrier, billto, shipto, wcsnum, wonum, boxnum, boxid, salesplan, recipient_name, address, city, state, zip, status
            ) VALUES (
                :tracking_number, :exception_date, :exception_time, :exception_code, :exception_reason, :exception_detail, :exception_reason_type, :resched_delivery_date, :resched_delivery_time, :carrier, :billto, :shipto, :wcsnum, :wonum, :boxnum, :boxid, :salesplan, :recipient_name, :address, :city, :state, :zip, :status
            ) ON DUPLICATE KEY UPDATE
                exception_date = VALUES(exception_date),
                exception_time = VALUES(exception_time),
                exception_code = VALUES(exception_code),
                exception_reason = VALUES(exception_reason),
                exception_detail = VALUES(exception_detail),
                exception_reason_type = VALUES(exception_reason_type),
                resched_delivery_date = VALUES(resched_delivery_date),
                resched_delivery_time = VALUES(resched_delivery_time),
                carrier = VALUES(carrier),
                billto = VALUES(billto),
                shipto = VALUES(shipto),
                wcsnum = VALUES(wcsnum),
                wonum = VALUES(wonum),
                boxnum = VALUES(boxnum),
                boxid = VALUES(boxid),
                salesplan = VALUES(salesplan),
                recipient_name = VALUES(recipient_name),
                address = VALUES(address),
                city = VALUES(city),
                state = VALUES(state),
                zip = VALUES(zip),
                status = VALUES(status)
        ");
        $insertException->execute([
            ':tracking_number' => $ex['POTRK#'],
            ':exception_date' => $exception_date,
            ':exception_time' => $ex['POTIME'],
            ':exception_code' => $ex['PORSCD'],
            ':exception_reason' => $ex['POEXRE'],
            ':exception_detail' => $ex['POEXRD'],
            ':exception_reason_type' => $ex['POEXRT'],
            ':resched_delivery_date' => $resched_delivery_date,
            ':resched_delivery_time' => $ex['POREDT'],
            ':carrier' => $ex['POCARRIER'],
            ':billto' => $billto,
            ':shipto' => $shipto,
            ':wcsnum' => $wcsnum,
            ':wonum' => $wonum,
            ':boxnum' => $boxnum,
            ':boxid' => $ex['POCBI#'],
            ':salesplan' => $ex['POPFT3'],
            ':recipient_name' => $ex['PONAME'],
            ':address' => $ex['POADDR'],
            ':city' => $ex['POCITY'],
            ':state' => $ex['POSTE'],
            ':zip' => $ex['POZIP'],
            ':status' => $ex['POSTAT']
        ]);
    }
} catch (Exception $e) {
    error_log('UPS Exception Insert Error: ' . $e->getMessage());
}

$dates = $aseriesconn->prepare("SELECT DISTINCT XHDDAT FROM A.HSIPCORDTA.NOTHDR WHERE  XHDDAT  >= $startdate");
$dates->execute();
$datesarray = $dates->fetchAll(pdo::FETCH_COLUMN);

$columns = 'SALESPLAN, WHSE, WCSNUM, WONUM, BOXNUM, SHIPZONE, SHIPCLASS, TRACER, BOXSIZE, HAZCLASS, BOXLINES, BOXWEIGHT, ZIPCODE, BOXVALUE, DELIVERDATE, DELIVERTIME, LICENSE, CARRIER, SHIPDATE, SHIPTIME, BILLTO, SHIPTO';



foreach ($datesarray as $value) {
    $sqldelete = "TRUNCATE TABLE custaudit.delivery_dates_only";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    $result1 = $aseriesconn->prepare("SELECT
       POPRV3 as SALESPLAN,
       PBWHSE as WHSE     ,
       PBWCS# as WCSNUM   ,
       PBWKNO as WONUM    ,
       PBBOX# as BOXNUM   ,
       PBSHPZ as SHIPZONE ,
       PBSHPC as SHIPCLASS,
       PBTRC# as TRACER   ,
       PBBXSZ as BOXSIZE  ,
       CPHAZT as HAZCLASS ,
       PBBOXL as BOXLINES ,
       PBBXAW as BOXWEIGHT,
       GCZIP5 as ZIPCODE  ,
       PBBXVS as BOXVALUE ,
	   substring(PODATE,1,4)
              || '-'
              || substring(PODATE,5,2)
              || '-'
              || substring(PODATE,7,2) as DELIVERDATE,
			  case
              when POTIME <= 999
                     then substring(POTIME,1,1)
                            || ':'
                            || substring(POTIME,2,2)
                     else substring(POTIME,1,2)
                            || ':'
                            || substring(POTIME,3,2)
       end   as DELIVERTIME, 
       max(substring(PODATE,1,4)
              || '-'
              || substring(PODATE,5,2)
              || '-'
              || substring(PODATE,7,2) || ' ' || 
       case
              when POTIME <= 999
                     then substring(POTIME,1,1)
                            || ':'
                            || substring(POTIME,2,2)
                     else substring(POTIME,1,2)
                            || ':'
                            || substring(POTIME,3,2)
       end)    as DELIVERDATETIME ,
       XHLP9D as LICENSE     ,
       XHCRNM as CARRIER     ,
       substring(XHSDAT,1,4)
              || '-'
              || substring(XHSDAT,5,2)
              || '-'
              || substring(XHSDAT,7,2) as SHIPDATE,
       case
              when XHSTIM <= 99999
                     then substring(XHSTIM,1,1)
                            || ':'
                            || substring(XHSTIM,2,2)
                     else substring(XHSTIM,1,2)
                            || ':'
                            || substring(XHSTIM,3,2)
       end    as SHIPTIME,
       XHAN8  AS BILLTO  ,
       XHSHAN AS SHIPTO
FROM
       A.HSIPCORDTA.NOTHDR
       JOIN
              A.HSIPCORDTA.NOFPOD
              on
                     PBWCS#     = POHSIINV
                     and PBWKNO = POHSIWRK
                     and PBBOX# = POHSIBOX#
WHERE
       PODATE        = $value
       and POSTAT like 'D%'
   GROUP BY     POPRV3,   PBWHSE ,
       PBWCS#,
       PBWKNO,
       PBBOX#,
       PBSHPZ,
       PBSHPC,
       PBTRC#,
       PBBXSZ,
       CPHAZT,
       PBBOXL,
       PBBXAW,
       GCZIP5,
       PBBXVS,
       XHLP9D,
       XHCRNM ,
       substring(XHSDAT,1,4)
              || '-'
              || substring(XHSDAT,5,2)
              || '-'
              || substring(XHSDAT,7,2),
       case
              when XHSTIM <= 99999
                     then substring(XHSTIM,1,1)
                            || ':'
                            || substring(XHSTIM,2,2)
                     else substring(XHSTIM,1,2)
                            || ':'
                            || substring(XHSTIM,3,2)
       end ,
       XHAN8,
       XHSHAN,
	   PODATE,
	   POTIME");
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
            $SALESPLAN = trim($masterdisplayarray[$counter]['SALESPLAN']);
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

            $data[] = "('$SALESPLAN', $WHSE, $WCSNUM, $WONUM, $BOXNUM, '$SHIPZONE', '$SHIPCLASS', '$TRACER', '$BOXSIZE', '$HAZCLASS', $BOXLINES, '$BOXWEIGHT', $ZIPCODE, '$BOXVALUE', '$DELIVERDATE', '$DELIVERTIME', $LICENSE, '$CARRIER', '$SHIPDATE', '$SHIPTIME', $BILLTO, $SHIPTO)";
            $counter += 1;
        }

        $values = implode(',', $data);
        if (empty($values)) {
            break;
        }

        $sql = "INSERT INTO custaudit.delivery_dates_only ($columns) VALUES $values 
                ON DUPLICATE KEY UPDATE 
                SALESPLAN = VALUES(SALESPLAN),
                SHIPZONE = VALUES(SHIPZONE),
                SHIPCLASS = VALUES(SHIPCLASS),
                TRACER = VALUES(TRACER),
                BOXSIZE = VALUES(BOXSIZE),
                HAZCLASS = VALUES(HAZCLASS),
                BOXLINES = VALUES(BOXLINES),
                BOXWEIGHT = VALUES(BOXWEIGHT),
                ZIPCODE = VALUES(ZIPCODE),
                BOXVALUE = VALUES(BOXVALUE),
                DELIVERDATE = VALUES(DELIVERDATE),
                DELIVERTIME = VALUES(DELIVERTIME),
                LICENSE = VALUES(LICENSE),
                CARRIER = VALUES(CARRIER),
                SHIPDATE = VALUES(SHIPDATE),
                SHIPTIME = VALUES(SHIPTIME),
                BILLTO = VALUES(BILLTO),
                SHIPTO = VALUES(SHIPTO)";
        $query = $conn1->prepare($sql);
        $query->execute();


//select all from the merge table and join with the standard times in transit table to determine if ontime
        $dayscalc = $conn1->prepare("INSERT INTO delivery_dates_merge
                                                                             SELECT 
                                                                                A.*,
                                                                                DAYS + (SELECT 
                                                                                        COUNT(*)
                                                                                    FROM
                                                                                        custaudit.ups_holiday
                                                                                    WHERE
                                                                                        (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE)) AS SHOULDDAYS,
                                                                                (5 * (DATEDIFF(DELIVERDATE, SHIPDATE) DIV 7) + MID('0123444401233334012222340111123400001234000123440',
                                                                                    7 * WEEKDAY(SHIPDATE) + WEEKDAY(DELIVERDATE) + 1,
                                                                                    1)) - (SELECT 
                                                                                        COUNT(*)
                                                                                    FROM
                                                                                        custaudit.ups_holiday
                                                                                    WHERE
                                                                                        (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE)) AS ACTUALDAYS,
                                                                                CASE
                                                                                    WHEN
                                                                                        DAYS + (SELECT 
                                                                                                COUNT(*)
                                                                                            FROM
                                                                                                custaudit.ups_holiday
                                                                                            WHERE
                                                                                                (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE)) < (5 * (DATEDIFF(DELIVERDATE, SHIPDATE) DIV 7) + MID('0123444401233334012222340111123400001234000123440',
                                                                                                    7 * WEEKDAY(SHIPDATE) + WEEKDAY(DELIVERDATE) + 1,
                                                                                                    1)) - (SELECT 
                                                                                                        COUNT(*)
                                                                                                    FROM
                                                                                                        custaudit.ups_holiday
                                                                                                    WHERE
                                                                                                        (upsholiday_date BETWEEN SHIPDATE AND DELIVERDATE))
                                                                                    THEN
                                                                                        1
                                                                                    ELSE 0
                                                                                END AS LATE
                                                                            FROM
                                                                                custaudit.delivery_dates_only A
                                                                                    JOIN
                                                                                custaudit.transit_times B ON A.WHSE = B.SHIPDC
                                                                                    AND A.ZIPCODE = B.ZIPCODE
                                                                            ON DUPLICATE KEY UPDATE
                                                                                SALESPLAN = VALUES(SALESPLAN),
                                                                                SHIPZONE = VALUES(SHIPZONE),
                                                                                SHIPCLASS = VALUES(SHIPCLASS),
                                                                                TRACER = VALUES(TRACER),
                                                                                BOXSIZE = VALUES(BOXSIZE),
                                                                                HAZCLASS = VALUES(HAZCLASS),
                                                                                BOXLINES = VALUES(BOXLINES),
                                                                                BOXWEIGHT = VALUES(BOXWEIGHT),
                                                                                ZIPCODE = VALUES(ZIPCODE),
                                                                                BOXVALUE = VALUES(BOXVALUE),
                                                                                DELIVERDATE = VALUES(DELIVERDATE),
                                                                                DELIVERTIME = VALUES(DELIVERTIME),
                                                                                LICENSE = VALUES(LICENSE),
                                                                                CARRIER = VALUES(CARRIER),
                                                                                SHIPDATE = VALUES(SHIPDATE),
                                                                                SHIPTIME = VALUES(SHIPTIME),
                                                                                BILLTO = VALUES(BILLTO),
                                                                                SHIPTO = VALUES(SHIPTO),
                                                                                SHOULDDAYS = VALUES(SHOULDDAYS),
                                                                                ACTUALDAYS = VALUES(ACTUALDAYS),
                                                                                LATE = VALUES(LATE)");
                                                                                    $dayscalc->execute();


        //once merge table has been populated, insert on duplicate key update here.
        $sqlmerge2 = "INSERT INTO custaudit.delivery_dates (SALESPLAN, WHSE, WCSNUM, WONUM, BOXNUM, SHIPZONE, SHIPCLASS, TRACER, BOXSIZE, HAZCLASS, BOXLINES, BOXWEIGHT, ZIPCODE, BOXVALUE, DELIVERDATE, DELIVERTIME, LICENSE, CARRIER, SHIPDATE, SHIPTIME, BILLTO, SHIPTO, SHOULDDAYS, ACTUALDAYS, LATE)
                                    SELECT A.SALESPLAN, A.WHSE, A.WCSNUM, A.WONUM, A.BOXNUM, A.SHIPZONE, A.SHIPCLASS, A.TRACER, A.BOXSIZE, A.HAZCLASS, A.BOXLINES, A.BOXWEIGHT, A.ZIPCODE, A.BOXVALUE, A.DELIVERDATE, A.DELIVERTIME, A.LICENSE, A.CARRIER, A.SHIPDATE, A.SHIPTIME, A.BILLTO, A.SHIPTO, A.SHOULDDAYS, A.ACTUALDAYS, A.LATE FROM custaudit.delivery_dates_merge A
                                    ON DUPLICATE KEY UPDATE SALESPLAN = A.SALESPLAN, DELIVERDATE = A.DELIVERDATE, DELIVERTIME = A.DELIVERTIME, SHOULDDAYS = A.SHOULDDAYS, ACTUALDAYS = A.ACTUALDAYS, LATE = A.LATE;  ";
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

$sqlmerge2 = "INSERT INTO custaudit.tnt_summary (SALESPLAN, tnt_billto, tnt_shipto, tnt_boxes_mnt, tnt_late_mnt, tnt_mnt_ontime, tnt_boxes_qtr, tnt_late_qtr, tnt_qtr_ontime, tnt_boxes_r12, tnt_late_r12, tnt_r12_ontime, tnt_avg_mnt, tnt_avg_qtr, tnt_avg_r12)
SELECT 
    SALESPLAN, BILLTO, SHIPTO,
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
GROUP BY SALESPLAN, BILLTO, SHIPTO
ON DUPLICATE KEY UPDATE
    SALESPLAN = VALUES(SALESPLAN),
    tnt_boxes_mnt = VALUES(tnt_boxes_mnt),
    tnt_late_mnt = VALUES(tnt_late_mnt),
    tnt_mnt_ontime = VALUES(tnt_mnt_ontime),
    tnt_boxes_qtr = VALUES(tnt_boxes_qtr),
    tnt_late_qtr = VALUES(tnt_late_qtr),
    tnt_qtr_ontime = VALUES(tnt_qtr_ontime),
    tnt_boxes_r12 = VALUES(tnt_boxes_r12),
    tnt_late_r12 = VALUES(tnt_late_r12),
    tnt_r12_ontime = VALUES(tnt_r12_ontime),
    tnt_avg_mnt = VALUES(tnt_avg_mnt),
    tnt_avg_qtr = VALUES(tnt_avg_qtr),
    tnt_avg_r12 = VALUES(tnt_avg_r12)
";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();

