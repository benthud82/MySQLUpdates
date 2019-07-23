<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';



if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.mysql_npflsm WHERE Whse = $var_whse";
    $whsefilter = 'LOWHSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.mysql_npflsm";
    $whsefilter = 'LOWHSE in (2,3,6,7,9,11,12,16)';
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'LMWHSE, LMLOC, LMITEM, LMLOCK, LMGRID, LMSHLA, LMFIXA, LMFIXT, LMSTGT, LMHIGH, LMDEEP, LMWIDE, LMVOL9, LMPKGU, LMSLR,LMTIER, LMZONE, LMGRD5, CURMAX, CURMIN, CURTF, LMBAY';

$whsearray = array(2,3,6,7,9);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT LMWHSE, LMLOC#, LMITEM, LMLOCK, LMGRID, LMSHLA, LMFIXA, LMFIXT, LMSTGT, LMHIGH, LMDEEP, LMWIDE, LMVOL9, LMPKGU,  LMSLR#, LMTIER, LMZONE, LMGRD5, LOMAXC, LOMINC, VCCTRF as CURTF, substring(LMLOC#,1,5) as LMBAY FROM HSIPCORDTA.NPFLSM LEFT JOIN HSIPCORDTA.NPFLOC  on LMWHSE = LOWHSE AND LMLOC# = LOLOC#  LEFT JOIN HSIPCORDTA.NPFMVC on LMWHSE = VCWHSE and VCLOC# = LMLOC# WHERE LMWHSE = $whsval  and LMSLR# not in ('1', '2','4')");
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
            $LMWHSE = intval($tierarray[$counter]['LMWHSE']);
            $LMLOC = $tierarray[$counter]['LMLOC#'];
            $LMITEM = intval($tierarray[$counter]['LMITEM']);
            $LMLOCK = ($tierarray[$counter]['LMLOCK']);
            $LMGRID = ($tierarray[$counter]['LMGRID']);
            $LMSHLA = ($tierarray[$counter]['LMSHLA']);
            $LMFIXA = ($tierarray[$counter]['LMFIXA']);
            $LMFIXT = ($tierarray[$counter]['LMFIXT']);
            $LMSTGT = ($tierarray[$counter]['LMSTGT']);
            $LMHIGH = intval($tierarray[$counter]['LMHIGH']);
            $LMDEEP = intval($tierarray[$counter]['LMDEEP']);
            $LMWIDE = intval($tierarray[$counter]['LMWIDE']);
            $LMVOL9 = intval($tierarray[$counter]['LMVOL9']);
            $LMPKGU = intval($tierarray[$counter]['LMPKGU']);
            $LMSLR = ($tierarray[$counter]['LMSLR#']);
            $LMTIER = ($tierarray[$counter]['LMTIER']);
            $LMZONE = ($tierarray[$counter]['LMZONE']);
            $LMGRD5 = ($tierarray[$counter]['LMGRD5']);
            $CURMAX = intval($tierarray[$counter]['LOMAXC']);
            $CURMIN = intval($tierarray[$counter]['LOMINC']);
            $CURTF = intval($tierarray[$counter]['CURTF']);
            if($LMTIER == 'L01'){
                $LMBAY = $LMLOC;
            } else{
            $LMBAY = ($tierarray[$counter]['LMBAY']);
            }

            $data[] = "($LMWHSE, '$LMLOC', $LMITEM, '$LMLOCK', '$LMGRID', '$LMSHLA', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, $LMPKGU, '$LMSLR', '$LMTIER', '$LMZONE', '$LMGRD5', $CURMAX, $CURMIN, $CURTF, '$LMBAY')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.mysql_npflsm ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}




$whsearray = array(11);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn_can->prepare("SELECT LMWHSE, LMLOC#, LMITEM, LMLOCK, LMGRID, LMSHLA, LMFIXA, LMFIXT, LMSTGT, LMHIGH, LMDEEP, LMWIDE, LMVOL9, LMPKGU,  LMSLR#, LMTIER, LMZONE, LMGRD5, LOMAXC, LOMINC, VCCTRF as CURTF, substring(LMLOC#,1,5) as LMBAY  FROM ARCPCORDTA.NPFLSM JOIN ARCPCORDTA.NPFLOC  on LMWHSE = LOWHSE AND LMLOC# = LOLOC#  LEFT JOIN ARCPCORDTA.NPFMVC on LMWHSE = VCWHSE and VCLOC# = LMLOC# WHERE LMSLR# not in ('2','4')");
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
            $LMWHSE = intval($tierarray[$counter]['LMWHSE']);
            $LMLOC = $tierarray[$counter]['LMLOC#'];
            $LMITEM = ($tierarray[$counter]['LMITEM']);
            $LMLOCK = ($tierarray[$counter]['LMLOCK']);
            $LMGRID = ($tierarray[$counter]['LMGRID']);
            $LMSHLA = ($tierarray[$counter]['LMSHLA']);
            $LMFIXA = ($tierarray[$counter]['LMFIXA']);
            $LMFIXT = ($tierarray[$counter]['LMFIXT']);
            $LMSTGT = ($tierarray[$counter]['LMSTGT']);
            $LMHIGH = intval($tierarray[$counter]['LMHIGH']);
            $LMDEEP = intval($tierarray[$counter]['LMDEEP']);
            $LMWIDE = intval($tierarray[$counter]['LMWIDE']);
            $LMVOL9 = intval($tierarray[$counter]['LMVOL9']);
            $LMPKGU = intval($tierarray[$counter]['LMPKGU']);
            $LMSLR = ($tierarray[$counter]['LMSLR#']);
            $LMTIER = ($tierarray[$counter]['LMTIER']);
            $LMZONE = ($tierarray[$counter]['LMZONE']);
            $LMGRD5 = ($tierarray[$counter]['LMGRD5']);
            $CURMAX = ($tierarray[$counter]['LOMAXC']);
            $CURMIN = ($tierarray[$counter]['LOMINC']);
            $CURTF = intval($tierarray[$counter]['CURTF']);
            $LMBAY = ($tierarray[$counter]['LMBAY']);

            $data[] = "($LMWHSE, '$LMLOC', '$LMITEM', '$LMLOCK', '$LMGRID', '$LMSHLA', '$LMFIXA', '$LMFIXT', '$LMSTGT', $LMHIGH, $LMDEEP, $LMWIDE, $LMVOL9, $LMPKGU, '$LMSLR', '$LMTIER', '$LMZONE', '$LMGRD5', $CURMAX, $CURMIN, $CURTF,'$LMBAY')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.mysql_npflsm ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}