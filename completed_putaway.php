
<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_printvis.php';
include '../globalfunctions/slottingfunctions.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';

$prevbusday = _prevbusday();
$converted_date = intval(_CYYMMDDtodate($prevbusday));

$sqldelete = "TRUNCATE TABLE printvis.completed_putaway";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'comp_put_whse,comp_put_trans,comp_put_item,comp_put_totqty,comp_put_caseqty,comp_put_eachqty,comp_put_loc,comp_put_log,comp_put_datetime,comp_put_lot,comp_put_expiry,comp_put_tsm, comp_put_equip ';

$tierresult = $aseriesconn->prepare("SELECT EAWHSE, 
                                                                            a.EATRN#,
                                                                            a.EAITEM,  
                                                                            a.EATRNQ, 
                                                                            CASE WHEN c.PCCPKU > 0 then int(a.EATRNQ /  c.PCCPKU) else 0 end as CASEHANDLE,  
                                                                            CASE WHEN c.PCCPKU > 0 then mod(a.EATRNQ ,  c.PCCPKU) else (CASE WHEN a.EATRNQ > 100 THEN 100 ELSE A.EATRNQ END) end as EACHHANDLE,
                                                                            a.EATLOC, 
                                                                            a.EALOG#, 
                                                                            a.EATRND, 
                                                                            a.EACMPT,
                                                                            EASP12, 
                                                                            EAEXPD,
                                                                            EATRNE,
                                                                            EAEQPT
                                                                            FROM HSIPCORDTA.NPFCPC c, HSIPCORDTA.NPFLOC d, HSIPCORDTA.NPFERA a inner join (SELECT EATRN#, max(EASEQ3) as max_seq FROM HSIPCORDTA.NPFERA GROUP BY EATRN#) b on b.EATRN# = a.EATRN# and a.EASEQ3 = max_seq and EASTAT = 'C'  
                                                                            WHERE EAWHSE in (2,3,6,7,9) and PCITEM = EAITEM and PCWHSE = 0 and LOWHSE = EAWHSE and LOLOC# = EATLOC and EATRND = $converted_date  and EAEQPT in ('CRT', 'TOT') ORDER BY EALOG#, EACMPT");
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
        $EATRN = $tierarray[$counter]['EATRN#'];
        $EAITEM = $tierarray[$counter]['EAITEM'];
        $EATRNQ = $tierarray[$counter]['EATRNQ'];
        $CASEHANDLE = $tierarray[$counter]['CASEHANDLE'];
        $EACHHANDLE = $tierarray[$counter]['EACHHANDLE'];   
        $EATLOC = $tierarray[$counter]['EATLOC'];
        $EALOG = $tierarray[$counter]['EALOG#'];
        $EATRND = $tierarray[$counter]['EATRND'];
        $EACMPT = $tierarray[$counter]['EACMPT'];
       $EACMPT_padded =  str_pad($EACMPT, 6, "0", STR_PAD_LEFT); 
       $combinedDT = date('Y-m-d H:i:s', strtotime("$prevbusday $EACMPT_padded"));
        $EASP12 = $tierarray[$counter]['EASP12'];
        $EAEXPD = $tierarray[$counter]['EAEXPD'];
       $EATRNE = $tierarray[$counter]['EATRNE'];
       $EAEQPT = $tierarray[$counter]['EAEQPT'];


        $data[] = "($EAWHSE, $EATRN, $EAITEM, $EATRNQ, $CASEHANDLE, $EACHHANDLE, '$EATLOC', $EALOG, '$combinedDT', '$EASP12', $EAEXPD,$EATRNE, '$EAEQPT')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.completed_putaway ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
} while ($counter <= $rowcount); //end of item by whse loop


//add elapsed time logic

//write a table high elapsed times

//purge completd_put table