<!--Code to update the MySQL table "2invlinesshipped"-->
<?php
//Load data from A-System to an array
set_time_limit(99999);
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';


$result1 = $aseriesconn->prepare("SELECT PDWHSE as WHSE, PBSHJD as JDATE, count(PDCOMP) as INVLINES FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NOTWPS WHERE PBCOMP = PDCOMP and PBWHSE = PDWHSE and PBWCS# = PDWCS# and PBWKNO = PDWKNO and PBBOX# = PDBOX# and PDLOC# <> '*MSDS' AND PDPCKL - ROUND(PDPCKL,0) = 0 and PDWHSE = 3 and PBSHJD >= 14000 GROUP BY PBSHJD, PDWHSE ORDER BY PBSHJD ASC, PDWHSE ASC");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'INVWHSE, INVDATE, INVLINES';


$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($mindaysarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table

		$WHSE = $mindaysarray[$counter]['WHSE'];
		$JDATE = $mindaysarray[$counter]['JDATE'];
		$INVLINES = $mindaysarray[$counter]['INVLINES'];

		$year = "20" . substr($JDATE, 0, 2);
		$days = substr($JDATE, 2, 3);

		$ts = mktime(0, 0, 0, 1, $days, $year);
		$mydate = "'" . date('Y-m-d', $ts) . "'";
		
        $data[] = "($WHSE, $mydate, $INVLINES)";
        $counter +=1;
}


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO slotting.3invlinesshipped ($columns) VALUES $values ON DUPLICATE KEY UPDATE INVLINES=values(INVLINES)";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);


