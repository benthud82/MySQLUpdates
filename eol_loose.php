<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include_once '../Off_System_Slotting/connection/connection_details.php';
include_once '../globalincludes/usa_asys.php';
$deletedate = $today = date('Y-m-d', strtotime("-90 days"));
$yesterday = date('Y-m-d', strtotime("-3 days"));
$sqldelete = "DELETE FROM printvis.eol_loose WHERE date(eolloose_datetime) <= '$deletedate' ";
$querydelete2 = $conn1->prepare($sqldelete);
$querydelete2->execute();

$columns = 'eolloose_whs, eolloose_build, eolloose_batch, eolloose_function, eolloose_lpnum, eolloose_tote,  eolloose_boxsize, eolloose_boxlines, eolloose_tsm, eolloose_wi, eolloose_ce, eolloose_mi, eolloose_ai, eolloose_pe, eolloose_err6, eolloose_err7, eolloose_err8, eolloose_item, eolloose_pkgu, eolloose_datetime, eolloose_packid, eolloose_expflag';

$eolloosesql = $aseriesconn->prepare("SELECT trim(substring(NVEFLT, 3, 2)) as WHS,
                                                                                    trim(substring(NVEFLT, 5, 2)) as BUILD, 
                                                                                    trim(substring(NVEFLT, 7, 5)) as BATCH, 
                                                                                    trim(substring(NVEFLT, 12, 6)) as FUNCT, 
                                                                                    trim(substring(NVEFLT, 18, 9)) as LPNUM, 
                                                                                    trim(substring(NVEFLT, 27, 3)) as TOTENUM, 
                                                                                    trim(substring(NVEFLT, 30, 3)) as BOXSIZE, 
                                                                                    trim(substring(NVEFLT, 33, 3)) as BOXLINES, 
                                                                                    trim(substring(NVEFLT, 36, 10)) as TSM, 
                                                                                    trim(substring(NVEFLT, 46, 2)) as WI, 
                                                                                    trim(substring(NVEFLT, 48, 2)) as CE, 
                                                                                    trim(substring(NVEFLT, 50, 2)) as MI, 
                                                                                    trim(substring(NVEFLT, 52, 2)) as AI, 
                                                                                    trim(substring(NVEFLT, 54, 2)) as PE, 
                                                                                    trim(substring(NVEFLT, 56, 2)) as ERR6, 
                                                                                    trim(substring(NVEFLT, 58, 2)) as ERR7, 
                                                                                    trim(substring(NVEFLT, 60, 2)) as ERR8, 
                                                                                    trim(substring(NVEFLT, 62, 7)) as ITEM, 
                                                                                    trim(substring(NVEFLT, 69, 4)) as PKGU, 
                                                                                    trim(substring(NVEFLT, 73, 26)) as DATE_TIME, 
                                                                                    trim(substring(NVEFLT, 99, 10)) as PACKERID,
                                                                                    trim(substring(NVEFLT, 101, 1)) as  EXPFLAG
                                                            FROM HSIPCORDTA.NOFEOL
                                                           WHERE date(trim(substring(NVEFLT, 73, 26))) >= '$yesterday'  ");
$eolloosesql->execute();
$eolloosearray = $eolloosesql->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 9999;
$counter = 0;
$rowcount = count($eolloosearray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WHS = intval($eolloosearray[$counter]['WHS']);
        $BUILD = intval($eolloosearray[$counter]['BUILD']);
        $BATCH = intval($eolloosearray[$counter]['BATCH']);
        $FUNCT = ($eolloosearray[$counter]['FUNCT']);
        $LPNUM = intval($eolloosearray[$counter]['LPNUM']);
        $TOTENUM = intval($eolloosearray[$counter]['TOTENUM']);
        $BOXSIZE = ($eolloosearray[$counter]['BOXSIZE']);
        $BOXLINES = intval($eolloosearray[$counter]['BOXLINES']);
        $TSM = intval($eolloosearray[$counter]['TSM']);
        $WI = ($eolloosearray[$counter]['WI']);
        $CE = ($eolloosearray[$counter]['CE']);
        $MI = ($eolloosearray[$counter]['MI']);
        $AI = ($eolloosearray[$counter]['AI']);
        $PE = ($eolloosearray[$counter]['PE']);
        $ERR6 = ($eolloosearray[$counter]['ERR6']);
        $ERR7 = ($eolloosearray[$counter]['ERR7']);
        $ERR8 = ($eolloosearray[$counter]['ERR8']);
        $ITEM = intval($eolloosearray[$counter]['ITEM']);
        $PKGU = intval($eolloosearray[$counter]['PKGU']);
        $DATE = date('Y-m-d', strtotime(substr($eolloosearray[$counter]['DATE_TIME'], 0, 10)));
        $TIME = date('H:i:s', strtotime(substr($eolloosearray[$counter]['DATE_TIME'], 11, 8)));
        $DATE_TIME = date('Y-m-d H:i:s', strtotime($DATE . ' ' . $TIME));
        $PACKID = intval($eolloosearray[$counter]['PACKERID']);
        $EXPFLAG = ($eolloosearray[$counter]['EXPFLAG']);

        $data[] = "($WHS, $BUILD, $BATCH, '$FUNCT', $LPNUM, $TOTENUM, '$BOXSIZE',  $BOXLINES, $TSM, '$WI', '$CE',  '$MI', '$AI', '$PE', '$ERR6', '$ERR7', '$ERR8', $ITEM, $PKGU, '$DATE_TIME', $PACKID, '$EXPFLAG')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.eol_loose ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop

