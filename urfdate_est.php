<?php

//estimate the PO delivery date by item
//Start at 5 key and traverse down to 2 key
//Run openpo_update first to ensure most up to date data

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../connections/conn_custaudit.php';  //conn1
//include '../../globalincludes/usa_asys.php';  //conn1
//include '../../globalincludes/ustxgpslotting_mysql.php';  //conn1
include_once 'globalfunctions.php';
//$whse = 7;

date_default_timezone_set('America/New_York');
$today = date("Y-m-d H:i:s");

//Global variables
$minocc = 2;
$date_format = 'Y-m-d';

//pull in holiday dates to array
$sqlholiday = $conn1->prepare("SELECT * from custaudit.holidays");
$sqlholiday->execute();
$holidays = $sqlholiday->fetchAll(pdo::FETCH_COLUMN); //fetch column returns the date in a single dimensional array!


$sqltruncate = "DELETE from custaudit.urfdate_est WHERE OPENWHSE in $whse";
$querydelete = $conn1->prepare($sqltruncate);
$querydelete->execute();


//SQL to pull open pos by item
$sql1 = $conn1->prepare("SELECT 
                            OPENSUPP,
                            OPENWHSE,
                            OPENITEM,
                            OPENVENDADD,
                            OPENPONUM,
                            PODATE,
                            EDIRECDATE,
                            inbound_4key_grouped.4KEYPOtoURFAVG_group as WCSAVG4KEY,
                            inbound_4key_grouped.4KEYPOtoURFSTD_group as WCSSTD4KEY,
                            inbound_2key_grouped.2KEYPOtoURFAVG_group as WCSAVG2KEY,
                            inbound_2key_grouped.2KEYPOtoURFSTD_group as WCSSTD2KEY,
                            edi_4key_grouped.4KEYEDItoURFAVG_group as EDIAVG4KEY,
                            edi_4key_grouped.4KEYEDItoURFSTD_group as EDISTD4KEY,
                            edi_2key_grouped.2KEYEDItoURFAVG_group as EDIAVG2KEY,
                            edi_2key_grouped.2KEYEDItoURFSTD_group as EDISTD2KEY
                        FROM
                            custaudit.openpo
                                left join
                            custaudit.edidates ON EDIPONUMB = OPENPONUM
                                and OPENITEM = EDIITEM
                                left join
                            custaudit.inbound_4key_grouped ON inbound_4key_grouped.4KEYVEND_group = OPENSUPP
                                and inbound_4key_grouped.4KEYDC_group = OPENWHSE
                                and inbound_4key_grouped.4KEYITEM_group = OPENITEM
                                and inbound_4key_grouped.4KEYVNADD_group = OPENVENDADD
                                and inbound_4key_grouped.4KEYCOUNT_group > 2
                                left join
                            custaudit.edi_4key_grouped ON edi_4key_grouped.4KEYVEND_group = OPENSUPP
                                and edi_4key_grouped.4KEYDC_group = OPENWHSE
                                and edi_4key_grouped.4KEYITEM_group = OPENITEM
                                and edi_4key_grouped.4KEYVNADD_group = OPENVENDADD
                                and edi_4key_grouped.4KEYCOUNT_group > 2
                                left join
                            custaudit.inbound_2key_grouped ON inbound_2key_grouped.2KEYVEND_group = OPENSUPP
                                and inbound_2key_grouped.2KEYDC_group = OPENWHSE
                                and inbound_2key_grouped.2KEYCOUNT_group > 2
                                left join
                            custaudit.edi_2key_grouped ON edi_2key_grouped.2KEYVEND_group = OPENSUPP
                                and edi_2key_grouped.2KEYDC_group = OPENWHSE
                                and edi_2key_grouped.2KEYCOUNT_group > 2
                        WHERE

                            OPENWHSE in $whse");

$sql1->execute();
$sql1array = $sql1->fetchAll(pdo::FETCH_ASSOC);



//$columns = implode(", ", array_keys($sql1array[0])) . ', AVGURFDATE, MAXURFDATE, GROUPUSED';
$columns = ("OPENSUPP, OPENWHSE, OPENITEM, OPENVENDADD, OPENPONUM, PODATE, AVGURFDATE, MAXURFDATE, GROUPUSEDWCS, AVGEDIDATE, MAXEDIDATE, GROUPUSEDEDI, UPDATEDATETIME");
$values = array();
$maxrange = 99;
$counter = 0;
$rowcount = count($sql1array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $AVGURFDATE = $MAXURFDATE = $GROUPUSEDWCS = $keyid4 = $keyid3 = $keyid2 = $keyid4edi = $keyid3edi = $keyid2edi = null;
        $AVGEDIDATE = $MAXEDIDATE = "''";
        $GROUPUSEDEDI = '-';

        $OPENSUPP = $sql1array[$counter]['OPENSUPP'];
        $OPENWHSE = intval($sql1array[$counter]['OPENWHSE']);
        $OPENITEM = intval($sql1array[$counter]['OPENITEM']);
        $OPENVENDADD = intval($sql1array[$counter]['OPENVENDADD']);
        $OPENPONUM = intval($sql1array[$counter]['OPENPONUM']);
        $PODATE = $sql1array[$counter]['PODATE'];
        $EDIDATE = $sql1array[$counter]['EDIRECDATE'];


        //WCS DATE
        if ($sql1array[$counter]['WCSAVG4KEY'] <> null) {
            $start_date = date('Y-m-d', strtotime($PODATE));
            $avgdaysbetween = ceil($sql1array[$counter]['WCSAVG4KEY']);
            $stddays = $sql1array[$counter]['WCSSTD4KEY'] * 2;
            $maxdays = ceil($stddays + $avgdaysbetween);

            $AVGURFDATE = add_business_days($start_date, $avgdaysbetween, $holidays, $date_format);
            $MAXURFDATE = add_business_days($start_date, $maxdays, $holidays, $date_format);
            $GROUPUSEDWCS = '4KEY';
        } elseif ($sql1array[$counter]['WCSAVG2KEY'] <> null) {
            $start_date = date('Y-m-d', strtotime($PODATE));
            $avgdaysbetween = ceil($sql1array[$counter]['WCSAVG2KEY']);
            $stddays = $sql1array[$counter]['WCSSTD2KEY'] * 2;
            $maxdays = ceil($stddays + $avgdaysbetween);

            $AVGURFDATE = add_business_days($start_date, $avgdaysbetween, $holidays, $date_format);
            $MAXURFDATE = add_business_days($start_date, $maxdays, $holidays, $date_format);
            $GROUPUSEDWCS = '2KEY';
        }


        //EDI DATE
        if ($sql1array[$counter]['EDIAVG4KEY'] <> null) {
            $start_date = date('Y-m-d', strtotime($PODATE));
            $avgdaysbetween = ceil($sql1array[$counter]['EDIAVG4KEY']);
            $stddays = $sql1array[$counter]['EDISTD4KEY'] * 2;
            $maxdays = ceil($stddays + $avgdaysbetween);

            $AVGEDIDATE = add_business_days($start_date, $avgdaysbetween, $holidays, $date_format);
            $MAXEDIDATE = add_business_days($start_date, $maxdays, $holidays, $date_format);
            $GROUPUSEDEDI = '4KEY';
        } elseif ($sql1array[$counter]['EDIAVG2KEY'] <> null) {
            $start_date = date('Y-m-d', strtotime($PODATE));
            $avgdaysbetween = ceil($sql1array[$counter]['EDIAVG2KEY']);
            $stddays = $sql1array[$counter]['EDISTD2KEY'] * 2;
            $maxdays = ceil($stddays + $avgdaysbetween);

            $AVGEDIDATE = add_business_days($start_date, $avgdaysbetween, $holidays, $date_format);
            $MAXEDIDATE = add_business_days($start_date, $maxdays, $holidays, $date_format);
            $GROUPUSEDEDI = '2KEY';
        }



        $data[] = "('$OPENSUPP', $OPENWHSE, $OPENITEM, $OPENVENDADD, $OPENPONUM, '$PODATE', '$AVGURFDATE', '$MAXURFDATE', '$GROUPUSEDWCS', '$AVGEDIDATE', '$MAXEDIDATE', '$GROUPUSEDEDI', '$today')";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO custaudit.urfdate_est ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=100;
} while ($counter <= $rowcount);








$insert3 = $conn1->prepare("INSERT INTO custaudit.urfdate_est_hist
                                    (OPENSUPP, 
                                     OPENWHSE, 
                                     OPENITEM, 
                                     OPENVENDADD, 
                                     OPENPONUM, 
                                     PODATE,
                                     AVGURFDATE, 
                                     MAXURFDATE,
                                     GROUPUSEDWCS,
                                     AVGEDIDATE,
                                     MAXEDIDATE,
                                     GROUPUSEDEDI,
                                     UPDATEDATETIME) 
                            SELECT 
                                *
                            from
                                custaudit.urfdate_est
                            ON DUPLICATE KEY UPDATE urfdate_est_hist.AVGURFDATE = urfdate_est.AVGURFDATE, urfdate_est_hist.MAXURFDATE = urfdate_est.MAXURFDATE, urfdate_est_hist.GROUPUSEDWCS = urfdate_est.GROUPUSEDWCS, urfdate_est_hist.AVGEDIDATE = urfdate_est.AVGEDIDATE, urfdate_est_hist.MAXEDIDATE = urfdate_est.MAXEDIDATE, urfdate_est_hist.GROUPUSEDEDI = urfdate_est.GROUPUSEDEDI, urfdate_est_hist.UPDATEDATETIME = urfdate_est.UPDATEDATETIME");
$insert3->execute();


//Pull history to see if any items have been urfed and putaway.  Determine accuracy of estimate.  
//Would the estimated date have been late?  Pull last 8 days of putaways and compare actual URF to estimated URF.

$estimateerror = $conn1->prepare("INSERT ignore INTO custaudit.estimate_error
                                   (URFVEND_EST,
                                    URFVNAD_EST,
                                    URFCARR_EST,
                                    URFTODC_EST,
                                    URFPONM_EST,
                                    URFRECN_EST,
                                    URFDCIN_EST,
                                    URFTMST_EST,
                                    PUTITEM_EST,
                                    PUTTMST_EST,
                                    PODATE_EST,
                                    AVGURFDATE_EST,
                                    MAXURFDATE_EST,
                                    GROUPUSEDWCS_EST,
                                    AVGEDIDATE_EST,
                                    MAXEDIDATE_EST,
                                    GROUPUSEDEDI_EST,
                                    UPDATEDATETIME_EST) 
                                   SELECT 
                                       URFVEND,
                                       URFVNAD,
                                       URFCARR,
                                       URFTODC,
                                       URFPONM,
                                       URFRECN,
                                       URFDCIN,
                                       URFTMST,
                                       PUTITEM,
                                       PUTTMST,
                                       PODATE,
                                       AVGURFDATE,
                                       MAXURFDATE,
                                       GROUPUSEDWCS,
                                       AVGEDIDATE,
                                       MAXEDIDATE,
                                       GROUPUSEDEDI,
                                       UPDATEDATETIME
                                   FROM
                                       custaudit.urfdate,
                                       custaudit.putdate,
                                       custaudit.urfdate_est_hist
                                   where
                                        PUTTMST BETWEEN NOW() - INTERVAL 8 DAY AND NOW()
                                                   and OPENPONUM = URFPONM
                                           and OPENITEM = PUTITEM
                                           and URFPONM = PUTPONM
                                           and URFRECN = PUTRECN
                                           and URFDCIN = PUTDCIN
                                           and exists( select 
                                               *
                                           from
                                               custaudit.urfdate_est_hist
                                           where
                                               OPENPONUM = URFPONM
                                                   and OPENITEM = PUTITEM)");

$estimateerror->execute();