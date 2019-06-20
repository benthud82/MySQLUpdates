<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include_once '../Off_System_Slotting/connection/connection_details.php';
include_once '../globalincludes/usa_asys.php';
$deletedate = $today = date('Y-m-d', strtotime("-90 days"));
$yesterday = date('Y-m-d', strtotime("-3 days"));
$sqldelete = "DELETE FROM printvis.eol_case WHERE date(eolcase_datetime) <= '$deletedate' ";
$querydelete2 = $conn1->prepare($sqldelete);
$querydelete2->execute();

$columns = 'eolcase_whs, eolcase_build, eolcase_batch, eolcase_function, eolcase_eqtype, eolcase_lpnum, eolcase_boxnum, eolcase_tote, eolcase_tsm, eolcase_ot, eolcase_ra, eolcase_err3, eolcase_err4, eolcase_err5, eolcase_err6, eolcase_err7, eolcase_err8, eolcase_item, eolcase_datetime, eolcase_packid, eolcase_expflag';

$eolcasesql = $aseriesconn->prepare("SELECT trim(substring(NVOFLT, 3, 2)) as WHS,
                                                                          trim(substring(NVOFLT, 5, 2)) as BUILD, 
                                                                          trim(substring(NVOFLT, 7, 5)) as BATCH, 
                                                                          trim(substring(NVOFLT, 12, 6)) as FUNCT, 
                                                                          trim(substring(NVOFLT, 18, 3)) as EQTYPE, 
                                                                          trim(substring(NVOFLT, 21, 9)) as LPNUM, 
                                                                          trim(substring(NVOFLT, 30, 5)) as BOXNUM, 
                                                                          trim(substring(NVOFLT, 35, 3)) as TOTENUM, 
                                                                          trim(substring(NVOFLT, 38, 10)) as TSM, 
                                                                          trim(substring(NVOFLT, 48, 2)) as OT, 
                                                                          trim(substring(NVOFLT, 50, 2)) as RA, 
                                                                          trim(substring(NVOFLT, 52, 2)) as ERR3, 
                                                                          trim(substring(NVOFLT, 54, 2)) as ERR4, 
                                                                          trim(substring(NVOFLT, 56, 2)) as ERR5, 
                                                                          trim(substring(NVOFLT, 58, 2)) as ERR6, 
                                                                          trim(substring(NVOFLT, 60, 2)) as ERR7, 
                                                                          trim(substring(NVOFLT, 62, 2)) as ERR8, 
                                                                          trim(substring(NVOFLT, 64, 7)) as ITEM, 
                                                                          trim(substring(NVOFLT, 71, 26)) as DATE_TIME, 
                                                                          trim(substring(NVOFLT, 97, 11)) as PACKID, 
                                                                          trim(substring(NVOFLT, 108, 1)) as EXPFLAG 
                                                            FROM HSIPCORDTA.NOFCOL
                                                            WHERE date(trim(substring(NVOFLT, 71, 26))) >= '$yesterday' ");
$eolcasesql->execute();
$eolcasearray = $eolcasesql->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 9999;
$counter = 0;
$rowcount = count($eolcasearray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WHS = intval($eolcasearray[$counter]['WHS']);
        $BUILD = intval($eolcasearray[$counter]['BUILD']);
        $BATCH = intval($eolcasearray[$counter]['BATCH']);
        $FUNCT = ($eolcasearray[$counter]['FUNCT']);
        $EQTYPE = ($eolcasearray[$counter]['EQTYPE']);
        $LPNUM = intval($eolcasearray[$counter]['LPNUM']);
        $BOXNUM = intval($eolcasearray[$counter]['BOXNUM']);
        $TOTENUM = intval($eolcasearray[$counter]['TOTENUM']);
        $TSM = intval($eolcasearray[$counter]['TSM']);
        $OT = ($eolcasearray[$counter]['OT']);
        $RA = ($eolcasearray[$counter]['RA']);
        $ERR3 = ($eolcasearray[$counter]['ERR3']);
        $ERR4 = ($eolcasearray[$counter]['ERR4']);
        $ERR5 = ($eolcasearray[$counter]['ERR5']);
        $ERR6 = ($eolcasearray[$counter]['ERR6']);
        $ERR7 = ($eolcasearray[$counter]['ERR7']);
        $ERR8 = ($eolcasearray[$counter]['ERR8']);
        $ITEM = intval($eolcasearray[$counter]['ITEM']);
        $DATE = date('Y-m-d', strtotime(substr($eolcasearray[$counter]['DATE_TIME'], 0, 10)));
        $TIME = date('H:i:s', strtotime(substr($eolcasearray[$counter]['DATE_TIME'], 11, 8)));
        $DATE_TIME = date('Y-m-d H:i:s', strtotime($DATE . ' ' . $TIME));
        $PACKID = intval($eolcasearray[$counter]['PACKID']);
        $EXPFLAG = ($eolcasearray[$counter]['EXPFLAG']);

        $data[] = "($WHS, $BUILD, $BATCH, '$FUNCT', '$EQTYPE', $LPNUM, $BOXNUM, $TOTENUM, $TSM, '$OT', '$RA',  '$ERR3', '$ERR4', '$ERR5', '$ERR6', '$ERR7', '$ERR8', $ITEM, '$DATE_TIME', $PACKID, '$EXPFLAG')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.eol_case ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop

