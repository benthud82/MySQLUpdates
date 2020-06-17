<?php

set_time_limit(99999);
ini_set('memory_limit', '-1');

class Cls {

    function arraymapfunct($entry) {
        return $entry[0];
    }

}


include '../connections/conn_slotting.php';
$tbl_name = "slotting.3moves"; // Table name

include_once '../globalincludes/usa_asys.php';

$excl = $conn1->prepare("SELECT CONCAT(MVITEM,MVTPKG,MVFZNE,MVTZNE,MVTYPE,MVDATE,MVREQT) as EXCLKEY FROM 3moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 13 DAY) AND NOW()");
$excl->execute();
$exclarray = $excl->fetchAll(PDO::FETCH_NUM);

$output = array();

foreach ($exclarray as $current) {
    // create the array key if it doesn't exist already
    if (!array_key_exists($current[0], $output)) {
        $output[$current[0]] = 1;
    }
}


$result = $aseriesconn->prepare("SELECT MVTITM, MVTPKG, MVFZNE, MVTZNE, MVTYPE, date(substr(MVREQD,1,4) || '-' || substr(MVREQD,5,2) || '-' || substr(MVREQD,7,2)) as DATE,  MVREQT, MVTLC# FROM A.HSIPCORDTA.NPFMVE WHERE (MVTPKG <> 0) and MVCNFQ<>0 and (MVDESC like 'COMPLETED%' or MVDESC like 'MAN%') and MVWHSE = 3 and ((CURRENT DATE) - date(substr(MVREQD,1,4) || '-' || substr(MVREQD,5,2) || '-' || substr(MVREQD,7,2))) <= 30 GROUP BY MVTITM, MVTPKG, MVFZNE, MVTZNE, MVTYPE, date(substr(MVREQD,1,4) || '-' || substr(MVREQD,5,2) || '-' || substr(MVREQD,7,2)), MVREQT, MVTLC#");
$result->execute();
$resultarray = $result->fetchAll(PDO::FETCH_NUM);

foreach ($resultarray as $key => $value) {
    $item = $resultarray[$key][0];
    $topkg = intval($resultarray[$key][1]);
    $fromzone = intval($resultarray[$key][2]);
    $tozone = intval($resultarray[$key][3]);
    $type = $resultarray[$key][4];
    $date = $resultarray[$key][5];
    $dayofweek = date('w', strtotime($date));
    $time = intval($resultarray[$key][6]);
    $loc = ($resultarray[$key][7]);
    if($loc >= 'W400000'){
        $build = 2;
    }else{
        $build = 1;
    }

    if ($dayofweek == 6) {
        $date = date('Y-m-d', strtotime($resultarray[$key][5] . ' + 2 day'));
    } elseif ($dayofweek == 0) {
        $date = date('Y-m-d', strtotime($resultarray[$key][5] . ' + 1 day'));
    }

    $testexcl = $item . $topkg . $fromzone . $tozone . $type . $date . $time;
    
    if (!array_key_exists($testexcl, $output)) {

    $sql = "INSERT IGNORE INTO $tbl_name (MVITEM, MVTPKG, MVFZNE, MVTZNE, MVTYPE, MVDATE, MVREQT, MVBUILD) VALUES (:MVITEM, :MVTPKG, :MVFZNE, :MVTZNE, :MVTYPE, :MVDATE, :MVREQT, :MVBUILD)";
    $query = $conn1->prepare($sql);
    $query->execute(array(':MVITEM' => $item, ':MVTPKG' => $topkg, ':MVFZNE' => $fromzone, ':MVTZNE' => $tozone, ':MVTYPE' => $type, ':MVDATE' => $date, ':MVREQT' => $time, ':MVBUILD' => $build));
    }
}



