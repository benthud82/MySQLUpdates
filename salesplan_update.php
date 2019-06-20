<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn
include '../globalfunctions/custdbfunctions.php';



$sqldelete = "TRUNCATE TABLE custaudit.salesplan_merge ";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$startdate = date('Y-m-d', strtotime('-30 days'));
//convert startdate for sql connection jdate below
$startyear = date('y', strtotime($startdate));
$startday = date('z', strtotime($startdate)) + 1;
if ($startday < 10) {
    $startday = '00' . $startday;
} else if ($startday < 100) {
    $startday = '0' . $startday;
}
$startdatej = intval('1' . $startyear . $startday);

$sql1 = $aseriesconn->prepare("SELECT A.PHASN as SDASN, A.PHAN8 as SDAN8, A.PHSHAN as SDSHAN, max(A.PHRCJD) FROM HSIPCORDTA.NOTWPY A GROUP BY A.PHASN, A.PHAN8, A.PHSHAN HAVING max(A.PHRCJD) = (SELECT max(B.PHRCJD) FROM HSIPCORDTA.NOTWPY B WHERE B.PHAN8 = A.PHAN8 and B.PHSHAN = A.PHSHAN GROUP BY B.PHASN, B.PHAN8, B.PHSHAN ORDER BY max(B.PHRCJD) desc FETCH FIRST ROW ONLY) ");
$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);

$columns = 'SALESPLAN, BILLTO, SHIPTO';

$values = array();

$maxrange = 9999;
$counter = 0;
$rowcount = count($sql1array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $SALESPLAN = $sql1array[$counter]['SDASN'];
        $BILLTO = intval($sql1array[$counter]['SDAN8']);
        $SHIPTO = intval($sql1array[$counter]['SDSHAN']);

        $data[] = "('$SALESPLAN', $BILLTO, $SHIPTO)";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO custaudit.salesplan_merge ($columns) VALUES $values ON DUPLICATE KEY UPDATE SALESPLAN = VALUES(SALESPLAN)";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount);


