
<?php
$JAX_ENDCAP = 1;

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

$slowdownsizecutoff = 99999;
include '../connections/conn_slotting.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalfunctions/slottingfunctions.php';

////Total L04 endcap volume available for Jax
//$jaxendcapvolsql = $conn1->prepare("SELECT  sum(LMVOL9) as TIER_VOL_ENDCAP FROM slotting.mysql_npflsm WHERE LMWHSE = $whssel and LMTIER = 'L04' and substring(LMLOC,4,2) = '01' ");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
//$jaxendcapvolsql->execute();
//$jaxendcapvolarray = $jaxendcapvolsql->fetchAll(pdo::FETCH_ASSOC);
//
//$jaxendcapvol = intval($jaxendcapvolarray[0]['TIER_VOL_ENDCAP']);
//$sqlexclude = '';
////*** Step 4: L04 Designation ***
////include '../connections/conn_slotting.php';
////Pull in available L04 Grid5s by volume ascending order
////$L04GridsSQL = $conn1->prepare("SELECT 
////                                    LMGRD5, LMHIGH, LMDEEP, LMWIDE, LMVOL9, count(LMGRD5)
////                                FROM
////                                    slotting.mysql_npflsm
////                                WHERE
////                                    LMWHSE = $whssel and LMTIER = 'L04' and LMGRD5 <> ' '
////                                        and CONCAT(LMWHSE, LMTIER, LMGRD5, LMDEEP) not in (SELECT 
////                                            gridexcl_key
////                                        from
////                                            slotting.gridexclusions
////                                        WHERE
////                                            gridexcl_whse = $whssel)
////                                GROUP BY LMGRD5 , LMHIGH , LMDEEP , LMWIDE , LMVOL9
////                                HAVING count(LMGRD5) >= 10
////                                ORDER BY LMVOL9");
////$L04GridsSQL->execute();
////$L04GridsArray = $L04GridsSQL->fetchAll(pdo::FETCH_ASSOC);
////
////usort($L04GridsArray, 'sortascLMVOL9');
////using standardized locations for the endcaps
////******* add these standardized grid sizes to the L04 and run through as normal.  Will only allow 48 inch shelfs on the endcaps  ********



$baycube = $aseriesconn->prepare("SELECT 
                                    substring(LMLOC#, 4, 2) as BAY, sum(LMVOL9) as BAYVOL
                                FROM
                                    HSIPCORDTA.NPFLSM
                                WHERE
                                    LMWHSE = $whssel and LMTIER = 'L04'
                                        and substring(LMLOC#, 4, 2) not in ('99' , 'ZZ', 'OT') and LMSLR# not in ('1', '2','4')
                                GROUP BY substring(LMLOC#, 4, 2)
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

$jaxendcapvol = $baycubearray[0]['BAYVOL'];





$L04GridsArray_endcap = array();
$L04GridsArray_endcap[0]['LMGRD5'] = '06T04';
$L04GridsArray_endcap[0]['LMHIGH'] = 6;
$L04GridsArray_endcap[0]['LMDEEP'] = 6;
$L04GridsArray_endcap[0]['LMWIDE'] = 4;
$L04GridsArray_endcap[0]['LMVOL9'] = 144;
$L04GridsArray_endcap[0]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[1]['LMGRD5'] = '06T06';
$L04GridsArray_endcap[1]['LMHIGH'] = 6;
$L04GridsArray_endcap[1]['LMDEEP'] = 6;
$L04GridsArray_endcap[1]['LMWIDE'] = 6;
$L04GridsArray_endcap[1]['LMVOL9'] = 216;
$L04GridsArray_endcap[1]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[2]['LMGRD5'] = '06T04';
$L04GridsArray_endcap[2]['LMHIGH'] = 6;
$L04GridsArray_endcap[2]['LMDEEP'] = 24;
$L04GridsArray_endcap[2]['LMWIDE'] = 4;
$L04GridsArray_endcap[2]['LMVOL9'] = 576;
$L04GridsArray_endcap[2]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[3]['LMGRD5'] = '06T06';
$L04GridsArray_endcap[3]['LMHIGH'] = 6;
$L04GridsArray_endcap[3]['LMDEEP'] = 24;
$L04GridsArray_endcap[3]['LMWIDE'] = 6;
$L04GridsArray_endcap[3]['LMVOL9'] = 864;
$L04GridsArray_endcap[3]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[4]['LMGRD5'] = '06T11';
$L04GridsArray_endcap[4]['LMHIGH'] = 6;
$L04GridsArray_endcap[4]['LMDEEP'] = 24;
$L04GridsArray_endcap[4]['LMWIDE'] = 11;
$L04GridsArray_endcap[4]['LMVOL9'] = 1584;
$L04GridsArray_endcap[4]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[5]['LMGRD5'] = '10T11';
$L04GridsArray_endcap[5]['LMHIGH'] = 10;
$L04GridsArray_endcap[5]['LMDEEP'] = 24;
$L04GridsArray_endcap[5]['LMWIDE'] = 11;
$L04GridsArray_endcap[5]['LMVOL9'] = 2640;
$L04GridsArray_endcap[5]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[6]['LMGRD5'] = '10S23';
$L04GridsArray_endcap[6]['LMHIGH'] = 10;
$L04GridsArray_endcap[6]['LMDEEP'] = 24;
$L04GridsArray_endcap[6]['LMWIDE'] = 23;
$L04GridsArray_endcap[6]['LMVOL9'] = 5520;
$L04GridsArray_endcap[6]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[7]['LMGRD5'] = '15S23';
$L04GridsArray_endcap[7]['LMHIGH'] = 15;
$L04GridsArray_endcap[7]['LMDEEP'] = 24;
$L04GridsArray_endcap[7]['LMWIDE'] = 23;
$L04GridsArray_endcap[7]['LMVOL9'] = 8280;
$L04GridsArray_endcap[7]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[8]['LMGRD5'] = '10S47';
$L04GridsArray_endcap[8]['LMHIGH'] = 10;
$L04GridsArray_endcap[8]['LMDEEP'] = 24;
$L04GridsArray_endcap[8]['LMWIDE'] = 47;
$L04GridsArray_endcap[8]['LMVOL9'] = 11280;
$L04GridsArray_endcap[8]['GRIDCOUNT'] = 99999;

$L04GridsArray_endcap[9]['LMGRD5'] = '15S47';
$L04GridsArray_endcap[9]['LMHIGH'] = 15;
$L04GridsArray_endcap[9]['LMDEEP'] = 24;
$L04GridsArray_endcap[9]['LMWIDE'] = 47;
$L04GridsArray_endcap[9]['LMVOL9'] = 16920;
$L04GridsArray_endcap[9]['GRIDCOUNT'] = 99999;


$L04sql = $conn1->prepare("SELECT DISTINCT
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
                                D.CURTF,
                                case
                                    when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    else ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                end as DLY_CUBE_VEL,
                                case when X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 then ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID else ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID end as DLY_PICK_VEL,
                                PERC_SHIPQTY,
                                PERC_PERC,
                                $sql_dailypick as DAILYPICK,
                                $sql_dailyunit as DAILYUNIT,
                               S.CASETF
                              
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
                                      LEFT JOIN
                                slotting.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                            WHERE
                                A.WAREHOUSE = $whssel
                                    and A.PACKAGE_TYPE = ('LSE')
                                    and B.ITEM_TYPE = 'ST'
                                    and A.NBR_SHIP_OCC >= 4
                                    AND D.LMSLR NOT IN (2,4)
                                    -- and AVGD_BTW_SLE > 0
                                    and F.ITEM_NUMBER IS NULL
                                  
                                        
                            ORDER BY DLY_CUBE_VEL desc");
$L04sql->execute();
$L04array_endcap = $L04sql->fetchAll(pdo::FETCH_ASSOC);


foreach ($L04array_endcap as $key => $value) {

    $ITEM_NUMBER = intval($L04array_endcap[$key]['ITEM_NUMBER']);
    if($ITEM_NUMBER == 5700344){
        echo 't';
    }
    //Check OK in Shelf Setting
    $var_OKINSHLF = $L04array_endcap[$key]['CPCSHLF'];
    $var_stacklimit = $L04array_endcap[$key]['CPCESTK'];
    $var_casetf = $L04array_endcap[$key]['CASETF'];
    $var_CURTF = $L04array_endcap[$key]['CURTF'];

    $var_AVGSHIPQTY = $L04array_endcap[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L04array_endcap[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($L04array_endcap[$key]['AVG_INV_OH']);

    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $L04array_endcap[$key]['CPCLIQU'];

    $var_PCEHEIin = $L04array_endcap[$key]['CPCEHEI'] * 0.393701;
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $L04array_endcap[$key]['CPCCHEI'] * 0.393701;
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $L04array_endcap[$key]['CPCELEN'] * 0.393701;
    if ($var_PCELENin == 0) {
        $var_PCELENin = $L04array_endcap[$key]['CPCCLEN'] * 0.393701;
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $L04array_endcap[$key]['CPCEWID'] * 0.393701;
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $L04array_endcap[$key]['CPCCWID'] * 0.393701;
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_PCCHEIin = $L04array_endcap[$key]['CPCCHEI'] * 0.393701;
    $var_PCCLENin = $L04array_endcap[$key]['CPCCLEN'] * 0.393701;
    $var_PCCWIDin = $L04array_endcap[$key]['CPCCWID'] * 0.393701;

    $var_eachqty = $L04array_endcap[$key]['CPCEPKU'];
    $var_caseqty = $L04array_endcap[$key]['CPCCPKU'];
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
                $daystostock = 24;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 9;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 7;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 5;
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
                $daystostock = 50;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 25;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 12;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 15) {
                $daystostock = 10;
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
                $daystostock = 43;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 25;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 18;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 12;
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

    $PKGU_PERC_Restriction = $L04array_endcap[$key]['PERC_PERC'];

    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($L04array_endcap[$key]['PACKAGE_UNIT']);
        $var_pkty = $L04array_endcap[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];
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
    foreach ($L04GridsArray_endcap as $key2 => $value) {
        //if total slot volume is less than location volume, then continue
//        if ($totalslotvol > $L04GridsArray[$key2]['LMVOL9']) {
//            continue;
//        }

        $var_grid5 = $L04GridsArray_endcap [$key2]['LMGRD5'];
        if ($var_OKINSHLF == 'N' && substr($var_grid5, 2, 1) == 'S') {
            continue;
        }
        $var_gridheight = $L04GridsArray_endcap [$key2]['LMHIGH'];
        $var_griddepth = $L04GridsArray_endcap [$key2]['LMDEEP'];
        $var_gridwidth = $L04GridsArray_endcap [$key2]['LMWIDE'];
        $var_locvol = $L04GridsArray_endcap [$key2]['LMVOL9'];

        //Call the true fit for L04`
        if ($var_casetf == 'Y' && substr($var_grid5, 2, 1) == 'S' && ($var_PCCHEIin * $var_PCCLENin * $var_PCCWIDin * $var_caseqty > 0)) {
            $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCCHEIin, $var_PCCLENin, $var_PCCWIDin, $var_caseqty);
        } else if ($var_stacklimit > 0) {
            $SUGGESTED_MAX_array = _truefit($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, 0, $var_stacklimit);
        } else {
            $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        }
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


    $SUGGESTED_MAX = $SUGGESTED_MAX_test;

    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

    //append data to array for writing to my_npfmvc table
    $L04array_endcap[$key]['SUGGESTED_TIER'] = 'L04';
    $L04array_endcap[$key]['SUGGESTED_GRID5'] = $lastusedgrid5;
    $L04array_endcap[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L04array_endcap[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L04array_endcap[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L04array_endcap[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L04array_endcap[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L04array_endcap[$key]['SHIP_QTY_MN'], $L04array_endcap[$key]['AVGD_BTW_SLE']);
    $L04array_endcap[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves_withcurrentTF($L04array_endcap[$key]['CURMAX'], $L04array_endcap[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L04array_endcap[$key]['SHIP_QTY_MN'], $L04array_endcap[$key]['AVGD_BTW_SLE'], $var_CURTF);
    $L04array_endcap[$key]['SUGGESTED_NEWLOCVOL'] = intval(substr($lastusedgrid5, 0, 2)) * intval(substr($lastusedgrid5, 3, 2)) * intval($var_griddepth);
    $L04array_endcap[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L04array_endcap[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);
    $L04array_endcap[$key]['PPI'] = number_format($L04array_endcap[$key]['DAILYPICK'] / $L04array_endcap[$key]['SUGGESTED_NEWLOCVOL'], 10);
}
//sort by PPI descending
$sort = array();
foreach ($L04array_endcap as $k => $v) {
    $sort['PPI'][$k] = $v['PPI'];
    $sort['SUGGESTED_NEWLOCVOL'][$k] = $v['SUGGESTED_NEWLOCVOL'];
}
array_multisort($sort['PPI'], SORT_DESC, $sort['SUGGESTED_NEWLOCVOL'], SORT_ASC, $L04array_endcap);



foreach ($L04array_endcap as $key => $value) {
    if ($jaxendcapvol < 0) {
        break;  //if all available L04 volume has been used, exit
    }
    if (substr($L04array_endcap[$key]['SUGGESTED_GRID5'], 3) == '17' || $L04array_endcap[$key]['SUGGESTED_GRID5'] == '35') {  //cannot put 17 or 35 depth in endcap so skip
        continue;
    }
//********** START of SQL to ADD TO TABLE **********

    $WAREHOUSE = intval($L04array_endcap[$key]['WAREHOUSE']);
    $ITEM_NUMBER = intval($L04array_endcap[$key]['ITEM_NUMBER']);
    $PACKAGE_UNIT = intval($L04array_endcap[$key]['PACKAGE_UNIT']);
    $PACKAGE_TYPE = $L04array_endcap[$key]['PACKAGE_TYPE'];
    $DSL_TYPE = $L04array_endcap[$key]['DSL_TYPE'];
    $CUR_LOCATION = $L04array_endcap[$key]['LMLOC'];
    $DAYS_FRM_SLE = intval($L04array_endcap[$key]['DAYS_FRM_SLE']);
    $AVGD_BTW_SLE = intval($L04array_endcap[$key]['AVGD_BTW_SLE']);
    $AVG_INV_OH = intval($L04array_endcap[$key]['AVG_INV_OH']);
    $NBR_SHIP_OCC = intval($L04array_endcap[$key]['NBR_SHIP_OCC']);
    $PICK_QTY_MN = intval($L04array_endcap[$key]['PICK_QTY_MN']);
    $PICK_QTY_SD = $L04array_endcap[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = intval($L04array_endcap[$key]['SHIP_QTY_MN']);
    $SHIP_QTY_SD = $L04array_endcap[$key]['SHIP_QTY_SD'];
    $ITEM_TYPE = $L04array_endcap[$key]['ITEM_TYPE'];
    $CPCEPKU = intval($L04array_endcap[$key]['CPCEPKU']);
    $CPCIPKU = intval($L04array_endcap[$key]['CPCIPKU']);
    $CPCCPKU = intval($L04array_endcap[$key]['CPCCPKU']);
    $CPCFLOW = $L04array_endcap[$key]['CPCFLOW'];
    $CPCTOTE = $L04array_endcap[$key]['CPCTOTE'];
    $CPCSHLF = $L04array_endcap[$key]['CPCSHLF'];
    $CPCROTA = $L04array_endcap[$key]['CPCROTA'];
    $CPCESTK = intval($L04array_endcap[$key]['CPCESTK']);
    $CPCLIQU = $L04array_endcap[$key]['CPCLIQU'];
    $CPCELEN = $L04array_endcap[$key]['CPCELEN'];
    $CPCEHEI = $L04array_endcap[$key]['CPCEHEI'];
    $CPCEWID = $L04array_endcap[$key]['CPCEWID'];
    $CPCCLEN = $L04array_endcap[$key]['CPCCLEN'];
    $CPCCHEI = $L04array_endcap[$key]['CPCCHEI'];
    $CPCCWID = $L04array_endcap[$key]['CPCCWID'];
    $LMFIXA = $L04array_endcap[$key]['LMFIXA'];
    $LMFIXT = $L04array_endcap[$key]['LMFIXT'];
    $LMSTGT = $L04array_endcap[$key]['LMSTGT'];
    $LMHIGH = intval($L04array_endcap[$key]['LMHIGH']);
    $LMDEEP = intval($L04array_endcap[$key]['LMDEEP']);
    $LMWIDE = intval($L04array_endcap[$key]['LMWIDE']);
    $LMVOL9 = intval($L04array_endcap[$key]['LMVOL9']);
    $LMTIER = $L04array_endcap[$key]['LMTIER'];
    $LMGRD5 = $L04array_endcap[$key]['LMGRD5'];
    $DLY_CUBE_VEL = intval($L04array_endcap[$key]['DLY_CUBE_VEL']);
    $DLY_PICK_VEL = intval($L04array_endcap[$key]['DLY_PICK_VEL']);
    $SUGGESTED_TIER = $L04array_endcap[$key]['SUGGESTED_TIER'];
    $SUGGESTED_GRID5 = $L04array_endcap[$key]['SUGGESTED_GRID5'];
    $SUGGESTED_DEPTH = $L04array_endcap[$key]['SUGGESTED_DEPTH'];
    $SUGGESTED_MAX = intval($L04array_endcap[$key]['SUGGESTED_MAX']);
    $SUGGESTED_MIN = intval($L04array_endcap[$key]['SUGGESTED_MIN']);
    $SUGGESTED_SLOTQTY = intval($L04array_endcap[$key]['SUGGESTED_SLOTQTY']);

    $SUGGESTED_IMPMOVES = ($L04array_endcap[$key]['SUGGESTED_IMPMOVES']);
    $CURRENT_IMPMOVES = ($L04array_endcap[$key]['CURRENT_IMPMOVES']);
    $SUGGESTED_NEWLOCVOL = intval($L04array_endcap[$key]['SUGGESTED_NEWLOCVOL']);
    $SUGGESTED_DAYSTOSTOCK = intval($L04array_endcap[$key]['SUGGESTED_DAYSTOSTOCK']);
    $AVG_DAILY_PICK = $L04array_endcap[$key]['DAILYPICK'];
    $AVG_DAILY_UNIT = $L04array_endcap[$key]['DAILYUNIT'];
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

    if ($key % 100 == 0 && $key <> 0) {
        $values = implode(',', $data);



        include '../connections/conn_slotting.php';
        $sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();

        $data = array();
    }

//********** END of SQL to ADD TO TABLE **********


    $jaxendcapvol -= $SUGGESTED_NEWLOCVOL;
}
$values = implode(',', $data);
include '../connections/conn_slotting.php';
$sql = "INSERT IGNORE INTO slotting.my_npfmvc ($columns) VALUES $values";
$query = $conn1->prepare($sql);
$query->execute();

$data = array();
echo $whssel . ' available ENDCAP volume is ' . $jaxendcapvol;


//
