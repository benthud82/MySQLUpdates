<?php

$JAX_ENDCAP = 0;
$slowdownsizecutoff = 99999;
include_once '../globalincludes/usa_asys.php';
include '../connections/conn_slotting.php';
//true l01 count
$L01countsql = $aseriesconn->prepare("SELECT  COUNT(*) as L01COUNT FROM HSIPCORDTA.NPFLSM WHERE LMTIER = 'L01' and LMWHSE = $whssel ");
$L01countsql->execute();
$L01countarray = $L01countsql->fetchAll(pdo::FETCH_ASSOC);
$L01Count = intval($L01countarray[0]['L01COUNT']) - $L01onholdcount;

$L01GridsSQL = $aseriesconn->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, COUNT(*) as GRIDCOUNT FROM HSIPCORDTA.NPFLSM WHERE LMTIER = 'L01' and LMWHSE =  $whssel GROUP BY LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9 ORDER BY LMVOL9");
$L01GridsSQL->execute();
$L01GridsArray = $L01GridsSQL->fetchAll(pdo::FETCH_ASSOC);

//subtract out the held grids from the grids array
$onholdsql = $conn1->prepare("SELECT 
                                                                        HOLDGRID, COUNT(*) as HOLDCOUNT
                                                                    FROM
                                                                        slotting.item_settings
                                                                    WHERE
                                                                        HOLDTIER = 'L01' AND WHSE = $whssel
                                                                    GROUP BY HOLDGRID");
$onholdsql->execute();
$onholdsqlarray = $onholdsql->fetchAll(pdo::FETCH_ASSOC);

foreach ($onholdsqlarray as $key => $value) {
    $onholdkey = array_search($onholdsqlarray[$key]['HOLDGRID'], array_column($L01GridsArray, 'LMGRD5')); //Find Grid5 associated key
    $L01GridsArray[$onholdkey]['GRIDCOUNT'] -= $onholdsqlarray[$key]['HOLDCOUNT'];  //subtract the count of held grids from available grid count
    //remove grid if new count = 0
    if ($L01GridsArray[$onholdkey]['GRIDCOUNT'] == 0) {
        unset($L01GridsArray[$onholdkey]);
        $L01GridsArray = array_values($L01GridsArray);
    }
}




$L01sql = $conn1->prepare("SELECT DISTINCT
                                    A.WAREHOUSE,
                                    A.ITEM_NUMBER,
                                    A.PACKAGE_UNIT,
                                    A.PACKAGE_TYPE,
                                    A.DSL_TYPE,
                                    D.LMLOC,
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
                                    and D.LMITEM = A.ITEM_NUMBER
                                    and D.LMPKGU = A.PACKAGE_UNIT
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
                                        and CPCNEST = 0
                                        and A.PACKAGE_TYPE = ('LSE')
                                        and B.ITEM_TYPE = 'ST'
                                        AND A.DSL_TYPE NOT IN (2,4)
                                        and D.LMTIER <> 'L17'  -- no colgate
                                         and F.ITEM_NUMBER IS NULL
                                         AND D.LMTIER not like 'C%'
                                         and A.ITEM_NUMBER not like '543%'
                                ORDER BY DLY_CUBE_VEL desc
                                LIMIT $L01Count");
$L01sql->execute();
$L01array = $L01sql->fetchAll(pdo::FETCH_ASSOC);

foreach ($L01array as $key => $value) {

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

        $result2 = $conn1->prepare("INSERT INTO slotting.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,$whssel, $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
        $result2->execute();
        
    } else {
        $slotqty = $slotqty_return_array['OPTQTY'];
    }

    //loop through available L01 grids and assign highest cube items to smallest location entire slot qty will fit
    foreach ($L01GridsArray as $key2 => $value) {

        $var_grid5 = $L01GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L01GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L01GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L01GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L01GridsArray[$key2]['LMVOL9'];

        //Call the case true fit for L01
        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, $var_caseqty);
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
                $lastusedgrid5 = $var_grid5;
                break;
            }
        }
        //to prevent issue of suggesting a shelf when not accpetable according to OK in flag
        $lastusedgrid5 = $var_grid5;
    }

    $L01GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
    if ($L01GridsArray[$key2]['GRIDCOUNT'] <= 0) {
        unset($L01GridsArray[$key2]);
        $L01GridsArray = array_values($L01GridsArray);  //reset array
    }

    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_caseqty));

    //append data to array for writing to my_npfmvc table
    $L01array[$key]['SUGGESTED_TIER'] = 'L01';
    $L01array[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
    $L01array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L01array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L01array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L01array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L01array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
    $L01array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L01array[$key]['CURMAX'], $L01array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L01array[$key]['SHIP_QTY_MN'], $L01array[$key]['AVGD_BTW_SLE']);
    $L01array[$key]['SUGGESTED_NEWLOCVOL'] = intval($var_locvol);
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
        $CUR_LOCATION = $L01array[$counter]['LMLOC'];
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
        if ($LMTIER == 'L01' || $LMTIER == 'L15') {
            $VCBAY = $CUR_LOCATION;
        } else if ($LMTIER == 'L05' && $WAREHOUSE == 3) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '12';
        } else if ($LMTIER == 'L05' ) {
            $VCBAY = substr($CUR_LOCATION, 0, 3) . '01';
        } else {
            $VCBAY = substr($CUR_LOCATION, 0, 5);
        }
        $data[] = "($WAREHOUSE,$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$DSL_TYPE','$CUR_LOCATION',$DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,'$ITEM_TYPE',$CPCEPKU,$CPCIPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMFIXA','$LMFIXT','$LMSTGT',$LMHIGH,$LMDEEP,$LMWIDE,$LMVOL9,'$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5',$SUGGESTED_DEPTH,$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES',$SUGGESTED_NEWLOCVOL,$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', '$VCBAY', $JAX_ENDCAP)";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 1000;
    
} while ($counter <= $rowcount);

