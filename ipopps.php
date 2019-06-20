<!--Code to update the MySQL table "ipopps"-->
<?php
//Load data from A-System to an array
set_time_limit(99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';

$sqldelete = "TRUNCATE TABLE ipopps";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


#Dallas
$result = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU as IPPKGU, sum(temp.OPP) as TOTOPP FROM A.HSIPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 7 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 7 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.HSIPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.HSIPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 7 and PCPFRA = 'Y') and VCWHSE = 7 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}


#Indy
$result2 = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.HSIPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 2 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 2 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.HSIPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.HSIPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 2 and PCPFRA = 'Y') and VCWHSE = 2 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result2->execute();


foreach ($result2 as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql2 = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, ipPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query2 = $conn1->prepare($sql);
    $query2->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}



#Sparks
$result = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.HSIPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 3 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 3 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.HSIPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.HSIPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 3 and PCPFRA = 'Y') and VCWHSE = 3 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}



#Denver
$result = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.HSIPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 6 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 6 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.HSIPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.HSIPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 6 and PCPFRA = 'Y') and VCWHSE = 6 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}



#Jax
$result = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.HSIPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 9 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 9 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.HSIPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.HSIPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 9 and PCPFRA = 'Y') and VCWHSE = 9 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}


$aseriesconn = null; //close US connection

#NOTL
$result = $aseriesconn_can->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.ARCPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.ARCPCORDTA.NOTWPT, A.ARCPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 11 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.ARCPCORDTA.NPFLSM where LMWHSE = 11 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.ARCPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.ARCPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 11 and PCPFRA = 'Y') and VCWHSE = 11 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}


#VANC
$result = $aseriesconn_can->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.ARCPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.ARCPCORDTA.NOTWPT, A.ARCPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 12 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.ARCPCORDTA.NPFLSM where LMWHSE = 12 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.ARCPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.ARCPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 12 and PCPFRA = 'Y') and VCWHSE = 12 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}



#calgary
$result = $aseriesconn_can->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU, sum(temp.OPP) as TOTOPP FROM A.ARCPCORDTA.NPFMVC INNER JOIN (SELECT PDWHSE, PDWCS#, PDITEM, sum(PDPCKQ), PDPKGU, PCIPKU as IPPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCIPKU) - mod(sum(PDPCKQ), PCIPKU) as OPP  FROM A.ARCPCORDTA.NOTWPT, A.ARCPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 16 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '1000000' and '9999999' and PDPCKQ > 1 and PCIPKU > 1 group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCIPKU) temp on PDWHSE||PDITEM||PDPKGU = VCWHSE||VCITEM||VCPKGU WHERE VCITEM not in (select LMITEM from A.ARCPCORDTA.NPFLSM where LMWHSE = 16 and LMTIER like 'C%' or LMTIER like 'L17' or LMTIER like 'L18' or LMTIER like 'L03' or LMTIER like 'L15') and VCITEM not in (SELECT PCITEM FROM A.ARCPCORDTA.NPFCPC INNER JOIN (SELECT PCITEM as CORITEM, PCPFRC as CORP FROM  A.ARCPCORDTA.NPFCPC WHERE PCWHSE = 0 and PCPFRC = 'P') temp2 on CORITEM = PCITEM WHERE PCWHSE = 16 and PCPFRA = 'Y') and VCWHSE = 16 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06')GROUP BY VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, temp.IPPKGU");
$result->execute();


foreach ($result as $msrow) {


    $VCWHSE = $msrow['VCWHSE'];
    $VCITEM = $msrow['VCITEM'];
    $VCLOC = $msrow['VCLOC#'];
    $VCPKGU = $msrow['VCPKGU'];
    $VCGRD5 = $msrow['VCGRD5'];
    $IPPKGU = $msrow['IPPKGU'];
    $TOTOPP = $msrow['TOTOPP'];

    $sql = "INSERT INTO ipopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, IPPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :IPPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':IPPKGU' => $IPPKGU, ':TOTOPP' => $TOTOPP));
}





$conn1 = null;


