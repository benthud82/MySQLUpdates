<?php
$JAX_ENDCAP = 0;
if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}
$skippedkeycount = 0;

//what is total L05 volume available.  Only used for capacity constraints
//$L05onholdkey = array_search('L05', array_column($holdvolumearray, 'SUGGESTED_GRID5')); //Find 'L06' associated key in items on hold array to subtract from available volume


include '../connections/conn_slotting.php';
//Pull in available L05 Grid5s by volume ascending order
$L05GridsSQL = $conn1->prepare("SELECT LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5) as GRIDCOUNT FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L05' and CONCAT(LMWHSE, LMTIER, LMGRD5) not in (SELECT gridexcl_key from slotting.gridexclusions WHERE gridexcl_whse = $whssel)GROUP BY LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9 HAVING count(LMGRD5) >= 10 ORDER BY LMVOL9");
$L05GridsSQL->execute();
$L05GridsArray = $L05GridsSQL->fetchAll(pdo::FETCH_ASSOC);

if ($L05key == FALSE || count($L05GridsArray) == 0) {
////add drawers for NOTL analysis
    $L05GridsArray[0]['LMGRD5'] = '02D03';
    $L05GridsArray[0]['LMHIGH'] = 2;
    $L05GridsArray[0]['LMDEEP'] = 4;
    $L05GridsArray[0]['LMWIDE'] = 3;
    $L05GridsArray[0]['LMVOL9'] = 24;
    $L05GridsArray[0]['GRIDCOUNT'] = 300;

    $L05GridsArray[1]['LMGRD5'] = '02D05';
    $L05GridsArray[1]['LMHIGH'] = 2;
    $L05GridsArray[1]['LMDEEP'] = 5;
    $L05GridsArray[1]['LMWIDE'] = 5;
    $L05GridsArray[1]['LMVOL9'] = 50;
    $L05GridsArray[1]['GRIDCOUNT'] = 300;
}

usort($L05GridsArray, 'sortascLMVOL9');


$L05sql = $conn1->prepare("SELECT DISTINCT
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
     --   and (A.AVG_INV_OH * PERC_PERC * 1.2) <= 50
        and A.PACKAGE_TYPE = 'LSE'
        and B.ITEM_TYPE = 'ST'
        and A.NBR_SHIP_OCC >= 4
        and A.AVGD_BTW_SLE > 0
        and A.AVG_INV_OH > 0
        and F.ITEM_NUMBER IS NULL
        AND A.DSL_TYPE NOT IN (2,4)
ORDER BY ($sql_dailypick) DESC;");

$L05sql->execute();
$L05array = $L05sql->fetchAll(pdo::FETCH_ASSOC);

foreach ($L05array as $key => $value) {

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
        $slotqty = intval(ceil($var_AVGINV * 1.2 * $PKGU_PERC_Restriction));
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
            $L05GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L05GridsArray[$key2]['GRIDCOUNT'] <= 0) {
                unset($L05GridsArray[$key2]);
                $L05GridsArray = array_values($L05GridsArray);  //reset array
            }
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

    if (count($L05GridsArray) <= 0) {
        break;
    }
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
        $CUR_LOCATION = $L05array[$counter]['LMLOC'];
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
        $counter +=1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    
    $maxrange +=1000;
} while ($counter <= $rowcount);

