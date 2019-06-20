
<?php

//Pull in all JDE pending orders.  By item must assign source of supply and deduct from available qty


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
ini_set('max_allowed_packet', 999999999);
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_esys.php';  //conn1
//include '../../globalincludes/ustxgpslotting_mysql.php';  //conn1
include_once '../globalincludes/usa_asys.php';
include 'globalfunctions.php';
date_default_timezone_set('America/New_York');
$today = date("Y-m-d H:i:s");

$sqldelete = "TRUNCATE TABLE custaudit.open_order_levelset";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();
//Need to loop through for each of the pends?
$uniqueitem = $eseriesconn->prepare("SELECT DISTINCT CAST(SDAITM as char(7) CCSID 37) as SDAITM, right(" . 'QC$DC' . ", 2) as PRIMDC                             
                                    FROM
                                        HSIPDTA71.F5501,
                                        HSIPDTA71.F4211
                                    WHERE
                                        QCDOCO = SDDOCO
                                        and SDNXTR = '533'
                                        AND SDAITM >= '1000000' AND SDAITM <= '9999999' 
                                        and (SDNXTR < '600' and SDNXTR >= '500')");
$uniqueitem->execute();
$uniqueitemarray = $uniqueitem->fetchAll(pdo::FETCH_ASSOC);


foreach ($uniqueitemarray as $key => $value) {
    $openpoarray = array();
    $onhandqtyarray = array();

    $orditem = $uniqueitemarray[$key]['SDAITM'];
   

    
    $whseprim = intval($uniqueitemarray[$key]['PRIMDC']);



    //file to pull in open POs by item.  Data in returned array $openpoarray
    include 'openpobyitem_dc.php';  //check if primary DC has open PO
    if (strlen($whseprim) == 1 || $whseprim < 10) {
        $whseprim = '0' . $whseprim;
    }
    if (count($openpoarray) > 0) { //if primary DC has open PO, run logic
        //currently only looking at items on BO!!!!!  REMOVE SDSOBK filter to look at all.
        $allpends = $eseriesconn->prepare("SELECT QCDOCO,QCTRDJ,QCCRTM,SDAN8,SDSHAN,SDPA8,SUM(SDUORG) AS SDUORG, SUM(SDSOQS) AS SDSOQS, SUM(SDSOBK) AS SDSOBK,SUM(SDPQOR) AS SDPQOR, SDSOCN,SDLTTR,SDNXTR,SDDSC1,SDLNTY,SDAITM,right(" . 'QC$DC' . ", 2) as PRIMDC
                                    FROM
                                        HSIPDTA71.F5501,
                                        HSIPDTA71.F4211
                                    WHERE
                                        QCDOCO = SDDOCO
                                        and SDNXTR = '533'
                                        and (SDNXTR < '600' and SDNXTR >= '500')
                                        and SDAITM = '$orditem'
                                        and right(" . 'QC$DC' . ", 2) = '$whseprim'
                                    GROUP BY QCDOCO,QCTRDJ,QCCRTM,SDAN8,SDSHAN,SDPA8,SDSOCN,SDLTTR,SDNXTR,SDDSC1,SDLNTY,SDAITM,right(" . 'QC$DC' . ", 2)
                                    ORDER BY QCTRDJ, QCCRTM");
        $allpends->execute();

        //pull pends by item now in array sorted in JDE order date/time
        $allpendsarray = $allpends->fetchAll(pdo::FETCH_ASSOC);
    } else {
        $whseprim = 10;  //check to see if GIV has an open PO for the NSI item
        include 'openpobyitem_dc.php';
        $whseprim = intval($uniqueitemarray[$key]['PRIMDC']);  //change back to primary DC
        if (strlen($whseprim) == 1 || $whseprim < 10) {
            $whseprim = '0' . $whseprim;
        }
        if (count($openpoarray) > 0) {
            $allpends = $eseriesconn->prepare("SELECT QCDOCO,QCTRDJ,QCCRTM,SDAN8,SDSHAN,SDPA8,SUM(SDUORG) AS SDUORG, SUM(SDSOQS) AS SDSOQS, SUM(SDSOBK) AS SDSOBK,SUM(SDPQOR) AS SDPQOR, SDSOCN,SDLTTR,SDNXTR,SDDSC1,SDLNTY,SDAITM,right(" . 'QC$DC' . ", 2) as PRIMDC
                                    FROM
                                        HSIPDTA71.F5501,
                                        HSIPDTA71.F4211
                                    WHERE
                                        QCDOCO = SDDOCO
                                        and SDNXTR = '533'
                                        and (SDNXTR < '600' and SDNXTR >= '500')
                                        and SDAITM = '$orditem'
                                        and right(" . 'QC$DC' . ", 2) = '$whseprim'
                                    GROUP BY QCDOCO,QCTRDJ,QCCRTM,SDAN8,SDSHAN,SDPA8,SDSOCN,SDLTTR,SDNXTR,SDDSC1,SDLNTY,SDAITM,right(" . 'QC$DC' . ", 2)
                                    ORDER BY QCTRDJ, QCCRTM");
            $allpends->execute();

            //pull pends by item now in array sorted in JDE order date/time
            $allpendsarray = $allpends->fetchAll(pdo::FETCH_ASSOC);
        }
    }


    if (count($openpoarray) <= 0) {  //if there are no open POs for the NSI item, skip.  Don't have the ability to forcast an order date at this time.
        continue;
    }
    //file to pull in availability at primary DC.  Purpose is to double check that new inventory is not available that can be used to allocated to BOs.  Data in returned array $onhandqtyarray
    include 'allwhseonhandqty.php';

    //item/customer/order characteristics.  Can it be xshipped?  Is it a BO?  Is it a DS?  Why did it pend?
    //logic to build open_order_levelset table
    $totalorderqty = 0;

    if (strlen($whseprim) == 1) {
        $whseprim = '0' . $whseprim;
    }





    foreach ($allpendsarray as $key2 => $value) { //loop through all pending orders by item/dc to assign source
        //qty still on backorder
        $source = $sourceID = null;
        $ORDQTY = intval($allpendsarray[$key2]['SDSOBK']);
        $ORDERDOCNUM = intval($allpendsarray[$key2]['QCDOCO']);
        $ORDERDATE = date("Y-m-d", strtotime(_1yydddtogregdate($allpendsarray[$key2]['QCTRDJ'])));
        if ($ORDERDATE <= '1971-00-00') {
            continue;
        }

        if (intval($allpendsarray[$key2]['QCCRTM']) <= 99999) {
            $ORDERTIME = '00:00:00';
        } else {
            $ORDERTIME = _stringtime(intval($allpendsarray[$key2]['QCCRTM']));
        }

        $ORDERDATETIME = $ORDERDATE . ' ' . $ORDERTIME;



        if (count($openpoarray) >= 1) {
            foreach ($openpoarray as $pokey => $value) { //loop through open pos till enough onorder to satisfy backorder qty
                $availnextpo = intval($openpoarray[$pokey]['OPENPURQTY']);  //qty available on the PO
                if ($availnextpo >= $ORDQTY) {
                    $source = 'OPENPO'; //assign source
                    $sourceID = $openpoarray[$pokey]['OPENPONUM']; //assign soureid to PO number

                    $OPENWHSE = intval($openpoarray[$pokey]['OPENWHSE']);
                    $PODATE = $openpoarray[$pokey]['PODATE'];
                    $AVGURFDATE = $openpoarray[$pokey]['AVGURFDATE'];
                    $MAXURFDATE = $openpoarray[$pokey]['MAXURFDATE'];
                    $GROUPUSEDWCS = $openpoarray[$pokey]['GROUPUSEDWCS'];
                    $AVGEDIDATE = $openpoarray[$pokey]['AVGEDIDATE'];
                    $MAXEDIDATE = $openpoarray[$pokey]['MAXEDIDATE'];
                    $GROUPUSEDEDI = $openpoarray[$pokey]['GROUPUSEDEDI'];
                    $CONFDATE = $openpoarray[$pokey]['CONFDATE'];


                    $openpoarray[$pokey]['OPENPURQTY'] -= $ORDQTY;  //subtract bo qty from open po qty
                    break;
                } else {
                    continue;
                }
            }
        } else {
            $source = 'TBD_OPENPO'; //assign source
            $sourceID = 'TBD_OPENPO'; //assign soureid to PO number

            $OPENWHSE = 0;
            $PODATE = '0000-00-00';
            $AVGURFDATE = '0000-00-00';
            $MAXURFDATE = '0000-00-00';
            $GROUPUSEDWCS = 'NONE';
            $AVGEDIDATE = '0000-00-00';
            $MAXEDIDATE = '0000-00-00';
            $GROUPUSEDEDI = 'NONE';
            $CONFDATE = '0000-00-00';
        }

        if ($source == null) {
            continue;
        }

        $insert = $conn1->prepare("INSERT INTO custaudit.open_order_levelset (ORDERDOCNUM, ITEMNUM, PRIMWHS, SUPPLYSOURCE, SUPPLYSOURCEID, ORDERQTY, PRIORNEEDQTY, ORDERDATETIME, UPDATEDATETIME, OPENWHSE, PODATE, AVGURFDATE, MAXURFDATE, GROUPUSEDWCS, AVGEDIDATE, MAXEDIDATE, GROUPUSEDEDI, CONFDATE) values ($ORDERDOCNUM, $orditem , $whseprim, '" . $source . "','" . $sourceID . "', $ORDQTY, $totalorderqty,'" . $ORDERDATETIME . "','" . $today . "', '" . $OPENWHSE . "', '" . $PODATE . "', '" . $AVGURFDATE . "', '" . $MAXURFDATE . "', '" . $GROUPUSEDWCS . "', '" . $AVGEDIDATE . "', '" . $MAXEDIDATE . "', '" . $GROUPUSEDEDI . "', '" . $CONFDATE . "')");
        $insert->execute();

        $totalorderqty += $ORDQTY;
    }
}

//write live records to historical table for analysis purposes.  Make sure this is run after urfdate_est has been updated.
$sqlmerge = "INSERT INTO custaudit.open_order_levelset_history (ORDERDOCNUM, ITEMNUM, PRIMWHS, SUPPLYSOURCE, SUPPLYSOURCEID, ORDERQTY, PRIORNEEDQTY, ORDERDATETIME, UPDATEDATETIME, OPENWHSE, PODATE, AVGURFDATE, MAXURFDATE, GROUPUSEDWCS, AVGEDIDATE, MAXEDIDATE, GROUPUSEDEDI, CONFDATE)
SELECT * FROM custaudit.open_order_levelset;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();
