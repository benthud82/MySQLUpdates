<!--Code to update the MySQL table "caseopps"-->
<?php
//Load data from A-System to an array
include_once("../globalfunctions/slottingfunctions.php");
set_time_limit(99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';


$sqldelete = "DELETE FROM slotting.caseopps WHERE VCWHSE = 6";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


//Array of opportunity by item by invoice (must roll this up to get total opp) exluding items where PFR is set to P corporately
$dallasoppresult = $aseriesconn->prepare("SELECT PDWHSE, PDWCS#, PDITEM as ITEM, sum(PDPCKQ), PDPKGU, PCCPKU as CSEPKGU, sum(PDPCKQ) -  floor(sum(PDPCKQ) / PCCPKU) - mod(sum(PDPCKQ), PCCPKU) as OPP, PDWHSE||PDITEM||PDPKGU as KEYValue  FROM A.HSIPCORDTA.NOTWPT, A.HSIPCORDTA.NPFCPC WHERE (CASE WHEN (PDSHPD<99999) THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,1) || '-' || substr(PDSHPD,2,2))) WHEN PDSHPD>99999 THEN (date(('20' || RIGHT(PDSHPD,2)) || '-' || substr(PDSHPD,1,2) || '-' || substr(PDSHPD,3,2))) END) >= (CURRENT DATE - 180 Days) and PDWHSE = 6 and PCWHSE = 0 and PDITEM = PCITEM and PDBXSZ <> 'CSE' and (PCCPKU > 0 or PCIPKU > 0) and PDPKGU = 1 and PDITEM between '100000' and '999999' and PDPCKQ > 1 and PCCPKU > 1 and PCITEM not in (SELECT DISTINCT PCITEM as KEYValue FROM A.HSIPCORDTA.NPFCPC WHERE PCWHSE in (0) and PCPFRC in ('P') and PCITEM not in (SELECT DISTINCT PCITEM FROM A.HSIPCORDTA.NPFCPC WHERE PCWHSE in (6) and PCPFRA in ('N'))) group by PDWHSE, PDWCS#, PDITEM, PDPKGU, PCCPKU, PCIPKU HAVING sum(PDPCKQ) >= PCCPKU");
$dallasoppresult->execute();
$dallasoppresultarray = $dallasoppresult->fetchAll(PDO::FETCH_ASSOC);

$output = array();
//Rolled up opportunity by item
foreach ($dallasoppresultarray as $current) {
    // create the array key if it doesn't exist already
    if (!array_key_exists($current['ITEM'], $output)) {
        $output[$current['ITEM']] = 0;
    }

    // add opportunity to total by keyvalue
    $output[$current['ITEM']] += $current['OPP'];
}

//create unique list of opportunity items
$uniquelist = "('" . implode("','", array_keys($output)) . "')";


//Array of all items with package unit of 1 in the loose pick area that do not have a case pick location
$dallasmvcresult = $aseriesconn->prepare("SELECT VCWHSE, VCITEM, VCLOC#, VCPKGU, VCGRD5, PCCPKU FROM A.HSIPCORDTA.NPFMVC, A.HSIPCORDTA.NPFCPC WHERE PCITEM = VCITEM and PCWHSE = 0 and VCWHSE = 6 and VCPKGU = 1 and VCFTIR in ('L02','L04','L06') and VCITEM not in (select LMITEM from A.HSIPCORDTA.NPFLSM where LMWHSE = 6 and (LMTIER in('L17','L18','L03','L15') or LMTIER like 'C%')) and VCITEM in $uniquelist");
$dallasmvcresult->execute();
$dallasmvcresultarray = $dallasmvcresult->fetchAll(PDO::FETCH_NUM);



foreach ($dallasmvcresultarray as $key => $value) {
    $itemkeyval = intval($dallasmvcresultarray[$key][1]);
    //find the total opportunity based on the item from the output array
    $dallasmvcresultarray[$key][6] = $output[$itemkeyval];

    $VCWHSE = $dallasmvcresultarray[$key][0];
    $VCITEM = $dallasmvcresultarray[$key][1];
    $VCLOC = $dallasmvcresultarray[$key][2];
    $VCPKGU = $dallasmvcresultarray[$key][3];
    $VCGRD5 = $dallasmvcresultarray[$key][4];
    $CASEPKGU = $dallasmvcresultarray[$key][5];
    $TOTOPP = $dallasmvcresultarray[$key][6];

    $sql = "INSERT INTO slotting.caseopps (VCWHSE, VCITEM, VCLOC, VCPKGU, VCGRD5, CASEPKGU, TOTOPP) VALUES (:VCWHSE, :VCITEM, :VCLOC, :VCPKGU, :VCGRD5, :CASEPKGU, :TOTOPP)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':VCWHSE' => $VCWHSE, ':VCITEM' => $VCITEM, ':VCLOC' => $VCLOC, ':VCPKGU' => $VCPKGU, ':VCGRD5' => $VCGRD5, ':CASEPKGU' => $CASEPKGU, ':TOTOPP' => $TOTOPP));
}

