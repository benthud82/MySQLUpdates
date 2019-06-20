
<?php

//determine the number of returns per invoice by ship to.  This does not provide detail.  Need to create another file to show indivudal detail.
set_time_limit(99999);

include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';  //modelling connection
include '../globalincludes/usa_asys.php';
include '../globalfunctions/custdbfunctions.php';

//Find the first day of current month 1yyddd
$roll_month_start_1yyddd = _rollmonth1yyddd();  //call current month function to find start for for current month for sql
//Find the first day of roll quarter 1yyddd
$roll_quarter_start_1yyddd = _rollquarter1yyddd();  //call roll quarter function to find start for for roll quarter for sql
//Find first day for rolling 12 month 1yyddd
$rolling_12_start_1yyddd = _rolling12startyyddd();  //call rolling start function to find start date for rolling 12 month sql

$sqldelete = "TRUNCATE TABLE custaudit.oscmerge";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


$startdate = date('Y-m-d', strtotime('-8 days'));
//$startdate = '2015-01-30';


//convert startdate for sql connection jdate below
$startyear = date('y', strtotime($startdate));
$startday = date('z', strtotime($startdate)) + 1;
if ($startday < 10) {
    $startday = '00' . $startday;
} else if ($startday < 100) {
    $startday = '0' . $startday;
}
$startdatej = intval('1' . $startyear . $startday);
//$startdatej = intval(115098);



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
//$enddatej = intval(115104);


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

for ($startx = $startdatej; $startx <= $enddatej; $startx++) {

//pull in all customer returns for specific bill-to
    $selectclause = "DISTINCT ORD_NUM, BILL_TO, CUSTOMER, OR_DATE, sum(case when IP_FIL_TYP = '' then 0 else 1 end), count(ITEM), sum(case when IP_FIL_TYP <> 'D' then 0 else 1 end)";
    $whereclause = 'OR_DATE = ' . $startx;
    $groupbyclause = " ORD_NUM, BILL_TO, CUSTOMER, OR_DATE";
    $ocs = $aseriesconn->prepare("SELECT $selectclause FROM A.HSIPCORDTA.IM0011 WHERE $whereclause GROUP BY $groupbyclause");
    $ocs->execute();
    $ocsarray = $ocs->fetchAll(pdo::FETCH_NUM);



    foreach ($ocsarray as $key => $value) {

        $ORDNUM = $ocsarray[$key][0];
        $BILLTONUM = $ocsarray[$key][1];
        $SHIPTONUM = $ocsarray[$key][2];
        $ORDDATE = $ocsarray[$key][3];
        $FILLRATECOUNT = $ocsarray[$key][4];
        $LINECOUNT = $ocsarray[$key][5];
        $DROPSHIPCOUNT = $ocsarray[$key][6];



        $sql = "INSERT IGNORE INTO custaudit.oscmerge (ORDNUM, BILLTONUM, SHIPTONUM, ORDDATE, FILLRATECOUNT, LINECOUNT, DROPSHIPCOUNT) VALUES (:ORDNUM, :BILLTONUM, :SHIPTONUM, :ORDDATE, :FILLRATECOUNT, :LINECOUNT, :DROPSHIPCOUNT)";
        $query = $conn1->prepare($sql);
        $query->execute(array(':ORDNUM' => $ORDNUM, ':BILLTONUM' => $BILLTONUM, ':SHIPTONUM' => $SHIPTONUM, ':ORDDATE' => $ORDDATE, ':FILLRATECOUNT' => $FILLRATECOUNT, ':LINECOUNT' => $LINECOUNT, ':DROPSHIPCOUNT' => $DROPSHIPCOUNT));
    }
}


$sqlmerge = "INSERT INTO custaudit.ordershipcomplete(ordershipcomplete.ORDNUM, ordershipcomplete.BILLTONUM, ordershipcomplete.SHIPTONUM, ordershipcomplete.ORDDATE, ordershipcomplete.FILLRATECOUNT, ordershipcomplete.LINECOUNT, ordershipcomplete.DROPSHIPCOUNT)
SELECT oscmerge.ORDNUM, oscmerge.BILLTONUM, oscmerge.SHIPTONUM, oscmerge.ORDDATE, oscmerge.FILLRATECOUNT, oscmerge.LINECOUNT, oscmerge.DROPSHIPCOUNT FROM custaudit.oscmerge
ON DUPLICATE KEY UPDATE ordershipcomplete.FILLRATECOUNT = oscmerge.FILLRATECOUNT, ordershipcomplete.LINECOUNT = oscmerge.LINECOUNT, ordershipcomplete.DROPSHIPCOUNT = oscmerge.DROPSHIPCOUNT;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();


$sqldelete2 = "TRUNCATE TABLE custaudit.oscbyshipto";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

//sql to update 
$sqlmerge2 = "insert into custaudit.oscbyshipto select 
    ordershipcomplete.BILLTONUM,
    ordershipcomplete.SHIPTONUM,
    sum(case
        when ORDDATE >= $roll_month_start_1yyddd and FILLRATECOUNT = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_MNTH,
    sum(case
        when ORDDATE >= $roll_month_start_1yyddd then 1
        else 0
    end) as TOTAL_ORDERS_MNTH,
    sum(case
        when ORDDATE >= $roll_quarter_start_1yyddd and FILLRATECOUNT = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_QTR,
    sum(case
        when ORDDATE >= $roll_quarter_start_1yyddd then 1
        else 0
    end) as TOTAL_ORDERS_QTR,
    sum(case
        when ORDDATE >= $rolling_12_start_1yyddd and FILLRATECOUNT = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_R12,
    sum(case
        when ORDDATE >= $rolling_12_start_1yyddd then 1
        else 0
    end) as TOTAL_ORDERS_R12,
    sum(case
        when ORDDATE >= $roll_month_start_1yyddd and (FILLRATECOUNT - DROPSHIPCOUNT) = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_MNTH_EXCLDS,
    sum(case
        when ORDDATE >= $roll_quarter_start_1yyddd and (FILLRATECOUNT - DROPSHIPCOUNT) = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_QTR_EXCLDS,
    sum(case
        when ORDDATE >= $rolling_12_start_1yyddd and (FILLRATECOUNT - DROPSHIPCOUNT) = 0 then 1
        else 0
    end) as COMPLETE_ORDERS_R12_EXCLDS
from
    custaudit.ordershipcomplete
GROUP BY ordershipcomplete.BILLTONUM , ordershipcomplete.SHIPTONUM;";
$querymerge2 = $conn1->prepare($sqlmerge2);
$querymerge2->execute();
