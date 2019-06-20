<?php

//must be run after salesplan_update because of merge at end of script


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
//include '../globalincludes/ustxgpslotting_mysql.php';  //conn1
include '../globalincludes/usa_asys.php';  //$aseriesconn
include '../globalfunctions/custdbfunctions.php';
include '../globalincludes/usa_esys.php';

$today = date('Y-m-d');

$currentJdate = _gregdateto1yyddd($today);

$sqldelete = "TRUNCATE TABLE custaudit.salesplan_desc ";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//Pull in Salesplan description and write to a separate table.  Once that table is written, join to slotting.salesplan table and overwrite "NO DESC" with actual description if available
$sql2 = $eseriesconn->prepare("SELECT DISTINCT PJAN8 as BILLTO, trim(CAST(DRKY AS CHAR(20) CCSID 37)) as SALESPLAN, DRDL01 as DESCRIPTION, PJEFTJ, PJEXDJ, trim(CAST(ABAC01 AS CHAR(20) CCSID 37)) as ABAC01 ,trim(CAST(ABAC02 AS CHAR(20) CCSID 37)) as ABAC02 ,trim(CAST(ABAC03 AS CHAR(20) CCSID 37))  as ABAC03 ,trim(CAST(ABAC04 AS CHAR(20) CCSID 37))  as ABAC04,trim(CAST(ABAC05 AS CHAR(20) CCSID 37)) as ABAC05 ,trim(CAST(ABAC06 AS CHAR(20) CCSID 37)) as ABAC06 ,trim(CAST(ABAC07 AS CHAR(20) CCSID 37)) as ABAC07 ,trim(CAST(ABAC08 AS CHAR(20) CCSID 37)) as ABAC08,trim(CAST(ABAC09 AS CHAR(20) CCSID 37)) as ABAC09,trim(CAST(ABAC10 AS CHAR(20) CCSID 37)) as ABAC10 ,trim(CAST(ABAC11 AS CHAR(20) CCSID 37)) as ABAC11 ,trim(CAST(ABAC12 AS CHAR(20) CCSID 37))  as ABAC12,trim(CAST(ABAC13 AS CHAR(20) CCSID 37))  as ABAC13,trim(CAST(ABAC14 AS CHAR(20) CCSID 37)) as ABAC14 ,trim(CAST(ABAC15 AS CHAR(20) CCSID 37))  as ABAC15,trim(CAST(ABAC16 AS CHAR(20) CCSID 37))  as ABAC16,trim(CAST(ABAC17 AS CHAR(20) CCSID 37)) as ABAC17 ,trim(CAST(ABAC18 AS CHAR(20) CCSID 37))  as ABAC18,trim(CAST(ABAC19 AS CHAR(20) CCSID 37)) as ABAC19 ,trim(CAST(ABAC20 AS CHAR(20) CCSID 37)) as ABAC20 ,trim(CAST(ABAC21 AS CHAR(20) CCSID 37)) as ABAC21 ,trim(CAST(ABAC22 AS CHAR(20) CCSID 37)) as ABAC22 ,trim(CAST(ABAC23 AS CHAR(20) CCSID 37)) as ABAC23 ,trim(CAST(ABAC24 AS CHAR(20) CCSID 37)) as ABAC24 ,trim(CAST(ABAC25 AS CHAR(20) CCSID 37)) as ABAC25 ,trim(CAST(ABAC26 AS CHAR(20) CCSID 37)) as ABAC26 ,trim(CAST(ABAC27 AS CHAR(20) CCSID 37)) as ABAC27 ,trim(CAST(ABAC28 AS CHAR(20) CCSID 37)) as ABAC28 ,trim(CAST(ABAC29 AS CHAR(20) CCSID 37)) as ABAC29 ,trim(CAST(ABAC30 AS CHAR(20) CCSID 37))  as ABAC30 FROM E.HSIPCOM71.F0005 JOIN E.HSIPDTA71.F40314 on trim(DRKY) = trim(PJASN)  JOIN E.HSIPDTA71.F0101 on PJAN8 = ABAN8 WHERE DRSY = '40' and DRRT = 'AS' and $currentJdate >= PJEFTJ and $currentJdate <= PJEXDJ");
$sql2->execute();
$sql1array = $sql2->fetchAll(pdo::FETCH_ASSOC);


$columns = 'SPDESC_BILLTO, SPDESC_SALESPLAN, SPDESC_DESC, ABAC01 ,ABAC02 ,ABAC03 ,ABAC04 ,ABAC05 ,ABAC06 ,ABAC07 ,ABAC08 ,ABAC09 ,ABAC10 ,ABAC11 ,ABAC12 ,ABAC13 ,ABAC14 ,ABAC15 ,ABAC16 ,ABAC17 ,ABAC18 ,ABAC19 ,ABAC20 ,ABAC21 ,ABAC22 ,ABAC23 ,ABAC24 ,ABAC25 ,ABAC26 ,ABAC27 ,ABAC28 ,ABAC29 ,ABAC30 ';

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
        $SPDESC_BILLTO = intval($sql1array[$counter]['BILLTO']);
        $SPDESC_SALESPLAN = ($sql1array[$counter]['SALESPLAN']);
        $SPDESC_DESC = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['DESCRIPTION'])));
        $ABAC01 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC01'])));
        $ABAC02 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC02'])));
        $ABAC03 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC03'])));
        $ABAC04 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC04'])));
        $ABAC05 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC05'])));
        $ABAC06 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC06'])));
        $ABAC07 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC07'])));
        $ABAC08 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC08'])));
        $ABAC09 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC09'])));
        $ABAC10 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC10'])));
        $ABAC11 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC11'])));
        $ABAC12 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC12'])));
        $ABAC13 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC13'])));
        $ABAC14 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC14'])));
        $ABAC15 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC15'])));
        $ABAC16 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC16'])));
        $ABAC17 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC17'])));
        $ABAC18 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC18'])));
        $ABAC19 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC19'])));
        $ABAC20 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC20'])));
        $ABAC21 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC21'])));
        $ABAC22 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC22'])));
        $ABAC23 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC23'])));
        $ABAC24 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC24'])));
        $ABAC25 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC25'])));
        $ABAC26 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC26'])));
        $ABAC27 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC27'])));
        $ABAC28 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC28'])));
        $ABAC29 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC29'])));
        $ABAC30 = trim(preg_replace('/[^ \w]+/', '', ($sql1array[$counter]['ABAC30'])));



        $data[] = "($SPDESC_BILLTO, '$SPDESC_SALESPLAN', '$SPDESC_DESC', '$ABAC01', '$ABAC02', '$ABAC03', '$ABAC04', '$ABAC05', '$ABAC06', '$ABAC07', '$ABAC08', '$ABAC09', '$ABAC10', '$ABAC11', '$ABAC12', '$ABAC13', '$ABAC14', '$ABAC15', '$ABAC16', '$ABAC17', '$ABAC18', '$ABAC19', '$ABAC20', '$ABAC21', '$ABAC22', '$ABAC23', '$ABAC24', '$ABAC25', '$ABAC26', '$ABAC27', '$ABAC28', '$ABAC29', '$ABAC30')";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO custaudit.salesplan_desc ($columns) VALUES $values ";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount);


//merge description shipto with this lising.
$sqldelete2 = "TRUNCATE TABLE custaudit.salesplan";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

$sqlmerge = "insert into custaudit.salesplan
SELECT B.SALESPLAN, B.BILLTO, B.SHIPTO, SPDESC_DESC, ABAC01 ,ABAC02 ,ABAC03 ,ABAC04 ,ABAC05 ,ABAC06 ,ABAC07 ,ABAC08 ,ABAC09 ,ABAC10 ,ABAC11 ,ABAC12 ,ABAC13 ,ABAC14 ,ABAC15 ,ABAC16 ,ABAC17 ,ABAC18 ,ABAC19 ,ABAC20 ,ABAC21 ,ABAC22 ,ABAC23 ,ABAC24 ,ABAC25 ,ABAC26 ,ABAC27 ,ABAC28 ,ABAC29 ,ABAC30 FROM custaudit.salesplan_desc A join salesplan_merge B on (A.SPDESC_SALESPLAN) = (B.SALESPLAN) and A.SPDESC_BILLTO = B.BILLTO";
$querymerge2 = $conn1->prepare($sqlmerge);
$querymerge2->execute();
