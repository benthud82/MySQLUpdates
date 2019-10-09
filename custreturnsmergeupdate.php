
<?php

//determine the number of returns per invoice by ship to.  This does not provide detail.  Need to create another file to show indivudal detail.
set_time_limit(99999);

class Cls {

    function arraymapfunct($entry) {
        return $entry[0];
    }

}

set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';
include '../globalincludes/usa_asys.php';
include '../globalincludes/usa_esys.php';
include '../globalfunctions/custdbfunctions.php';

$sqldelete = "TRUNCATE TABLE custaudit.custreturnsmerge";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$startdate = date('Y-m-d', strtotime('-40 days'));
$pickpackdate = date('Y-m-d', strtotime('-365 days'));
//$startdate = '2017-02-18';
//convert startdate for sql connection jdate below
$startyear = date('y', strtotime($startdate));
$startday = date('z', strtotime($startdate)) + 1;
if ($startday < 10) {
    $startday = '00' . $startday;
} else if ($startday < 100) {
    $startday = '0' . $startday;
}
$startdatej = intval('1' . $startyear . $startday);
//$startdatej = 114294;

$enddate = date('Y-m-d');

//convert enddate for sql connection jdate below
$endyear = date('y', strtotime($enddate));
$endday = date('z', strtotime($enddate)) + 1;
if ($endday < 10) {
    $endday = '00' . $endday;
} else if ($endday < 100) {
    $endday = '0' . $endday;
}
$enddatej = intval('1' . $endyear . $endday);
//$enddatej = 114307;


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');


$columns = 'BILLTONUM, BILLTONAME, SHIPTONUM, SHIPTONAME, WCSNUM, WONUM, SHIPDATEJ, JDENUM, RINUM, RETURNCODE, ITEMCODE, RETURNDATE, SHIPZONE, TRACERNUM, BOXNUM, BOXSIZE, WHSE, DIVISION, ORD_RETURNDATE, LPNUM, SALESREP, WEIGHT_EST, WEIGHT_ACT, PBRCJD, PBRCHM, PBPTJD, PBPTHM, PBRLJD, PBRLHM ';




for ($xstart = $startdatej; $xstart <= $enddatej; $xstart++) {

//pull in all customer returns for specific bill-to
    $selectclause = '$GDOC as RETURNSKEY, $G$OIN, $G$WON, $GAN8, $GSVDB, CAST($GLITM AS CHAR(20) CCSID 37), CAST($G$RMI AS CHAR(20) CCSID 37)';
    $whereclause = '$G$RMI' . " in('LABL', 'IBNS', 'WQSP', 'WISP', 'EXPR', 'TEMP', 'CRID', 'LITR', 'TDNR', 'WQTY', 'CSNS', 'NRSP', 'CNCL', 'SDAT', 'WIOD', 'IBNO', 'TRPX')" . ' and $GSVDB =' . $xstart . ' and CAST($G$RMI AS CHAR(20) CCSID 37) <> ' . "''";
    $custreturns = $eseriesconn->prepare("SELECT $selectclause FROM E.HSIPDTA71.F5717 WHERE $whereclause");
    $custreturns->execute();
    $custreturnsarray = $custreturns->fetchAll(pdo::FETCH_NUM);

    $values = array();


    foreach ($custreturnsarray as $key => $value) {

        $id = $custreturnsarray[$key][0];  //id to search for, WCS-WO
        $wpspush = $aseriesconn->prepare("SELECT DISTINCT PBDOC AS MAINKEY, IM0018.BILL_TO, IM0018.BILL_TO_NAME, IM0018.CUSTOMER, IM0018.CUST_NAME, PBSHJD, PBDOCO, PBSHPC, PBTRC#, PBBOX#, PBBXSZ, PBWHSE, case when SLS_DVN2 = 'DSL' then 'Dental' when SLS_DVN2 = 'MDL' then 'Medical' when SLS_DVN2 = 'MPH' then 'Medical' when SLS_DVN2 = 'INS' then 'Medical' when SLS_DVN2 = '34B' then 'Medical' when SLS_DVN2 = 'MTX' then 'Medical' else '' end as DIVISION, PBLP9D,  TER_DESC, PBBOXW, PBBXAW, PBRCJD, PBRCHM, PBPTJD, PBPTHM, PBRLJD, PBRLHM FROM A.HSIPCORDTA.NOTWPS NOTWPS, A.HSIPCORDTA.IM0018 IM0018 WHERE IM0018.CUSTOMER = PBSHAN and PBDOC = $id and IM0018.BILL_TO_NAME <> 'Henry Schein France S A'");
        $wpspush->execute();
        $wpspusharray = $wpspush->fetchAll(pdo::FETCH_NUM);
        $keyvalindex = _searchForKey($id, $wpspusharray, 0);  //call function to find matching array in returns info
        if (isset($keyvalindex)) {
            $custreturnsarray[$key][20] = $wpspusharray[$keyvalindex][3];  //if match is found, push the ship to num to end of array
            $custreturnsarray[$key][21] = utf8_encode($wpspusharray[$keyvalindex][4]);  //if match is found, push the ship to name to end of array
            $custreturnsarray[$key][22] = utf8_encode($wpspusharray[$keyvalindex][2]);  //if match is found, push the bill to name to end of array
            $custreturnsarray[$key][23] = $wpspusharray[$keyvalindex][5];  //if match is found, push the shipdate to end of array
            $custreturnsarray[$key][24] = $wpspusharray[$keyvalindex][6];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][25] = $wpspusharray[$keyvalindex][7];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][26] = $wpspusharray[$keyvalindex][8];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][27] = $wpspusharray[$keyvalindex][9];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][28] = $wpspusharray[$keyvalindex][10];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][29] = $wpspusharray[$keyvalindex][11];  //if match is found, push the PBDOCO to end of array
            $custreturnsarray[$key][30] = $wpspusharray[$keyvalindex][12];  //if match is found, push the DIVISION to end of array
            $custreturnsarray[$key][31] = $wpspusharray[$keyvalindex][13];  //if match is found, push the LP to end of array
            $custreturnsarray[$key][32] = $wpspusharray[$keyvalindex][14];  //if match is found, push the TER_DESC to end of array
            $custreturnsarray[$key][33] = $wpspusharray[$keyvalindex][15];  //if match is found, push the box weight to end of array
            $custreturnsarray[$key][34] = $wpspusharray[$keyvalindex][16];  //if match is found, push the actual weight to end of array
            $custreturnsarray[$key][35] = $wpspusharray[$keyvalindex][17];  //if match is found, push the PBRCJD to end of array
            $custreturnsarray[$key][36] = $wpspusharray[$keyvalindex][18];  //if match is found, push the PBRCHM  to end of array
            $custreturnsarray[$key][37] = $wpspusharray[$keyvalindex][19];  //if match is found, push the PBPTJD  to end of array
            $custreturnsarray[$key][38] = $wpspusharray[$keyvalindex][20];  //if match is found, push the PBPTHM  to end of array
            $custreturnsarray[$key][39] = $wpspusharray[$keyvalindex][21];  //if match is found, push the PBRLJD  to end of array
            $custreturnsarray[$key][40] = $wpspusharray[$keyvalindex][22];  //if match is found, push the PBRLHM  to end of array
            $custreturnsarray[$key] = array_values($custreturnsarray[$key]);

            $RINUM = intval($custreturnsarray[$key][1]);
            $WCSNUM = intval($custreturnsarray[$key][0]);
            $WONUM = intval($custreturnsarray[$key][2]);
            $BILLTONUM = intval($custreturnsarray[$key][3]);
            $RETURNDATE = intval($custreturnsarray[$key][4]);
            $ITEMCODE = intval($custreturnsarray[$key][5]);
            $RETURNCODE = $custreturnsarray[$key][6];
            $SHIPTONUM = intval($custreturnsarray[$key][7]);

//            $SHIPTONAME = trim(preg_replace('/[^ \w]+/', '', $custreturnsarray[$key][8]));
            $SHIPTONAME = addslashes($custreturnsarray[$key][8]);
//            $BILLTONAME = trim(preg_replace('/[^ \w]+/', '', $custreturnsarray[$key][9]));
            $BILLTONAME = addslashes($custreturnsarray[$key][9]);
            $SHIPDATEJ = intval($custreturnsarray[$key][10]);
            $JDENUM = intval($custreturnsarray[$key][11]);
            $SHIPZONE = $custreturnsarray[$key][12];
            $TRACERNUM = $custreturnsarray[$key][13];
            $BOXNUM = intval($custreturnsarray[$key][14]);
            $BOXSIZE = $custreturnsarray[$key][15];
            $WHSE = intval($custreturnsarray[$key][16]);
            $DIVISION = $custreturnsarray[$key][17];
            $LPNUM = $custreturnsarray[$key][18];
            $TER_DESC = addslashes($custreturnsarray[$key][19]);
            $PBBOXW = ($custreturnsarray[$key][20]);
            $PBBXAW = ($custreturnsarray[$key][21]);
            $PBRCJD = ($custreturnsarray[$key][22]);
            $PBRCHM = ($custreturnsarray[$key][23]);
            $PBPTJD = ($custreturnsarray[$key][24]);
            $PBPTHM = ($custreturnsarray[$key][25]);
            $PBRLJD = ($custreturnsarray[$key][26]);
            $PBRLHM = ($custreturnsarray[$key][27]);
            $ORD_RETURNDATE = date('Y-m-d', strtotime(_1yydddtogregdate($RETURNDATE)));


            $data[] = "($BILLTONUM, '$BILLTONAME', $SHIPTONUM, '$SHIPTONAME', $WCSNUM, $WONUM, $SHIPDATEJ,$JDENUM, $RINUM, '$RETURNCODE', $ITEMCODE, $RETURNDATE, '$SHIPZONE', '$TRACERNUM', $BOXNUM, '$BOXSIZE', $WHSE, '$DIVISION', '$ORD_RETURNDATE', $LPNUM, '$TER_DESC', '$PBBOXW', '$PBBXAW', $PBRCJD, $PBRCHM, $PBPTJD, $PBPTHM, $PBRLJD, $PBRLHM )";



//            $sql = "INSERT IGNORE INTO custreturnsmerge (BILLTONUM, BILLTONAME, SHIPTONUM, SHIPTONAME, WCSNUM, WONUM, SHIPDATEJ, JDENUM, RINUM, RETURNCODE, ITEMCODE, RETURNDATE, SHIPZONE, TRACERNUM, BOXNUM, BOXSIZE, WHSE, DIVISION, ORD_RETURNDATE) VALUES (:BILLTONUM, :BILLTONAME, :SHIPTONUM, :SHIPTONAME, :WCSNUM, :WONUM, :SHIPDATEJ, :JDENUM, :RINUM, :RETURNCODE, :ITEMCODE, :RETURNDATE, :SHIPZONE, :TRACERNUM, :BOXNUM, :BOXSIZE, :WHSE, :DIVISION, :ORD_RETURNDATE)";
//            $query = $conn1->prepare($sql);
//            $query->execute(array(':BILLTONUM' => $BILLTONUM, ':BILLTONAME' => $BILLTONAME, ':SHIPTONUM' => $SHIPTONUM, ':SHIPTONAME' => $SHIPTONAME, ':WCSNUM' => $WCSNUM, ':WONUM' => $WONUM, ':SHIPDATEJ' => $SHIPDATEJ, ':JDENUM' => $JDENUM, ':RINUM' => $RINUM, ':RETURNCODE' => $RETURNCODE, ':ITEMCODE' => $ITEMCODE, ':RETURNDATE' => $RETURNDATE, ':SHIPZONE' => $SHIPZONE, ':TRACERNUM' => $TRACERNUM, ':BOXNUM' => $BOXNUM, ':BOXSIZE' => $BOXSIZE, ':WHSE' => $WHSE, ':DIVISION' => $DIVISION, ':ORD_RETURNDATE' => $ORD_RETURNDATE));
        } else {
            unset($wpspusharray[$key]);  //if no match, unset key
        }
    }

    //move sql here to add to merge table



    if (!empty($data)) {
        $values = implode(',', $data);
        $sql = "INSERT IGNORE INTO custaudit.custreturnsmerge ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
    }
}


$sqlmerge = "INSERT INTO custaudit.custreturns(BILLTONUM, BILLTONAME, SHIPTONUM, SHIPTONAME, WCSNUM, WONUM, SHIPDATEJ, JDENUM, RINUM, RETURNCODE, ITEMCODE, RETURNDATE, SHIPZONE, TRACERNUM, BOXNUM, BOXSIZE, WHSE, DIVISION, ORD_RETURNDATE, LPNUM, SALESREP, WEIGHT_EST, WEIGHT_ACT, PBRCJD, PBRCHM, PBPTJD, PBPTHM, PBRLJD, PBRLHM)
SELECT custreturnsmerge.BILLTONUM, custreturnsmerge.BILLTONAME, custreturnsmerge.SHIPTONUM, custreturnsmerge.SHIPTONAME, custreturnsmerge.WCSNUM, custreturnsmerge.WONUM, custreturnsmerge.SHIPDATEJ, custreturnsmerge.JDENUM, custreturnsmerge.RINUM, custreturnsmerge.RETURNCODE, custreturnsmerge.ITEMCODE, custreturnsmerge.RETURNDATE, custreturnsmerge.SHIPZONE, custreturnsmerge.TRACERNUM, custreturnsmerge.BOXNUM, custreturnsmerge.BOXSIZE, custreturnsmerge.WHSE, custreturnsmerge.DIVISION, custreturnsmerge.ORD_RETURNDATE, custreturnsmerge.LPNUM, custreturnsmerge.SALESREP, custreturnsmerge.WEIGHT_EST, custreturnsmerge.WEIGHT_ACT, custreturnsmerge.PBRCJD, custreturnsmerge.PBRCHM, custreturnsmerge.PBPTJD, custreturnsmerge.PBPTHM, custreturnsmerge.PBRLJD, custreturnsmerge.PBRLHM FROM custaudit.custreturnsmerge
ON DUPLICATE KEY UPDATE custreturns.BILLTONUM = custreturnsmerge.BILLTONUM, custreturns.BILLTONAME = custreturnsmerge.BILLTONAME, custreturns.SHIPTONUM = custreturnsmerge.SHIPTONUM, custreturns.SHIPTONAME = custreturnsmerge.SHIPTONAME, custreturns.WCSNUM = custreturnsmerge.WCSNUM, custreturns.WONUM = custreturnsmerge.WONUM, custreturns.SHIPDATEJ = custreturnsmerge.SHIPDATEJ, custreturns.JDENUM = custreturnsmerge.JDENUM, custreturns.RINUM = custreturnsmerge.RINUM, custreturns.RETURNCODE = custreturnsmerge.RETURNCODE, custreturns.ITEMCODE = custreturnsmerge.ITEMCODE, custreturns.RETURNDATE = custreturnsmerge.RETURNDATE, custreturns.SHIPZONE = custreturnsmerge.SHIPZONE, custreturns.TRACERNUM = custreturnsmerge.TRACERNUM, custreturns.BOXNUM = custreturnsmerge.BOXNUM, custreturns.BOXSIZE = custreturnsmerge.BOXSIZE, custreturns.WHSE = custreturnsmerge.WHSE, custreturns.DIVISION = custreturnsmerge.DIVISION, custreturns.ORD_RETURNDATE = custreturnsmerge.ORD_RETURNDATE, custreturns.LPNUM = custreturnsmerge.LPNUM, custreturns.SALESREP = custreturnsmerge.SALESREP, custreturns.WEIGHT_EST = custreturnsmerge.WEIGHT_EST, custreturns.WEIGHT_ACT = custreturnsmerge.WEIGHT_ACT,
custreturns.PBRCJD = custreturnsmerge.PBRCJD, custreturns.PBRCHM = custreturnsmerge.PBRCHM, custreturns.PBPTJD = custreturnsmerge.PBPTJD, custreturns.PBPTHM = custreturnsmerge.PBPTHM, custreturns.PBRLJD = custreturnsmerge.PBRLJD, custreturns.PBRLHM = custreturnsmerge.PBRLHM;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();


$sqlmerge2 = " INSERT INTO custaudit.complaint_detail 
SELECT DISTINCT
    BILLTONUM,
    BILLTONAME,
    SHIPTONUM,
    SHIPTONAME,
    WCSNUM,
    WONUM,
    BOXNUM,
    JDENUM,
    LPNUM,
    RETURNCODE,
    T1.ITEMCODE,
    ORD_RETURNDATE,
    SHIPZONE,
    TRACERNUM,
    BOXSIZE,
    CASE
        WHEN T2.Whse IS NULL THEN caselp_whse
        ELSE T2.Whse
    END AS PICK_WHSE,
    CASE
        WHEN Batch_Num IS NULL THEN caselp_batch
        ELSE Batch_Num
    END AS Batch_Num,
    CASE
        WHEN Location IS NULL THEN caselp_loc
        ELSE Location
    END AS Location,
    DateTimeFirstPick AS PICK_DATE,
    ReserveUSerID AS PICK_TSMNUM,
    (SELECT DISTINCT
            tsm_name
        FROM
            printvis.tsm T
        WHERE
            T.tsm_num = ReserveUSerID) AS PICK_TSM,
    cartstart_tsm AS PACK_TSM,
    (SELECT DISTINCT
            tsm_name
        FROM
            printvis.tsm T
        WHERE
            T.tsm_num = cartstart_tsm) AS PACK_TSMNAME,
    cartstart_starttime AS PACK_DATE,
    cartstart_packstation AS PACK_STATION,
    totetimes_packfunction AS PACK_TYPE,
    caselp_tsm AS CASEPICK_TSM,
    (SELECT DISTINCT
            tsm_name
        FROM
            printvis.tsm T
        WHERE
            T.tsm_num = caselp_tsm) AS CASEPICK_TSMNAME,
    caselp_pickdatetime AS CASEPICK_DATETIME,
    eolloose_tsm AS EOLLOOSE_TSM,
    eolloose_wi,
    eolloose_ce,
    eolloose_mi,
    eolloose_ai,
    eolloose_pe,
    eolcase_tsm,
    eolcase_ot,
    eolcase_ra,
    SALESREP,
    WEIGHT_EST,
    WEIGHT_ACT,
    PBRCJD,
    PBRCHM,
    PBPTJD,
    PBPTHM,
    PBRLJD,
    PBRLHM
FROM
    custaudit.custreturns t1
        LEFT JOIN
    printvis.voicepicks_hist t2 ON WCSNUM = WCS_NUM
        AND WORKORDER_NUM = WONUM
        AND BOX_NUM = BOXNUM
        AND t2.ItemCode = t1.ITEMCODE
        LEFT JOIN
    printvis.allcart_history_hist t3 ON t2.Whse = t3.cartstart_whse
        AND Batch_Num = cartstart_batch
        AND DATE(dateaddedtotable) >= DATE_SUB(DATE(DateTimeFirstPick),
        INTERVAL 5 DAY)
        AND DATE(dateaddedtotable) < DATE_ADD(DATE(DateTimeFirstPick),
        INTERVAL 5 DAY)
        LEFT JOIN
    printvis.alltote_history t4 ON t4.totelp = t1.LPNUM
        AND t3.cartstart_batch = t4.totetimes_cart
        LEFT JOIN
    printvis.eol_loose t5 ON t5.eolloose_lpnum = t1.LPNUM
        LEFT JOIN
    printvis.eol_case t6 ON t6.eolcase_lpnum = t1.LPNUM
        LEFT JOIN
    printvis.caselp_hist ON caselp_lp = t1.LPNUM
WHERE
    (DATE(dateaddedtotable) >= '$pickpackdate'
        OR DATE(caselp_pickdatetime) >= '$pickpackdate')
        AND ORD_RETURNDATE >= '$startdate'
                                                    ON DUPLICATE KEY UPDATE complaint_detail.SALESREP = VALUES(complaint_detail.SALESREP), complaint_detail.WEIGHT_EST = VALUES(complaint_detail.WEIGHT_EST), complaint_detail.WEIGHT_ACT = VALUES(complaint_detail.WEIGHT_ACT), complaint_detail.PBRCJD = VALUES(complaint_detail.PBRCJD), complaint_detail.PBRCHM = VALUES(complaint_detail.PBRCHM), 
                                                    complaint_detail.PBPTJD = VALUES(complaint_detail.PBPTJD), complaint_detail.PBPTHM = VALUES(complaint_detail.PBPTHM), complaint_detail.PBRLJD = VALUES(complaint_detail.PBRLJD), complaint_detail.PBRLHM = VALUES(complaint_detail.PBRLHM)";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();