<?php
$whssel = 7;
if (isset($whssel)) {
    $whsefilter = ' and LOWHSE = ' . $whssel;
} else {
    $whsefilter = '';
}

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../../CustomerAudit/connection/connection_details.php';
//include_once '../globalincludes/usa_asys.php';
//include_once '../globalincludes/newcanada_asys.php';
include_once '../../globalfunctions/slottingfunctions.php';
include 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
$reconfigured = 4;  //Bay23
$whse11endcapopp = ((32 - $reconfigured) * 6336);




if (isset($whssel)) {
    $sqldelete = "DELETE FROM slotting.optimalbay WHERE OPT_WHSE = $whssel";
} else {
    $sqldelete = "TRUNCATE TABLE slotting.optimalbay";
}
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$baycube = $conn1->prepare("SELECT 
                                    substring(LMLOC, 4, 2) as BAY, sum(LMVOL9) as BAYVOL
                                FROM
                                    mysql_npflsm
                                WHERE
                                    LMWHSE = $whssel and LMTIER = 'L04'
                                        and substring(LMLOC, 4, 2) not in ('99' , 'ZZ', 'OT')
                                GROUP BY substring(LMLOC, 4, 2)
                                ORDER BY substring(LMLOC, 4, 2)");
$baycube->execute();
$baycubearray = $baycube->fetchAll(pdo::FETCH_ASSOC);

if ($whssel == 11) {
    $baycubearray[0]['BAYVOL'] += $whse11endcapopp;
} elseif ($whssel == 7) { //endcap opportunity
    $baycubearray[0]['BAYVOL'] += 1175040;
} elseif ($whssel == 6) {
    $baycubearray[0]['BAYVOL'] += 3317760;
}

$L01feetsql = $conn1->prepare("SELECT BAY, WALKFEET FROM slotting.vectormap WHERE VECTWHSE = $whssel");
$L01feetsql->execute();
$L01feetarray = $L01feetsql->fetchAll(pdo::FETCH_ASSOC);


//Result set for PPC sorted by highest PPC for items currently in L04
$ppc = $conn1->prepare("SELECT 
                                WAREHOUSE as OPT_WHSE,
                                ITEM_NUMBER as OPT_ITEM,
                                PACKAGE_UNIT as OPT_PKGU,
                                CUR_LOCATION as OPT_LOC,
                                AVGD_BTW_SLE as OPT_ADBS,
                                PACKAGE_TYPE as OPT_CSLS,
                                case
                                    when (CPCELEN * CPCEHEI * CPCEWID) > 0 then (CPCELEN * CPCEHEI * CPCEWID)
                                    else (CPCCLEN * CPCCHEI * CPCCWID)
                                end as OPT_CUBE,
                                LMTIER as OPT_CURTIER,
                                SUGGESTED_TIER as OPT_TOTIER,
                                SUGGESTED_GRID5 as OPT_NEWGRID,
                                SUGGESTED_DEPTH as OPT_NDEP,
                                PICK_QTY_MN as OPT_AVGPICK,
                                $sql_dailypick as OPT_DAILYPICKS,
                                cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH as OPT_NEWGRIDVOL,
                                ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) * 1000 as OPT_PPCCALC
                            FROM
                                my_npfmvc A
                            WHERE
                                WAREHOUSE = $whssel
                                    and SUGGESTED_TIER in ('L04' , 'L02', 'L06', 'L05')
                                    and AVGD_BTW_SLE > 0
                                    and SUGGESTED_DEPTH > 0
                                    and cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) > 0
                                    and cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) > 0
                            ORDER BY ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) DESC");
$ppc->execute();
$ppcarray = $ppc->fetchAll(pdo::FETCH_ASSOC);

//Result set for PPC sorted by highest PPC for items currently in L01
$ppcL01 = $conn1->prepare("SELECT 
                                WAREHOUSE as OPT_WHSE,
                                ITEM_NUMBER as OPT_ITEM,
                                PACKAGE_UNIT as OPT_PKGU,
                                CUR_LOCATION as OPT_LOC,
                                AVGD_BTW_SLE as OPT_ADBS,
                                PACKAGE_TYPE as OPT_CSLS,
                                case
                                    when (CPCELEN * CPCEHEI * CPCEWID) > 0 then (CPCELEN * CPCEHEI * CPCEWID)
                                    else (CPCCLEN * CPCCHEI * CPCCWID)
                                end as OPT_CUBE,
                                LMTIER as OPT_CURTIER,
                                SUGGESTED_TIER as OPT_TOTIER,
                                SUGGESTED_GRID5 as OPT_NEWGRID,
                                SUGGESTED_DEPTH as OPT_NDEP,
                                PICK_QTY_MN as OPT_AVGPICK,
                                $sql_dailypick as OPT_DAILYPICKS,
                                cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH as OPT_NEWGRIDVOL,
                                ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) * 1000 as OPT_PPCCALC
                            FROM
                                my_npfmvc A
                            WHERE
                                WAREHOUSE = $whssel
                                    and SUGGESTED_TIER = ('L01')
                                    and AVGD_BTW_SLE > 0
                                    and SUGGESTED_DEPTH > 0
                                    and cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) > 0
                                    and cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) > 0
                            ORDER BY ($sql_dailypick) DESC");
$ppcL01->execute();
$ppcL01array = $ppcL01->fetchAll(pdo::FETCH_ASSOC);






$columns = 'OPT_WHSE, OPT_ITEM, OPT_PKGU, OPT_LOC, OPT_ADBS, OPT_CSLS, OPT_CUBE, OPT_CURTIER, OPT_TOTIER, OPT_NEWGRID, OPT_NDEP, OPT_AVGPICK, OPT_DAILYPICKS, OPT_NEWGRIDVOL, OPT_PPCCALC, OPT_OPTBAY, OPT_CURRBAY, OPT_CURRDAILYFT, OPT_SHLDDAILYFT, OPT_ADDTLFTPERPICK, OPT_ADDTLFTPERDAY, OPT_WALKCOST';


$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($ppcarray);
$newgrid_runningvol = 0;
$baykey = 0;
$maxbaykey = count($baycubearray) - 1;
$baytotalvolume = intval($baycubearray[$baykey]['BAYVOL']);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) {
        $OPT_TOTIER = $ppcarray[$counter]['OPT_TOTIER'];
        if ($OPT_TOTIER === 'L02' || $OPT_TOTIER == 'L05') {
            $OPT_WHSE = intval($ppcarray[$counter]['OPT_WHSE']);
            $OPT_ITEM = intval($ppcarray[$counter]['OPT_ITEM']);
            $OPT_PKGU = intval($ppcarray[$counter]['OPT_PKGU']);
            $OPT_LOC = $ppcarray[$counter]['OPT_LOC'];
            $OPT_ADBS = intval($ppcarray[$counter]['OPT_ADBS']);
            $OPT_CSLS = $ppcarray[$counter]['OPT_CSLS'];
            $OPT_CUBE = intval($ppcarray[$counter]['OPT_CUBE']);
            $OPT_CURTIER = $ppcarray[$counter]['OPT_CURTIER'];
            $OPT_NEWGRID = $ppcarray[$counter]['OPT_NEWGRID'];
            $OPT_NDEP = intval($ppcarray[$counter]['OPT_NDEP']);
            $OPT_AVGPICK = intval($ppcarray[$counter]['OPT_AVGPICK']);
            $OPT_DAILYPICKS = $ppcarray[$counter]['OPT_DAILYPICKS'];
            $OPT_NEWGRIDVOL = intval($ppcarray[$counter]['OPT_NEWGRIDVOL']);
            $OPT_PPCCALC = $ppcarray[$counter]['OPT_PPCCALC'];
            $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
            $OPT_OPTBAY = intval(0);
            if ($whssel == 11) {
                $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
            } else {
                $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
            }
            $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
            $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
            $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
            $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
            $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
            $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, $OPT_DAILYPICKS, $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST)";
            $counter +=1;
        } else {
            $OPT_WHSE = intval($ppcarray[$counter]['OPT_WHSE']);
            $OPT_ITEM = intval($ppcarray[$counter]['OPT_ITEM']);
            $OPT_PKGU = intval($ppcarray[$counter]['OPT_PKGU']);
            $OPT_LOC = $ppcarray[$counter]['OPT_LOC'];
            $OPT_ADBS = intval($ppcarray[$counter]['OPT_ADBS']);
            $OPT_CSLS = $ppcarray[$counter]['OPT_CSLS'];
            $OPT_CUBE = intval($ppcarray[$counter]['OPT_CUBE']);
            $OPT_CURTIER = $ppcarray[$counter]['OPT_CURTIER'];
            $OPT_NEWGRID = $ppcarray[$counter]['OPT_NEWGRID'];
            $OPT_NDEP = intval($ppcarray[$counter]['OPT_NDEP']);
            $OPT_AVGPICK = intval($ppcarray[$counter]['OPT_AVGPICK']);
            $OPT_DAILYPICKS = $ppcarray[$counter]['OPT_DAILYPICKS'];
            $OPT_NEWGRIDVOL = intval($ppcarray[$counter]['OPT_NEWGRIDVOL']);
            $OPT_PPCCALC = $ppcarray[$counter]['OPT_PPCCALC'];
//                if ($OPT_CURTIER == 'L05') {
//                    $OPT_CURRBAY = 0;  //Have to move SV to endcap.  This is an issue in Reno.  Need to adjust to actual walk time.
//                } else {
            $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
//                }
            $newgrid_runningvol += $OPT_NEWGRIDVOL; //add newgrid vol to running total of newgrid vol

            if ($newgrid_runningvol <= $baytotalvolume) {  //can next item volume fit into current available room?
//                    if ($OPT_TOTIER == 'L05') { //if SV to tier, have to put at endcap.  Walk cost of 0
//                        $OPT_OPTBAY = 0;
//                    } else { //find suggested bay
                $OPT_OPTBAY = intval($baycubearray[$baykey]['BAY']);
//                    }
                if ($whssel == 11) {
                    $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                } else {
                    $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                }
                $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
                $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
                $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
                $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
                $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
                $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, $OPT_DAILYPICKS, $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST)";
                $counter +=1;
            } else { //item cannot fit.  Increase bay key and reset
                if ($baykey < $maxbaykey) {
                    $baykey += 1; //add one to baykey to proceed to next bay
                }
                $newgrid_runningvol = $OPT_NEWGRIDVOL; //reset running total for new grid vol
                $baytotalvolume = intval($baycubearray[$baykey]['BAYVOL']); //reset available bay volume for next bay
//                    if ($OPT_TOTIER == 'L05') { //if SV to tier, have to put at endcap.  Walk cost of 0
//                        $OPT_OPTBAY = 0;
//                    } else { //find suggested bay
                $OPT_OPTBAY = intval($baycubearray[$baykey]['BAY']);
//                    }

                if ($whssel == 11) {
                    $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                } else {
                    $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                }
                $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
                $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
                $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
                $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
                $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
                $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, $OPT_DAILYPICKS, $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST)";
                $counter +=1;
            }
        }
    }

    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO slotting.optimalbay ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);


//update history table

//$sql_hist = "INSERT IGNORE INTO slotting.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
//                 SELECT OPT_WHSE, OPT_CURTIER, CURDATE(), substring(OPT_LOC,1,5) as BAY, sum(OPT_DAILYPICKS), avg(ABS(OPT_WALKCOST)), count(OPT_ITEM) FROM slotting.optimalbay GROUP BY OPT_WHSE, OPT_CURTIER, CURDATE(), substring(OPT_LOC,1,5);";
//$query_hist = $conn1->prepare($sql_hist);
//$query_hist->execute();

