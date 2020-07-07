<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_printvis.php';
include '../globalfunctions/slottingfunctions.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';

$prevbusday = _prevbusday();
$converted_date = intval(_CYYMMDDtodate($prevbusday));

$columns = 'comp_rec_id, comp_rec_whse, comp_rec_item, comp_rec_trans, comp_rec_PO, comp_rec_tsm, comp_rec_datetime, comp_rec_transqty, comp_rec_location, comp_rec_type, comp_rec_expiry, comp_rec_lot, comp_rec_workstation, comp_rec_supplier, comp_rec_dci, comp_rec_casehandle, comp_rec_eachhandle';  

$tierresult = $aseriesconn->prepare("SELECT A.eawhse,
                                            a.EAITEM,
                                            a.EATRN#,
                                            A.EAPONM,
                                            A.EATRNE,
                                            A.EATRND,
                                            A.EATRNT,
                                            a.EATRNQ,
                                            a.EATLOC,
                                            a.EATYPE,
                                            A.EAEXPD,
                                            A.EALOT#,
                                            A.EAUS08,  
                                            B.EDSUPL,
                                            C.EHRECN,  
                                            CASE WHEN D.PCCPKU > 0 then int(a.EATRNQ /  D.PCCPKU) else 0 end as CASEHANDLE,
                                            CASE WHEN D.PCCPKU > 0 then mod(a.EATRNQ ,  D.PCCPKU) else a.EATRNQ end as EACHHANDLE
 
                                            FROM HSIPCORDTA.NPFERA A 
                                            JOIN HSIPCORDTA.NPFERD B on EDLIN# = EALIN# AND EAWHSE = EDWHSE and EAITEM = EDITEM AND EAERCN = EDERCN
                                            JOIN HSIPCORDTA.NPFERH C on EHERCN = EAERCN
                                            JOIN HSIPCORDTA.NPFCPC D on PCITEM = EAITEM

                                            WHERE PCWHSE = 0 AND EATRND = $converted_date AND EASEQ3 = 1 and EAWHSE IN (2,3,6,7,9) and EATYPE <> 'V'
                                                
                                            ORDER BY EATRNE, EATRNT");
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
        $EAWHSE = $tierarray[$counter]['EAWHSE'];
        $EAITEM = $tierarray[$counter]['EAITEM'];
        $EATRN = $tierarray[$counter]['EATRN#'];
        $EAPONM = $tierarray[$counter]['EAPONM'];
        $EATRNE = $tierarray[$counter]['EATRNE'];
        $EATRND = $tierarray[$counter]['EATRND'];
        $EATRNT = $tierarray[$counter]['EATRNT'];
        $EATRNT_padded = str_pad($EATRNT, 6, "0", STR_PAD_LEFT);
        $combinedDT = date('Y-m-d H:i:s', strtotime("$prevbusday $EATRNT_padded"));        
        $EATRNQ = $tierarray[$counter]['EATRNQ'];
        $EATLOC = $tierarray[$counter]['EATLOC'];
        $EATYPE = $tierarray[$counter]['EATYPE'];
        $EAEXPD = $tierarray[$counter]['EAEXPD'];
        $EALOT= $tierarray[$counter]['EALOT#'];
        $EAUS08 = $tierarray[$counter]['EAUS08'];
        $EDSUPL = $tierarray[$counter]['EDSUPL'];
        $EHRECN = $tierarray[$counter]['EHRECN'];
        $CASEHANDLE = $tierarray[$counter]['CASEHANDLE'];
        $EACHHANDLE = $tierarray[$counter]['EACHHANDLE'];
        
       
       
        $data[] = "(0,$EAWHSE,$EAITEM,$EATRN,'$EAPONM', $EATRNE, '$combinedDT', $EATRNQ , '$EATLOC', '$EATYPE', $EAEXPD, '$EALOT', '$EAUS08', '$EDSUPL', $EHRECN, $CASEHANDLE, $EACHHANDLE)";
        $counter += 1;
    }

$values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.completed_receipts ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount); //end of item by whse loop




/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

