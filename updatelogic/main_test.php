
<?php

set_time_limit(0);
ini_set('max_execution_time', 99999);
ini_set('set_time_limit', 99999);
ini_set('memory_limit', '-1');
ini_set('request_terminate_timeout', 99999);
//main core file to update slotting recommendation file --MY_NPFMVC--
//global includes

include_once '../../globalfunctions/slottingfunctions.php';
include_once '../../globalfunctions/newitem.php';

include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//assign columns variable for my_npfmvc table
$columns = 'WAREHOUSE,ITEM_NUMBER,PACKAGE_UNIT,PACKAGE_TYPE,DSL_TYPE,CUR_LOCATION,DAYS_FRM_SLE,AVGD_BTW_SLE,AVG_INV_OH,NBR_SHIP_OCC,PICK_QTY_MN,PICK_QTY_SD,SHIP_QTY_MN,SHIP_QTY_SD,ITEM_TYPE,CPCEPKU,CPCIPKU,CPCCPKU,CPCFLOW,CPCTOTE,CPCSHLF,CPCROTA,CPCESTK,CPCLIQU,CPCELEN,CPCEHEI,CPCEWID,CPCCLEN,CPCCHEI,CPCCWID,LMFIXA,LMFIXT,LMSTGT,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK, AVG_DAILY_PICK, AVG_DAILY_UNIT';

$whssel = 3;



    include '../../CustomerAudit/connection/connection_details.php';
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//Delete inventory restricted items
    $sqldelete3 = "DELETE FROM slotting.inventory_restricted WHERE WHSE_INV_REST = $whssel;";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    $sqldelete = "DELETE FROM slotting.my_npfmvc WHERE WAREHOUSE = $whssel and PACKAGE_TYPE in ('LSE', 'INP')";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

//--pull in available tiers--
    $alltiersql = $conn1->prepare("SELECT * FROM slotting.tiercounts WHERE TIER_WHS = $whssel");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $alltiersql->execute();
    $alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);

//--pull in volume by tier--
    $allvolumesql = $conn1->prepare("SELECT LMWHSE, LMTIER, sum(LMVOL9) as TIER_VOL FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel GROUP BY LMWHSE, LMTIER");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $allvolumesql->execute();
    $allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
//call L06 update logic  ***Is not needed for Canada.  Will have to add for US based slotting.  Should try to keep L06 (PICK_QTY_MN / AVGD_BTW_SLE) less that 1% of total
//what is total L06 volume available.
    $L06key = array_search('L06', array_column($allvolumearray, 'LMTIER')); //Find 'L06' associated key
    $L06Vol = intval($allvolumearray[$L06key]['TIER_VOL']);



    if ($L06key !== FALSE) {
//    include 'L06update.php';
        //Pull in total picks by day.  Want to keep less than 1% of picks in slow move zone??
        if ($whssel == 3) {
            $L06_pick_limit = .03;
        } elseif ($whssel == 2) {
            $L06_pick_limit = .03;
        } else {
            $L06_pick_limit = .01;
        }

        include '../../CustomerAudit/connection/connection_details.php';
        $LSEpicksSQL = $conn1->prepare("SELECT 
                                    sum(case
                                    when AVGD_BTW_SLE >= 365 then 0
                                    when DAYS_FRM_SLE >= 180 then 0
                                    when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                    when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then PICK_QTY_MN
                                    when AVGD_BTW_SLE = 0 then (PICK_QTY_MN / DAYS_FRM_SLE)
                                    else (PICK_QTY_MN / AVGD_BTW_SLE)
                                end) as TOTPICKS
                                FROM
                                    mysql_nptsld
                                WHERE
                                    WAREHOUSE = $whssel and PACKAGE_TYPE = 'LSE'");
        $LSEpicksSQL->execute();
        $conn1 = null;
        $LSEpicksArray = $LSEpicksSQL->fetchAll(pdo::FETCH_ASSOC);
        $LSE_Picks = intval($LSEpicksArray[0]['TOTPICKS']);
        $Max_L06_picks = $L06_pick_limit * $LSE_Picks;  //maximum number of picks to reside in L06 based off daily pick forecast
//Pull in available L06 Grid5s by volume ascending order

        include '../../CustomerAudit/connection/connection_details.php';
        $L06GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L06' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
        $L06GridsSQL->execute();
        $L06GridsArray = $L06GridsSQL->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        include '../../CustomerAudit/connection/connection_details.php';
        $L06sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                case
                                   when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                   else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                            WHERE
                                WAREHOUSE = $whssel AND
                                    $sql_dailypick <= 1
                                    and PACKAGE_TYPE in ('LSE')
                                    and ITEM_TYPE = 'ST'
                                    and CUR_LOCATION not like 'Q%'
                                    and CUR_LOCATION not like 'N%'
                                    and concat(A.ITEM_NUMBER,
                                        A.PACKAGE_UNIT,
                                        A.PACKAGE_TYPE,
                                        A.DSL_TYPE) not in (SELECT 
                                        concat(ITEM_NUMBER,
                                                    PACKAGE_UNIT,
                                                    PACKAGE_TYPE,
                                                    DSL_TYPE)
                                    FROM
                                        slotting.my_npfmvc
                                    WHERE
                                        WAREHOUSE = $whssel
                                            and PACKAGE_TYPE in ('LSE')
                                            and ITEM_TYPE = 'ST')
                            ORDER BY case
                                    when AVGD_BTW_SLE >= 365 then 0
                                    when DAYS_FRM_SLE >= 180 then 0
                                    when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                    when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then PICK_QTY_MN
                                    when AVGD_BTW_SLE = 0 then (PICK_QTY_MN / DAYS_FRM_SLE)
                                    else (PICK_QTY_MN / AVGD_BTW_SLE)
                                end asc");
        $L06sql->execute();
        $L06array = $L06sql->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        $running_L06_picks = 0; //initilize picks
        foreach ($L06array as $key => $value) {
            if ($running_L06_picks > $Max_L06_picks) {
                break;  //if exceeded pre-determined max picks from L06
            }

            //Check OK in Shelf Setting
            $var_OKINSHLF = $L06array[$key]['CPCSHLF'];

            $var_AVGSHIPQTY = $L06array[$key]['SHIP_QTY_MN'];
            $AVGD_BTW_SLE = intval($L06array[$key]['AVGD_BTW_SLE']);
            if ($AVGD_BTW_SLE == 0) {
                $AVGD_BTW_SLE = 999;
            }

            $var_AVGINV = intval($L06array[$key]['AVG_INV_OH']);
            $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
            if ($avgdailyshipqty == 0) {
                $avgdailyshipqty = .000000001;
            }

            $avgdailypickqty = $L06array[$key]['DAILYPICK'];

            $var_PCLIQU = $L06array[$key]['CPCLIQU'];

            $var_PCEHEIin = $L06array[$key]['CPCEHEI'] * 0.393701;
            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = $L06array[$key]['CPCCHEI'] * 0.393701;
            }

            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = 1;
            }

            $var_PCELENin = $L06array[$key]['CPCELEN'] * 0.393701;
            if ($var_PCELENin == 0) {
                $var_PCELENin = $L06array[$key]['CPCCLEN'] * 0.393701;
            }

            if ($var_PCELENin == 0) {
                $var_PCELENin = 1;
            }

            $var_PCEWIDin = $L06array[$key]['CPCEWID'] * 0.393701;
            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = $L06array[$key]['CPCCWID'] * 0.393701;
            }

            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = 1;
            }

            $var_eachqty = $L06array[$key]['CPCEPKU'];
            if ($var_eachqty == 0) {
                $var_eachqty = 1;
            }

            $PKGU_PERC_Restriction = $L06array[$key]['PERC_PERC'];
            $ITEM_NUMBER = intval($L06array[$key]['ITEM_NUMBER']);


            $slotqty = intval(ceil($var_AVGINV * $PKGU_PERC_Restriction)); //does it make sense to slot slow movers to average inventory?

            if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
                $slotqty = 1;
            }

            //calculate total slot valume to determine what grid to start
            $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

            //loop through available L06 grids to determine smallest location to accomodate slot quantity
            foreach ($L06GridsArray as $key2 => $value) {
                //if total slot volume is less than location volume, then continue
                if ($totalslotvol > $L06GridsArray[$key2]['LMVOL9']) {
                    continue;
                }

                $var_grid5 = $L06GridsArray[$key2]['LMGRD5'];
                if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
                    continue;
                }
                $var_gridheight = $L06GridsArray[$key2]['LMHIGH'];
                $var_griddepth = $L06GridsArray[$key2]['LMDEEP'];
                $var_gridwidth = $L06GridsArray[$key2]['LMWIDE'];
                $var_locvol = $L06GridsArray[$key2]['LMVOL9'];

                //Call the  true fit for L06
                $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
                $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

                if ($SUGGESTED_MAX_test >= $slotqty) {
                    break;
                }
            }


            $SUGGESTED_MAX = $SUGGESTED_MAX_test;
            //Call the min calc logic
            $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));
            if ($SUGGESTED_MIN == 0) {
                $SUGGESTED_MIN = 1;
            }

            //append data to array for writing to my_npfmvc table
            $L06array[$key]['SUGGESTED_TIER'] = 'L06';
            $L06array[$key]['SUGGESTED_GRID5'] = $var_grid5;
            $L06array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
            $L06array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
            $L06array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
            $L06array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
            $L06array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
            $L06array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L06array[$key]['CURMAX'], $L06array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
            $L06array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
            $L06array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

            $running_L06_picks +=$avgdailypickqty;
        }


//L06 items have been designated.  Loop through L06 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
        array_splice($L06array, ($key));

        $L06array = array_values($L06array);  //reset array



        $values = array();
        $intranid = 0;
        $maxrange = 999;
        $counter = 0;
        $rowcount = count($L06array);

        do {
            if ($maxrange > $rowcount) {  //prevent undefined offset
                $maxrange = $rowcount - 1;
            }

            $data = array();
            $values = array();
            while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
                $WAREHOUSE = intval($L06array[$counter]['WAREHOUSE']);
                $ITEM_NUMBER = intval($L06array[$counter]['ITEM_NUMBER']);
                $PACKAGE_UNIT = intval($L06array[$counter]['PACKAGE_UNIT']);
                $PACKAGE_TYPE = $L06array[$counter]['PACKAGE_TYPE'];
                $DSL_TYPE = $L06array[$counter]['DSL_TYPE'];
                $CUR_LOCATION = $L06array[$counter]['CUR_LOCATION'];
                $DAYS_FRM_SLE = intval($L06array[$counter]['DAYS_FRM_SLE']);
                $AVGD_BTW_SLE = intval($L06array[$counter]['AVGD_BTW_SLE']);
                $AVG_INV_OH = intval($L06array[$counter]['AVG_INV_OH']);
                $NBR_SHIP_OCC = intval($L06array[$counter]['NBR_SHIP_OCC']);
                $PICK_QTY_MN = intval($L06array[$counter]['PICK_QTY_MN']);
                $PICK_QTY_SD = $L06array[$counter]['PICK_QTY_SD'];
                $SHIP_QTY_MN = intval($L06array[$counter]['SHIP_QTY_MN']);
                $SHIP_QTY_SD = $L06array[$counter]['SHIP_QTY_SD'];
                $ITEM_TYPE = $L06array[$counter]['ITEM_TYPE'];
                $CPCEPKU = intval($L06array[$counter]['CPCEPKU']);
                $CPCIPKU = intval($L06array[$counter]['CPCIPKU']);
                $CPCCPKU = intval($L06array[$counter]['CPCCPKU']);
                $CPCFLOW = $L06array[$counter]['CPCFLOW'];
                $CPCTOTE = $L06array[$counter]['CPCTOTE'];
                $CPCSHLF = $L06array[$counter]['CPCSHLF'];
                $CPCROTA = $L06array[$counter]['CPCROTA'];
                $CPCESTK = intval($L06array[$counter]['CPCESTK']);
                $CPCLIQU = $L06array[$counter]['CPCLIQU'];
                $CPCELEN = $L06array[$counter]['CPCELEN'];
                $CPCEHEI = $L06array[$counter]['CPCEHEI'];
                $CPCEWID = $L06array[$counter]['CPCEWID'];
                $CPCCLEN = $L06array[$counter]['CPCCLEN'];
                $CPCCHEI = $L06array[$counter]['CPCCHEI'];
                $CPCCWID = $L06array[$counter]['CPCCWID'];
                $LMFIXA = $L06array[$counter]['LMFIXA'];
                $LMFIXT = $L06array[$counter]['LMFIXT'];
                $LMSTGT = $L06array[$counter]['LMSTGT'];
                $LMHIGH = intval($L06array[$counter]['LMHIGH']);
                $LMDEEP = intval($L06array[$counter]['LMDEEP']);
                $LMWIDE = intval($L06array[$counter]['LMWIDE']);
                $LMVOL9 = intval($L06array[$counter]['LMVOL9']);
                $LMTIER = $L06array[$counter]['LMTIER'];
                $LMGRD5 = $L06array[$counter]['LMGRD5'];
                $DLY_CUBE_VEL = intval($L06array[$counter]['DLY_CUBE_VEL']);
                $DLY_PICK_VEL = intval($L06array[$counter]['DLY_PICK_VEL']);
                $SUGGESTED_TIER = $L06array[$counter]['SUGGESTED_TIER'];
                $SUGGESTED_GRID5 = $L06array[$counter]['SUGGESTED_GRID5'];
                $SUGGESTED_DEPTH = $L06array[$counter]['SUGGESTED_DEPTH'];
                $SUGGESTED_MAX = intval($L06array[$counter]['SUGGESTED_MAX']);
                $SUGGESTED_MIN = intval($L06array[$counter]['SUGGESTED_MIN']);
                $SUGGESTED_SLOTQTY = intval($L06array[$counter]['SUGGESTED_SLOTQTY']);

                $SUGGESTED_IMPMOVES = ($L06array[$counter]['SUGGESTED_IMPMOVES']);
                $CURRENT_IMPMOVES = ($L06array[$counter]['CURRENT_IMPMOVES']);
                $SUGGESTED_NEWLOCVOL = intval($L06array[$counter]['SUGGESTED_NEWLOCVOL']);
                $SUGGESTED_DAYSTOSTOCK = intval($L06array[$counter]['SUGGESTED_DAYSTOSTOCK']);
                $AVG_DAILY_PICK = $L06array[$counter]['DAILYPICK'];
                $AVG_DAILY_UNIT = $L06array[$counter]['DAILYUNIT'];
                $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
                $counter +=1;
            }
            $values = implode(',', $data);

            if (empty($values)) {
                break;
            }
            include '../../CustomerAudit/connection/connection_details.php';
            $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
            $query = $conn1->prepare($sql);
            $query->execute();
            $conn1 = null;
            $maxrange +=1000;
        } while ($counter <= $rowcount);

        $conn1 = null;
    }
//***END OF L06 UPDATE***
//call L01 Update logic
    $L01key = array_search('L01', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
    if ($L01key !== FALSE) {
//    include 'L01update.php';
        $daystostock = 15;  //stock 10 shipping occurences as max
//count L01 grids available

        $L01Count = $alltierarray[$L01key]['TIER_COUNT'];
        $slowdownsizecutoff = 99999;
//*** Step 1: L01 Designation ***
//if ($whssel == 7) {
//    $L01Count += 62;
//}
        include '../../CustomerAudit/connection/connection_details.php';
        $L01sql = $conn1->prepare("SELECT DISTINCT
                                    A.WAREHOUSE,
                                    A.ITEM_NUMBER,
                                    A.PACKAGE_UNIT,
                                    A.PACKAGE_TYPE,
                                    A.DSL_TYPE,
                                    A.CUR_LOCATION,
                                    A.DAYS_FRM_SLE,
                                    A.AVGD_BTW_SLE,
                                    A.AVG_INV_OH,
                                    A.NBR_SHIP_OCC,
                                    A.PICK_QTY_MN,
                                    A.PICK_QTY_SD,
                                    A.SHIP_QTY_MN,
                                    A.SHIP_QTY_SD,
                                    B.ITEM_TYPE,
                                    X.CPCEPKU,
                                    X.CPCIPKU,
                                    X.CPCCPKU,
                                    X.CPCFLOW,
                                    X.CPCTOTE,
                                    X.CPCSHLF,
                                    X.CPCROTA,
                                    X.CPCESTK,
                                    X.CPCLIQU,
                                    X.CPCELEN,
                                    X.CPCEHEI,
                                    X.CPCEWID,
                                    X.CPCCLEN,
                                    X.CPCCHEI,
                                    X.CPCCWID,
                                    X.CPCNEST,
                                    D.LMFIXA,
                                    D.LMFIXT,
                                    D.LMSTGT,
                                    D.LMHIGH,
                                    D.LMDEEP,
                                    D.LMWIDE,
                                    D.LMVOL9,
                                    D.LMTIER,
                                    D.LMGRD5,
                                    D.CURMAX,
                                    D.CURMIN,
                                    case
                                        when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                        else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                    end as DLY_CUBE_VEL,
                                    case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                    PERC_SHIPQTY,
                                    PERC_PERC,
                                    $sql_dailypick as DAILYPICK,
                                    $sql_dailyunit as DAILYUNIT
                                FROM
                                    slotting.mysql_nptsld A
                                        JOIN
                                    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                        and B.ITEM = A.ITEM_NUMBER
                                        JOIN
                                    slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                        AND X.CPCITEM = A.ITEM_NUMBER
                                        JOIN
                                    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                        and D.LMLOC = A.CUR_LOCATION
                                        JOIN
                                    slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                        and E.PERC_ITEM = A.ITEM_NUMBER 
                                        and E.PERC_PKGU = A.PACKAGE_UNIT
                                        and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                WHERE
                                    WAREHOUSE = $whssel
                                        and CPCNEST = 0
                                        and PACKAGE_TYPE in ('LSE')
                                        and CUR_LOCATION not like 'Q%'
                                        and CUR_LOCATION not like 'N%'
                                        and ITEM_TYPE = 'ST'
                                        AND A.DSL_TYPE NOT IN (2,4)
                                        and D.LMTIER <> 'L17'  -- no colgate
                                ORDER BY DLY_CUBE_VEL desc
                                LIMIT $L01Count");
        $L01sql->execute();
        $L01array = $L01sql->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        foreach ($L01array as $key => $value) {

            $var_grid5 = '48P44';
            $var_gridheight = 48;
            $var_griddepth = 48;
            $var_gridwidth = 44;

            $var_AVGSHIPQTY = $L01array[$key]['SHIP_QTY_MN'];
            $AVGD_BTW_SLE = intval($L01array[$key]['AVGD_BTW_SLE']);
            $var_AVGINV = intval($L01array[$key]['AVG_INV_OH']);
            $avgdailyshipqty = round($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
            if ($avgdailyshipqty == 0) {
                $avgdailyshipqty = .000000001;
            }
            $var_PCLIQU = $L01array[$key]['CPCLIQU'];

            $var_PCEHEIin = $L01array[$key]['CPCCHEI'] * 0.393701;
            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = $L01array[$key]['CPCEHEI'] * 0.393701;
            }

            $var_PCELENin = $L01array[$key]['CPCCLEN'] * 0.393701;
            if ($var_PCELENin == 0) {
                $var_PCELENin = $L01array[$key]['CPCELEN'] * 0.393701;
            }

            $var_PCEWIDin = $L01array[$key]['CPCCWID'] * 0.393701;
            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = $L01array[$key]['CPCEWID'] * 0.393701;
            }

            $var_caseqty = $L01array[$key]['CPCCPKU'];
            if ($var_caseqty == 0) {
                $var_caseqty = 1;
            }
            $PKGU_PERC_Restriction = $L01array[$key]['PERC_PERC'];
            $ITEM_NUMBER = intval($L01array[$key]['ITEM_NUMBER']);



            //call slot quantity logic
            $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

            if (isset($slotqty_return_array['CEILQTY'])) {
                $var_pkgu = intval($L01array[$key]['PACKAGE_UNIT']);
                $var_pkty = $L01array[$key]['PACKAGE_TYPE'];
                $optqty = $slotqty_return_array['OPTQTY'];
                $slotqty = $slotqty_return_array['CEILQTY'];
                //write to table inventory_restricted
                include '../../CustomerAudit/connection/connection_details.php';
                $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
                $result2->execute();
                $conn1 = null;
            } else {
                $slotqty = $slotqty_return_array['OPTQTY'];
            }

            //Call the case true fit for L01
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
            $SUGGESTED_MAX = $SUGGESTED_MAX_array[1];
            //Call the min calc logic
            $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

            //append data to array for writing to my_npfmvc table
            $L01array[$key]['SUGGESTED_TIER'] = 'L01';
            $L01array[$key]['SUGGESTED_GRID5'] = $var_grid5;
            $L01array[$key]['SUGGESTED_DEPTH'] = 48;
            $L01array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
            $L01array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
            $L01array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
            $L01array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
            $L01array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L01array[$key]['CURMAX'], $L01array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
            $L01array[$key]['SUGGESTED_NEWLOCVOL'] = intval(48 * 48 * 44);
            $L01array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);
        }

//L01 items have been designated.  Loop through L01 array to add to my_npfmvc table


        $values = array();
        $intranid = 0;
        $maxrange = 999;
        $counter = 0;
        $rowcount = count($L01array);

        do {
            if ($maxrange > $rowcount) {  //prevent undefined offset
                $maxrange = $rowcount - 1;
            }

            $data = array();
            $values = array();
            while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
                $WAREHOUSE = intval($L01array[$counter]['WAREHOUSE']);
                $ITEM_NUMBER = intval($L01array[$counter]['ITEM_NUMBER']);
                $PACKAGE_UNIT = intval($L01array[$counter]['PACKAGE_UNIT']);
                $PACKAGE_TYPE = $L01array[$counter]['PACKAGE_TYPE'];
                $DSL_TYPE = $L01array[$counter]['DSL_TYPE'];
                $CUR_LOCATION = $L01array[$counter]['CUR_LOCATION'];
                $DAYS_FRM_SLE = intval($L01array[$counter]['DAYS_FRM_SLE']);
                $AVGD_BTW_SLE = intval($L01array[$counter]['AVGD_BTW_SLE']);
                $AVG_INV_OH = intval($L01array[$counter]['AVG_INV_OH']);
                $NBR_SHIP_OCC = intval($L01array[$counter]['NBR_SHIP_OCC']);
                $PICK_QTY_MN = intval($L01array[$counter]['PICK_QTY_MN']);
                $PICK_QTY_SD = $L01array[$counter]['PICK_QTY_SD'];
                $SHIP_QTY_MN = intval($L01array[$counter]['SHIP_QTY_MN']);
                $SHIP_QTY_SD = $L01array[$counter]['SHIP_QTY_SD'];
                $ITEM_TYPE = $L01array[$counter]['ITEM_TYPE'];
                $CPCEPKU = intval($L01array[$counter]['CPCEPKU']);
                $CPCIPKU = intval($L01array[$counter]['CPCIPKU']);
                $CPCCPKU = intval($L01array[$counter]['CPCCPKU']);
                $CPCFLOW = $L01array[$counter]['CPCFLOW'];
                $CPCTOTE = $L01array[$counter]['CPCTOTE'];
                $CPCSHLF = $L01array[$counter]['CPCSHLF'];
                $CPCROTA = $L01array[$counter]['CPCROTA'];
                $CPCESTK = intval($L01array[$counter]['CPCESTK']);
                $CPCLIQU = $L01array[$counter]['CPCLIQU'];
                $CPCELEN = $L01array[$counter]['CPCELEN'];
                $CPCEHEI = $L01array[$counter]['CPCEHEI'];
                $CPCEWID = $L01array[$counter]['CPCEWID'];
                $CPCCLEN = $L01array[$counter]['CPCCLEN'];
                $CPCCHEI = $L01array[$counter]['CPCCHEI'];
                $CPCCWID = $L01array[$counter]['CPCCWID'];
                $LMFIXA = $L01array[$counter]['LMFIXA'];
                $LMFIXT = $L01array[$counter]['LMFIXT'];
                $LMSTGT = $L01array[$counter]['LMSTGT'];
                $LMHIGH = intval($L01array[$counter]['LMHIGH']);
                $LMDEEP = intval($L01array[$counter]['LMDEEP']);
                $LMWIDE = intval($L01array[$counter]['LMWIDE']);
                $LMVOL9 = intval($L01array[$counter]['LMVOL9']);
                $LMTIER = $L01array[$counter]['LMTIER'];
                $LMGRD5 = $L01array[$counter]['LMGRD5'];
                $DLY_CUBE_VEL = $L01array[$counter]['DLY_CUBE_VEL'];
                $DLY_PICK_VEL = $L01array[$counter]['DLY_PICK_VEL'];
                $SUGGESTED_TIER = $L01array[$counter]['SUGGESTED_TIER'];
                $SUGGESTED_GRID5 = $L01array[$counter]['SUGGESTED_GRID5'];
                $SUGGESTED_DEPTH = $L01array[$counter]['SUGGESTED_DEPTH'];
                $SUGGESTED_MAX = intval($L01array[$counter]['SUGGESTED_MAX']);
                $SUGGESTED_MIN = intval($L01array[$counter]['SUGGESTED_MIN']);
                $SUGGESTED_SLOTQTY = intval($L01array[$counter]['SUGGESTED_SLOTQTY']);

                $SUGGESTED_IMPMOVES = ($L01array[$counter]['SUGGESTED_IMPMOVES']);
                $CURRENT_IMPMOVES = ($L01array[$counter]['CURRENT_IMPMOVES']);
                $SUGGESTED_NEWLOCVOL = intval($L01array[$counter]['SUGGESTED_NEWLOCVOL']);
                $SUGGESTED_DAYSTOSTOCK = intval($L01array[$counter]['SUGGESTED_DAYSTOSTOCK']);
                $AVG_DAILY_PICK = $L01array[$counter]['DAILYPICK'];
                $AVG_DAILY_UNIT = $L01array[$counter]['DAILYUNIT'];

                $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
                $counter +=1;
            }
            $values = implode(',', $data);

            if (empty($values)) {
                break;
            }
            include '../../CustomerAudit/connection/connection_details.php';
            $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
            $query = $conn1->prepare($sql);
            $query->execute();
            $maxrange +=1000;
            $conn1 = null;
        } while ($counter <= $rowcount);
        $conn1 = null;
    }
//***END OF L01 UPDATE***
//call L02 Update logic
//include 'L02update.php';
//*** RESTRICTION VARIABLES ***
    $minadbs = 5;  //need to have at least 15% of the available items.  For NOTL, 5 ADBS represents 4678 of 31000 items or 15%
    $mindsls = 14; //sold in the last two weeks
//$daystostock = 15;  //stock 10 shipping occurences as max
    $slowdownsizecutoff = 999999;  //min ADBS to only stock to 2 ship occurences as Max.  Not used right now till capacity is determined
    $skippedkeycount = 0;

//what is total L02 volume available
    $L02key = array_search('L02', array_column($allvolumearray, 'LMTIER')); //Find 'L02' associated key
    $L02Vol = intval($allvolumearray[$L02key]['TIER_VOL']);

//*** Step 2: L02 Designation ***

    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L02 Grid5s by volume ascending order
    $L02GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L02' GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L02GridsSQL->execute();
    $L02GridsArray = $L02GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    include '../../CustomerAudit/connection/connection_details.php';
    $L02sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                 case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                               PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                    LEFT JOIN
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE in ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.CUR_LOCATION not like 'Q%'
                                    and A.CUR_LOCATION not like 'N%'
                                    and A.NBR_SHIP_OCC >= 4
                                    and A.AVGD_BTW_SLE > 0
                                    and A.AVGD_BTW_SLE <= $minadbs
                                    and A.DAYS_FRM_SLE <= $mindsls
                                    and F.ITEM_NUMBER IS NULL
                                    AND A.DSL_TYPE NOT IN (2,4)
                            ORDER BY DLY_CUBE_VEL desc");
    $L02sql->execute();
    $L02array = $L02sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L02array as $key => $value) {
        if ($L02Vol < 0) {
            break;  //if all available L02 volume has been used, exit
        }

        //Check OK in Flow Setting
        $var_OKINFLOW = $L02array[$key]['CPCFLOW'];
        if ($var_OKINFLOW == 'N') {
            $var_item = intval($L02array[$key]['ITEM_NUMBER']);
            $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
            $var_should = 'FLOW';
            include '../../CustomerAudit/connection/connection_details.php';
            //write to table that should have gone to flow and was restricted
            $result2 = $conn1->prepare("INSERT INTO slotting.items_restricted (REST_ID, REST_WHSE, REST_ITEM, REST_PKGU, REST_PKTY, REST_SHOULD) values (0,$whssel, $var_item ,$var_pkgu,'" . $var_pkty . "','" . $var_should . "')");
            $result2->execute();
            $conn1 = null;
            $skippedkeycount +=1;
            unset($L02array[$key]);
            continue;
        }

        $var_AVGSHIPQTY = $L02array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L02array[$key]['AVGD_BTW_SLE']);
        $var_AVGINV = intval($L02array[$key]['AVG_INV_OH']);
        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L02array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L02array[$key]['CPCCHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L02array[$key]['CPCEHEI'] * 0.393701;
        }

        $var_PCELENin = $L02array[$key]['CPCCLEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L02array[$key]['CPCELEN'] * 0.393701;
        }

        $var_PCEWIDin = $L02array[$key]['CPCCWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L02array[$key]['CPCEWID'] * 0.393701;
        }

        $var_caseqty = $L02array[$key]['CPCCPKU'];
        if ($var_caseqty == 0) {
            $var_caseqty = 1;
        }

        switch ($whssel) {
            case 2:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            case 11:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 12:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 16:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 3:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            case 7:

                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 7;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 2;
                }
                break;

//below is optimal if additional L01 could be added to match Jax
//            if ($AVGD_BTW_SLE <= 1) {
//                $daystostock = 10;
//            } elseif ($AVGD_BTW_SLE <= 2) {
//                $daystostock = 6;
//            } elseif ($AVGD_BTW_SLE <= 3) {
//                $daystostock = 5;
//            } elseif ($AVGD_BTW_SLE <= 4) {
//                $daystostock = 3;
//            } elseif ($AVGD_BTW_SLE <= 5) {
//                $daystostock = 3;
//            }
//            break;

            case 6:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;

            case 9:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            default:
                break;
        }

        $PKGU_PERC_Restriction = $L02array[$key]['PERC_PERC'];
        $ITEM_NUMBER = intval($L02array[$key]['ITEM_NUMBER']);

        //call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
            $optqty = $slotqty_return_array['OPTQTY'];
            $slotqty = $slotqty_return_array['CEILQTY'];
            include '../../CustomerAudit/connection/connection_details.php';
            //write to table inventory_restricted
            $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
            $result2->execute();
            $conn1 = null;
        } else {
            $slotqty = $slotqty_return_array['OPTQTY'];
        }

        //calculate total slot valume to determine what grid to start
        $totalslotvol = ($slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin) / $var_caseqty;

        //loop through available L02 grids to determine smallest location to accomodate slot quantity
        foreach ($L02GridsArray as $key2 => $value) {
            //if total slot volume is less than location volume, then continue
            if ($totalslotvol > $L02GridsArray[$key2]['LMVOL9']) {
                continue;
            }

            $var_grid5 = $L02GridsArray[$key2]['LMGRD5'];
            $var_gridheight = $L02GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L02GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L02GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L02GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L02
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($SUGGESTED_MAX_test >= $slotqty) {
                break;
            }
        }




        $SUGGESTED_MAX = $SUGGESTED_MAX_test;
        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

        //append data to array for writing to my_npfmvc table
        $L02array[$key]['SUGGESTED_TIER'] = 'L02';
        $L02array[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $L02array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L02array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L02array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L02array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L02array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
        $L02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L02array[$key]['CURMAX'], $L02array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
        $L02array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($var_grid5, 0, 2)) * intval(substr($var_grid5, 3, 2)) * intval($var_griddepth);
        $L02array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

        $L02Vol -= $var_locvol;
    }

//L02 items have been designated.  Loop through L02 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L02array, ($key - $skippedkeycount - 1));

    $L02array = array_values($L02array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L02array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L02array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L02array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L02array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L02array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L02array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L02array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L02array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L02array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L02array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L02array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L02array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L02array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L02array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L02array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L02array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L02array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L02array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L02array[$counter]['CPCCPKU']);
            $CPCFLOW = $L02array[$counter]['CPCFLOW'];
            $CPCTOTE = $L02array[$counter]['CPCTOTE'];
            $CPCSHLF = $L02array[$counter]['CPCSHLF'];
            $CPCROTA = $L02array[$counter]['CPCROTA'];
            $CPCESTK = intval($L02array[$counter]['CPCESTK']);
            $CPCLIQU = $L02array[$counter]['CPCLIQU'];
            $CPCELEN = $L02array[$counter]['CPCELEN'];
            $CPCEHEI = $L02array[$counter]['CPCEHEI'];
            $CPCEWID = $L02array[$counter]['CPCEWID'];
            $CPCCLEN = $L02array[$counter]['CPCCLEN'];
            $CPCCHEI = $L02array[$counter]['CPCCHEI'];
            $CPCCWID = $L02array[$counter]['CPCCWID'];
            $LMFIXA = $L02array[$counter]['LMFIXA'];
            $LMFIXT = $L02array[$counter]['LMFIXT'];
            $LMSTGT = $L02array[$counter]['LMSTGT'];
            $LMHIGH = intval($L02array[$counter]['LMHIGH']);
            $LMDEEP = intval($L02array[$counter]['LMDEEP']);
            $LMWIDE = intval($L02array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L02array[$counter]['LMVOL9']);
            $LMTIER = $L02array[$counter]['LMTIER'];
            $LMGRD5 = $L02array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = $L02array[$counter]['DLY_CUBE_VEL'];
            $DLY_PICK_VEL = $L02array[$counter]['DLY_PICK_VEL'];
            $SUGGESTED_TIER = $L02array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L02array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L02array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L02array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L02array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L02array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L02array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L02array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L02array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L02array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L02array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L02array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $conn1 = null;
        $maxrange +=1000;
    } while ($counter <= $rowcount);

    $conn1 = null;


//***END OF L02 LOGIC***
//include 'L05update.php';
    $skippedkeycount = 0;

//what is total L05 volume available.  Only used for capacity constraints
    $L05key = array_search('L05', array_column($allvolumearray, 'LMTIER')); //Find 'L05' associated key
//if ($L05key != FALSE) {
//    $L05Vol = intval($allvolumearray[$L05key]['TIER_VOL']);
//} else {

    if ($whssel == 11) {
        $L05Vol = 32000;  //model two drawers for NOTL
    } else {
        $L05Vol = 150000; //remove, just for testing purposes.
    }
//}
    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L05 Grid5s by volume ascending order
    $L05GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L05' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L05GridsSQL->execute();
    $L05GridsArray = $L05GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    if ($L05key == FALSE || count($L05GridsArray) == 0) {
////add drawers for NOTL analysis
        $L05GridsArray[0]['LMGRD5'] = '02D03';
        $L05GridsArray[0]['LMHIGH'] = 2;
        $L05GridsArray[0]['LMDEEP'] = 4;
        $L05GridsArray[0]['LMWIDE'] = 3;
        $L05GridsArray[0]['LMVOL9'] = 24;
        $L05GridsArray[0]['COUNT'] = 10;

        $L05GridsArray[1]['LMGRD5'] = '02D05';
        $L05GridsArray[1]['LMHIGH'] = 2;
        $L05GridsArray[1]['LMDEEP'] = 5;
        $L05GridsArray[1]['LMWIDE'] = 5;
        $L05GridsArray[1]['LMVOL9'] = 50;
        $L05GridsArray[1]['COUNT'] = 10;
    }

    usort($L05GridsArray, 'sortascLMVOL9');

    include '../../CustomerAudit/connection/connection_details.php';
    $L05sql = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    A.DSL_TYPE,
    A.CUR_LOCATION,
    A.DAYS_FRM_SLE,
    A.AVGD_BTW_SLE,
    A.AVG_INV_OH,
    A.NBR_SHIP_OCC,
    A.PICK_QTY_MN,
    A.PICK_QTY_SD,
    A.SHIP_QTY_MN,
    A.SHIP_QTY_SD,
    B.ITEM_TYPE,
    X.CPCEPKU,
    X.CPCIPKU,
    X.CPCCPKU,
    X.CPCFLOW,
    X.CPCTOTE,
    X.CPCSHLF,
    X.CPCROTA,
    X.CPCESTK,
    X.CPCLIQU,
    X.CPCELEN,
    X.CPCEHEI,
    X.CPCEWID,
    X.CPCCLEN,
    X.CPCCHEI,
    X.CPCCWID,
    D.LMFIXA,
    D.LMFIXT,
    D.LMSTGT,
    D.LMHIGH,
    D.LMDEEP,
    D.LMWIDE,
    D.LMVOL9,
    D.LMTIER,
    D.LMGRD5,
    D.CURMAX,
    D.CURMIN,
    case
       when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
       else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
   end as DLY_CUBE_VEL,
   case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                              
    PERC_SHIPQTY,
    PERC_PERC,
    $sql_dailypick as DAILYPICK,
    $sql_dailyunit as DAILYUNIT
FROM
    slotting.mysql_nptsld A
        JOIN
    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
        and B.ITEM = A.ITEM_NUMBER
        JOIN
    slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
        AND X.CPCITEM = A.ITEM_NUMBER
        JOIN
    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
        and D.LMITEM = A.ITEM_NUMBER
        and A.PACKAGE_UNIT = D.LMPKGU
        JOIN
    slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
        and E.PERC_ITEM = A.ITEM_NUMBER
        and E.PERC_PKGU = A.PACKAGE_UNIT
        and E.PERC_PKGTYPE = A.PACKAGE_TYPE
        LEFT JOIN
    slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
        and F.ITEM_NUMBER = A.ITEM_NUMBER
        and F.PACKAGE_TYPE = A.PACKAGE_TYPE
        and F.PACKAGE_UNIT = A.PACKAGE_UNIT
WHERE
    A.WAREHOUSE = $whssel
        and (A.AVG_INV_OH * PERC_PERC * 1.2) <= 50
        and A.PACKAGE_TYPE = 'LSE'
        and B.ITEM_TYPE = 'ST'
        and A.NBR_SHIP_OCC >= 4
        and A.AVGD_BTW_SLE > 0
        and A.CUR_LOCATION not like 'Q%'
        and A.CUR_LOCATION not like 'N%'
        and A.AVG_INV_OH > 0
        and F.ITEM_NUMBER IS NULL
        AND A.DSL_TYPE NOT IN (2,4)
ORDER BY ($sql_dailypick) DESC;");

    $L05sql->execute();
    $L05array = $L05sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L05array as $key => $value) {
        if ($L05Vol < 0) {
            break;  //if all available L05 volume has been used, exit
        }
        $ITEM_NUMBER = intval($L05array[$key]['ITEM_NUMBER']);
        $PKGU_PERC_Restriction = $L05array[$key]['PERC_PERC'];


        $var_AVGSHIPQTY = $L05array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L05array[$key]['AVGD_BTW_SLE']);
        if ($AVGD_BTW_SLE == 0) {
            $AVGD_BTW_SLE = 999;
        }

        $var_AVGINV = intval($L05array[$key]['AVG_INV_OH']);
        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L05array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L05array[$key]['CPCEHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L05array[$key]['CPCCHEI'] * 0.393701;
        }

        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = 1;
        }

        $var_PCELENin = $L05array[$key]['CPCELEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L05array[$key]['CPCCLEN'] * 0.393701;
        }

        if ($var_PCELENin == 0) {
            $var_PCELENin = 1;
        }

        $var_PCEWIDin = $L05array[$key]['CPCEWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L05array[$key]['CPCCWID'] * 0.393701;
        }

        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = 1;
        }

        $var_eachqty = $L05array[$key]['CPCEPKU'];
        if ($var_eachqty == 0) {
            $var_eachqty = 1;
        }

        //for drawers, slot to average inventory plus 20% excess
        if ($var_AVGINV == 0) {
            $slotqty = 1;
        } else {
            $slotqty = intval($var_AVGINV * 1.2 * $PKGU_PERC_Restriction);
        }


        //loop through available L05 grids to determine smallest location to accomodate slot quantity
        foreach ($L05GridsArray as $key2 => $value) {
            $var_grid5 = $L05GridsArray[$key2]['LMGRD5'];
            $var_gridheight = $L05GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L05GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L05GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L05GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L04
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($SUGGESTED_MAX_test >= $slotqty) {
                break;
            }
        }

        if ($SUGGESTED_MAX_test < $slotqty) {
            $skippedkeycount +=1;
            unset($L05array[$key]);
            continue;
        }


        $SUGGESTED_MAX = $SUGGESTED_MAX_test;
        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

        //append data to array for writing to my_npfmvc table
        $L05array[$key]['SUGGESTED_TIER'] = 'L05';
        $L05array[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $L05array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L05array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L05array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L05array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L05array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
        $L05array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L05array[$key]['CURMAX'], $L05array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
        $L05array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
        $L05array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

        $L05Vol -= $var_locvol;
    }


//L04 items have been designated.  Loop through L04 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L05array, ($key - $skippedkeycount));

    $L05array = array_values($L05array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L05array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L05array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L05array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L05array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L05array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L05array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L05array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L05array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L05array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L05array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L05array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L05array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L05array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L05array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L05array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L05array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L05array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L05array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L05array[$counter]['CPCCPKU']);
            $CPCFLOW = $L05array[$counter]['CPCFLOW'];
            $CPCTOTE = $L05array[$counter]['CPCTOTE'];
            $CPCSHLF = $L05array[$counter]['CPCSHLF'];
            $CPCROTA = $L05array[$counter]['CPCROTA'];
            $CPCESTK = intval($L05array[$counter]['CPCESTK']);
            $CPCLIQU = $L05array[$counter]['CPCLIQU'];
            $CPCELEN = $L05array[$counter]['CPCELEN'];
            $CPCEHEI = $L05array[$counter]['CPCEHEI'];
            $CPCEWID = $L05array[$counter]['CPCEWID'];
            $CPCCLEN = $L05array[$counter]['CPCCLEN'];
            $CPCCHEI = $L05array[$counter]['CPCCHEI'];
            $CPCCWID = $L05array[$counter]['CPCCWID'];
            $LMFIXA = $L05array[$counter]['LMFIXA'];
            $LMFIXT = $L05array[$counter]['LMFIXT'];
            $LMSTGT = $L05array[$counter]['LMSTGT'];
            $LMHIGH = intval($L05array[$counter]['LMHIGH']);
            $LMDEEP = intval($L05array[$counter]['LMDEEP']);
            $LMWIDE = intval($L05array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L05array[$counter]['LMVOL9']);
            $LMTIER = $L05array[$counter]['LMTIER'];
            $LMGRD5 = $L05array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = intval($L05array[$counter]['DLY_CUBE_VEL']);
            $DLY_PICK_VEL = intval($L05array[$counter]['DLY_PICK_VEL']);
            $SUGGESTED_TIER = $L05array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L05array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L05array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L05array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L05array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L05array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L05array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L05array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L05array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L05array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L05array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L05array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $conn1 = null;
        $maxrange +=1000;
    } while ($counter <= $rowcount);
    $conn1 = null;

//***END OF L05 UPDATE LOGIC***
//include 'L04update.php';
    $slowdownsizecutoff = 99999;


    $reconfigured = 4;  //Bay23
    $whse11endcapopp = ((32 - $reconfigured) * 6336);

//what is total L04 volume available.  Only used for capacity constraints
    $L04key = array_search('L04', array_column($allvolumearray, 'LMTIER')); //Find 'L04' associated key
    $L04Vol = intval($allvolumearray[$L04key]['TIER_VOL']);

    $sqlexclude = '';

    if ($whssel == 11) {
        $L04Vol += $whse11endcapopp;
        $sqlexclude = " and A.CUR_LOCATION not like 'B34%' and A.CUR_LOCATION not like 'B35%'";
    } elseif ($whssel == 7) { //endcap opportunity
        $L04Vol += 1175040;
//    $L04Vol += 999999999999999999999;
    } elseif ($whssel == 6) {
        $L04Vol += 3317760;
    }




//*** Step 4: L04 Designation ***

    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L04 Grid5s by volume ascending order
    $L04GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L04' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L04GridsSQL->execute();
    $L04GridsArray = $L04GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    usort($L04GridsArray, 'sortascLMVOL9');
    include '../../CustomerAudit/connection/connection_details.php';
    $L04sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                    LEFT JOIN
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE in ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.CUR_LOCATION not like 'Q%'
                                    and A.CUR_LOCATION not like 'N%'
                                    and A.NBR_SHIP_OCC >= 4
                                    AND A.DSL_TYPE NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and F.ITEM_NUMBER IS NULL
                                    $sqlexclude
                            ORDER BY DLY_CUBE_VEL desc");
    $L04sql->execute();
    $L04array = $L04sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L04array as $key => $value) {
        //prevent timeout
        if ($key == 5000 || $key == 10000 || $key == 15000 || $key == 20000 || $key == 25000 || $key == 30000) {
            include '../../CustomerAudit/connection/connection_details.php';

            $sqldelete3 = "DELETE FROM slotting.my_npfmvc  WHERE WAREHOUSE = $whssel and SUGGESTED_TIER = 'L04';";
            $querydelete3 = $conn1->prepare($sqldelete3);
            $querydelete3->execute();
            $conn1 = null;
        }

        if ($L04Vol < 0) {
            break;  //if all available L04 volume has been used, exit
        }
        $ITEM_NUMBER = intval($L04array[$key]['ITEM_NUMBER']);
        //Check OK in Shelf Setting
        $var_OKINSHLF = $L04array[$key]['CPCSHLF'];

        $var_AVGSHIPQTY = $L04array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L04array[$key]['AVGD_BTW_SLE']);
        if ($AVGD_BTW_SLE == 0) {
            $AVGD_BTW_SLE = 999;
        }

        $var_AVGINV = intval($L04array[$key]['AVG_INV_OH']);

        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L04array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L04array[$key]['CPCEHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L04array[$key]['CPCCHEI'] * 0.393701;
        }

        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = 1;
        }

        $var_PCELENin = $L04array[$key]['CPCELEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L04array[$key]['CPCCLEN'] * 0.393701;
        }

        if ($var_PCELENin == 0) {
            $var_PCELENin = 1;
        }

        $var_PCEWIDin = $L04array[$key]['CPCEWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L04array[$key]['CPCCWID'] * 0.393701;
        }

        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = 1;
        }

        $var_eachqty = $L04array[$key]['CPCEPKU'];
        if ($var_eachqty == 0) {
            $var_eachqty = 1;
        }

        switch ($whssel) {
            case 2:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 18;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 13;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            case 11:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 60;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 22;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 12:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 60;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 22;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 16:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 75;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 35;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 25;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 7:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 26;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            case 9:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 40;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 2;
                }
                break;
            case 6:
                //Pass 3
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 40;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 2;
                }
                break;
            case 3:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 26;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            default:
                break;
        }

        $PKGU_PERC_Restriction = $L04array[$key]['PERC_PERC'];

        //call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($L04array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L04array[$key]['PACKAGE_TYPE'];
            $optqty = $slotqty_return_array['OPTQTY'];
            $slotqty = $slotqty_return_array['CEILQTY'];
            //write to table inventory_restricted
            include '../../CustomerAudit/connection/connection_details.php';
            $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
            $result2->execute();
            $conn1 = null;
        } else {
            $slotqty = $slotqty_return_array['OPTQTY'];
        }


        if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
            $slotqty = 1;
        } elseif ($slotqty == 0) {
            $slotqty = $var_AVGINV;
        }

        //calculate total slot valume to determine what grid to start
        $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

//    if ($var_OKINSHLF == 'N') {
//        $lastusedgrid5 = '15T11';
//    } else {
//        $lastusedgrid5 = '15S47';
//    }
//    $maxkey = count($L04GridsArray) - 1; //if reach max key and not figured true fit, calc at max
        //loop through available L04 grids to determine smallest location to accomodate slot quantity
        foreach ($L04GridsArray as $key2 => $value) {
            //if total slot volume is less than location volume, then continue
//        if ($totalslotvol > $L04GridsArray[$key2]['LMVOL9']) {
//            continue;
//        }

            $var_grid5 = $L04GridsArray[$key2]['LMGRD5'];
            if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
                continue;
            }
            $var_gridheight = $L04GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L04GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L04GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L04GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L04
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($whssel == 11) {
                $SUGGESTED_MAX_test = intval(floor($SUGGESTED_MAX_test * .95));  //take down suggested max by 5% to correct true fit issues for NOTL
            }

            if ($var_locvol < 100) {  //location is a drawer
                if ($SUGGESTED_MAX_test >= $var_AVGINV) {
                    break;
                }
            } elseif ($var_locvol >= 100) {
                if ($SUGGESTED_MAX_test >= $slotqty) {
                    break;
                }
            }
            //to prevent issue of suggesting a shelf when not accpetable according to OK in flag
            $lastusedgrid5 = $var_grid5;
        }


        $SUGGESTED_MAX = $SUGGESTED_MAX_test;

        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

        //append data to array for writing to my_npfmvc table
        $L04array[$key]['SUGGESTED_TIER'] = 'L04';
        $L04array[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
        $L04array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L04array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L04array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L04array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L04array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
        $L04array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L04array[$key]['CURMAX'], $L04array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
        $L04array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($lastusedgrid5, 0, 2)) * intval(substr($lastusedgrid5, 3, 2)) * intval($var_griddepth);
        $L04array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
        $L04array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

        $L04Vol -= $var_locvol;
    }

//L04 items have been designated.  Loop through L04 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L04array, ($key));

    $L04array = array_values($L04array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L04array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L04array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L04array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L04array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L04array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L04array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L04array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L04array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L04array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L04array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L04array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L04array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L04array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L04array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L04array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L04array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L04array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L04array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L04array[$counter]['CPCCPKU']);
            $CPCFLOW = $L04array[$counter]['CPCFLOW'];
            $CPCTOTE = $L04array[$counter]['CPCTOTE'];
            $CPCSHLF = $L04array[$counter]['CPCSHLF'];
            $CPCROTA = $L04array[$counter]['CPCROTA'];
            $CPCESTK = intval($L04array[$counter]['CPCESTK']);
            $CPCLIQU = $L04array[$counter]['CPCLIQU'];
            $CPCELEN = $L04array[$counter]['CPCELEN'];
            $CPCEHEI = $L04array[$counter]['CPCEHEI'];
            $CPCEWID = $L04array[$counter]['CPCEWID'];
            $CPCCLEN = $L04array[$counter]['CPCCLEN'];
            $CPCCHEI = $L04array[$counter]['CPCCHEI'];
            $CPCCWID = $L04array[$counter]['CPCCWID'];
            $LMFIXA = $L04array[$counter]['LMFIXA'];
            $LMFIXT = $L04array[$counter]['LMFIXT'];
            $LMSTGT = $L04array[$counter]['LMSTGT'];
            $LMHIGH = intval($L04array[$counter]['LMHIGH']);
            $LMDEEP = intval($L04array[$counter]['LMDEEP']);
            $LMWIDE = intval($L04array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L04array[$counter]['LMVOL9']);
            $LMTIER = $L04array[$counter]['LMTIER'];
            $LMGRD5 = $L04array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = intval($L04array[$counter]['DLY_CUBE_VEL']);
            $DLY_PICK_VEL = intval($L04array[$counter]['DLY_PICK_VEL']);
            $SUGGESTED_TIER = $L04array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L04array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L04array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L04array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L04array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L04array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L04array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L04array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L04array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L04array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L04array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L04array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
        $conn1 = null;
    } while ($counter <= $rowcount);

    $conn1 = null;


//***END OF L04 UPDATE LOGIC***

    
    
    $whssel = 7;



    include '../../CustomerAudit/connection/connection_details.php';
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//Delete inventory restricted items
    $sqldelete3 = "DELETE FROM slotting.inventory_restricted WHERE WHSE_INV_REST = $whssel;";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    $sqldelete = "DELETE FROM slotting.my_npfmvc WHERE WAREHOUSE = $whssel and PACKAGE_TYPE in ('LSE', 'INP')";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

//--pull in available tiers--
    $alltiersql = $conn1->prepare("SELECT * FROM slotting.tiercounts WHERE TIER_WHS = $whssel");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $alltiersql->execute();
    $alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);

//--pull in volume by tier--
    $allvolumesql = $conn1->prepare("SELECT LMWHSE, LMTIER, sum(LMVOL9) as TIER_VOL FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel GROUP BY LMWHSE, LMTIER");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $allvolumesql->execute();
    $allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
//call L06 update logic  ***Is not needed for Canada.  Will have to add for US based slotting.  Should try to keep L06 (PICK_QTY_MN / AVGD_BTW_SLE) less that 1% of total
//what is total L06 volume available.
    $L06key = array_search('L06', array_column($allvolumearray, 'LMTIER')); //Find 'L06' associated key
    $L06Vol = intval($allvolumearray[$L06key]['TIER_VOL']);



    if ($L06key !== FALSE) {
//    include 'L06update.php';
        //Pull in total picks by day.  Want to keep less than 1% of picks in slow move zone??
        if ($whssel == 3) {
            $L06_pick_limit = .03;
        } elseif ($whssel == 2) {
            $L06_pick_limit = .03;
        } else {
            $L06_pick_limit = .01;
        }

        include '../../CustomerAudit/connection/connection_details.php';
        $LSEpicksSQL = $conn1->prepare("SELECT 
                                    sum(case
                                    when AVGD_BTW_SLE >= 365 then 0
                                    when DAYS_FRM_SLE >= 180 then 0
                                    when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                    when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then PICK_QTY_MN
                                    when AVGD_BTW_SLE = 0 then (PICK_QTY_MN / DAYS_FRM_SLE)
                                    else (PICK_QTY_MN / AVGD_BTW_SLE)
                                end) as TOTPICKS
                                FROM
                                    mysql_nptsld
                                WHERE
                                    WAREHOUSE = $whssel and PACKAGE_TYPE = 'LSE'");
        $LSEpicksSQL->execute();
        $conn1 = null;
        $LSEpicksArray = $LSEpicksSQL->fetchAll(pdo::FETCH_ASSOC);
        $LSE_Picks = intval($LSEpicksArray[0]['TOTPICKS']);
        $Max_L06_picks = $L06_pick_limit * $LSE_Picks;  //maximum number of picks to reside in L06 based off daily pick forecast
//Pull in available L06 Grid5s by volume ascending order

        include '../../CustomerAudit/connection/connection_details.php';
        $L06GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L06' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
        $L06GridsSQL->execute();
        $L06GridsArray = $L06GridsSQL->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        include '../../CustomerAudit/connection/connection_details.php';
        $L06sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                case
                                   when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                   else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                            WHERE
                                WAREHOUSE = $whssel AND
                                    $sql_dailypick <= 1
                                    and PACKAGE_TYPE in ('LSE')
                                    and ITEM_TYPE = 'ST'
                                    and CUR_LOCATION not like 'Q%'
                                    and CUR_LOCATION not like 'N%'
                                    and concat(A.ITEM_NUMBER,
                                        A.PACKAGE_UNIT,
                                        A.PACKAGE_TYPE,
                                        A.DSL_TYPE) not in (SELECT 
                                        concat(ITEM_NUMBER,
                                                    PACKAGE_UNIT,
                                                    PACKAGE_TYPE,
                                                    DSL_TYPE)
                                    FROM
                                        slotting.my_npfmvc
                                    WHERE
                                        WAREHOUSE = $whssel
                                            and PACKAGE_TYPE in ('LSE')
                                            and ITEM_TYPE = 'ST')
                            ORDER BY case
                                    when AVGD_BTW_SLE >= 365 then 0
                                    when DAYS_FRM_SLE >= 180 then 0
                                    when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                    when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then PICK_QTY_MN
                                    when AVGD_BTW_SLE = 0 then (PICK_QTY_MN / DAYS_FRM_SLE)
                                    else (PICK_QTY_MN / AVGD_BTW_SLE)
                                end asc");
        $L06sql->execute();
        $L06array = $L06sql->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        $running_L06_picks = 0; //initilize picks
        foreach ($L06array as $key => $value) {
            if ($running_L06_picks > $Max_L06_picks) {
                break;  //if exceeded pre-determined max picks from L06
            }

            //Check OK in Shelf Setting
            $var_OKINSHLF = $L06array[$key]['CPCSHLF'];

            $var_AVGSHIPQTY = $L06array[$key]['SHIP_QTY_MN'];
            $AVGD_BTW_SLE = intval($L06array[$key]['AVGD_BTW_SLE']);
            if ($AVGD_BTW_SLE == 0) {
                $AVGD_BTW_SLE = 999;
            }

            $var_AVGINV = intval($L06array[$key]['AVG_INV_OH']);
            $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
            if ($avgdailyshipqty == 0) {
                $avgdailyshipqty = .000000001;
            }

            $avgdailypickqty = $L06array[$key]['DAILYPICK'];

            $var_PCLIQU = $L06array[$key]['CPCLIQU'];

            $var_PCEHEIin = $L06array[$key]['CPCEHEI'] * 0.393701;
            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = $L06array[$key]['CPCCHEI'] * 0.393701;
            }

            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = 1;
            }

            $var_PCELENin = $L06array[$key]['CPCELEN'] * 0.393701;
            if ($var_PCELENin == 0) {
                $var_PCELENin = $L06array[$key]['CPCCLEN'] * 0.393701;
            }

            if ($var_PCELENin == 0) {
                $var_PCELENin = 1;
            }

            $var_PCEWIDin = $L06array[$key]['CPCEWID'] * 0.393701;
            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = $L06array[$key]['CPCCWID'] * 0.393701;
            }

            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = 1;
            }

            $var_eachqty = $L06array[$key]['CPCEPKU'];
            if ($var_eachqty == 0) {
                $var_eachqty = 1;
            }

            $PKGU_PERC_Restriction = $L06array[$key]['PERC_PERC'];
            $ITEM_NUMBER = intval($L06array[$key]['ITEM_NUMBER']);


            $slotqty = intval(ceil($var_AVGINV * $PKGU_PERC_Restriction)); //does it make sense to slot slow movers to average inventory?

            if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
                $slotqty = 1;
            }

            //calculate total slot valume to determine what grid to start
            $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

            //loop through available L06 grids to determine smallest location to accomodate slot quantity
            foreach ($L06GridsArray as $key2 => $value) {
                //if total slot volume is less than location volume, then continue
                if ($totalslotvol > $L06GridsArray[$key2]['LMVOL9']) {
                    continue;
                }

                $var_grid5 = $L06GridsArray[$key2]['LMGRD5'];
                if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
                    continue;
                }
                $var_gridheight = $L06GridsArray[$key2]['LMHIGH'];
                $var_griddepth = $L06GridsArray[$key2]['LMDEEP'];
                $var_gridwidth = $L06GridsArray[$key2]['LMWIDE'];
                $var_locvol = $L06GridsArray[$key2]['LMVOL9'];

                //Call the  true fit for L06
                $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
                $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

                if ($SUGGESTED_MAX_test >= $slotqty) {
                    break;
                }
            }


            $SUGGESTED_MAX = $SUGGESTED_MAX_test;
            //Call the min calc logic
            $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));
            if ($SUGGESTED_MIN == 0) {
                $SUGGESTED_MIN = 1;
            }

            //append data to array for writing to my_npfmvc table
            $L06array[$key]['SUGGESTED_TIER'] = 'L06';
            $L06array[$key]['SUGGESTED_GRID5'] = $var_grid5;
            $L06array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
            $L06array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
            $L06array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
            $L06array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
            $L06array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
            $L06array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L06array[$key]['CURMAX'], $L06array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
            $L06array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
            $L06array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

            $running_L06_picks +=$avgdailypickqty;
        }


//L06 items have been designated.  Loop through L06 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
        array_splice($L06array, ($key));

        $L06array = array_values($L06array);  //reset array



        $values = array();
        $intranid = 0;
        $maxrange = 999;
        $counter = 0;
        $rowcount = count($L06array);

        do {
            if ($maxrange > $rowcount) {  //prevent undefined offset
                $maxrange = $rowcount - 1;
            }

            $data = array();
            $values = array();
            while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
                $WAREHOUSE = intval($L06array[$counter]['WAREHOUSE']);
                $ITEM_NUMBER = intval($L06array[$counter]['ITEM_NUMBER']);
                $PACKAGE_UNIT = intval($L06array[$counter]['PACKAGE_UNIT']);
                $PACKAGE_TYPE = $L06array[$counter]['PACKAGE_TYPE'];
                $DSL_TYPE = $L06array[$counter]['DSL_TYPE'];
                $CUR_LOCATION = $L06array[$counter]['CUR_LOCATION'];
                $DAYS_FRM_SLE = intval($L06array[$counter]['DAYS_FRM_SLE']);
                $AVGD_BTW_SLE = intval($L06array[$counter]['AVGD_BTW_SLE']);
                $AVG_INV_OH = intval($L06array[$counter]['AVG_INV_OH']);
                $NBR_SHIP_OCC = intval($L06array[$counter]['NBR_SHIP_OCC']);
                $PICK_QTY_MN = intval($L06array[$counter]['PICK_QTY_MN']);
                $PICK_QTY_SD = $L06array[$counter]['PICK_QTY_SD'];
                $SHIP_QTY_MN = intval($L06array[$counter]['SHIP_QTY_MN']);
                $SHIP_QTY_SD = $L06array[$counter]['SHIP_QTY_SD'];
                $ITEM_TYPE = $L06array[$counter]['ITEM_TYPE'];
                $CPCEPKU = intval($L06array[$counter]['CPCEPKU']);
                $CPCIPKU = intval($L06array[$counter]['CPCIPKU']);
                $CPCCPKU = intval($L06array[$counter]['CPCCPKU']);
                $CPCFLOW = $L06array[$counter]['CPCFLOW'];
                $CPCTOTE = $L06array[$counter]['CPCTOTE'];
                $CPCSHLF = $L06array[$counter]['CPCSHLF'];
                $CPCROTA = $L06array[$counter]['CPCROTA'];
                $CPCESTK = intval($L06array[$counter]['CPCESTK']);
                $CPCLIQU = $L06array[$counter]['CPCLIQU'];
                $CPCELEN = $L06array[$counter]['CPCELEN'];
                $CPCEHEI = $L06array[$counter]['CPCEHEI'];
                $CPCEWID = $L06array[$counter]['CPCEWID'];
                $CPCCLEN = $L06array[$counter]['CPCCLEN'];
                $CPCCHEI = $L06array[$counter]['CPCCHEI'];
                $CPCCWID = $L06array[$counter]['CPCCWID'];
                $LMFIXA = $L06array[$counter]['LMFIXA'];
                $LMFIXT = $L06array[$counter]['LMFIXT'];
                $LMSTGT = $L06array[$counter]['LMSTGT'];
                $LMHIGH = intval($L06array[$counter]['LMHIGH']);
                $LMDEEP = intval($L06array[$counter]['LMDEEP']);
                $LMWIDE = intval($L06array[$counter]['LMWIDE']);
                $LMVOL9 = intval($L06array[$counter]['LMVOL9']);
                $LMTIER = $L06array[$counter]['LMTIER'];
                $LMGRD5 = $L06array[$counter]['LMGRD5'];
                $DLY_CUBE_VEL = intval($L06array[$counter]['DLY_CUBE_VEL']);
                $DLY_PICK_VEL = intval($L06array[$counter]['DLY_PICK_VEL']);
                $SUGGESTED_TIER = $L06array[$counter]['SUGGESTED_TIER'];
                $SUGGESTED_GRID5 = $L06array[$counter]['SUGGESTED_GRID5'];
                $SUGGESTED_DEPTH = $L06array[$counter]['SUGGESTED_DEPTH'];
                $SUGGESTED_MAX = intval($L06array[$counter]['SUGGESTED_MAX']);
                $SUGGESTED_MIN = intval($L06array[$counter]['SUGGESTED_MIN']);
                $SUGGESTED_SLOTQTY = intval($L06array[$counter]['SUGGESTED_SLOTQTY']);

                $SUGGESTED_IMPMOVES = ($L06array[$counter]['SUGGESTED_IMPMOVES']);
                $CURRENT_IMPMOVES = ($L06array[$counter]['CURRENT_IMPMOVES']);
                $SUGGESTED_NEWLOCVOL = intval($L06array[$counter]['SUGGESTED_NEWLOCVOL']);
                $SUGGESTED_DAYSTOSTOCK = intval($L06array[$counter]['SUGGESTED_DAYSTOSTOCK']);
                $AVG_DAILY_PICK = $L06array[$counter]['DAILYPICK'];
                $AVG_DAILY_UNIT = $L06array[$counter]['DAILYUNIT'];
                $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
                $counter +=1;
            }
            $values = implode(',', $data);

            if (empty($values)) {
                break;
            }
            include '../../CustomerAudit/connection/connection_details.php';
            $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
            $query = $conn1->prepare($sql);
            $query->execute();
            $conn1 = null;
            $maxrange +=1000;
        } while ($counter <= $rowcount);

        $conn1 = null;
    }
//***END OF L06 UPDATE***
//call L01 Update logic
    $L01key = array_search('L01', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
    if ($L01key !== FALSE) {
//    include 'L01update.php';
        $daystostock = 15;  //stock 10 shipping occurences as max
//count L01 grids available

        $L01Count = $alltierarray[$L01key]['TIER_COUNT'];
        $slowdownsizecutoff = 99999;
//*** Step 1: L01 Designation ***
//if ($whssel == 7) {
//    $L01Count += 62;
//}
        include '../../CustomerAudit/connection/connection_details.php';
        $L01sql = $conn1->prepare("SELECT DISTINCT
                                    A.WAREHOUSE,
                                    A.ITEM_NUMBER,
                                    A.PACKAGE_UNIT,
                                    A.PACKAGE_TYPE,
                                    A.DSL_TYPE,
                                    A.CUR_LOCATION,
                                    A.DAYS_FRM_SLE,
                                    A.AVGD_BTW_SLE,
                                    A.AVG_INV_OH,
                                    A.NBR_SHIP_OCC,
                                    A.PICK_QTY_MN,
                                    A.PICK_QTY_SD,
                                    A.SHIP_QTY_MN,
                                    A.SHIP_QTY_SD,
                                    B.ITEM_TYPE,
                                    X.CPCEPKU,
                                    X.CPCIPKU,
                                    X.CPCCPKU,
                                    X.CPCFLOW,
                                    X.CPCTOTE,
                                    X.CPCSHLF,
                                    X.CPCROTA,
                                    X.CPCESTK,
                                    X.CPCLIQU,
                                    X.CPCELEN,
                                    X.CPCEHEI,
                                    X.CPCEWID,
                                    X.CPCCLEN,
                                    X.CPCCHEI,
                                    X.CPCCWID,
                                    X.CPCNEST,
                                    D.LMFIXA,
                                    D.LMFIXT,
                                    D.LMSTGT,
                                    D.LMHIGH,
                                    D.LMDEEP,
                                    D.LMWIDE,
                                    D.LMVOL9,
                                    D.LMTIER,
                                    D.LMGRD5,
                                    D.CURMAX,
                                    D.CURMIN,
                                    case
                                        when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                        else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                    end as DLY_CUBE_VEL,
                                    case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                    PERC_SHIPQTY,
                                    PERC_PERC,
                                    $sql_dailypick as DAILYPICK,
                                    $sql_dailyunit as DAILYUNIT
                                FROM
                                    slotting.mysql_nptsld A
                                        JOIN
                                    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                        and B.ITEM = A.ITEM_NUMBER
                                        JOIN
                                    slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                        AND X.CPCITEM = A.ITEM_NUMBER
                                        JOIN
                                    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                        and D.LMLOC = A.CUR_LOCATION
                                        JOIN
                                    slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                        and E.PERC_ITEM = A.ITEM_NUMBER 
                                        and E.PERC_PKGU = A.PACKAGE_UNIT
                                        and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                WHERE
                                    WAREHOUSE = $whssel
                                        and CPCNEST = 0
                                        and PACKAGE_TYPE in ('LSE')
                                        and CUR_LOCATION not like 'Q%'
                                        and CUR_LOCATION not like 'N%'
                                        and ITEM_TYPE = 'ST'
                                        AND A.DSL_TYPE NOT IN (2,4)
                                        and D.LMTIER <> 'L17'  -- no colgate
                                ORDER BY DLY_CUBE_VEL desc
                                LIMIT $L01Count");
        $L01sql->execute();
        $L01array = $L01sql->fetchAll(pdo::FETCH_ASSOC);
        $conn1 = null;
        foreach ($L01array as $key => $value) {

            $var_grid5 = '48P44';
            $var_gridheight = 48;
            $var_griddepth = 48;
            $var_gridwidth = 44;

            $var_AVGSHIPQTY = $L01array[$key]['SHIP_QTY_MN'];
            $AVGD_BTW_SLE = intval($L01array[$key]['AVGD_BTW_SLE']);
            $var_AVGINV = intval($L01array[$key]['AVG_INV_OH']);
            $avgdailyshipqty = round($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
            if ($avgdailyshipqty == 0) {
                $avgdailyshipqty = .000000001;
            }
            $var_PCLIQU = $L01array[$key]['CPCLIQU'];

            $var_PCEHEIin = $L01array[$key]['CPCCHEI'] * 0.393701;
            if ($var_PCEHEIin == 0) {
                $var_PCEHEIin = $L01array[$key]['CPCEHEI'] * 0.393701;
            }

            $var_PCELENin = $L01array[$key]['CPCCLEN'] * 0.393701;
            if ($var_PCELENin == 0) {
                $var_PCELENin = $L01array[$key]['CPCELEN'] * 0.393701;
            }

            $var_PCEWIDin = $L01array[$key]['CPCCWID'] * 0.393701;
            if ($var_PCEWIDin == 0) {
                $var_PCEWIDin = $L01array[$key]['CPCEWID'] * 0.393701;
            }

            $var_caseqty = $L01array[$key]['CPCCPKU'];
            if ($var_caseqty == 0) {
                $var_caseqty = 1;
            }
            $PKGU_PERC_Restriction = $L01array[$key]['PERC_PERC'];
            $ITEM_NUMBER = intval($L01array[$key]['ITEM_NUMBER']);



            //call slot quantity logic
            $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

            if (isset($slotqty_return_array['CEILQTY'])) {
                $var_pkgu = intval($L01array[$key]['PACKAGE_UNIT']);
                $var_pkty = $L01array[$key]['PACKAGE_TYPE'];
                $optqty = $slotqty_return_array['OPTQTY'];
                $slotqty = $slotqty_return_array['CEILQTY'];
                //write to table inventory_restricted
                include '../../CustomerAudit/connection/connection_details.php';
                $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
                $result2->execute();
                $conn1 = null;
            } else {
                $slotqty = $slotqty_return_array['OPTQTY'];
            }

            //Call the case true fit for L01
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
            $SUGGESTED_MAX = $SUGGESTED_MAX_array[1];
            //Call the min calc logic
            $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

            //append data to array for writing to my_npfmvc table
            $L01array[$key]['SUGGESTED_TIER'] = 'L01';
            $L01array[$key]['SUGGESTED_GRID5'] = $var_grid5;
            $L01array[$key]['SUGGESTED_DEPTH'] = 48;
            $L01array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
            $L01array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
            $L01array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
            $L01array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
            $L01array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L01array[$key]['CURMAX'], $L01array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
            $L01array[$key]['SUGGESTED_NEWLOCVOL'] = intval(48 * 48 * 44);
            $L01array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(15);
        }

//L01 items have been designated.  Loop through L01 array to add to my_npfmvc table


        $values = array();
        $intranid = 0;
        $maxrange = 999;
        $counter = 0;
        $rowcount = count($L01array);

        do {
            if ($maxrange > $rowcount) {  //prevent undefined offset
                $maxrange = $rowcount - 1;
            }

            $data = array();
            $values = array();
            while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
                $WAREHOUSE = intval($L01array[$counter]['WAREHOUSE']);
                $ITEM_NUMBER = intval($L01array[$counter]['ITEM_NUMBER']);
                $PACKAGE_UNIT = intval($L01array[$counter]['PACKAGE_UNIT']);
                $PACKAGE_TYPE = $L01array[$counter]['PACKAGE_TYPE'];
                $DSL_TYPE = $L01array[$counter]['DSL_TYPE'];
                $CUR_LOCATION = $L01array[$counter]['CUR_LOCATION'];
                $DAYS_FRM_SLE = intval($L01array[$counter]['DAYS_FRM_SLE']);
                $AVGD_BTW_SLE = intval($L01array[$counter]['AVGD_BTW_SLE']);
                $AVG_INV_OH = intval($L01array[$counter]['AVG_INV_OH']);
                $NBR_SHIP_OCC = intval($L01array[$counter]['NBR_SHIP_OCC']);
                $PICK_QTY_MN = intval($L01array[$counter]['PICK_QTY_MN']);
                $PICK_QTY_SD = $L01array[$counter]['PICK_QTY_SD'];
                $SHIP_QTY_MN = intval($L01array[$counter]['SHIP_QTY_MN']);
                $SHIP_QTY_SD = $L01array[$counter]['SHIP_QTY_SD'];
                $ITEM_TYPE = $L01array[$counter]['ITEM_TYPE'];
                $CPCEPKU = intval($L01array[$counter]['CPCEPKU']);
                $CPCIPKU = intval($L01array[$counter]['CPCIPKU']);
                $CPCCPKU = intval($L01array[$counter]['CPCCPKU']);
                $CPCFLOW = $L01array[$counter]['CPCFLOW'];
                $CPCTOTE = $L01array[$counter]['CPCTOTE'];
                $CPCSHLF = $L01array[$counter]['CPCSHLF'];
                $CPCROTA = $L01array[$counter]['CPCROTA'];
                $CPCESTK = intval($L01array[$counter]['CPCESTK']);
                $CPCLIQU = $L01array[$counter]['CPCLIQU'];
                $CPCELEN = $L01array[$counter]['CPCELEN'];
                $CPCEHEI = $L01array[$counter]['CPCEHEI'];
                $CPCEWID = $L01array[$counter]['CPCEWID'];
                $CPCCLEN = $L01array[$counter]['CPCCLEN'];
                $CPCCHEI = $L01array[$counter]['CPCCHEI'];
                $CPCCWID = $L01array[$counter]['CPCCWID'];
                $LMFIXA = $L01array[$counter]['LMFIXA'];
                $LMFIXT = $L01array[$counter]['LMFIXT'];
                $LMSTGT = $L01array[$counter]['LMSTGT'];
                $LMHIGH = intval($L01array[$counter]['LMHIGH']);
                $LMDEEP = intval($L01array[$counter]['LMDEEP']);
                $LMWIDE = intval($L01array[$counter]['LMWIDE']);
                $LMVOL9 = intval($L01array[$counter]['LMVOL9']);
                $LMTIER = $L01array[$counter]['LMTIER'];
                $LMGRD5 = $L01array[$counter]['LMGRD5'];
                $DLY_CUBE_VEL = $L01array[$counter]['DLY_CUBE_VEL'];
                $DLY_PICK_VEL = $L01array[$counter]['DLY_PICK_VEL'];
                $SUGGESTED_TIER = $L01array[$counter]['SUGGESTED_TIER'];
                $SUGGESTED_GRID5 = $L01array[$counter]['SUGGESTED_GRID5'];
                $SUGGESTED_DEPTH = $L01array[$counter]['SUGGESTED_DEPTH'];
                $SUGGESTED_MAX = intval($L01array[$counter]['SUGGESTED_MAX']);
                $SUGGESTED_MIN = intval($L01array[$counter]['SUGGESTED_MIN']);
                $SUGGESTED_SLOTQTY = intval($L01array[$counter]['SUGGESTED_SLOTQTY']);

                $SUGGESTED_IMPMOVES = ($L01array[$counter]['SUGGESTED_IMPMOVES']);
                $CURRENT_IMPMOVES = ($L01array[$counter]['CURRENT_IMPMOVES']);
                $SUGGESTED_NEWLOCVOL = intval($L01array[$counter]['SUGGESTED_NEWLOCVOL']);
                $SUGGESTED_DAYSTOSTOCK = intval($L01array[$counter]['SUGGESTED_DAYSTOSTOCK']);
                $AVG_DAILY_PICK = $L01array[$counter]['DAILYPICK'];
                $AVG_DAILY_UNIT = $L01array[$counter]['DAILYUNIT'];

                $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
                $counter +=1;
            }
            $values = implode(',', $data);

            if (empty($values)) {
                break;
            }
            include '../../CustomerAudit/connection/connection_details.php';
            $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
            $query = $conn1->prepare($sql);
            $query->execute();
            $maxrange +=1000;
            $conn1 = null;
        } while ($counter <= $rowcount);
        $conn1 = null;
    }
//***END OF L01 UPDATE***
//call L02 Update logic
//include 'L02update.php';
//*** RESTRICTION VARIABLES ***
    $minadbs = 5;  //need to have at least 15% of the available items.  For NOTL, 5 ADBS represents 4678 of 31000 items or 15%
    $mindsls = 14; //sold in the last two weeks
//$daystostock = 15;  //stock 10 shipping occurences as max
    $slowdownsizecutoff = 999999;  //min ADBS to only stock to 2 ship occurences as Max.  Not used right now till capacity is determined
    $skippedkeycount = 0;

//what is total L02 volume available
    $L02key = array_search('L02', array_column($allvolumearray, 'LMTIER')); //Find 'L02' associated key
    $L02Vol = intval($allvolumearray[$L02key]['TIER_VOL']);

//*** Step 2: L02 Designation ***

    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L02 Grid5s by volume ascending order
    $L02GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L02' GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L02GridsSQL->execute();
    $L02GridsArray = $L02GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    include '../../CustomerAudit/connection/connection_details.php';
    $L02sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                 case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                               PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                    LEFT JOIN
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE in ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.CUR_LOCATION not like 'Q%'
                                    and A.CUR_LOCATION not like 'N%'
                                    and A.NBR_SHIP_OCC >= 4
                                    and A.AVGD_BTW_SLE > 0
                                    and A.AVGD_BTW_SLE <= $minadbs
                                    and A.DAYS_FRM_SLE <= $mindsls
                                    and F.ITEM_NUMBER IS NULL
                                    AND A.DSL_TYPE NOT IN (2,4)
                            ORDER BY DLY_CUBE_VEL desc");
    $L02sql->execute();
    $L02array = $L02sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L02array as $key => $value) {
        if ($L02Vol < 0) {
            break;  //if all available L02 volume has been used, exit
        }

        //Check OK in Flow Setting
        $var_OKINFLOW = $L02array[$key]['CPCFLOW'];
        if ($var_OKINFLOW == 'N') {
            $var_item = intval($L02array[$key]['ITEM_NUMBER']);
            $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
            $var_should = 'FLOW';
            include '../../CustomerAudit/connection/connection_details.php';
            //write to table that should have gone to flow and was restricted
            $result2 = $conn1->prepare("INSERT INTO slotting.items_restricted (REST_ID, REST_WHSE, REST_ITEM, REST_PKGU, REST_PKTY, REST_SHOULD) values (0,$whssel, $var_item ,$var_pkgu,'" . $var_pkty . "','" . $var_should . "')");
            $result2->execute();
            $conn1 = null;
            $skippedkeycount +=1;
            unset($L02array[$key]);
            continue;
        }

        $var_AVGSHIPQTY = $L02array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L02array[$key]['AVGD_BTW_SLE']);
        $var_AVGINV = intval($L02array[$key]['AVG_INV_OH']);
        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L02array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L02array[$key]['CPCCHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L02array[$key]['CPCEHEI'] * 0.393701;
        }

        $var_PCELENin = $L02array[$key]['CPCCLEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L02array[$key]['CPCELEN'] * 0.393701;
        }

        $var_PCEWIDin = $L02array[$key]['CPCCWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L02array[$key]['CPCEWID'] * 0.393701;
        }

        $var_caseqty = $L02array[$key]['CPCCPKU'];
        if ($var_caseqty == 0) {
            $var_caseqty = 1;
        }

        switch ($whssel) {
            case 2:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            case 11:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 12:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 16:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 15;
                }
                break;
            case 3:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            case 7:

                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 7;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 2;
                }
                break;

//below is optimal if additional L01 could be added to match Jax
//            if ($AVGD_BTW_SLE <= 1) {
//                $daystostock = 10;
//            } elseif ($AVGD_BTW_SLE <= 2) {
//                $daystostock = 6;
//            } elseif ($AVGD_BTW_SLE <= 3) {
//                $daystostock = 5;
//            } elseif ($AVGD_BTW_SLE <= 4) {
//                $daystostock = 3;
//            } elseif ($AVGD_BTW_SLE <= 5) {
//                $daystostock = 3;
//            }
//            break;

            case 6:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;

            case 9:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 3;
                }
                break;
            default:
                break;
        }

        $PKGU_PERC_Restriction = $L02array[$key]['PERC_PERC'];
        $ITEM_NUMBER = intval($L02array[$key]['ITEM_NUMBER']);

        //call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
            $optqty = $slotqty_return_array['OPTQTY'];
            $slotqty = $slotqty_return_array['CEILQTY'];
            include '../../CustomerAudit/connection/connection_details.php';
            //write to table inventory_restricted
            $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
            $result2->execute();
            $conn1 = null;
        } else {
            $slotqty = $slotqty_return_array['OPTQTY'];
        }

        //calculate total slot valume to determine what grid to start
        $totalslotvol = ($slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin) / $var_caseqty;

        //loop through available L02 grids to determine smallest location to accomodate slot quantity
        foreach ($L02GridsArray as $key2 => $value) {
            //if total slot volume is less than location volume, then continue
            if ($totalslotvol > $L02GridsArray[$key2]['LMVOL9']) {
                continue;
            }

            $var_grid5 = $L02GridsArray[$key2]['LMGRD5'];
            $var_gridheight = $L02GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L02GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L02GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L02GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L02
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($SUGGESTED_MAX_test >= $slotqty) {
                break;
            }
        }




        $SUGGESTED_MAX = $SUGGESTED_MAX_test;
        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

        //append data to array for writing to my_npfmvc table
        $L02array[$key]['SUGGESTED_TIER'] = 'L02';
        $L02array[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $L02array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L02array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L02array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L02array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L02array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
        $L02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L02array[$key]['CURMAX'], $L02array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
        $L02array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($var_grid5, 0, 2)) * intval(substr($var_grid5, 3, 2)) * intval($var_griddepth);
        $L02array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

        $L02Vol -= $var_locvol;
    }

//L02 items have been designated.  Loop through L02 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L02array, ($key - $skippedkeycount - 1));

    $L02array = array_values($L02array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L02array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L02array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L02array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L02array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L02array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L02array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L02array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L02array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L02array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L02array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L02array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L02array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L02array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L02array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L02array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L02array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L02array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L02array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L02array[$counter]['CPCCPKU']);
            $CPCFLOW = $L02array[$counter]['CPCFLOW'];
            $CPCTOTE = $L02array[$counter]['CPCTOTE'];
            $CPCSHLF = $L02array[$counter]['CPCSHLF'];
            $CPCROTA = $L02array[$counter]['CPCROTA'];
            $CPCESTK = intval($L02array[$counter]['CPCESTK']);
            $CPCLIQU = $L02array[$counter]['CPCLIQU'];
            $CPCELEN = $L02array[$counter]['CPCELEN'];
            $CPCEHEI = $L02array[$counter]['CPCEHEI'];
            $CPCEWID = $L02array[$counter]['CPCEWID'];
            $CPCCLEN = $L02array[$counter]['CPCCLEN'];
            $CPCCHEI = $L02array[$counter]['CPCCHEI'];
            $CPCCWID = $L02array[$counter]['CPCCWID'];
            $LMFIXA = $L02array[$counter]['LMFIXA'];
            $LMFIXT = $L02array[$counter]['LMFIXT'];
            $LMSTGT = $L02array[$counter]['LMSTGT'];
            $LMHIGH = intval($L02array[$counter]['LMHIGH']);
            $LMDEEP = intval($L02array[$counter]['LMDEEP']);
            $LMWIDE = intval($L02array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L02array[$counter]['LMVOL9']);
            $LMTIER = $L02array[$counter]['LMTIER'];
            $LMGRD5 = $L02array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = $L02array[$counter]['DLY_CUBE_VEL'];
            $DLY_PICK_VEL = $L02array[$counter]['DLY_PICK_VEL'];
            $SUGGESTED_TIER = $L02array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L02array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L02array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L02array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L02array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L02array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L02array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L02array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L02array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L02array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L02array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L02array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $conn1 = null;
        $maxrange +=1000;
    } while ($counter <= $rowcount);

    $conn1 = null;


//***END OF L02 LOGIC***
//include 'L05update.php';
    $skippedkeycount = 0;

//what is total L05 volume available.  Only used for capacity constraints
    $L05key = array_search('L05', array_column($allvolumearray, 'LMTIER')); //Find 'L05' associated key
//if ($L05key != FALSE) {
//    $L05Vol = intval($allvolumearray[$L05key]['TIER_VOL']);
//} else {

    if ($whssel == 11) {
        $L05Vol = 32000;  //model two drawers for NOTL
    } else {
        $L05Vol = 150000; //remove, just for testing purposes.
    }
//}
    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L05 Grid5s by volume ascending order
    $L05GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L05' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L05GridsSQL->execute();
    $L05GridsArray = $L05GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    if ($L05key == FALSE || count($L05GridsArray) == 0) {
////add drawers for NOTL analysis
        $L05GridsArray[0]['LMGRD5'] = '02D03';
        $L05GridsArray[0]['LMHIGH'] = 2;
        $L05GridsArray[0]['LMDEEP'] = 4;
        $L05GridsArray[0]['LMWIDE'] = 3;
        $L05GridsArray[0]['LMVOL9'] = 24;
        $L05GridsArray[0]['COUNT'] = 10;

        $L05GridsArray[1]['LMGRD5'] = '02D05';
        $L05GridsArray[1]['LMHIGH'] = 2;
        $L05GridsArray[1]['LMDEEP'] = 5;
        $L05GridsArray[1]['LMWIDE'] = 5;
        $L05GridsArray[1]['LMVOL9'] = 50;
        $L05GridsArray[1]['COUNT'] = 10;
    }

    usort($L05GridsArray, 'sortascLMVOL9');

    include '../../CustomerAudit/connection/connection_details.php';
    $L05sql = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    A.DSL_TYPE,
    A.CUR_LOCATION,
    A.DAYS_FRM_SLE,
    A.AVGD_BTW_SLE,
    A.AVG_INV_OH,
    A.NBR_SHIP_OCC,
    A.PICK_QTY_MN,
    A.PICK_QTY_SD,
    A.SHIP_QTY_MN,
    A.SHIP_QTY_SD,
    B.ITEM_TYPE,
    X.CPCEPKU,
    X.CPCIPKU,
    X.CPCCPKU,
    X.CPCFLOW,
    X.CPCTOTE,
    X.CPCSHLF,
    X.CPCROTA,
    X.CPCESTK,
    X.CPCLIQU,
    X.CPCELEN,
    X.CPCEHEI,
    X.CPCEWID,
    X.CPCCLEN,
    X.CPCCHEI,
    X.CPCCWID,
    D.LMFIXA,
    D.LMFIXT,
    D.LMSTGT,
    D.LMHIGH,
    D.LMDEEP,
    D.LMWIDE,
    D.LMVOL9,
    D.LMTIER,
    D.LMGRD5,
    D.CURMAX,
    D.CURMIN,
    case
       when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
       else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
   end as DLY_CUBE_VEL,
   case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                              
    PERC_SHIPQTY,
    PERC_PERC,
    $sql_dailypick as DAILYPICK,
    $sql_dailyunit as DAILYUNIT
FROM
    slotting.mysql_nptsld A
        JOIN
    slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
        and B.ITEM = A.ITEM_NUMBER
        JOIN
    slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
        AND X.CPCITEM = A.ITEM_NUMBER
        JOIN
    slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
        and D.LMITEM = A.ITEM_NUMBER
        and A.PACKAGE_UNIT = D.LMPKGU
        JOIN
    slotting.pkgu_percent E ON E.PERC_WHSE = A.WAREHOUSE
        and E.PERC_ITEM = A.ITEM_NUMBER
        and E.PERC_PKGU = A.PACKAGE_UNIT
        and E.PERC_PKGTYPE = A.PACKAGE_TYPE
        LEFT JOIN
    slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
        and F.ITEM_NUMBER = A.ITEM_NUMBER
        and F.PACKAGE_TYPE = A.PACKAGE_TYPE
        and F.PACKAGE_UNIT = A.PACKAGE_UNIT
WHERE
    A.WAREHOUSE = $whssel
        and (A.AVG_INV_OH * PERC_PERC * 1.2) <= 50
        and A.PACKAGE_TYPE = 'LSE'
        and B.ITEM_TYPE = 'ST'
        and A.NBR_SHIP_OCC >= 4
        and A.AVGD_BTW_SLE > 0
        and A.CUR_LOCATION not like 'Q%'
        and A.CUR_LOCATION not like 'N%'
        and A.AVG_INV_OH > 0
        and F.ITEM_NUMBER IS NULL
        AND A.DSL_TYPE NOT IN (2,4)
ORDER BY ($sql_dailypick) DESC;");

    $L05sql->execute();
    $L05array = $L05sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L05array as $key => $value) {
        if ($L05Vol < 0) {
            break;  //if all available L05 volume has been used, exit
        }
        $ITEM_NUMBER = intval($L05array[$key]['ITEM_NUMBER']);
        $PKGU_PERC_Restriction = $L05array[$key]['PERC_PERC'];


        $var_AVGSHIPQTY = $L05array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L05array[$key]['AVGD_BTW_SLE']);
        if ($AVGD_BTW_SLE == 0) {
            $AVGD_BTW_SLE = 999;
        }

        $var_AVGINV = intval($L05array[$key]['AVG_INV_OH']);
        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L05array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L05array[$key]['CPCEHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L05array[$key]['CPCCHEI'] * 0.393701;
        }

        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = 1;
        }

        $var_PCELENin = $L05array[$key]['CPCELEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L05array[$key]['CPCCLEN'] * 0.393701;
        }

        if ($var_PCELENin == 0) {
            $var_PCELENin = 1;
        }

        $var_PCEWIDin = $L05array[$key]['CPCEWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L05array[$key]['CPCCWID'] * 0.393701;
        }

        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = 1;
        }

        $var_eachqty = $L05array[$key]['CPCEPKU'];
        if ($var_eachqty == 0) {
            $var_eachqty = 1;
        }

        //for drawers, slot to average inventory plus 20% excess
        if ($var_AVGINV == 0) {
            $slotqty = 1;
        } else {
            $slotqty = intval($var_AVGINV * 1.2 * $PKGU_PERC_Restriction);
        }


        //loop through available L05 grids to determine smallest location to accomodate slot quantity
        foreach ($L05GridsArray as $key2 => $value) {
            $var_grid5 = $L05GridsArray[$key2]['LMGRD5'];
            $var_gridheight = $L05GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L05GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L05GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L05GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L04
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($SUGGESTED_MAX_test >= $slotqty) {
                break;
            }
        }

        if ($SUGGESTED_MAX_test < $slotqty) {
            $skippedkeycount +=1;
            unset($L05array[$key]);
            continue;
        }


        $SUGGESTED_MAX = $SUGGESTED_MAX_test;
        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

        //append data to array for writing to my_npfmvc table
        $L05array[$key]['SUGGESTED_TIER'] = 'L05';
        $L05array[$key]['SUGGESTED_GRID5'] = $var_grid5;
        $L05array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L05array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L05array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L05array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L05array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
        $L05array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L05array[$key]['CURMAX'], $L05array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L05array[$key]['SHIP_QTY_MN'], $L05array[$key]['AVGD_BTW_SLE']);
        $L05array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
        $L05array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

        $L05Vol -= $var_locvol;
    }


//L04 items have been designated.  Loop through L04 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L05array, ($key - $skippedkeycount));

    $L05array = array_values($L05array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L05array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L05array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L05array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L05array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L05array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L05array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L05array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L05array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L05array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L05array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L05array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L05array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L05array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L05array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L05array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L05array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L05array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L05array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L05array[$counter]['CPCCPKU']);
            $CPCFLOW = $L05array[$counter]['CPCFLOW'];
            $CPCTOTE = $L05array[$counter]['CPCTOTE'];
            $CPCSHLF = $L05array[$counter]['CPCSHLF'];
            $CPCROTA = $L05array[$counter]['CPCROTA'];
            $CPCESTK = intval($L05array[$counter]['CPCESTK']);
            $CPCLIQU = $L05array[$counter]['CPCLIQU'];
            $CPCELEN = $L05array[$counter]['CPCELEN'];
            $CPCEHEI = $L05array[$counter]['CPCEHEI'];
            $CPCEWID = $L05array[$counter]['CPCEWID'];
            $CPCCLEN = $L05array[$counter]['CPCCLEN'];
            $CPCCHEI = $L05array[$counter]['CPCCHEI'];
            $CPCCWID = $L05array[$counter]['CPCCWID'];
            $LMFIXA = $L05array[$counter]['LMFIXA'];
            $LMFIXT = $L05array[$counter]['LMFIXT'];
            $LMSTGT = $L05array[$counter]['LMSTGT'];
            $LMHIGH = intval($L05array[$counter]['LMHIGH']);
            $LMDEEP = intval($L05array[$counter]['LMDEEP']);
            $LMWIDE = intval($L05array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L05array[$counter]['LMVOL9']);
            $LMTIER = $L05array[$counter]['LMTIER'];
            $LMGRD5 = $L05array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = intval($L05array[$counter]['DLY_CUBE_VEL']);
            $DLY_PICK_VEL = intval($L05array[$counter]['DLY_PICK_VEL']);
            $SUGGESTED_TIER = $L05array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L05array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L05array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L05array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L05array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L05array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L05array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L05array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L05array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L05array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L05array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L05array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $conn1 = null;
        $maxrange +=1000;
    } while ($counter <= $rowcount);
    $conn1 = null;

//***END OF L05 UPDATE LOGIC***
//include 'L04update.php';
    $slowdownsizecutoff = 99999;


    $reconfigured = 4;  //Bay23
    $whse11endcapopp = ((32 - $reconfigured) * 6336);

//what is total L04 volume available.  Only used for capacity constraints
    $L04key = array_search('L04', array_column($allvolumearray, 'LMTIER')); //Find 'L04' associated key
    $L04Vol = intval($allvolumearray[$L04key]['TIER_VOL']);

    $sqlexclude = '';

    if ($whssel == 11) {
        $L04Vol += $whse11endcapopp;
        $sqlexclude = " and A.CUR_LOCATION not like 'B34%' and A.CUR_LOCATION not like 'B35%'";
    } elseif ($whssel == 7) { //endcap opportunity
        $L04Vol += 1175040;
//    $L04Vol += 999999999999999999999;
    } elseif ($whssel == 6) {
        $L04Vol += 3317760;
    }




//*** Step 4: L04 Designation ***

    include '../../CustomerAudit/connection/connection_details.php';
//Pull in available L04 Grid5s by volume ascending order
    $L04GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L04' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
    $L04GridsSQL->execute();
    $L04GridsArray = $L04GridsSQL->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    usort($L04GridsArray, 'sortascLMVOL9');
    include '../../CustomerAudit/connection/connection_details.php';
    $L04sql = $conn1->prepare("SELECT DISTINCT
                                A.WAREHOUSE,
                                A.ITEM_NUMBER,
                                A.PACKAGE_UNIT,
                                A.PACKAGE_TYPE,
                                A.DSL_TYPE,
                                A.CUR_LOCATION,
                                A.DAYS_FRM_SLE,
                                A.AVGD_BTW_SLE,
                                A.AVG_INV_OH,
                                A.NBR_SHIP_OCC,
                                A.PICK_QTY_MN,
                                A.PICK_QTY_SD,
                                A.SHIP_QTY_MN,
                                A.SHIP_QTY_SD,
                                B.ITEM_TYPE,
                                X.CPCEPKU,
                                X.CPCIPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                D.LMFIXA,
                                D.LMFIXT,
                                D.LMSTGT,
                                D.LMHIGH,
                                D.LMDEEP,
                                D.LMWIDE,
                                D.LMVOL9,
                                D.LMTIER,
                                D.LMGRD5,
                                D.CURMAX,
                                D.CURMIN,
                                case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT
                            FROM
                                slotting.mysql_nptsld A
                                    JOIN
                                slotting.itemdesignation B ON B.WHSE = A.WAREHOUSE
                                    and B.ITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                    JOIN
                                slotting.mysql_npflsm D ON D.LMWHSE = A.WAREHOUSE
                                    and D.LMLOC = A.CUR_LOCATION
                                    JOIN
                                slotting.pkgu_percent E on E.PERC_WHSE = A.WAREHOUSE
                                    and E.PERC_ITEM = A.ITEM_NUMBER 
                                    and E.PERC_PKGU = A.PACKAGE_UNIT
                                    and E.PERC_PKGTYPE = A.PACKAGE_TYPE
                                    LEFT JOIN
                                slotting.my_npfmvc F ON F.WAREHOUSE = A.WAREHOUSE
                                    and F.ITEM_NUMBER = A.ITEM_NUMBER
                                    and F.PACKAGE_TYPE = A.PACKAGE_TYPE
                                    and F.PACKAGE_UNIT = A.PACKAGE_UNIT
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE in ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.CUR_LOCATION not like 'Q%'
                                    and A.CUR_LOCATION not like 'N%'
                                    and A.NBR_SHIP_OCC >= 4
                                    AND A.DSL_TYPE NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and F.ITEM_NUMBER IS NULL
                                    $sqlexclude
                            ORDER BY DLY_CUBE_VEL desc");
    $L04sql->execute();
    $L04array = $L04sql->fetchAll(pdo::FETCH_ASSOC);
    $conn1 = null;
    foreach ($L04array as $key => $value) {
        //prevent timeout
        if ($key == 5000 || $key == 10000 || $key == 15000 || $key == 20000 || $key == 25000 || $key == 30000) {
            include '../../CustomerAudit/connection/connection_details.php';

            $sqldelete3 = "DELETE FROM slotting.my_npfmvc  WHERE WAREHOUSE = $whssel and SUGGESTED_TIER = 'L04';";
            $querydelete3 = $conn1->prepare($sqldelete3);
            $querydelete3->execute();
            $conn1 = null;
        }

        if ($L04Vol < 0) {
            break;  //if all available L04 volume has been used, exit
        }
        $ITEM_NUMBER = intval($L04array[$key]['ITEM_NUMBER']);
        //Check OK in Shelf Setting
        $var_OKINSHLF = $L04array[$key]['CPCSHLF'];

        $var_AVGSHIPQTY = $L04array[$key]['SHIP_QTY_MN'];
        $AVGD_BTW_SLE = intval($L04array[$key]['AVGD_BTW_SLE']);
        if ($AVGD_BTW_SLE == 0) {
            $AVGD_BTW_SLE = 999;
        }

        $var_AVGINV = intval($L04array[$key]['AVG_INV_OH']);

        $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
        if ($avgdailyshipqty == 0) {
            $avgdailyshipqty = .000000001;
        }
        $var_PCLIQU = $L04array[$key]['CPCLIQU'];

        $var_PCEHEIin = $L04array[$key]['CPCEHEI'] * 0.393701;
        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = $L04array[$key]['CPCCHEI'] * 0.393701;
        }

        if ($var_PCEHEIin == 0) {
            $var_PCEHEIin = 1;
        }

        $var_PCELENin = $L04array[$key]['CPCELEN'] * 0.393701;
        if ($var_PCELENin == 0) {
            $var_PCELENin = $L04array[$key]['CPCCLEN'] * 0.393701;
        }

        if ($var_PCELENin == 0) {
            $var_PCELENin = 1;
        }

        $var_PCEWIDin = $L04array[$key]['CPCEWID'] * 0.393701;
        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = $L04array[$key]['CPCCWID'] * 0.393701;
        }

        if ($var_PCEWIDin == 0) {
            $var_PCEWIDin = 1;
        }

        $var_eachqty = $L04array[$key]['CPCEPKU'];
        if ($var_eachqty == 0) {
            $var_eachqty = 1;
        }

        switch ($whssel) {
            case 2:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 18;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 13;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            case 11:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 60;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 22;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 12:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 60;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 30;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 22;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 16:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 75;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 35;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 25;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 1;
                }
                break;
            case 7:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 26;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            case 9:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 40;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 2;
                }
                break;
            case 6:
                //Pass 3
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 40;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 20;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 12;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 5;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 2;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 2;
                } else {
                    $daystostock = 2;
                }
                break;
            case 3:
                if ($AVGD_BTW_SLE <= 1) {
                    $daystostock = 26;
                } elseif ($AVGD_BTW_SLE <= 2) {
                    $daystostock = 15;
                } elseif ($AVGD_BTW_SLE <= 3) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 4) {
                    $daystostock = 10;
                } elseif ($AVGD_BTW_SLE <= 5) {
                    $daystostock = 8;
                } elseif ($AVGD_BTW_SLE <= 7) {
                    $daystostock = 6;
                } elseif ($AVGD_BTW_SLE <= 10) {
                    $daystostock = 4;
                } elseif ($AVGD_BTW_SLE <= 15) {
                    $daystostock = 3;
                } elseif ($AVGD_BTW_SLE <= 20) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 25) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 30) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 40) {
                    $daystostock = 1;
                } elseif ($AVGD_BTW_SLE <= 50) {
                    $daystostock = 1;
                } else {
                    $daystostock = 1;
                }
                break;
            default:
                break;
        }

        $PKGU_PERC_Restriction = $L04array[$key]['PERC_PERC'];

        //call slot quantity logic
        $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

        if (isset($slotqty_return_array['CEILQTY'])) {
            $var_pkgu = intval($L04array[$key]['PACKAGE_UNIT']);
            $var_pkty = $L04array[$key]['PACKAGE_TYPE'];
            $optqty = $slotqty_return_array['OPTQTY'];
            $slotqty = $slotqty_return_array['CEILQTY'];
            //write to table inventory_restricted
            include '../../CustomerAudit/connection/connection_details.php';
            $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
            $result2->execute();
            $conn1 = null;
        } else {
            $slotqty = $slotqty_return_array['OPTQTY'];
        }


        if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
            $slotqty = 1;
        } elseif ($slotqty == 0) {
            $slotqty = $var_AVGINV;
        }

        //calculate total slot valume to determine what grid to start
        $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

//    if ($var_OKINSHLF == 'N') {
//        $lastusedgrid5 = '15T11';
//    } else {
//        $lastusedgrid5 = '15S47';
//    }
//    $maxkey = count($L04GridsArray) - 1; //if reach max key and not figured true fit, calc at max
        //loop through available L04 grids to determine smallest location to accomodate slot quantity
        foreach ($L04GridsArray as $key2 => $value) {
            //if total slot volume is less than location volume, then continue
//        if ($totalslotvol > $L04GridsArray[$key2]['LMVOL9']) {
//            continue;
//        }

            $var_grid5 = $L04GridsArray[$key2]['LMGRD5'];
            if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
                continue;
            }
            $var_gridheight = $L04GridsArray[$key2]['LMHIGH'];
            $var_griddepth = $L04GridsArray[$key2]['LMDEEP'];
            $var_gridwidth = $L04GridsArray[$key2]['LMWIDE'];
            $var_locvol = $L04GridsArray[$key2]['LMVOL9'];

            //Call the case true fit for L04
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
            $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

            if ($whssel == 11) {
                $SUGGESTED_MAX_test = intval(floor($SUGGESTED_MAX_test * .95));  //take down suggested max by 5% to correct true fit issues for NOTL
            }

            if ($var_locvol < 100) {  //location is a drawer
                if ($SUGGESTED_MAX_test >= $var_AVGINV) {
                    break;
                }
            } elseif ($var_locvol >= 100) {
                if ($SUGGESTED_MAX_test >= $slotqty) {
                    break;
                }
            }
            //to prevent issue of suggesting a shelf when not accpetable according to OK in flag
            $lastusedgrid5 = $var_grid5;
        }


        $SUGGESTED_MAX = $SUGGESTED_MAX_test;

        //Call the min calc logic
        $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

        //append data to array for writing to my_npfmvc table
        $L04array[$key]['SUGGESTED_TIER'] = 'L04';
        $L04array[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
        $L04array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
        $L04array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
        $L04array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
        $L04array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
        $L04array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
        $L04array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L04array[$key]['CURMAX'], $L04array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
        $L04array[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($lastusedgrid5, 0, 2)) * intval(substr($lastusedgrid5, 3, 2)) * intval($var_griddepth);
        $L04array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
        $L04array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

        $L04Vol -= $var_locvol;
    }

//L04 items have been designated.  Loop through L04 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
    array_splice($L04array, ($key));

    $L04array = array_values($L04array);  //reset array



    $values = array();
    $intranid = 0;
    $maxrange = 999;
    $counter = 0;
    $rowcount = count($L04array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
            $WAREHOUSE = intval($L04array[$counter]['WAREHOUSE']);
            $ITEM_NUMBER = intval($L04array[$counter]['ITEM_NUMBER']);
            $PACKAGE_UNIT = intval($L04array[$counter]['PACKAGE_UNIT']);
            $PACKAGE_TYPE = $L04array[$counter]['PACKAGE_TYPE'];
            $DSL_TYPE = $L04array[$counter]['DSL_TYPE'];
            $CUR_LOCATION = $L04array[$counter]['CUR_LOCATION'];
            $DAYS_FRM_SLE = intval($L04array[$counter]['DAYS_FRM_SLE']);
            $AVGD_BTW_SLE = intval($L04array[$counter]['AVGD_BTW_SLE']);
            $AVG_INV_OH = intval($L04array[$counter]['AVG_INV_OH']);
            $NBR_SHIP_OCC = intval($L04array[$counter]['NBR_SHIP_OCC']);
            $PICK_QTY_MN = intval($L04array[$counter]['PICK_QTY_MN']);
            $PICK_QTY_SD = $L04array[$counter]['PICK_QTY_SD'];
            $SHIP_QTY_MN = intval($L04array[$counter]['SHIP_QTY_MN']);
            $SHIP_QTY_SD = $L04array[$counter]['SHIP_QTY_SD'];
            $ITEM_TYPE = $L04array[$counter]['ITEM_TYPE'];
            $CPCEPKU = intval($L04array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($L04array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($L04array[$counter]['CPCCPKU']);
            $CPCFLOW = $L04array[$counter]['CPCFLOW'];
            $CPCTOTE = $L04array[$counter]['CPCTOTE'];
            $CPCSHLF = $L04array[$counter]['CPCSHLF'];
            $CPCROTA = $L04array[$counter]['CPCROTA'];
            $CPCESTK = intval($L04array[$counter]['CPCESTK']);
            $CPCLIQU = $L04array[$counter]['CPCLIQU'];
            $CPCELEN = $L04array[$counter]['CPCELEN'];
            $CPCEHEI = $L04array[$counter]['CPCEHEI'];
            $CPCEWID = $L04array[$counter]['CPCEWID'];
            $CPCCLEN = $L04array[$counter]['CPCCLEN'];
            $CPCCHEI = $L04array[$counter]['CPCCHEI'];
            $CPCCWID = $L04array[$counter]['CPCCWID'];
            $LMFIXA = $L04array[$counter]['LMFIXA'];
            $LMFIXT = $L04array[$counter]['LMFIXT'];
            $LMSTGT = $L04array[$counter]['LMSTGT'];
            $LMHIGH = intval($L04array[$counter]['LMHIGH']);
            $LMDEEP = intval($L04array[$counter]['LMDEEP']);
            $LMWIDE = intval($L04array[$counter]['LMWIDE']);
            $LMVOL9 = intval($L04array[$counter]['LMVOL9']);
            $LMTIER = $L04array[$counter]['LMTIER'];
            $LMGRD5 = $L04array[$counter]['LMGRD5'];
            $DLY_CUBE_VEL = intval($L04array[$counter]['DLY_CUBE_VEL']);
            $DLY_PICK_VEL = intval($L04array[$counter]['DLY_PICK_VEL']);
            $SUGGESTED_TIER = $L04array[$counter]['SUGGESTED_TIER'];
            $SUGGESTED_GRID5 = $L04array[$counter]['SUGGESTED_GRID5'];
            $SUGGESTED_DEPTH = $L04array[$counter]['SUGGESTED_DEPTH'];
            $SUGGESTED_MAX = intval($L04array[$counter]['SUGGESTED_MAX']);
            $SUGGESTED_MIN = intval($L04array[$counter]['SUGGESTED_MIN']);
            $SUGGESTED_SLOTQTY = intval($L04array[$counter]['SUGGESTED_SLOTQTY']);

            $SUGGESTED_IMPMOVES = ($L04array[$counter]['SUGGESTED_IMPMOVES']);
            $CURRENT_IMPMOVES = ($L04array[$counter]['CURRENT_IMPMOVES']);
            $SUGGESTED_NEWLOCVOL = intval($L04array[$counter]['SUGGESTED_NEWLOCVOL']);
            $SUGGESTED_DAYSTOSTOCK = intval($L04array[$counter]['SUGGESTED_DAYSTOSTOCK']);
            $AVG_DAILY_PICK = $L04array[$counter]['DAILYPICK'];
            $AVG_DAILY_UNIT = $L04array[$counter]['DAILYUNIT'];
            $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT')";
            $counter +=1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        include '../../CustomerAudit/connection/connection_details.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
        $conn1 = null;
    } while ($counter <= $rowcount);

    $conn1 = null;


//***END OF L04 UPDATE LOGIC***
