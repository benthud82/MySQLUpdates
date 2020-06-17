<?php

if (isset($whssel)) {
    $whsefilter = ' and LOWHSE = ' . $whssel;
} else {
    $whsefilter = '';
}
$data = array();
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';
include_once '../globalfunctions/slottingfunctions.php';
include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
$reconfigured = 10;  //Bay23
$whse11endcapopp = ((32 - $reconfigured) * 6336);

$OPT_BUILDING = intval(1);

if ($whssel == 11 || $whssel == 12 || $whssel == 16) {
    $useconn = $aseriesconn_can;
    $useschema = 'ARCPCORDTA';
} else {
    $useconn = $aseriesconn;
    $useschema = 'HSIPCORDTA';
}


if (isset($whssel)) {
    $sqldelete = "DELETE FROM slotting.optimalbay WHERE OPT_WHSE = $whssel and OPT_CSLS LIKE 'L%'";
} else {
    $sqldelete = "TRUNCATE TABLE slotting.optimalbay";
}
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$baycube = $useconn->prepare("SELECT 
                                    substring(LMLOC#, 4, 2) as BAY, sum(LMVOL9) as BAYVOL
                                FROM
                                    $useschema.NPFLSM
                                WHERE
                                    LMWHSE = $whssel and LMTIER = 'L04'
                                        and substring(LMLOC#, 4, 2) not in ('99' , 'ZZ', 'OT') and LMSLR# not in ('1', '2','4')
                                GROUP BY substring(LMLOC#, 4, 2)  HAVING sum(LMVOL9)  >= 250000
                                ORDER BY substring(LMLOC#, 4, 2)");
$baycube->execute();
$baycubearray = $baycube->fetchAll(pdo::FETCH_ASSOC);

//subtract cube from items on hold from L04 cube
$holdcube = $conn1->prepare("SELECT 
                                    substring(HOLDLOCATION, 4, 2) as HOLDBAY, sum(LMVOL9) as HOLDBAYVOL
                                FROM
                                    slotting.item_settings
                                 JOIN slotting.mysql_npflsm on LMWHSE = WHSE and LMLOC = HOLDLOCATION
                                WHERE
                                    WHSE = $whssel and HOLDTIER = 'L04'
                                GROUP BY substring(HOLDLOCATION, 4, 2)");
$holdcube->execute();
$holdcubearray = $holdcube->fetchAll(pdo::FETCH_ASSOC);

foreach ($holdcubearray as $key => $value) {
    $bay = $holdcubearray[$key]['HOLDBAY'];
    $baysubtractkey = array_search($bay, array_column($baycubearray, 'BAY'));
    $baycubearray[$baysubtractkey]['BAYVOL'] = $baycubearray[$baysubtractkey]['BAYVOL'] - $holdcubearray[$key]['HOLDBAYVOL'];
}




if ($whssel == 11) {
    $baycubearray[0]['BAYVOL'] += $whse11endcapopp;
//} elseif ($whssel == 7) { //endcap opportunity
//    $baycubearray[0]['BAYVOL'] += 138240;  //two additional endcaps from drug room
//} elseif ($whssel == 6) {
//    $baycubearray[0]['BAYVOL'] += 3317760;
}

//For Jax, 
//Result set for PPC sorted by highest PPC for items currently in L04
$ppc = $conn1->prepare("SELECT 
                                A.WAREHOUSE as OPT_WHSE,
                                A.ITEM_NUMBER as OPT_ITEM,
                                A.PACKAGE_UNIT as OPT_PKGU,
                                A.CUR_LOCATION as OPT_LOC,
                                A.AVGD_BTW_SLE as OPT_ADBS,
                                A.PACKAGE_TYPE as OPT_CSLS,
                                case
                                    when (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 then (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
                                end as OPT_CUBE,
                                A.LMTIER as OPT_CURTIER,
                                A.SUGGESTED_TIER as OPT_TOTIER,
                                A.SUGGESTED_GRID5 as OPT_NEWGRID,
                                A.SUGGESTED_DEPTH as OPT_NDEP,
                                A.PICK_QTY_MN as OPT_AVGPICK,
                                $sql_dailypick as OPT_DAILYPICKS,
                                cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH as OPT_NEWGRIDVOL,
                                ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) * 1000 as OPT_PPCCALC,
                                V.WALKFEET as CURWALKFEET,
                                HOLDTIER,
                                HOLDGRID,
                                HOLDLOCATION
                            FROM
                                slotting.my_npfmvc A
                            JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                           LEFT  JOIN slotting.vectormap V on WAREHOUSE = V.VECTWHSE and VCBAY = V.BAY
                           LEFT JOIN
                                slotting.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                            WHERE
                                WAREHOUSE = $whssel
                                    and SUGGESTED_TIER in ('L04' , 'L02', 'L06', 'L05')
                                    and AVGD_BTW_SLE > 0
                                    and SUGGESTED_DEPTH > 0
                                    and cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) > 0
                                    and cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) > 0
                                    and JAX_ENDCAP = 1   -- only include jax endcap items
                            ORDER BY ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) DESC, A.SUGGESTED_NEWLOCVOL ASC");
$ppc->execute();
$ppcarray_jaxendcap = $ppc->fetchAll(pdo::FETCH_ASSOC);


//Result set for PPC sorted by highest PPC for items currently in L04
$ppc = $conn1->prepare("SELECT 
                                A.WAREHOUSE as OPT_WHSE,
                                A.ITEM_NUMBER as OPT_ITEM,
                                A.PACKAGE_UNIT as OPT_PKGU,
                                A.CUR_LOCATION as OPT_LOC,
                                A.AVGD_BTW_SLE as OPT_ADBS,
                                A.PACKAGE_TYPE as OPT_CSLS,
                                case
                                    when (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 then (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
                                end as OPT_CUBE,
                                A.LMTIER as OPT_CURTIER,
                                A.SUGGESTED_TIER as OPT_TOTIER,
                                A.SUGGESTED_GRID5 as OPT_NEWGRID,
                                A.SUGGESTED_DEPTH as OPT_NDEP,
                                A.PICK_QTY_MN as OPT_AVGPICK,
                                $sql_dailypick as OPT_DAILYPICKS,
                                cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH as OPT_NEWGRIDVOL,
                                ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) * 1000 as OPT_PPCCALC,
                                V.WALKFEET as CURWALKFEET,
                                HOLDTIER,
                                HOLDGRID,
                                HOLDLOCATION
                            FROM
                                slotting.my_npfmvc A
                            JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                           LEFT  JOIN slotting.vectormap V on WAREHOUSE = V.VECTWHSE and VCBAY = V.BAY
                           LEFT JOIN
                                slotting.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                            WHERE
                                WAREHOUSE = $whssel
                                    and SUGGESTED_TIER in ('L04' , 'L02', 'L06', 'L05')
                                    and AVGD_BTW_SLE > 0
                                    and SUGGESTED_DEPTH > 0
                                    and cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) > 0
                                    and cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) > 0
                                    and JAX_ENDCAP = 0   -- exlude jax endcap items
                            ORDER BY ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) DESC, A.SUGGESTED_NEWLOCVOL ASC");
$ppc->execute();
$ppcarray = $ppc->fetchAll(pdo::FETCH_ASSOC);


//Result set for PPC sorted by highest PPC for items currently in L01
$ppcL01 = $conn1->prepare("SELECT 
                                    WAREHOUSE as OPT_WHSE,
                                    ITEM_NUMBER as OPT_ITEM,
                                    PACKAGE_UNIT as OPT_PKGU,
                                    CUR_LOCATION as OPT_LOC,
                                    AVGD_BTW_SLE as OPT_ADBS,
                                    A.PACKAGE_TYPE as OPT_CSLS,
                                    case
                                        when (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 then (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                        else (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
                                    end as OPT_CUBE,
                                    LMTIER as OPT_CURTIER,
                                    SUGGESTED_TIER as OPT_TOTIER,
                                    SUGGESTED_GRID5 as OPT_NEWGRID,
                                    SUGGESTED_DEPTH as OPT_NDEP,
                                    PICK_QTY_MN as OPT_AVGPICK,
                                    $sql_dailypick as OPT_DAILYPICKS,
                                    cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH as OPT_NEWGRIDVOL,
                                    ($sql_dailypick) / (cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) * cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) * SUGGESTED_DEPTH) * 1000 as OPT_PPCCALC,
                                    V.WALKFEET as CURWALKFEET,
                                    S.HOLDLOCATION
                                FROM
                                    slotting.my_npfmvc A
                                LEFT JOIN
                                slotting.npfcpcsettings X ON X.CPCWHSE = A.WAREHOUSE
                                    AND X.CPCITEM = A.ITEM_NUMBER
                                LEFT JOIN slotting.vectormap V on WAREHOUSE = V.VECTWHSE and VCBAY = V.BAY
                           LEFT JOIN
                                slotting.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                                WHERE
                                    WAREHOUSE = $whssel
                                        and SUGGESTED_TIER = ('L01')
                                      --  and AVGD_BTW_SLE > 0
                                      --  and SUGGESTED_DEPTH > 0
                                      --  and cast(substring(SUGGESTED_GRID5, 4, 2) as UNSIGNED) > 0
                                      --  and cast(substring(SUGGESTED_GRID5, 1, 2) as UNSIGNED) > 0
                                ORDER BY ($sql_dailypick) DESC");
$ppcL01->execute();
$ppcL01array = $ppcL01->fetchAll(pdo::FETCH_ASSOC);




//L01 Locations in ascending walkfeet to match with highest picked L01 Recs
$L01Locs = $conn1->prepare("SELECT 
                                                        V.BAY AS LMLOC, V.WALKFEET, LMGRD5, LMDEEP
                                                    FROM
                                                        slotting.vectormap V
                                                            LEFT JOIN
                                                        slotting.mysql_npflsm ON LMWHSE = VECTWHSE AND LMBAY = V.BAY
                                                    WHERE
                                                        VECTWHSE = $whssel AND TIER = 'L01'
                                                            AND V.BAY NOT IN (SELECT 
                                                                HOLDLOCATION
                                                            FROM
                                                                slotting.item_settings
                                                            WHERE
                                                                WHSE = $whssel)
                                                    ORDER BY V.WALKFEET ASC");
$L01Locs->execute();
$L01Locsarray = $L01Locs->fetchAll(pdo::FETCH_ASSOC);



//assign L01s to specific location
foreach ($ppcL01array as $key => $value) {
//is there a hold location?
    $testloc = $ppcL01array[$key]['HOLDLOCATION'];

    $OPT_NEWGRID = $ppcL01array[$key]['OPT_NEWGRID'];
    $OPT_NDEP = intval($ppcL01array[$key]['OPT_NDEP']);



    if (!is_null($testloc) && $testloc <> '') {
        $OPT_LOCATION = $testloc;
        $OPT_Shouldwalkfeet = intval($ppcL01array[$key]['CURWALKFEET']); //since location is held, current walk feet = should walk feet
    } else if (count($L01Locsarray > 0)) {
        //need to verify the location size matches

        foreach ($L01Locsarray as $key2 => $value) {//loop through L01 non-assigned grids
            $l01grid = $L01Locsarray[$key2]['LMGRD5'];
            $l01depth = intval($L01Locsarray[$key2]['LMDEEP']);
            if ($OPT_NEWGRID == $l01grid && $l01depth == $OPT_NDEP) {
                $OPT_LOCATION = $L01Locsarray[$key2]['LMLOC'];
                $OPT_Shouldwalkfeet = $L01Locsarray[$key2]['WALKFEET'];  //Optimal walk feet per pick
                unset($L01Locsarray[$key2]);
                $L01Locsarray = array_values($L01Locsarray);
                break;
            }
        }
    } else {
        $OPT_LOCATION = '';
    }


    $OPT_TOTIER = $ppcL01array[$key]['OPT_TOTIER'];
    $OPT_WHSE = intval($ppcL01array[$key]['OPT_WHSE']);
    $OPT_ITEM = intval($ppcL01array[$key]['OPT_ITEM']);
    $OPT_PKGU = intval($ppcL01array[$key]['OPT_PKGU']);
    $OPT_LOC = $ppcL01array[$key]['OPT_LOC'];
    $OPT_ADBS = intval($ppcL01array[$key]['OPT_ADBS']);
    $OPT_CSLS = $ppcL01array[$key]['OPT_CSLS'];
    $OPT_CUBE = intval($ppcL01array[$key]['OPT_CUBE']);
    $OPT_CURTIER = $ppcL01array[$key]['OPT_CURTIER'];
    $OPT_AVGPICK = intval($ppcL01array[$key]['OPT_AVGPICK']);
    $OPT_DAILYPICKS = number_format($ppcL01array[$key]['OPT_DAILYPICKS'], 2);
    $OPT_NEWGRIDVOL = intval($ppcL01array[$key]['OPT_NEWGRIDVOL']);
    $OPT_PPCCALC = $ppcL01array[$key]['OPT_PPCCALC'];
    $currentfeetperpick = intval($ppcL01array[$key]['CURWALKFEET']);
    $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
    $OPT_OPTBAY = intval(0);

    $walkcostarray = _walkcost_feet($currentfeetperpick, $OPT_Shouldwalkfeet, $OPT_DAILYPICKS);

    $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
    $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
    $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
    $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
    $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];

    $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, '$OPT_PPCCALC', $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, '$OPT_LOCATION',$OPT_BUILDING)";
}
$columns = 'OPT_WHSE, OPT_ITEM, OPT_PKGU, OPT_LOC, OPT_ADBS, OPT_CSLS, OPT_CUBE, OPT_CURTIER, OPT_TOTIER, OPT_NEWGRID, OPT_NDEP, OPT_AVGPICK, OPT_DAILYPICKS, OPT_NEWGRIDVOL, OPT_PPCCALC, OPT_OPTBAY, OPT_CURRBAY, OPT_CURRDAILYFT, OPT_SHLDDAILYFT, OPT_ADDTLFTPERPICK, OPT_ADDTLFTPERDAY, OPT_WALKCOST, OPT_LOCATION, OPT_BUILDING';
$valuesl01 = array();
$valuesl01 = implode(',', $data);

if (!empty($valuesl01)) {
    include '../connections/conn_slotting.php';

    $sql = "INSERT IGNORE INTO slotting.optimalbay ($columns) VALUES $valuesl01";
    $query = $conn1->prepare($sql);
    $query->execute();
}




//if jacsonville, assign endcaps
If ($whssel === 9) {
    foreach ($ppcarray_jaxendcap as $key => $value) {


        $OPT_TOTIER = $ppcarray_jaxendcap[$key]['OPT_TOTIER'];
        $OPT_WHSE = intval($ppcarray_jaxendcap[$key]['OPT_WHSE']);
        $OPT_ITEM = intval($ppcarray_jaxendcap[$key]['OPT_ITEM']);
        if ($OPT_ITEM == 1091113) {
            echo '';
        }
        $OPT_PKGU = intval($ppcarray_jaxendcap[$key]['OPT_PKGU']);
        $OPT_LOC = $ppcarray_jaxendcap[$key]['OPT_LOC'];
        $OPT_ADBS = intval($ppcarray_jaxendcap[$key]['OPT_ADBS']);
        $OPT_CSLS = $ppcarray_jaxendcap[$key]['OPT_CSLS'];
        $OPT_CUBE = intval($ppcarray_jaxendcap[$key]['OPT_CUBE']);
        $OPT_CURTIER = $ppcarray_jaxendcap[$key]['OPT_CURTIER'];
        $OPT_NEWGRID = $ppcarray_jaxendcap[$key]['OPT_NEWGRID'];
        $OPT_NDEP = intval($ppcarray_jaxendcap[$key]['OPT_NDEP']);
        $OPT_AVGPICK = intval($ppcarray_jaxendcap[$key]['OPT_AVGPICK']);
        $OPT_DAILYPICKS = number_format($ppcarray_jaxendcap[$key]['OPT_DAILYPICKS'], 2);
        $OPT_NEWGRIDVOL = intval($ppcarray_jaxendcap[$key]['OPT_NEWGRIDVOL']);
        $OPT_PPCCALC = $ppcarray_jaxendcap[$key]['OPT_PPCCALC'];
        $CURRFEET = $ppcarray_jaxendcap[$key]['CURWALKFEET'];
        $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
        $OPT_OPTBAY = intval(1);
        $walkcostarray = _walkcost_JAX($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
        $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
        $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
        $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
        $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
        $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
        $OPT_LOCATION = '';
        $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, '$OPT_LOCATION',$OPT_BUILDING)";
        $counter += 1;
    }
    $columns = 'OPT_WHSE, OPT_ITEM, OPT_PKGU, OPT_LOC, OPT_ADBS, OPT_CSLS, OPT_CUBE, OPT_CURTIER, OPT_TOTIER, OPT_NEWGRID, OPT_NDEP, OPT_AVGPICK, OPT_DAILYPICKS, OPT_NEWGRIDVOL, OPT_PPCCALC, OPT_OPTBAY, OPT_CURRBAY, OPT_CURRDAILYFT, OPT_SHLDDAILYFT, OPT_ADDTLFTPERPICK, OPT_ADDTLFTPERDAY, OPT_WALKCOST, OPT_LOCATION, OPT_BUILDING';
    $values_jaxendcap = array();
    $values_jaxendcap = implode(',', $data);

    if (!empty($values_jaxendcap)) {
        include '../connections/conn_slotting.php';

        $sql = "INSERT IGNORE INTO slotting.optimalbay ($columns) VALUES $values_jaxendcap";
        $query = $conn1->prepare($sql);
        $query->execute();
    }
} //end of assigning jax endcaps



$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($ppcarray);
$newgrid_runningvol = 0;
$baykey = 0;
$maxbaykey = count($baycubearray) - 1;
$baytotalvolume = intval($baycubearray[$baykey]['BAYVOL']);

//for jax, endcap items have already been assigned, Add one to the array key
if ($whssel === 9) {
    $baykey += 1; //add one to baykey to proceed to next bay
}

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
            $OPT_DAILYPICKS = number_format($ppcarray[$counter]['OPT_DAILYPICKS'], 2);
            $OPT_NEWGRIDVOL = intval($ppcarray[$counter]['OPT_NEWGRIDVOL']);
            $OPT_PPCCALC = $ppcarray[$counter]['OPT_PPCCALC'];
            $CURRFEET = $ppcarray[$counter]['CURWALKFEET'];

            $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
            $OPT_OPTBAY = intval(0);

            if ($whssel == 11) {
                $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
            } else if ($whssel == 9) {
                $walkcostarray = _walkcost_JAX($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
            } else {
                $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
            }
            $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
            $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
            $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
            $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
            $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
            $OPT_LOCATION = '';
            $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, '$OPT_LOCATION',$OPT_BUILDING)";
            $counter += 1;
        } else {

            //not L01, L02, or L05
            $OPT_WHSE = intval($ppcarray[$counter]['OPT_WHSE']);
            $OPT_ITEM = intval($ppcarray[$counter]['OPT_ITEM']);
            if ($OPT_ITEM == 2480408) {
                echo 'y';
            }
            $OPT_PKGU = intval($ppcarray[$counter]['OPT_PKGU']);
            $OPT_LOC = $ppcarray[$counter]['OPT_LOC'];
            $OPT_ADBS = intval($ppcarray[$counter]['OPT_ADBS']);
            $OPT_CSLS = $ppcarray[$counter]['OPT_CSLS'];
            $OPT_CUBE = intval($ppcarray[$counter]['OPT_CUBE']);
            $OPT_CURTIER = $ppcarray[$counter]['OPT_CURTIER'];
            $OPT_NEWGRID = $ppcarray[$counter]['OPT_NEWGRID'];
            $OPT_NDEP = intval($ppcarray[$counter]['OPT_NDEP']);
            $OPT_AVGPICK = intval($ppcarray[$counter]['OPT_AVGPICK']);
            $OPT_DAILYPICKS = number_format($ppcarray[$counter]['OPT_DAILYPICKS'], 2);
            $OPT_NEWGRIDVOL = intval($ppcarray[$counter]['OPT_NEWGRIDVOL']);
            $OPT_PPCCALC = $ppcarray[$counter]['OPT_PPCCALC'];
            $OPT_CURRBAY = intval(substr($OPT_LOC, 3, 2));
            $OPT_LOCATION = '';
            $CURRFEET = $ppcarray[$counter]['CURWALKFEET'];
            $HOLDLOC = $ppcarray[$counter]['HOLDLOCATION'];
            if (is_null($HOLDLOC)) { //if location is held, the volume is already subtracted out of the available volume by bay
                $newgrid_runningvol += $OPT_NEWGRIDVOL; //add newgrid vol to running total of newgrid vol
            }

            if ($newgrid_runningvol <= $baytotalvolume) {  //can next item volume fit into current available room?
                if (is_null($HOLDLOC) || $HOLDLOC == '') {
                    $OPT_OPTBAY = intval($baycubearray[$baykey]['BAY']);
                } else {
                    $OPT_OPTBAY = intval(substr($HOLDLOC, 3, 2));
                }




                if ($whssel == 11) {
                    $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                } else if ($whssel == 9) {
                    $walkcostarray = _walkcost_JAX($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
                } else {
                    $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
                }
                $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
                $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
                $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
                $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
                $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
                $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, '$OPT_LOCATION',$OPT_BUILDING)";
                $counter += 1;
            } else { //item cannot fit.  Increase bay key and reset
                if ($baykey < $maxbaykey) {
                    $baykey += 1; //add one to baykey to proceed to next bay
                }
                $CURRFEET = $ppcarray[$counter]['CURWALKFEET'];
                $newgrid_runningvol = $OPT_NEWGRIDVOL; //reset running total for new grid vol
                $baytotalvolume = intval($baycubearray[$baykey]['BAYVOL']); //reset available bay volume for next bay
                if (is_null($HOLDLOC)) {
                    $OPT_OPTBAY = intval($baycubearray[$baykey]['BAY']);
                } else {
                    $OPT_OPTBAY = intval(substr($HOLDLOC, 3, 2));
                }

                if ($whssel == 11) {
                    $walkcostarray = _walkcost_NOTL($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS);
                } else {
                    $walkcostarray = _walkcost($OPT_CURRBAY, $OPT_OPTBAY, $OPT_DAILYPICKS, $CURRFEET);
                }
                $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
                $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
                $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
                $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
                $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
                $data[] = "($OPT_WHSE, $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, $OPT_PPCCALC, $OPT_OPTBAY, $OPT_CURRBAY, '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', $OPT_WALKCOST, '$OPT_LOCATION',$OPT_BUILDING)";
                $counter += 1;
            }
        }
    }

    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    include '../connections/conn_slotting.php';
    $sql = "INSERT IGNORE INTO slotting.optimalbay ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $maxrange += 4000;
} while ($counter <= $rowcount);

//update history table
include '../connections/conn_slotting.php';
$sql_hist = "INSERT IGNORE INTO slotting.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
                 SELECT 
                    OPT_WHSE,
                    OPT_CURTIER,
                    CURDATE(),
                    case
                        when OPT_CURTIER = 'L05' then concat(substring(OPT_LOC, 1, 3), '01')
                        else substring(OPT_LOC, 1, 5)
                    end as BAY,
                    sum(OPT_DAILYPICKS),
                    avg(ABS(OPT_WALKCOST)),
                    count(OPT_ITEM)
                FROM
                    slotting.optimalbay
                WHERE
                    OPT_CURTIER <> 'L01'
                GROUP BY OPT_WHSE , OPT_CURTIER , CURDATE() , case
                    when OPT_CURTIER = 'L05' then concat(substring(OPT_LOC, 1, 3), '01')
                    else substring(OPT_LOC, 1, 5)
                end;";
$query_hist = $conn1->prepare($sql_hist);
$query_hist->execute();

$sql_hist2 = "INSERT IGNORE INTO slotting.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
                 SELECT OPT_WHSE, OPT_CURTIER, CURDATE(), OPT_LOC as BAY, sum(OPT_DAILYPICKS), avg(ABS(OPT_WALKCOST)), count(OPT_ITEM) FROM slotting.optimalbay WHERE OPT_CURTIER = 'L01'  GROUP BY OPT_WHSE, OPT_CURTIER, CURDATE(), OPT_LOC;";
$query_hist2 = $conn1->prepare($sql_hist2);
$query_hist2->execute();

//add all others that weren't calculated.  Since using insert igore, can pull in all locations
$sql_hist3 = "INSERT IGNORE INTO slotting.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
SELECT 
    WAREHOUSE,
    LMTIER,
    CURDATE(),
    CASE
        WHEN LMTIER in ('L15', 'L01') THEN CUR_LOCATION
        ELSE SUBSTRING(CUR_LOCATION, 1, 5)
    END AS BAY,
    SUM(CASE
        WHEN AVGD_BTW_SLE >= 365 THEN 0
        WHEN DAYS_FRM_SLE >= 180 THEN 0
        WHEN
            PICK_QTY_MN > SHIP_QTY_MN
        THEN
            (SHIP_QTY_MN / (CASE
                WHEN CPCCPKU > 0 THEN CPCCPKU
                ELSE 1
            END)) / AVGD_BTW_SLE
        WHEN AVGD_BTW_SLE = 0 AND DAYS_FRM_SLE = 0 THEN PICK_QTY_MN
        WHEN AVGD_BTW_SLE = 0 THEN (PICK_QTY_MN / DAYS_FRM_SLE)
        ELSE (PICK_QTY_MN / AVGD_BTW_SLE)
    END) AS PICKSSUM,
    0 AS COST,
    0 AS CNT
FROM
    slotting.mysql_nptsld
        JOIN
    slotting.npfcpcsettings ON CPCWHSE = WAREHOUSE
        AND ITEM_NUMBER = CPCITEM
        JOIN
    slotting.mysql_npflsm ON LMWHSE = WAREHOUSE
        AND LMITEM = ITEM_NUMBER
        AND LMLOC = CUR_LOCATION
GROUP BY WAREHOUSE , LMTIER , CURDATE() , CASE
    WHEN LMTIER in ('L15', 'L01') THEN CUR_LOCATION
    ELSE SUBSTRING(CUR_LOCATION, 1, 5)
END;";
$query_hist3 = $conn1->prepare($sql_hist3);
$query_hist3->execute();
