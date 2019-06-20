
<?php

//determine the number of returns per invoice by ship to.  This does not provide detail.  Need to create another file to show indivudal detail.
set_time_limit(99999);


set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';
//include '../globalincludes/usa_asys.php';
//include '../globalincludes/usa_esys.php';
include '../globalfunctions/custdbfunctions.php';

//$sqldelete = "TRUNCATE TABLE custaudit.custreturnsmerge";
//$querydelete = $conn1->prepare($sqldelete);
//$querydelete->execute();

$startdate = '2018-10-15';

$enddate = '2018-01-01';



ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');


do {
    $rowreturn = 500;
    $offset = 0;
    do {
		
		$eolloosesql = $conn1->prepare("SELECT COUNT(*) as RET_COUNT FROM custaudit.custreturns WHERE ORD_RETURNDATE = '$startdate' ");
	$eolloosesql->execute();
	$eolloosearray = $eolloosesql->fetchAll(pdo::FETCH_ASSOC);

	$totalrecords = $eolloosearray[0]['RET_COUNT'];
		
		

        $sqlmerge = "INSERT IGNORE INTO custaudit.complaint_detail 
                                                SELECT 
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
                                                    T2.Whse AS PICK_WHSE,
                                                    Batch_Num,
                                                    Location,
                                                    DateTimeFirstPick AS PICK_DATE,
                                                    ReserveUSerID AS PICK_TSMNUM,
                                                    UserDescription AS PICK_TSM,
                                                    cartstart_tsm AS PACK_TSM,
                                                    cartstart_starttime AS PACK_DATE,
                                                    cartstart_packstation AS PACK_STATION,
                                                    totetimes_packfunction AS PACK_TYPE,
                                                    eolloose_tsm as EOLLOOSE_TSM,
                                                    eolloose_wi, 
                                                    eolloose_ce,
                                                    eolloose_mi,
                                                    eolloose_ai,
                                                    eolloose_pe,
                                                    eolcase_tsm,
                                                    eolcase_ot,
                                                    eolcase_ra
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
                                                        AND DATE(cartstart_starttime) = DATE(dateaddedtotable)
                                                        LEFT JOIN
                                                    printvis.alltote_history t4 ON t4.totelp = t1.LPNUM
                                                        AND t3.cartstart_batch = t4.totetimes_cart
                                                        LEFT JOIN
                                                    printvis.eol_loose t5 ON t5.eolloose_lpnum = t1.LPNUM
                                                        LEFT JOIN
                                                    printvis.eol_case t6 ON t6.eolcase_lpnum = t1.LPNUM
                                                    WHERE DATE(dateaddedtotable)= '$startdate'
                                                    LIMIT $offset, $rowreturn";
        $querymerge = $conn1->prepare($sqlmerge);
        $querymerge->execute();
        $offset += 500;
    } while ($offset <= $totalrecords);


    $startdate = date('Y-m-d', strtotime("-1 day", strtotime($startdate)));
}while ($startdate > $enddate);


