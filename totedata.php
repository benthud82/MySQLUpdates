<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
//include '../sessioninclude.php';
include '../globalincludes/usa_asys.php';
$firstrun = 0;
include "../connections/conn_printvis.php";
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../printvis/functions/functions_totetimes.php';

$whsearray = array(6, 9, 2, 7, 3);




$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}
$yesterdaytime = ('17:29:59');
$printcutoff = date('Y-m-d H:i:s', strtotime("$yesterday $yesterdaytime"));


$deletdate = date('Y-m-d', strtotime("-10 days"));
$deletdate_long = date('Y-m-d', strtotime("-180 days"));


$sqldelete = "DELETE FROM  printvis.packbatchdelete WHERE packbatchdelete_date < '$yesterday'";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$sqldelete = "DELETE FROM  printvis.alltote_history WHERE totetimes_dateadded < '$deletdate_long'";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//truncate open tote temp table
$sqltruncate = "TRUNCATE printvis.pack_opentotes";
$querytruncate = $conn1->prepare($sqltruncate);
$querytruncate->execute();




//columns for totetimes table
$columns = 'totelp, totetimes_packfunction, totetimes_whse, totetimes_cart, totetimes_bin, totetimes_boxsize, totetimes_shipzone, totetimes_linecount, totetimes_unitcount, totetimes_expcheck, totetimes_lotcheck, totetimes_sncheck, totetimes_shortcount, totetimes_tempcount, totetimes_odcount, totetimes_sqcount, totetimes_short, totetimes_scanlp, totetimes_boxprep, totetimes_contentlist, totetimes_unit, totetimes_line, totetimes_expiry, totetimes_lot, totetimes_lotunit, totetimes_sn, totetimes_box24, totetimes_temp, totetimes_od, totetimes_sq, totetimes_truhaz, totetimes_total, totetimes_totalPFD,totetimes_dateadded';

//columns for tote_end table
$columns_toteend = 'tote_end_whse, tote_end_batch, tote_end_LP, tote_end_tote, tote_end_boxsize, tote_end_boxlines, tote_end_tsm, tote_end_audit, tote_end_speed, tote_end_help, tote_end_endtime';

//columns for batch_start table
$columns_batchstart = 'batch_start_whse, batch_start_batch, batch_start_TSM, batch_start_time, batch_start_speedpack, batch_start_packstation';

//columns for openbatch table
$columns_opentotes = 'PBLP9D, PBWHSE, PBCART, PBBIN, PBBXSZ, PBSHPZ, PBPTJD,PBPTHR, PBICEF,PDHACL, PDHGCL, LINE_COUNT, UNIT_COUNT, EXP_CHECK, LOT_CHECK, LOT_UNITS, SERIAL_CHECK, TEMP_CHECK, OD_CHECK, SQ_CHECK, TRUEHAZ, SPEEDPACK, PACKFUNCTION';


$sqldelete3 = "DELETE FROM  printvis.shorts_daily WHERE shorts_date < '$yesterday' ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete4 = "DELETE FROM  printvis.shorts_daily_item WHERE shorts_item_date < '$yesterday' ";
$querydelete4 = $conn1->prepare($sqldelete4);
$querydelete4->execute();


foreach ($whsearray as $whsesel) {
    include '../printvis/timezoneset.php';
    include '../globalincludes/voice_' . $whsesel . '.php';

    $sqldelete = "DELETE FROM  printvis.totetimes_merge WHERE totetimes_whse = $whsesel ";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    $sqldelete2 = "DELETE FROM  printvis.batch_start_merge WHERE batch_start_whse = $whsesel ";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();

    $sqldelete3 = "DELETE FROM  printvis.tote_end_merge WHERE tote_end_whse = $whsesel ";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    //update short time if batch/tote has short on it.
    $shorts = $dbh->prepare("SELECT DISTINCT  Pick.Batch_Num, Tote.ToteLocation
                                                            FROM HenrySchein.dbo.Pick Pick, HenrySchein.dbo.Tote Tote
                                                            WHERE Tote.Tote_ID = Pick.Tote_ID AND ((Pick.Short_Status<>0) AND (Pick.DATECREATED >='$yesterday'))");
    $shorts->execute();
    $shorts_array = $shorts->fetchAll(pdo::FETCH_ASSOC);
    
    //table to track shorts at the item level for today only
    $shorts2 = $dbh->prepare("SELECT Pick.Batch_Num, Tote.ToteLocation, Pick.ItemCode, Pick.Location, (Pick.QtyOrder - Pick.QtyPick) as QtyShort, Tote.WCS_NUM, Tote.WORKORDER_NUM, Tote.BOX_NUM
                                                            FROM HenrySchein.dbo.Pick Pick, HenrySchein.dbo.Tote Tote
                                                            WHERE Tote.Tote_ID = Pick.Tote_ID AND ((Pick.Short_Status<>0) AND (Pick.DATECREATED >='$yesterday'))");
    $shorts2->execute();
    $shorts_array2 = $shorts2->fetchAll(pdo::FETCH_ASSOC);

    //insert shorts into daily shorts table
    $data = array();
    foreach ($shorts_array as $key => $value) {
        $batch = $shorts_array[$key]['Batch_Num'];
        $tote = $shorts_array[$key]['ToteLocation'];
        $data[] = "($whsesel, $batch, $tote, '$today')";
    }
    if (count($data) > 0) {
        $values = implode(',', $data);
        $sql = "INSERT IGNORE  INTO printvis.shorts_daily (shorts_whse, shorts_batch, shorts_tote, shorts_date) VALUES $values";
        $queryinsert = $conn1->prepare($sql);
        $queryinsert->execute();
    }

    //insert shorts into daily shorts table with ITEM
    $data2 = array();
    foreach ($shorts_array2 as $key => $value) {
//        $autoid = 0;
        $batch = $shorts_array2[$key]['Batch_Num'];
        $tote = $shorts_array2[$key]['ToteLocation'];
        $item = $shorts_array2[$key]['ItemCode'];
        $loc = $shorts_array2[$key]['Location'];
        $qty = intval($shorts_array2[$key]['QtyShort']);
        $wcsnum = $shorts_array2[$key]['WCS_NUM'];
        $wcsworkorder = $shorts_array2[$key]['WORKORDER_NUM'];
        $wcsboxnum = $shorts_array2[$key]['BOX_NUM'];
        $data2[] = "($whsesel, $batch, $tote, $item,'$loc', '$today', $qty ,$wcsnum, $wcsworkorder, $wcsboxnum)";
    }
    if (count($data2) > 0) {
        $values = implode(',', $data2);
        $sql = "INSERT IGNORE  INTO printvis.shorts_daily_item (shorts_item_whse, shorts_item_batch, shorts_item_tote, shorts_item_item, shorts_item_loc, shorts_item_date, shorts_item_qty, shorts_item_wcsnumber, shorts_item_workorder, shorts_item_boxnumber) VALUES $values";
        $queryinsert = $conn1->prepare($sql);
        $queryinsert->execute();
    }

    
   $shorts3 = $dbh->prepare("SELECT Pick.Batch_Num, Tote.ToteLocation, Pick.ItemCode, Pick.Location, (Pick.QtyOrder - Pick.QtyPick) as QtyShort, Tote.WCS_NUM, Tote.WORKORDER_NUM, Tote.BOX_NUM,Tote.SHIP_ZONE, Users.UserDescription, Pick.ReserveUSerID
                                                        
														FROM HenrySchein.dbo.Batch Batch JOIN HenrySchein.dbo.Pick Pick on Batch.Batch_ID = Pick.Batch_ID JOIN HenrySchein.dbo.Tote Tote on Tote.Batch_ID = Batch.Batch_ID JOIN JenX.dbo.Users Users on Pick.ReserveUserID = Users.UserName 	
														WHERE Tote.Tote_ID = Pick.Tote_ID AND ((Pick.Short_Status<>0) AND (Pick.DATECREATED >='$yesterday'))");
    $shorts3->execute();
    $shorts_array3 = $shorts3->fetchAll(pdo::FETCH_ASSOC);

$data3 = array();
    foreach ($shorts_array3 as $key => $value) {

        $batch = $shorts_array3[$key]['Batch_Num'];
        $tote = $shorts_array3[$key]['ToteLocation'];
        $item = $shorts_array3[$key]['ItemCode'];
        $loc = $shorts_array3[$key]['Location'];
        $qty = intval($shorts_array3[$key]['QtyShort']);
        $wcsnum = $shorts_array3[$key]['WCS_NUM'];
        $wcsworkorder = $shorts_array3[$key]['WORKORDER_NUM'];
        $wcsboxnum = $shorts_array3[$key]['BOX_NUM'];
		$shipzone = $shorts_array3[$key]['SHIP_ZONE'];
		$userid = $shorts_array3[$key]['UserDescription'];
		$reserveuserid = $shorts_array3[$key]['ReserveUSerID'];
	                                              
		
        $data3[] = "($whsesel, $batch, $tote, $item,'$loc', '$today', $qty ,$wcsnum, $wcsworkorder, $wcsboxnum, '$shipzone', '$userid', $reserveuserid)";
    }
    if (count($data3) > 0) {
        $values = implode(',', $data3);
        $sql = "INSERT IGNORE  INTO printvis.shorts_history (shorts_history_whse, shorts_history_batch, shorts_history_tote, shorts_history_item, shorts_history_loc, shorts_history_date, shorts_history_qty, shorts_history_wcsnumber, shorts_history_workorder, shorts_history_boxnumber, shorts_history_shipzone, shorts_history_username, shorts_history_userid) VALUES $values";
        $queryinsert = $conn1->prepare($sql);
        $queryinsert->execute();
    } 
    
    
    $printhourmin = intval(date('Hi', strtotime('-20 minutes')));  //this is local to the DC because of timezone set.
    $printhourmax = intval(date('Hi', strtotime('-5 minutes')));  //this is local to the DC because of timezone set.
    //$firstrun = intval($_POST['firstrun']);
    if ($firstrun == 1) {
        $printlimiter = ' ';
    } else {
        $printlimiter = "and PBPTHM >= $printhourmin and PBPTHM <= $printhourmax";
    }


// **** Include Global Time Variables ****
    //All totes that are currently in the open box and open detail file.
    $totedata = $aseriesconn->prepare("SELECT PBLP9D,
                                                                                          PBWHSE,
                                                                                          PBCART, 
                                                                                          PBBIN#, 
                                                                                          PBBXSZ, 
                                                                                          PBSHPZ,
                                                                                          PBPTJD,
                                                                                          PBPTHR,
                                                                                          PBICEF,
                                                                                          max(PDHACL) as PDHACL,
                                                                                          max(PDHGCL) as PDHGCL,
                                                                                          count(*) as LINE_COUNT, 
                                                                                          sum(PDPCKQ / PDPKGU) as UNIT_COUNT, 
                                                                                          sum(case when PDLOT# <> ' ' then (PDPCKQ / PDPKGU) else 0 end) as LOT_UNITS,
                                                                                          sum(case when IMDTYP = 'E'  then 1 else 0 end) as EXP_CHECK, 
                                                                                          sum(case when PDLOT# <> ' ' then 1 else 0 end) as LOT_CHECK, 
                                                                                          sum(case when PDSRLF = 'Y' then 1 else 0 end) as SERIAL_CHECK,
                                                                                          sum(case when PDLOC# like 'C%' then 1 when PDLOC# like 'I%' then 1 else 0 end) as TEMP_CHECK,
                                                                                          sum(case when PDHGCL = 'OD' then 1 else 0 end) as OD_CHECK,
                                                                                          sum(case when PDHGCL = 'SQ' then 1 else 0 end) as SQ_CHECK,
                                                                                          sum(CASE WHEN  PDHACL = 'C9' and (PDSHPZ in ('SDA','NXD','NDD') or PDSHPZ like ('SD%')) then 1 WHEN PDHGCL not in ('SQ','OD', ' ') then 1 else 0 end) as TRUEHAZ
                                                                               FROM HSIPCORDTA.NOTWPD JOIN HSIPCORDTA.NPFIMS on IMITEM = PDITEM
                                                                               JOIN HSIPCORDTA.NOTWPB on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX#
                                                                               WHERE PDWHSE = $whsesel  and PDBXSZ <> 'CSE' and PDCART > 0 and PDLOC# not like '%SDS%' and SUBSTR(PBSPCC,1,1) <> 'R' $printlimiter and PDCART <> 10118
                                                                               GROUP BY PBLP9D, PBWHSE, PBCART, PBBIN#, PBBXSZ, PBSHPZ, PBPTJD, PBPTHR, PBICEF");
    $totedata->execute();
    $totedataarray = $totedata->fetchAll(pdo::FETCH_ASSOC);





    //Start times for batches from NOFNVI file
    $cartstartdata = $aseriesconn->prepare("SELECT TRIM(substr(A.NVFLAT,3,2)) as WHSE,
                                                                                              TRIM(substr(A.NVFLAT,7,5)) as BATCH, 
                                                                                              TRIM(substr(A.NVFLAT,46,10)) as TSM, 
                                                                                              TRIM(substr(A.NVFLAT,111,19))  as STARTTIME,      
                                                                                              TRIM(substr(A.NVFLAT,171, 10)) as PACKSTATION                    
                                                                                FROM HSIPCORDTA.NOFNVI A
                                                                                WHERE TRIM(substring(A.NVFLAT,3,2)) = '0$whsesel' 
                                                                                               and TRIM(substr(A.NVFLAT,111,10)) <> ' ' 
                                                                                               and TRIM(substr(A.NVFLAT,111,10)) = CURDATE()
                                                                                               and TRIM(substr(A.NVFLAT,12,6)) = 'PCKSTR' 
                                                                                               and TRIM(substr(A.NVFLAT,111,19)) in (SELECT max(TRIM(substr(B.NVFLAT,111,19))) from HSIPCORDTA.NOFNVI B WHERE TRIM(substr(B.NVFLAT,46,10)) = TRIM(substr(A.NVFLAT,46,10)))");
    $cartstartdata->execute();
    $cartstartdata_array = $cartstartdata->fetchAll(pdo::FETCH_ASSOC);

    //End times for totes from NOFNVI file
    $toteenddata = $aseriesconn->prepare("SELECT TRIM(substr(NVFLAT,3,2)) as WHSE,
                                                                                             TRIM(substr(NVFLAT,7,5)) as BATCH, 
                                                                                             TRIM(substr(NVFLAT,18,9)) as LP, 
                                                                                             TRIM(substr(NVFLAT,27,3)) as TOTE, 
                                                                                             TRIM(substr(NVFLAT,30,3)) as BOXSIZE, 
                                                                                             TRIM(substr(NVFLAT,33,3)) as BOXLINES, 
                                                                                             TRIM(substr(NVFLAT,46,10)) as TSM, 
                                                                                             TRIM(substr(NVFLAT,56,1)) as AUDITPACK, 
                                                                                             CASE WHEN SUBSTR(FLDCTX, 4,1) = 'Y' THEN 'Y' else 'N' end as SPEEDPACK, 
                                                                                             TRIM(substr(NVFLAT,62,1)) as HELPPACK, 
                                                                                             TRIM(substr(NVFLAT,137,19))  as ENDTIME                        
                                                                                FROM HSIPCORDTA.NOFNVI 
                                                                                LEFT JOIN QGPL.HLPVDL on SUBSTR(FLDCDE, 5,5) = TRIM(substr(NVFLAT,46,10))                                                   
                                                                                WHERE TRIM(substring(NVFLAT,3,2)) = '0$whsesel' 
                                                                                              and TRIM(substr(NVFLAT,137,10)) <> ' ' 
                                                                                              and TRIM(substr(NVFLAT,137,10)) = CURDATE()");
    $toteenddata->execute();
    $toteenddata_array = $toteenddata->fetchAll(pdo::FETCH_ASSOC);

    //Loop through totedatarray and determine pack type (audit, speed, haz, or ice).  
    foreach ($totedataarray as $key => $value) {
        $PBLP9D = $totedataarray[$key]['PBLP9D'];
        $PBWHSE = $totedataarray[$key]['PBWHSE'];
        $PBCART = $totedataarray[$key]['PBCART'];
        $PBBIN = $totedataarray[$key]['PBBIN#'];
        $PBBXSZ = $totedataarray[$key]['PBBXSZ'];
        $PBSHPZ = $totedataarray[$key]['PBSHPZ'];
        $PBPTJD = $totedataarray[$key]['PBPTJD'];
        $PBPTHR = $totedataarray[$key]['PBPTHR'];
        $PBICEF = $totedataarray[$key]['PBICEF'];
        $PDHACL = $totedataarray[$key]['PDHACL'];
        $PDHGCL = $totedataarray[$key]['PDHGCL'];
        $LINE_COUNT = $totedataarray[$key]['LINE_COUNT'];
        $UNIT_COUNT = $totedataarray[$key]['UNIT_COUNT'];
        $EXP_CHECK = $totedataarray[$key]['EXP_CHECK'];
        $LOT_CHECK = $totedataarray[$key]['LOT_CHECK'];
        $LOT_UNITS = $totedataarray[$key]['LOT_UNITS'];
        $SERIAL_CHECK = $totedataarray[$key]['SERIAL_CHECK'];
        $TEMP_CHECK = $totedataarray[$key]['TEMP_CHECK'];
        $OD_CHECK = $totedataarray[$key]['OD_CHECK'];
        $SQ_CHECK = $totedataarray[$key]['SQ_CHECK'];
        $TRUEHAZ = $totedataarray[$key]['TRUEHAZ'];

        //Is this a speedpack batch?
        $speedpack_key = array_search($PBCART, array_column($toteenddata_array, 'BATCH')); //Find 'L04' associated key
        if ($speedpack_key !== FALSE) {
            //	if (!$speedpack_key) {
            $speedpack = $toteenddata_array[$speedpack_key]['SPEEDPACK'];
        } else {
            $speedpack = 'N';
        }

        //What type of pack is this?
        $packfunction = _packtype($PBICEF, $TRUEHAZ, $speedpack);
        $openpacktotes[] = "($PBLP9D, $PBWHSE, $PBCART, $PBBIN, '$PBBXSZ', '$PBSHPZ', $PBPTJD, $PBPTHR, '$PBICEF', '$PDHACL', '$PDHACL', $LINE_COUNT, $UNIT_COUNT, $EXP_CHECK, $LOT_CHECK, $LOT_UNITS, $SERIAL_CHECK, $TEMP_CHECK, $OD_CHECK, $SQ_CHECK, $TRUEHAZ, '$speedpack', '$packfunction')";
    }
    //$arraycount = count($openpacktotes);

    if (!empty($openpacktotes)) {
        //Add to table pack_opentotes
        $values5 = implode(',', $openpacktotes);
        $sql5 = "INSERT IGNORE INTO printvis.pack_opentotes ($columns_opentotes) VALUES $values5";
        $query5 = $conn1->prepare($sql5);
        $query5->execute();
    }



    //totes to delete
    $totedelete = $conn1->prepare("SELECT idpackbatchdelete FROM printvis.packbatchdelete;");
    $totedelete->execute();
    $totedelete_array = $totedelete->fetchAll(pdo::FETCH_ASSOC);

    //Pull in tote info and calculate tote packing times
    $totetimes = $conn1->prepare("SELECT 
                                                                            A.*,
                                                                            C.*,
                                                                            CASE
                                                                                WHEN shorts_whse > 0 THEN 1
                                                                                ELSE 0
                                                                            END AS SHORTCHECK
                                                                        FROM
                                                                            printvis.pack_opentotes A
                                                                                JOIN
                                                                            printvis.pm_packtimes C ON PBWHSE = loosepm_whse
                                                                                AND PACKFUNCTION = loosepm_function
                                                                                LEFT JOIN
                                                                            printvis.totetimes B ON A.PBLP9D = B.totelp
                                                                                LEFT JOIN
                                                                            printvis.shorts_daily S ON PBCART = shorts_batch
                                                                                AND PBBIN = shorts_tote
                                                                                AND shorts_whse = PBWHSE
                                                                        WHERE
                                                                            PBWHSE = $whsesel");
    $totetimes->execute();
    $totetimes_array = $totetimes->fetchAll(pdo::FETCH_ASSOC);
    //print_r($totetimes_array);

    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($totetimes_array);


    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
//Is tote already in table?
            $PDCART = intval($totetimes_array[$counter]['PBCART']);
            $PACKFUNCTION = ($totetimes_array[$counter]['PACKFUNCTION']);
            $PDBIN = intval($totetimes_array[$counter]['PBBIN']);

//Is print date greater than yesterday at 5:00 PM?
            $PBPTJD = intval($totetimes_array[$counter]['PBPTJD']);
            $PBPTHR = intval($totetimes_array[$counter]['PBPTHR']);
            $printtime = $PBPTHR . ':00:00';
            $printdate = _1yydddtogregdate($PBPTJD);
            $printdatetime = date('Y-m-d H:i:s', strtotime("$printdate $printtime"));
            if ($printdatetime < $printcutoff) {
                $counter += 1;
                continue;
            }


            $PBLP9D = intval($totetimes_array[$counter]['PBLP9D']);
            $PDWHSE = intval($totetimes_array[$counter]['PBWHSE']);
            $TRUEHAZ = intval($totetimes_array[$counter]['TRUEHAZ']);
            $speedpack = ($totetimes_array[$counter]['SPEEDPACK']);



            $PDBXSZ = $totetimes_array[$counter]['PBBXSZ'];
            $PDSHPZ = $totetimes_array[$counter]['PBSHPZ'];
            $LINE_COUNT = intval($totetimes_array[$counter]['LINE_COUNT']);
            $UNIT_COUNT = intval($totetimes_array[$counter]['UNIT_COUNT']);
            $EXP_CHECK = intval($totetimes_array[$counter]['EXP_CHECK']);
            $LOT_CHECK = intval($totetimes_array[$counter]['LOT_CHECK']);
            $LOT_UNITS = intval($totetimes_array[$counter]['LOT_UNITS']);
            $SERIAL_CHECK = intval($totetimes_array[$counter]['SERIAL_CHECK']);
            $TEMP_CHECK = intval($totetimes_array[$counter]['TEMP_CHECK']);
            $OD_CHECK = intval($totetimes_array[$counter]['OD_CHECK']);
            $SQ_CHECK = intval($totetimes_array[$counter]['SQ_CHECK']);
            $SHORTCHECK = intval($totetimes_array[$counter]['SHORTCHECK']);
            $PDHACL = ($totetimes_array[$counter]['PDHACL']);
            $PDHGCL = ($totetimes_array[$counter]['PDHGCL']);
            $shortcount = 0;  //where can i get short info?

            $loosepm_cartprep = $totetimes_array[$counter]['loosepm_cartprep'];
            $loosepm_short = $totetimes_array[$counter]['loosepm_short'];
            $loosepm_scanlp = $totetimes_array[$counter]['loosepm_scanlp'];
            $loosepm_unit = $totetimes_array[$counter]['loosepm_unit'];
            $loosepm_line = $totetimes_array[$counter]['loosepm_line'];
            $loosepm_contentlist = $totetimes_array[$counter]['loosepm_contentlist'];
            $loosepm_expiry = $totetimes_array[$counter]['loosepm_expiry'];
            $loosepm_lot = $totetimes_array[$counter]['loosepm_lot'];
            $loosepm_lotunit = $totetimes_array[$counter]['loosepm_lotunit'];
            $loosepm_sn = $totetimes_array[$counter]['loosepm_sn'];
            $loosepm_box24 = $totetimes_array[$counter]['loosepm_box24'];
            $loosepm_cartcomplete = $totetimes_array[$counter]['loosepm_cartcomplete'];
            $loosepm_temp = $totetimes_array[$counter]['loosepm_temp'];
            $loosepm_od = $totetimes_array[$counter]['loosepm_od'];
            $loosepm_sq = $totetimes_array[$counter]['loosepm_sq'];
            $loosepm_personal = $totetimes_array[$counter]['loosepm_personal'];
            $loosepm_fatigue = $totetimes_array[$counter]['loosepm_fatigue'];
            $loosepm_delay = $totetimes_array[$counter]['loosepm_delay'];
            $loosepm_boxheader = $totetimes_array[$counter]['loosepm_boxheader'];
            $loosepm_boxE2 = $totetimes_array[$counter]['loosepm_boxE2'];
            $loosepm_boxG3 = $totetimes_array[$counter]['loosepm_boxG3'];
            $loosepm_box02 = $totetimes_array[$counter]['loosepm_box02'];
            $loosepm_box04 = $totetimes_array[$counter]['loosepm_box04'];
            $loosepm_box05 = $totetimes_array[$counter]['loosepm_box05'];
            $loosepm_box09 = $totetimes_array[$counter]['loosepm_box09'];
            $loosepm_box12 = $totetimes_array[$counter]['loosepm_box12'];




            $loosepm_box13 = $totetimes_array[$counter]['loosepm_box13'];
            $loosepm_truehaz = $totetimes_array[$counter]['loosepm_truehaz'];
            $loosepm_shcode = $totetimes_array[$counter]['loosepm_shcode'];
            $loosepm_tempindicator = $totetimes_array[$counter]['loosepm_tempindicator'];


            $standard_shorts = $SHORTCHECK * $loosepm_short;
            $standard_scanLP = $loosepm_scanlp;
            //switch function to calculate box prep time
            $standard_boxprep = 0;
            switch ($PDBXSZ) {
                case '#E2':
                    $standard_boxprep = $loosepm_boxE2;
                    break;
                case '#G3':
                    $standard_boxprep = $loosepm_boxG3;
                    break;
                case '# 2':
                    $standard_boxprep = $loosepm_box02;
                    break;
                case '# 4':
                    $standard_boxprep = $loosepm_box04;
                    break;
                case '# 5':
                    $standard_boxprep = $loosepm_box05;
                    break;
                case '# 9':
                    $standard_boxprep = $loosepm_box09;
                    break;
                case '#12':
                    $standard_boxprep = $loosepm_box12;
                    break;
                default:
                    $standard_boxprep = 0;
                    break;
            }


            $standard_contentlist = $LINE_COUNT * $loosepm_contentlist;
            //Need to remove lot units from standard unit time
            $standard_unit = ($UNIT_COUNT - $LOT_UNITS) * $loosepm_unit;  //subtract out lot units from standard unit time
            $standard_line = ($LINE_COUNT - $LOT_CHECK) * $loosepm_line;  //subtract out lot lines from standard line time
            $standard_expiry = $EXP_CHECK * $loosepm_expiry;
            $standard_lot = $LOT_CHECK * $loosepm_lot;
            //Need to add standard lot unit time
            $standard_lot_unit = $loosepm_lotunit * $LOT_UNITS;
            $standard_sn = $SERIAL_CHECK * $loosepm_sn;
            $totalboxtime = $loosepm_boxheader + $standard_boxprep;

            if ($OD_CHECK > 0) {
                $standard_od = $loosepm_od;
            } else {
                $standard_od = '0';
            }


            if ($SQ_CHECK > 0) {
                $standard_sq = $loosepm_sq;
            } else {
                $standard_sq = '0';
            }


//took out $loosepm_box13, not on NY server yet
            $standard_totaltime = $standard_shorts + $loosepm_scanlp + $totalboxtime + $standard_contentlist + $standard_unit + $standard_line + $standard_expiry + $standard_lot + $standard_lot_unit + $standard_sn + $loosepm_box24 + $loosepm_temp + $standard_od + $standard_sq + $loosepm_shcode + $loosepm_truehaz + $loosepm_tempindicator;
            $standard_timewithPFD = $standard_totaltime * (1 + $loosepm_personal + $loosepm_fatigue + $loosepm_delay); //is this right?  at tote or batch level?  does it matter?
            if ($standard_timewithPFD > 999) {
                $standard_timewithPFD = 999;
            }

            $data[] = "($PBLP9D, '$PACKFUNCTION', $PDWHSE, $PDCART, $PDBIN, '$PDBXSZ', '$PDSHPZ', $LINE_COUNT, $UNIT_COUNT, $EXP_CHECK, $LOT_CHECK, $SERIAL_CHECK, $SHORTCHECK, $TEMP_CHECK, $OD_CHECK, $SQ_CHECK, '$standard_shorts', '$loosepm_scanlp', '$totalboxtime', '$standard_contentlist', '$standard_unit', '$standard_line', '$standard_expiry', '$standard_lot', '$standard_lot_unit', '$standard_sn', '$loosepm_box24', '$loosepm_temp','$standard_od','$standard_sq', '$loosepm_truehaz', '$standard_totaltime', '$standard_timewithPFD', '$printdatetime')";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT INTO printvis.totetimes_merge ($columns) VALUES $values ON DUPLICATE KEY UPDATE 
                                    totetimes_shortcount=VALUES(totetimes_shortcount), 
                                    totetimes_packfunction=VALUES(totetimes_packfunction), 
                                    totetimes_short=VALUES(totetimes_short),
                                    totetimes_scanlp=VALUES(totetimes_scanlp),
                                    totetimes_boxprep=VALUES(totetimes_boxprep),
                                    totetimes_contentlist=VALUES(totetimes_contentlist),
                                    totetimes_unit=VALUES(totetimes_unit),
                                    totetimes_line=VALUES(totetimes_line),
                                    totetimes_expiry=VALUES(totetimes_expiry),
                                    totetimes_lot=VALUES(totetimes_lot),
                                    totetimes_lotunit=VALUES(totetimes_lotunit),
                                    totetimes_sn=VALUES(totetimes_sn),
                                    totetimes_box24=VALUES(totetimes_box24),
                                    totetimes_temp=VALUES(totetimes_temp),
                                    totetimes_od=VALUES(totetimes_od),
                                    totetimes_sq=VALUES(totetimes_sq),
                                    totetimes_truhaz=VALUES(totetimes_truhaz),
                                    totetimes_total=VALUES(totetimes_total),
                                    totetimes_totalPFD=VALUES(totetimes_totalPFD)";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount); //end of do loop for tote times






    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($cartstartdata_array);
    //Cart batch start times for today
    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
            $batch_start_whse = intval($cartstartdata_array[$counter]['WHSE']);
            $batch_start_batch = intval($cartstartdata_array[$counter]['BATCH']);
            $batch_start_TSM = intval($cartstartdata_array[$counter]['TSM']);


            //Is batch in delete array?
            $delete_key = array_search($batch_start_batch . $batch_start_TSM, array_column($totedelete_array, 'idpackbatchdelete')); //Find 'L04' associated key
            if ($delete_key !== FALSE) {
                $counter += 1;  //add one to counter and continue
                continue;
            }


            $batch_start_time = ($cartstartdata_array[$counter]['STARTTIME']);
            $batch_start_packstation = ($cartstartdata_array[$counter]['PACKSTATION']);
            //Is this a speedpack batch?
//            $speedpack_key = array_search($batch_start_batch, array_column($toteenddata_array, 'BATCH')); //Find 'L04' associated key
//            if ($speedpack_key !== FALSE) {
            $speedpack = $toteenddata_array[$counter]['SPEEDPACK'];
//            } else {
//                $speedpack = 'N';
//            }

            $data[] = "($batch_start_whse, $batch_start_batch, $batch_start_TSM, '$batch_start_time', '$speedpack', '$batch_start_packstation')";
            $counter += 1;
        }
        $values = implode(',', $data);
        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.batch_start_merge ($columns_batchstart) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount); //end of do loop for cart batch start times

    $values = array();
    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($toteenddata_array);
    //Tote end times for today
    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
            $tote_end_whse = intval($toteenddata_array[$counter]['WHSE']);
            $tote_end_batch = intval($toteenddata_array[$counter]['BATCH']);
            $tote_end_LP = intval($toteenddata_array[$counter]['LP']);
            $tote_end_tote = intval($toteenddata_array[$counter]['TOTE']);
            $tote_end_boxsize = ($toteenddata_array[$counter]['BOXSIZE']);
            $tote_end_boxlines = intval($toteenddata_array[$counter]['BOXLINES']);
            $tote_end_tsm = intval($toteenddata_array[$counter]['TSM']);
            $tote_end_audit = ($toteenddata_array[$counter]['AUDITPACK']);
            $tote_end_speed = ($toteenddata_array[$counter]['SPEEDPACK']);
            $tote_end_help = ($toteenddata_array[$counter]['HELPPACK']);
            $tote_end_endtime = ($toteenddata_array[$counter]['ENDTIME']);


            $data[] = "($tote_end_whse, $tote_end_batch, $tote_end_LP, $tote_end_tote, '$tote_end_boxsize', $tote_end_boxlines, $tote_end_tsm, '$tote_end_audit', '$tote_end_speed', '$tote_end_help', '$tote_end_endtime')";
            $counter += 1;
        }
        $values = implode(',', $data);
        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.tote_end_merge ($columns_toteend) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount); //end of do loop for tote end times

    $sqldelete = "DELETE FROM printvis.totetimes WHERE (totetimes_dateadded) < '$printcutoff' and totetimes_whse = $whsesel ";  //delete active totetimes
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

//move data from merge table to active table
    $sqlmerge = "INSERT INTO printvis.totetimes  (SELECT * FROM printvis.totetimes_merge WHERE totetimes_whse = $whsesel) 
                                    ON DUPLICATE KEY UPDATE 
                                    totetimes_shortcount=VALUES(totetimes_shortcount), 
                                    totetimes_packfunction=VALUES(totetimes_packfunction), 
                                    totetimes_short=VALUES(totetimes_short),
                                    totetimes_scanlp=VALUES(totetimes_scanlp),
                                    totetimes_boxprep=VALUES(totetimes_boxprep),
                                    totetimes_contentlist=VALUES(totetimes_contentlist),
                                    totetimes_unit=VALUES(totetimes_unit),
                                    totetimes_line=VALUES(totetimes_line),
                                    totetimes_expiry=VALUES(totetimes_expiry),
                                    totetimes_lot=VALUES(totetimes_lot),
                                    totetimes_lotunit=VALUES(totetimes_lotunit),
                                    totetimes_sn=VALUES(totetimes_sn),
                                    totetimes_box24=VALUES(totetimes_box24),
                                    totetimes_temp=VALUES(totetimes_temp),
                                    totetimes_od=VALUES(totetimes_od),
                                    totetimes_sq=VALUES(totetimes_sq),
                                    totetimes_truhaz=VALUES(totetimes_truhaz),
                                    totetimes_total=VALUES(totetimes_total),
                                    totetimes_totalPFD=VALUES(totetimes_totalPFD)";
    $querymerge = $conn1->prepare($sqlmerge);
    $querymerge->execute();

    $sqldelete2 = "DELETE FROM printvis.batch_start WHERE batch_start_whse = $whsesel ";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();

//move data from merge table to active table
    $sqlmerge2 = "INSERT INTO printvis.batch_start  (SELECT DISTINCT * FROM printvis.batch_start_merge WHERE batch_start_whse = $whsesel)
                                    ON DUPLICATE KEY UPDATE 
                                    batch_start_speedpack=VALUES(batch_start_speedpack)";
    $querymerge2 = $conn1->prepare($sqlmerge2);
    $querymerge2->execute();

    $sqldelete3 = "DELETE FROM   printvis.tote_end WHERE tote_end_whse = $whsesel  and (tote_end_endtime) < '$printcutoff'";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

//move data from merge table to active table
    $sqlmerge3 = "INSERT INTO printvis.tote_end  (SELECT * FROM printvis.tote_end_merge WHERE tote_end_whse = $whsesel )
                                        ON DUPLICATE KEY UPDATE 
                                        tote_end_boxsize=VALUES(tote_end_boxsize), 
                                        tote_end_boxlines=VALUES(tote_end_boxlines), 
                                        tote_end_tsm=VALUES(tote_end_tsm), 
                                        tote_end_audit=VALUES(tote_end_audit), 
                                        tote_end_speed=VALUES(tote_end_speed), 
                                        tote_end_help=VALUES(tote_end_help), 
                                        tote_end_endtime=VALUES(tote_end_endtime)";
    $querymerge3 = $conn1->prepare($sqlmerge3);
    $querymerge3->execute();



    $totedatahistory = $conn1->prepare("INSERT  INTO printvis.alltote_history(totelp,totetimes_packfunction,totetimes_whse,totetimes_cart,totetimes_bin,totetimes_boxsize,totetimes_shipzone,totetimes_linecount,totetimes_unitcount,totetimes_expcheck,totetimes_lotcheck,totetimes_sncheck,totetimes_shortcount,totetimes_tempcount,totetimes_odcount,totetimes_sqcount,totetimes_short,totetimes_scanlp,totetimes_boxprep,totetimes_contentlist,totetimes_unit,totetimes_line,totetimes_expiry,totetimes_lot,totetimes_lotunit, totetimes_sn,totetimes_box24,totetimes_temp,totetimes_od,totetimes_sq,totetimes_truhaz, totetimes_total,totetimes_totalPFD,totetimes_dateadded)
                                                                                SELECT 
                                                                                    *
                                                                                FROM
                                                                                    printvis.totetimes
                                                                                  WHERE totetimes_whse = $whsesel
                                                                            on duplicate key update 
                                                                            totetimes_shortcount=VALUES(totetimes_shortcount),
                                                                            totetimes_packfunction=VALUES(totetimes_packfunction), 
                                                                            totetimes_short=VALUES(totetimes_short),
                                                                            totetimes_scanlp=VALUES(totetimes_scanlp),
                                                                            totetimes_boxprep=VALUES(totetimes_boxprep),
                                                                            totetimes_contentlist=VALUES(totetimes_contentlist),
                                                                            totetimes_unit=VALUES(totetimes_unit),
                                                                            totetimes_line=VALUES(totetimes_line),
                                                                            totetimes_expiry=VALUES(totetimes_expiry),
                                                                            totetimes_lot=VALUES(totetimes_lot),
                                                                            totetimes_lotunit=VALUES(totetimes_lotunit),
                                                                            totetimes_sn=VALUES(totetimes_sn),
                                                                            totetimes_box24=VALUES(totetimes_box24),
                                                                            totetimes_temp=VALUES(totetimes_temp),
                                                                            totetimes_od=VALUES(totetimes_od),
                                                                            totetimes_sq=VALUES(totetimes_sq),
                                                                            totetimes_truhaz=VALUES(totetimes_truhaz),
                                                                            totetimes_total=VALUES(totetimes_total),
                                                                            totetimes_totalPFD=VALUES(totetimes_totalPFD)");
    $totedatahistory->execute();



    //Must accout for non true haz totes packed on a true haz cart..  Overwrite the packfunction to 'PACKHAZ'
    $hazcarts = $conn1->prepare("SELECT 
                                                                PBCART
                                                            FROM
                                                                printvis.pack_opentotes
                                                            WHERE
                                                                PBWHSE = $whsesel
                                                            GROUP BY PBCART
                                                            HAVING MAX(TRUEHAZ) >= 1;");
    $hazcarts->execute();
    $hazcarts_array = $hazcarts->fetchAll(pdo::FETCH_ASSOC);

    foreach ($hazcarts_array as $key => $value) {
        $cartnum = $hazcarts_array[$key]['PBCART'];

        $sqldelete = "UPDATE printvis.totetimes SET totetimes_packfunction = 'PACKHAZ' WHERE totetimes_cart = '$cartnum'";
        $querydelete = $conn1->prepare($sqldelete);
        $querydelete->execute();
    }



//DELETE records older than a week from taskpred table
    $sqldelete = "DELETE FROM printvis.taskpred WHERE DATE(taskpred_updatetime) <= '$deletdate' ";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();
    //Populate IS file taskpred
    $taskpredcolumns = 'taskpred_id, taskpred_whse, taskpred_function, taskpred_type, taskpred_mintime, taskpred_maxtime, taskpred_updatetime';
    $taskpredfile = $conn1->prepare("INSERT INTO printvis.taskpred($taskpredcolumns)
                                                                                SELECT 
                                                                                    LPAD(totetimes_cart, 5, '0') AS BATCH,
                                                                                    totetimes_whse,
                                                                                    'PCK',
                                                                                    totetimes_packfunction,
                                                                                   case when CAST(SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete - 1
                                                                                        AS UNSIGNED) > 999 then 999 else CAST(SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete - 1
                                                                                        AS UNSIGNED) end  AS MINTIME,
                                                                                    case when CAST(SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete
                                                                                        AS UNSIGNED) > 999 then 999 else CAST(SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete
                                                                                        AS UNSIGNED) end  AS MAXTIME,
                                                                                    NOW()
                                                                                FROM
                                                                                    printvis.totetimes
                                                                                        JOIN
                                                                                    printvis.pm_packtimes ON loosepm_function = totetimes_packfunction
                                                                                        AND totetimes_whse = loosepm_whse
                                                                                WHERE
                                                                                    totetimes_whse = $whsesel and totetimes_cart not in (96409)
                                                                                GROUP BY totetimes_cart ON DUPLICATE KEY UPDATE taskpred_mintime=VALUES(taskpred_mintime), taskpred_maxtime=VALUES(taskpred_maxtime), taskpred_type=VALUES(taskpred_type)");
    $taskpredfile->execute();
} //end of whsearray foreach loop
