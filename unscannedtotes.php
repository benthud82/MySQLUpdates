
<?php

include '../globalincludes/usa_asys.php';
include '../CustomerAudit/connection/connection_details.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../printvis/functions/functions_totetimes.php';
//include '../globalfunctions/custdbfunctions.php';
$whsearray = array(2, 3, 6, 7, 9);

//before deleting allcart_history, insert records in the allcart_history_hist table
$sqlinsert = "INSERT IGNORE into printvis.allcart_history_hist SELECT * FROM printvis.allcart_history";
$queryinsert = $conn1->prepare($sqlinsert);
$queryinsert->execute();



$deletdate = date('Y-m-d', strtotime("-15 days"));
$unscanneddate = date('Y-m-d', strtotime("-5 days"));
$sqldelete = "DELETE FROM  printvis.allcart_history WHERE dateaddedtotable < '$deletdate'";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


//columns for totetimes table
$columns = 'history_lp,history_whse, history_tsm, history_batch, history_bin, history_boxsize,  history_linecount,   history_finaltime, history_dateadded';

//columns for totetimes table
$alltotecolumns = 'totelp, totetimes_whse, totetimes_cart, totetimes_bin, totetimes_boxsize,  totetimes_linecount, totetimes_unitcount, totetimes_expcheck, totetimes_lotcheck, totetimes_sncheck, totetimes_shortcount, totetimes_tempcount, totetimes_odcount, totetimes_sqcount, totetimes_short, totetimes_scanlp, totetimes_boxprep, totetimes_contentlist, totetimes_unit, totetimes_line, totetimes_expiry, totetimes_lot, totetimes_sn, totetimes_box24, totetimes_temp, totetimes_od, totetimes_sq, totetimes_total, totetimes_totalPFD,totetimes_dateadded';
$allscancolumns = 'allscan_whse, allscan_batch, allscan_lp, allscan_tote, allscan_boxsize, allscan_boxlines, allscan_tsm, allscan_endtime, dateaddedtotable';
$cartstartcols = 'cartstart_whse, cartstart_batch, cartstart_tsm, cartstart_starttime, cartstart_packstation, dateaddedtotable';

$today = date('Y-m-d');
$startday = date('Y-m-d', (strtotime('-5 days', strtotime($today))));
$startjday = _gregdatetoyyddd($startday);

foreach ($whsearray as $whsesel) {
    include '../printvis/globalvariables/PFD.php';
    include '../printvis/globalvariables/packtimes.php';
    include '../printvis/timezoneset.php';

    //All scanned totes
    $cartstartdata = $aseriesconn->prepare("SELECT DISTINCT TRIM(substr(NVFLAT,3,2)) as WHSE,
                                                                                             TRIM(substr(NVFLAT,7,5)) as BATCH, 
                                                                                             TRIM(substr(NVFLAT,18,9)) as LP, 
                                                                                             TRIM(substr(NVFLAT,27,3)) as TOTE, 
                                                                                             TRIM(substr(NVFLAT,30,3)) as BOXSIZE, 
                                                                                             TRIM(substr(NVFLAT,33,3)) as BOXLINES, 
                                                                                             TRIM(substr(NVFLAT,46,10)) as TSM, 
                                                                                             TRIM(substr(NVFLAT,56,1)) as AUDITPACK, 
                                                                                             TRIM(substr(NVFLAT,57,1)) as SPEEDPACK, 
                                                                                             TRIM(substr(NVFLAT,62,1)) as HELPPACK, 
                                                                                             TRIM(substr(NVFLAT,137,19))  as ENDTIME                        
                                                                                FROM HSIPCORDTA.NOFNVI 
                                                                                WHERE TRIM(substring(NVFLAT,3,2)) = '0$whsesel' 
                                                                                              and TRIM(substr(NVFLAT,137,10)) <> ' ' 
                                                                                              and TRIM(substr(NVFLAT,7,5)) * 1 <> 0
                                                                                              and TRIM(substr(NVFLAT,46,10)) not in ('00002','00003','00006','00007','00009')
                                                                                              and TRIM(substr(NVFLAT,137,10)) >= CURDATE() - 5 DAYS ");
    $cartstartdata->execute();
    $scannedtote_array = $cartstartdata->fetchAll(pdo::FETCH_ASSOC);

    //All totes from history file
    $cartstartdata = $aseriesconn->prepare("SELECT PBLP9D,
                                                                                          PBWHSE,
                                                                                          PBCART, 
                                                                                          PBBIN#, 
                                                                                          PBBXSZ, 
                                                                                          PBSHPZ,
                                                                                          PBPTJD,
                                                                                          PBPTHR,
                                                                                          count(*) as LINE_COUNT, 
                                                                                          sum(PDPCKQ / PDPKGU) as UNIT_COUNT, 
                                                                                          sum(case when IMDTYP = 'E'  then 1 else 0 end) as EXP_CHECK, 
                                                                                          sum(case when PDLOT# <> ' ' then 1 else 0 end) as LOT_CHECK, 
                                                                                          sum(case when PDSRLF = 'Y' then 1 else 0 end) as SERIAL_CHECK,
                                                                                          sum(case when PDLOC# like 'C%' then 1 else 0 end) as TEMP_CHECK,
                                                                                          sum(case when PDHGCL = 'OD' then 1 else 0 end) as OD_CHECK,
                                                                                          sum(case when PDHGCL = 'SQ' then 1 else 0 end) as SQ_CHECK
                                                                        FROM HSIPCORDTA.NOTWPT JOIN HSIPCORDTA.NPFIMS on IMITEM = PDITEM
                                                                        JOIN HSIPCORDTA.NOTWPS on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX#
                                                                        WHERE PDWHSE = $whsesel and 
                                                                                    PDBXSZ <> 'CSE' 
                                                                                    and PDCART > 0 
                                                                                    and PDLOC# not like '%SDS%'
                                                                                    and PBPTJD >= $startjday         
                                                                                         and SUBSTR(PBSPCC,1,1) <> 'R'
                                                                        GROUP BY PBLP9D, PBWHSE, PBCART, PBBIN#, PBBXSZ, PBSHPZ, PBPTJD, PBPTHR ");
    $cartstartdata->execute();
    $cartstartdata_array = $cartstartdata->fetchAll(pdo::FETCH_ASSOC);

    //Tote packing times
    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($scannedtote_array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }
        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
            $WHSE = $scannedtote_array[$counter]['WHSE'];
            $BATCH = $scannedtote_array[$counter]['BATCH'];
            $LP = $scannedtote_array[$counter]['LP'];
            $TOTE = $scannedtote_array[$counter]['TOTE'];
            $BOXSIZE = $scannedtote_array[$counter]['BOXSIZE'];
            $BOXLINES = $scannedtote_array[$counter]['BOXLINES'];
            $TSM = $scannedtote_array[$counter]['TSM'];
            $ENDTIME = $scannedtote_array[$counter]['ENDTIME'];

            $data[] = "($WHSE,$BATCH,$LP, $TOTE,'$BOXSIZE',$BOXLINES, $TSM, '$ENDTIME', '$today'  )";
            $counter += 1;
        }

        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.scannedtote_history ($allscancolumns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount); //end of do loop for tote times
    //pull in cart start time/tsm history
    //All scanned totes
    $cartstartdata = $aseriesconn->prepare("SELECT TRIM(substr(A.NVFLAT,3,2)) as WHSE,
                                                                                              TRIM(substr(A.NVFLAT,7,5)) as BATCH, 
                                                                                              TRIM(substr(A.NVFLAT,46,10)) as TSM, 
                                                                                              TRIM(substr(A.NVFLAT,111,19))  as STARTTIME,      
                                                                                              TRIM(substr(A.NVFLAT,171, 10)) as PACKSTATION                    
                                                                                FROM HSIPCORDTA.NOFNVI A
                                                                                WHERE TRIM(substring(A.NVFLAT,3,2)) = '0$whsesel' 
                                                                                               and TRIM(substr(A.NVFLAT,111,10)) <> ' ' 
                                                                                               and TRIM(substr(NVFLAT,7,5)) * 1 <> 0
                                                                                               and TRIM(substr(A.NVFLAT,12,6)) = 'PCKSTR'
                                                                                                and TRIM(substr(NVFLAT,46,10)) not in ('00002','00003','00006','00007','00009')
                                                                                               and TRIM(substr(A.NVFLAT,111,10)) >= CURDATE() - 5 Days");
    $cartstartdata->execute();
    $cartstarttime_array = $cartstartdata->fetchAll(pdo::FETCH_ASSOC);

    //Tote packing times
    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($cartstarttime_array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }
        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
            $WHSE = $cartstarttime_array[$counter]['WHSE'];
            $BATCH = $cartstarttime_array[$counter]['BATCH'];
            $TSM = $cartstarttime_array[$counter]['TSM'];
            $STARTTIME = $cartstarttime_array[$counter]['STARTTIME'];
            $PACKSTATION = $cartstarttime_array[$counter]['PACKSTATION'];


            $data[] = "($WHSE,$BATCH,$TSM,'$STARTTIME', '$PACKSTATION', '$today')";
            $counter += 1;
        }

        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.allcart_history ($cartstartcols) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount); //end of do loop for tote times
}

//update unscanned cases
$sqlinsert2 = "INSERT IGNORE into printvis.unscannedcases
                            SELECT 
                                casebatches_cart,
                                casebatches_printdate,
                                casebatches_whse,
                                casebatches_build,
                                casebatches_equipment,
                                casebatches_lines,
                                casebatches_time_final
                            FROM
                                printvis.casebatches_time_hist
                                    LEFT JOIN
                                printvis.casebatchstarttime_hist ON starttime_whse = casebatches_whse
                                    AND starttime_batch = casebatches_cart
                                    AND DATE(starttime_starttime) = DATE(casebatches_printdate)
                            WHERE
                                DATE(casebatches_printdate) >= '$unscanneddate' and DATE(casebatches_printdate) <> CURDATE()
                                    AND starttime_whse IS NULL
                                    AND (casebatches_equipment <> 'REACH')";
$queryinsert2 = $conn1->prepare($sqlinsert2);
$queryinsert2->execute();








//insert into tote end history table
//$totedatahistory = $conn1->prepare("INSERT INTO printvis.totehistory(history_lp,history_whse, history_tsm, history_batch, history_bin, history_boxsize,  history_linecount,   history_finaltime, history_dateadded)
//                                                                                SELECT 
//                                                                                    totelp,
//                                                                                    totetimes_whse,
//                                                                                    tote_end_tsm,
//                                                                                    totetimes_cart,
//                                                                                    totetimes_bin,
//                                                                                    totetimes_boxsize,
//                                                                                    totetimes_shipzone,
//                                                                                    totetimes_linecount,
//                                                                                    totetimes_unitcount,
//                                                                                    totetimes_totalPFD,
//                                                                                    (tote_end_endtime) AS tote_end_endtime,
//                                                                                    totetimes_dateadded
//                                                                                FROM
//                                                                                    printvis.totetimes
//                                                                                        LEFT JOIN
//                                                                                    printvis.tote_end ON totetimes_whse = tote_end_whse
//                                                                                        AND totetimes_cart = tote_end_batch
//                                                                                        AND totelp = tote_end_LP
//                                                                                ON DUPLICATE KEY UPDATE history_finaltime=VALUES(history_finaltime)");
//$totedatahistory->execute();

