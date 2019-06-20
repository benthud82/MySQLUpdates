<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';



if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.system_npfmvc WHERE VCWHSE = $var_whse";
    $whsefilter = 'LOWHSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.system_npfmvc";
    $whsefilter = 'LOWHSE in (2,3,6,7,9,11,12,16)';
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'VCWHSE, VCITEM, VCPKGU, VCDSCL, VCLOC, VCZONE, VCCSLS, VCGRD5, VCVOL9, VCFTIR, VCTTIR, VCSTIR, VCE3OU, VCE3MN, VCE3BM, VCCTRF, VCCHLW, VCNGD5, VCNDMD, VCNDMC';

$whsearray = array(2);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn->prepare("SELECT NPFMVC.VCWHSE, NPFMVC.VCITEM, NPFMVC.VCPKGU, NPFMVC.VCDSCL, NPFMVC.VCLOC#, NPFMVC.VCZONE, NPFMVC.VCCSLS, NPFMVC.VCGRD5, NPFMVC.VCVOL9, NPFMVC.VCFTIR, NPFMVC.VCTTIR, NPFMVC.VCSTIR, NPFMVC.VCE3OU, NPFMVC.VCE3MN, NPFMVC.VCE3BM, NPFMVC.VCCTRF, NPFMVC.VCCHLW, NPFMVC.VCNGD5, NPFMVC.VCNDMD, NPFMVC.VCNDMC
FROM A.HSIPCORDTA.NPFMVC NPFMVC");
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

            $VCWHSE = intval($tierarray[$counter]['VCWHSE']);
            $VCITEM = intval($tierarray[$counter]['VCITEM']);
            $VCPKGU = intval($tierarray[$counter]['VCPKGU']);
            $VCDSCL = ($tierarray[$counter]['VCDSCL']);
            $VCLOC = ($tierarray[$counter]['VCLOC#']);
            $VCZONE = ($tierarray[$counter]['VCZONE']);
            $VCCSLS = ($tierarray[$counter]['VCCSLS']);
            $VCGRD5 = ($tierarray[$counter]['VCGRD5']);
            $VCVOL9 = intval($tierarray[$counter]['VCVOL9']);
            $VCFTIR = ($tierarray[$counter]['VCFTIR']);
            $VCTTIR = ($tierarray[$counter]['VCTTIR']);
            $VCSTIR = ($tierarray[$counter]['VCSTIR']);
            $VCE3OU = intval($tierarray[$counter]['VCE3OU']);
            $VCE3MN = intval($tierarray[$counter]['VCE3MN']);
            $VCE3BM = intval($tierarray[$counter]['VCE3BM']);
            $VCCTRF = intval($tierarray[$counter]['VCCTRF']);
            $VCCHLW = ($tierarray[$counter]['VCCHLW']);
            $VCNGD5 = ($tierarray[$counter]['VCNGD5']);
            $VCNDMD = intval($tierarray[$counter]['VCNDMD']);
            $VCNDMC = ($tierarray[$counter]['VCNDMC']);


            $data[] = "($VCWHSE, $VCITEM, $VCPKGU, '$VCDSCL', '$VCLOC', '$VCZONE', '$VCCSLS', '$VCGRD5', $VCVOL9, '$VCFTIR', '$VCTTIR', '$VCSTIR', $VCE3OU, $VCE3MN, $VCE3BM, '$VCCTRF', '$VCCHLW', '$VCNGD5', $VCNDMD, '$VCNDMC')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.system_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}




$whsearray = array(11);

foreach ($whsearray as $whsval) {

    $tierresult = $aseriesconn_can->prepare("SELECT NPFMVC.VCWHSE, NPFMVC.VCITEM, NPFMVC.VCPKGU, NPFMVC.VCDSCL, NPFMVC.VCLOC#, NPFMVC.VCZONE, NPFMVC.VCCSLS, NPFMVC.VCGRD5, NPFMVC.VCVOL9, NPFMVC.VCFTIR, NPFMVC.VCTTIR, NPFMVC.VCSTIR, NPFMVC.VCE3OU, NPFMVC.VCE3MN, NPFMVC.VCE3BM, NPFMVC.VCCTRF, NPFMVC.VCCHLW, NPFMVC.VCNGD5, NPFMVC.VCNDMD, NPFMVC.VCNDMC
FROM A.ARCPCORDTA.NPFMVC NPFMVC");
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
            $VCWHSE = intval($tierarray[$counter]['VCWHSE']);
            $VCITEM = intval($tierarray[$counter]['VCITEM']);
            $VCPKGU = intval($tierarray[$counter]['VCPKGU']);
            $VCDSCL = ($tierarray[$counter]['VCDSCL']);
            $VCLOC = ($tierarray[$counter]['VCLOC#']);
            $VCZONE = ($tierarray[$counter]['VCZONE']);
            $VCCSLS = ($tierarray[$counter]['VCCSLS']);
            $VCGRD5 = ($tierarray[$counter]['VCGRD5']);
            $VCVOL9 = intval($tierarray[$counter]['VCVOL9']);
            $VCFTIR = ($tierarray[$counter]['VCFTIR']);
            $VCTTIR = ($tierarray[$counter]['VCTTIR']);
            $VCSTIR = ($tierarray[$counter]['VCSTIR']);
            $VCE3OU = intval($tierarray[$counter]['VCE3OU']);
            $VCE3MN = intval($tierarray[$counter]['VCE3MN']);
            $VCE3BM = intval($tierarray[$counter]['VCE3BM']);
            $VCCTRF = intval($tierarray[$counter]['VCCTRF']);
            $VCCHLW = ($tierarray[$counter]['VCCHLW']);
            $VCNGD5 = ($tierarray[$counter]['VCNGD5']);
            $VCNDMD = intval($tierarray[$counter]['VCNDMD']);
            $VCNDMC = ($tierarray[$counter]['VCNDMC']);


            $data[] = "($VCWHSE, $VCITEM, $VCPKGU, '$VCDSCL', '$VCLOC', '$VCZONE', '$VCCSLS', '$VCGRD5', $VCVOL9, '$VCFTIR', '$VCTTIR', '$VCSTIR', $VCE3OU, $VCE3MN, $VCE3BM, '$VCCTRF', '$VCCHLW', '$VCNGD5', $VCNDMD, '$VCNDMC')";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.system_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}