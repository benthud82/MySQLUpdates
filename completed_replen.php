
<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_printvis.php';
include '../globalfunctions/slottingfunctions.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';

$prevbusday = _prevbusday();
$converted_date = intval(_prevbusdayYYYYMMDD($prevbusday));

$sqldelete = "TRUNCATE TABLE printvis.completed_replen";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'comp_replen_id, comp_replen_whse,comp_replen_trans,comp_replen_item,comp_replen_totqty,comp_replen_caseqty,comp_replen_eachqty,comp_replen_loc,comp_replen_log,comp_replen_datetime,comp_replen_lot,comp_replen_expiry,comp_replen_tsm, comp_replen_equip, comp_replen_path ';

$tierresult = $aseriesconn->prepare("SELECT MVWHSE, 
                                        MVTRN#,
                                        MVTITM,  
                                        MVCNFQ, 
                                        CASE WHEN PCCPKU > 0 then int(MVCNFQ /PCCPKU) else 0 end as CASEHANDLE,  
                                        CASE WHEN PCCPKU > 0 then mod(MVCNFQ , PCCPKU) else (CASE WHEN MVCNFQ > 100 THEN 100 ELSE MVCNFQ END) end as EACHHANDLE,
                                        MVTLC#, 
                                        MVTICK, 
                                        MVCNFD, 
                                        MVCNFT,
                                        IMLCTL, 
                                        case when IMDTYP = 'E' then 1 else 0 end as IMDTYP,
                                        MVCNFE,
                                        MVDEVD
                                        FROM 
                                                HSIPCORDTA.NPFMVE 
                                        JOIN HSIPCORDTA.NPFIMS on IMITEM = MVTITM
                                        JOIN HSIPCORDTA.NPFIMS on IMITEM = MVTITM
                                        JOIN HSIPCORDTA.NPFCPC on MVTITM = PCITEM
                                        WHERE PCWHSE = 0 and MVWHSE in (2,3,6,7,9) and MVCNFD = $converted_date
                                         ORDER BY MVCNFE, MVCNFT");
$tierresult->execute();
$tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 999;
$counter = 0;
$rowcount = count($tierarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $EAWHSE = $tierarray[$counter]['MVWHSE'];
        $EATRN = $tierarray[$counter]['MVTRN#'];
        $EAITEM = $tierarray[$counter]['MVTITM'];
        $EATRNQ = $tierarray[$counter]['MVCNFQ'];
        $CASEHANDLE = $tierarray[$counter]['CASEHANDLE'];
        $EACHHANDLE = $tierarray[$counter]['EACHHANDLE'];
        $EATLOC = $tierarray[$counter]['MVTLC#'];
        $EALOG = $tierarray[$counter]['MVTICK'];
        $EATRND = $tierarray[$counter]['MVCNFD'];
        $EACMPT = $tierarray[$counter]['MVCNFT'];
        $EACMPT_padded = str_pad($EACMPT, 6, "0", STR_PAD_LEFT);
        $combinedDT = date('Y-m-d H:i:s', strtotime("$prevbusday $EACMPT_padded"));
        $EASP12 = $tierarray[$counter]['IMLCTL'];
        $EAEXPD = $tierarray[$counter]['IMDTYP'];
        $EATRNE = $tierarray[$counter]['MVCNFE'];
        $EAEQPT = $tierarray[$counter]['MVDEVD'];
        $EAUS02 = 'PATH';


        $data[] = "(0,$EAWHSE, $EATRN, $EAITEM, $EATRNQ, $CASEHANDLE, $EACHHANDLE, '$EATLOC', $EALOG, '$combinedDT', '$EASP12', $EAEXPD,$EATRNE, '$EAEQPT', '$EAUS02')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.completed_replen ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount); //end of item by whse loop
//CANADA

$tierresult = $aseriesconn_can->prepare("SELECT MVWHSE, 
                                        MVTRN#,
                                        MVTITM,  
                                        MVCNFQ, 
                                        CASE WHEN PCCPKU > 0 then int(MVCNFQ /PCCPKU) else 0 end as CASEHANDLE,  
                                        CASE WHEN PCCPKU > 0 then mod(MVCNFQ , PCCPKU) else (CASE WHEN MVCNFQ > 100 THEN 100 ELSE MVCNFQ END) end as EACHHANDLE,
                                        MVTLC#, 
                                        MVTICK, 
                                        MVCNFD, 
                                        MVCNFT,
                                        IMLCTL, 
                                        case when IMDTYP = 'E' then 1 else 0 end as IMDTYP,
                                        MVCNFE,
                                        MVDEVD
                                        FROM 
                                                ARCPCORDTA.NPFMVE 
                                        JOIN ARCPCORDTA.NPFIMS on IMITEM = MVTITM
                                        JOIN ARCPCORDTA.NPFIMS on IMITEM = MVTITM
                                        JOIN ARCPCORDTA.NPFCPC on MVTITM = PCITEM
                                        WHERE PCWHSE = 0 and MVWHSE in (11,12, 16) and MVCNFD = $converted_date
                                         ORDER BY MVCNFE, MVCNFT");
$tierresult->execute();
$tierarray = $tierresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 999;
$counter = 0;
$rowcount = count($tierarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $EAWHSE = $tierarray[$counter]['MVWHSE'];
        $EATRN = $tierarray[$counter]['MVTRN#'];
        $EAITEM = $tierarray[$counter]['MVTITM'];
        $EATRNQ = $tierarray[$counter]['MVCNFQ'];
        $CASEHANDLE = $tierarray[$counter]['CASEHANDLE'];
        $EACHHANDLE = $tierarray[$counter]['EACHHANDLE'];
        $EATLOC = $tierarray[$counter]['MVTLC#'];
        $EALOG = $tierarray[$counter]['MVTICK'];
        $EATRND = $tierarray[$counter]['MVCNFD'];
        $EACMPT = $tierarray[$counter]['MVCNFT'];
        $EACMPT_padded = str_pad($EACMPT, 6, "0", STR_PAD_LEFT);
        $combinedDT = date('Y-m-d H:i:s', strtotime("$prevbusday $EACMPT_padded"));
        $EASP12 = $tierarray[$counter]['IMLCTL'];
        $EAEXPD = $tierarray[$counter]['IMDTYP'];
        $EATRNE = $tierarray[$counter]['MVCNFE'];
        $EAEQPT = $tierarray[$counter]['MVDEVD'];
        $EAUS02 = 'PATH';


        $data[] = "(0,$EAWHSE, $EATRN, $EAITEM, $EATRNQ, $CASEHANDLE, $EACHHANDLE, '$EATLOC', $EALOG, '$combinedDT', '$EASP12', $EAEXPD,$EATRNE, '$EAEQPT', '$EAUS02')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.completed_replen ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount); //end of item by whse loop




