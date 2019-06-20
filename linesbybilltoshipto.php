
<?php

//COUNT NUMBER OF LINES BY BILLTO/SHIPTO FOR CURRENT MONTH, CURRENT QUARTER, AND ROLLING 12 MONTHS
set_time_limit(99999);




include '../globalincludes/usa_asys.php';
include '../globalfunctions/custdbfunctions.php';





//Find the first day of current month 1yyddd
$roll_month_start_yyyymmdd = _rollmonthyyyymmdd();  //call current month function to find start for for current month for sql
//Find the first day of current quarter 1yyddd
$roll_quarter_start_yyyymmdd = _rollqtryyyymmdd();  //call current quarter function to find start for for current quarter for sql
//Find first day for rolling 12 month 1yyddd
$rolling_12_start_yyyymmdd = _roll12yyyymmdd();  //call rolling start function to find start date for rolling 12 month sql

$current_month_start_yyddd = _rollmonthyyddd();
$rolling_12_start_yyddd = _rolling12startyyddd();

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

//Large customers
//
//$largecust = $conn1->prepare("SELECT DISTINCT
//    B.BILLTO, A.SALESPLAN
//FROM
//    slotting.customerscores_salesplan A
//join slotting.salesplan B on A.SALESPLAN = B.SALESPLAN
//WHERE
//    A.SALESPLAN not in ('SMSTR' , 'CDH01', 'THS13', ' ')
//        and A.TOTR12SALES > 500000
//");
//$largecust->execute();
//$largecustarray = $largecust->fetchAll(pdo::FETCH_NUM);

//pull in all INVOICE lines for specific bill-to and ship-to
//$lines = $aseriesconn->prepare("SELECT PBAN8, PBSHAN, sum(case when PBSHJD >= $current_month_start_yyddd then 1 else 0 end) as MNTHCNT, sum(case when PBSHJD >= $current_month_start_yyddd then PBBXVS else 0 end) as MNTHDOL, sum(case when PBSHJD >= $current_quarter_start_yyddd then 1 else 0 end) as QTRCNT, sum(case when PBSHJD >= $current_quarter_start_yyddd then PBBXVS else 0 end) as QRTRDOL,  sum(case when PBSHJD >= $rolling_12_start_yyddd then 1 else 0 end) as R12CNT, sum(case when PBSHJD >= $rolling_12_start_yyddd then PBBXVS else 0 end) as ROL12DOL, BILL_TO_NAME, CUST_NAME  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NOTWPS, A.HSIPCORDTA.IM0018 WHERE PBAN8 = BILL_TO and PBSHAN = CUSTOMER AND PBCOMP = PDCOMP and PBWHSE = PDWHSE and PBWCS# = PDWCS# and PBWKNO = PDWKNO and PBBOX# = PDBOX# and PDLOC# <> '*MSDS' AND PDPCKL - ROUND(PDPCKL,0) = 0 and PBAN8 = 1742575 GROUP BY PBAN8, PBSHAN, BILL_TO_NAME, CUST_NAME");
$lines = $aseriesconn->prepare("SELECT IM0018.BILL_TO, IM0018.CUSTOMER, sum(case when TR_DATE >= '" . $roll_month_start_yyyymmdd . "' then 1 else 0 end) as MNTHCNT, sum(case when TR_DATE >= '" . $roll_month_start_yyyymmdd . "' then REP_COST else 0 end) as MNTHCOGS, sum(case when TR_DATE >= '" . $roll_month_start_yyyymmdd . "' then O_EXT_PRC else 0 end) as MNTHSALES, sum(case when TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "' then 1 else 0 end) as QTRCNT, sum(case when TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "' then REP_COST else 0 end) as QRTRCOGS,  sum(case when TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "' then O_EXT_PRC else 0 end) as QRTRSALES,  sum(case when TR_DATE >= '" . $rolling_12_start_yyyymmdd . "' then 1 else 0 end) as R12CNT, sum(case when TR_DATE >= '" . $rolling_12_start_yyyymmdd . "' then REP_COST else 0 end) as ROL12COGS,  sum(case when TR_DATE >= '" . $rolling_12_start_yyyymmdd . "' then O_EXT_PRC else 0 end) as ROL12SALES, BILL_TO_NAME, CUST_NAME FROM A.HSIPCORDTA.IM0011 IM0011 JOIN A.HSIPCORDTA.IM0018 IM0018 ON IM0018.CUSTOMER = IM0011.CUSTOMER  WHERE TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'  GROUP BY IM0018.BILL_TO, IM0018.CUSTOMER, BILL_TO_NAME, CUST_NAME");
$lines->execute();
$linesarray = $lines->fetchAll(pdo::FETCH_NUM);
$aseriesconn = null;


$columns = 'BILLTONUM, SHIPTONUM, CUR_MONTH_LINES, CUR_MONTH_COGS, CUR_MONTH_SALES, CUR_QTR_LINES, CUR_QTR_COGS, CUR_QTR_SALES, ROLL_12_LINES, ROLL_12_COGS, ROLL_12_SALES, BILL_TO_NAME, SHIP_TO_NAME';


$maxrange = 9999;
$counter = 0;
$rowcount = count($linesarray);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';


$sqldelete1 = "TRUNCATE TABLE custaudit.invlinesbyshiptomerge";
$querydelete1 = $conn1->prepare($sqldelete1);
$querydelete1->execute();
do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }
    $data = array();
    $values = array();

    while ($counter <= $maxrange) {


        $BILLTONUM = $linesarray[$counter][0];
        $SHIPTONUM = $linesarray[$counter] [1];
        $CUR_MONTH_LINES = $linesarray [$counter][2];
        $CUR_MONTH_COGS = $linesarray [$counter][3];
        $CUR_MONTH_SALES = $linesarray [$counter][4];
        $CUR_QTR_LINES = $linesarray[$counter][5];
        $CUR_QTR_COGS = $linesarray [$counter][6];
        $CUR_QTR_SALES = $linesarray [$counter][7];
        $ROLL_12_LINES = $linesarray[$counter][8];
        $ROLL_12_COGS = $linesarray [$counter][9];
        $ROLL_12_SALES = $linesarray [$counter][10];
//        $BILL_TO_NAME = mysqli_real_escape_string($link, $linesarray [$counter][11]);
//        $SHIP_TO_NAME = mysqli_real_escape_string($link, $linesarray [$counter][12]);
        
        $BILL_TO_NAME = preg_replace('/[^ \w]+/', '', $linesarray [$counter][11]);
        $SHIP_TO_NAME = preg_replace('/[^ \w]+/', '',  $linesarray [$counter][12]);


        $data[] = "($BILLTONUM,$SHIPTONUM, $CUR_MONTH_LINES, '$CUR_MONTH_COGS', '$CUR_MONTH_SALES', $CUR_QTR_LINES, '$CUR_QTR_COGS', '$CUR_QTR_SALES', $ROLL_12_LINES, '$ROLL_12_COGS', '$ROLL_12_SALES', '$BILL_TO_NAME', '$SHIP_TO_NAME')";
        $counter +=1;
    }

    $values = implode(',', $data);
    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO custaudit.invlinesbyshiptomerge ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount);



$sqldelete = "TRUNCATE TABLE custaudit.invlinesbyshipto";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


$sqlmerge = "INSERT INTO custaudit.invlinesbyshipto (invlinesbyshipto.BILLTONUM, invlinesbyshipto.SHIPTONUM, invlinesbyshipto.CUR_MONTH_LINES, invlinesbyshipto.CUR_MONTH_COGS, invlinesbyshipto.CUR_MONTH_SALES, invlinesbyshipto.CUR_QTR_LINES, invlinesbyshipto.CUR_QTR_COGS, invlinesbyshipto.CUR_QTR_SALES, invlinesbyshipto.ROLL_12_LINES, invlinesbyshipto.ROLL_12_COGS, invlinesbyshipto.ROLL_12_SALES, invlinesbyshipto.BILL_TO_NAME, invlinesbyshipto.SHIP_TO_NAME)
SELECT * FROM custaudit.invlinesbyshiptomerge;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();









