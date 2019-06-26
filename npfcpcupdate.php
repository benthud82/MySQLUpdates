<?php

//if (isset($var_whse)) {
//    $whsefilter = ' and LOWHSE = ' . $var_whse;
//} else {
//    $whsefilter = ' and LOWHSE in (2,3,6,7,9)';
//}

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//include_once '../globalincludes/nahsi_mysql.php';
include_once '../globalincludes/ustxgpslotting_mysql.php';
include_once '../globalincludes/usa_asys.php';
include_once '../globalincludes/newcanada_asys.php';
include_once '../globalfunctions/slottingfunctions.php';


if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.npfcpcsettings WHERE Whse = $var_whse";
    $whsefilter = 'LOWHSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.npfcpcsettings";
    $whsefilter = 'LOWHSE in (2,3,6,7,9,11,12,16)';
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'CPCWHSE, CPCITEM, CPCEPKU, CPCIPKU, CPCCPKU, CPCFLOW, CPCTOTE, CPCSHLF, CPCROTA, CPCESTK, CPCLIQU, CPCORSH, CPCPFRC, CPCPFRA, CPCELEN, CPCEHEI, CPCEWID, CPCCLEN, CPCCHEI, CPCCWID, CPCNEST, CPCCONV,CPCPPKU';

$whsearray = array(2, 3, 6, 7, 9);

foreach ($whsearray as $whsval) {

    $cpcresult = $aseriesconn->prepare("SELECT C.LOWHSE AS CPCWHSE
,LOITEM AS CPCITEM
,CASE WHEN PCEPKU	 = '0' THEN B.EA_PACKAGE_UNIT 
           WHEN PCEPKU	 IS NULL  THEN B.EA_PACKAGE_UNIT ELSE PCEPKU	 END AS CPCEPKU
,CASE WHEN PCIPKU		 = '0' THEN B.IP_UNIT 
           WHEN PCIPKU	 IS NULL  THEN B.IP_UNIT ELSE PCIPKU		 END AS CPCIPKU
,CASE WHEN PCCPKU	 = '0' THEN B.CA_PACKAGE_UNIT
           WHEN PCCPKU	 IS NULL  THEN B.CA_PACKAGE_UNIT ELSE PCCPKU	 END AS CPCCPKU
,CASE WHEN PCFLOR	 = '' THEN B.ITEM_OK_IN_FLOW_RACK
           WHEN PCFLOR IS NULL  THEN B.ITEM_OK_IN_FLOW_RACK ELSE PCFLOR END AS CPCFLOW
,CASE WHEN PCTOTE	 	 = '' THEN B.ITEM_OK_IN_TOTE 
           WHEN PCTOTE	  IS NULL  THEN B.ITEM_OK_IN_TOTE ELSE PCTOTE	  END AS CPCTOTE
,CASE WHEN PCSHLF	 = '' THEN B.ITEM_OK_ON_SHELF
           WHEN PCSHLF		 IS NULL  THEN B.ITEM_OK_ON_SHELF ELSE PCSHLF		 END AS CPCSHLF
,CASE WHEN PCEROT	 = '' THEN B.EA_ALLOW_ROTATION
           WHEN PCEROT	 IS NULL  THEN B.EA_ALLOW_ROTATION ELSE PCEROT		 END AS CPCROTA
,CASE WHEN PCESTA = '0' THEN B.EA_STACKABLE
           WHEN PCESTA	 IS NULL  THEN B.EA_STACKABLE ELSE PCESTA	 END AS CPCESTK
,CASE WHEN PCLIQU	 = '0' THEN B.PRODUCT_LIQUID
           WHEN PCLIQU	 IS NULL  THEN B.PRODUCT_LIQUID ELSE PCLIQU	 END AS CPCLIQU
,CASE WHEN PCORSH	 = '0' THEN B.ORIENTATION_LIQUID_ON_SHELF 
           WHEN PCORSH IS NULL  THEN B.ORIENTATION_LIQUID_ON_SHELF ELSE PCORSH	 END AS CPCORSH
,B.PRF_ACTIVE_AT_CORPORATE AS CPCPFRC
,CASE WHEN PCPFRA	 = '0' THEN B.PRF_ACTIVE_AT_WAREHOUSE
           WHEN PCPFRA	 IS NULL  THEN B.PRF_ACTIVE_AT_WAREHOUSE ELSE PCPFRA END AS CPCPFRA
,CASE WHEN PCELEN	 = '0' THEN B.EA_LENGTH
           WHEN PCELEN	 IS NULL  THEN B.EA_LENGTH ELSE PCELEN	 END AS CPCELEN
,CASE WHEN PCEHEI		 = '0' THEN B.EA_HEIGHT
           WHEN PCEHEI		 IS NULL  THEN B.EA_HEIGHT ELSE PCEHEI	 END AS CPCEHEI
,CASE WHEN PCEWID = '0' THEN B.EA_WIDTH
           WHEN PCEWID	 IS NULL  THEN B.EA_WIDTH ELSE PCEWID	 END AS CPCEWID
,CASE WHEN PCCLEN	 = '0' THEN B.PL_LENGTH
           WHEN PCCLEN	 IS NULL  THEN B.PL_LENGTH ELSE PCCLEN	 END AS CPCCLEN
,CASE WHEN PCCHEI		 = '0' THEN B.PL_HEIGHT
           WHEN PCCHEI	 IS NULL  THEN B.PL_HEIGHT ELSE PCCHEI		 END AS CPCCHEI
,CASE WHEN PCCWID	 = '0' THEN B.PL_WIDTH
           WHEN PCCWID	 IS NULL  THEN B.PL_WIDTH ELSE PCCWID	 END AS CPCCWID
,CASE WHEN PCNSTV	 	 = '0' THEN B.NEST_INC 
           WHEN PCNSTV	  IS NULL  THEN B.NEST_INC ELSE PCNSTV	  END AS PCNSTV
,CASE WHEN PCCCNV	 = '' THEN B.CASE_CONVEY
           WHEN PCCCNV		 IS NULL  THEN B.CASE_CONVEY ELSE PCCCNV		 END AS CPCCONV
,CASE WHEN PCPPKU	 = '0' THEN B.PL_PACKAGE_UNIT 
           WHEN PCPPKU	 IS NULL  THEN B.PL_PACKAGE_UNIT ELSE PCPPKU	 END AS CPCPPKU
	FROM HSIPCORDTA.NPFLOC C
LEFT JOIN HSIPCORDTA.NPFCPC A ON A.PCITEM = C.LOITEM AND A.PCWHSE = C.LOWHSE

LEFT JOIN 

                                                        (SELECT PCWHSE AS DC
                                                       ,PCITEM AS ITEM
                                                       ,PCEPKU	 AS EA_PACKAGE_UNIT
                                                       ,PCIPKU	 AS IP_UNIT
                                                       ,PCCPKU AS CA_PACKAGE_UNIT
	                                                       ,PCFLOR AS ITEM_OK_IN_FLOW_RACK
	                                                       ,PCTOTE	 AS ITEM_OK_IN_TOTE
                                                       ,PCSHLF	 AS ITEM_OK_ON_SHELF
                                                       ,PCEROT	 AS EA_ALLOW_ROTATION
                                                       ,PCESTA AS EA_STACKABLE
	                                                       ,PCLIQU	 AS PRODUCT_LIQUID
                                                       ,PCORSH	 AS ORIENTATION_LIQUID_ON_SHELF
                                                       ,PCPFRC	 AS PRF_ACTIVE_AT_CORPORATE
                                                       ,PCPFRA AS PRF_ACTIVE_AT_WAREHOUSE
	                                                       ,PCELEN	 AS EA_LENGTH
                                                       ,PCEHEI	  AS EA_HEIGHT
                                                       ,PCEWID	 AS EA_WIDTH
                                                       ,PCCLEN	 AS PL_LENGTH
                                                       ,PCCHEI	 AS PL_HEIGHT
                                                       ,PCCWID AS PL_WIDTH
                                                       ,PCNSTV AS NEST_INC
                                                       ,PCCCNV as CASE_CONVEY
                                                         ,PCPPKU as PL_PACKAGE_UNIT

                                                        FROM HSIPCORDTA.NPFCPC
                                                          
                                                        WHERE PCWHSE IN ('0')

                                                         ) B ON C.LOITEM = B.ITEM
WHERE LOWHSE = $whsval and LOITEM >= '1000000'

GROUP BY LOWHSE,	LOITEM	,PCEPKU	,PCIPKU	,PCCPKU	,PCFLOR	,PCTOTE	,PCSHLF,PCEROT	,PCESTA	,PCLIQU,	PCORSH,	PCPFRC,	PCPFRA	,PCELEN,	PCEHEI	,PCEWID,	PCCLEN	,PCCHEI	,PCCWID, PCNSTV,PCCCNV, PCPPKU,
EA_PACKAGE_UNIT,IP_UNIT, CA_PACKAGE_UNIT, ITEM_OK_IN_FLOW_RACK,	 ITEM_OK_IN_TOTE , ITEM_OK_ON_SHELF ,EA_ALLOW_ROTATION ,EA_STACKABLE  ,PRODUCT_LIQUID ,ORIENTATION_LIQUID_ON_SHELF,PRF_ACTIVE_AT_CORPORATE,PRF_ACTIVE_AT_WAREHOUSE, EA_LENGTH ,EA_HEIGHT,EA_WIDTH ,PL_LENGTH ,PL_HEIGHT  ,PL_WIDTH,NEST_INC,CASE_CONVEY, PL_PACKAGE_UNIT");
    $cpcresult->execute();
    $NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 999;
    $counter = 0;
    $rowcount = count($NPFCPC_ALL_array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $CPCWHSE = intval($NPFCPC_ALL_array[$counter]['CPCWHSE']);
            $CPCITEM = intval($NPFCPC_ALL_array[$counter]['CPCITEM']);
            if (!is_numeric($CPCITEM) || $CPCITEM < 1000000) {
                $counter +=1;
                continue;
            }
            $CPCEPKU = intval($NPFCPC_ALL_array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($NPFCPC_ALL_array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($NPFCPC_ALL_array[$counter]['CPCCPKU']);
            $CPCFLOW = $NPFCPC_ALL_array[$counter]['CPCFLOW'];
            $CPCTOTE = $NPFCPC_ALL_array[$counter]['CPCTOTE'];
            $CPCSHLF = $NPFCPC_ALL_array[$counter]['CPCSHLF'];
            $CPCROTA = $NPFCPC_ALL_array[$counter]['CPCROTA'];
            $CPCESTK = intval($NPFCPC_ALL_array[$counter]['CPCESTK']);
            $CPCLIQU = $NPFCPC_ALL_array[$counter]['CPCLIQU'];
            $CPCORSH = $NPFCPC_ALL_array[$counter]['CPCORSH'];
            $CPCPFRC = $NPFCPC_ALL_array[$counter]['CPCPFRC'];
            $CPCPFRA = $NPFCPC_ALL_array[$counter]['CPCPFRA'];
            $CPCELEN = number_format($NPFCPC_ALL_array[$counter]['CPCELEN'], 2, '.', '');
            $CPCEHEI = number_format($NPFCPC_ALL_array[$counter]['CPCEHEI'], 2, '.', '');
            $CPCEWID = number_format($NPFCPC_ALL_array[$counter]['CPCEWID'], 2, '.', '');
            $CPCCLEN = number_format($NPFCPC_ALL_array[$counter]['CPCCLEN'], 2, '.', '');
            $CPCCHEI = number_format($NPFCPC_ALL_array[$counter]['CPCCHEI'], 2, '.', '');
            $CPCCWID = number_format($NPFCPC_ALL_array[$counter]['CPCCWID'], 2, '.', '');
            $CPCNEST = intval($NPFCPC_ALL_array[$counter]['PCNSTV']);
            $CPCCONV = ($NPFCPC_ALL_array[$counter]['CPCCONV']);
            $CPCPPKU = intval($NPFCPC_ALL_array[$counter]['CPCPPKU']);



            $data[] = "($CPCWHSE, $CPCITEM, $CPCEPKU, $CPCIPKU, $CPCCPKU, '$CPCFLOW', '$CPCTOTE', '$CPCSHLF', '$CPCROTA', $CPCESTK, '$CPCLIQU', '$CPCORSH', '$CPCPFRC', '$CPCPFRA', $CPCELEN, $CPCEHEI, $CPCEWID, $CPCCLEN, $CPCCHEI, $CPCCWID, $CPCNEST, '$CPCCONV',$CPCPPKU)";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.npfcpcsettings ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}




$whsearray = array(11, 12, 16);

foreach ($whsearray as $whsval) {

    $cpcresult = $aseriesconn_can->prepare("SELECT C.LOWHSE AS CPCWHSE
,LOITEM AS CPCITEM
,CASE WHEN PCEPKU	 = '0' THEN B.EA_PACKAGE_UNIT 
           WHEN PCEPKU	 IS NULL  THEN B.EA_PACKAGE_UNIT ELSE PCEPKU	 END AS CPCEPKU
,CASE WHEN PCIPKU		 = '0' THEN B.IP_UNIT 
           WHEN PCIPKU	 IS NULL  THEN B.IP_UNIT ELSE PCIPKU		 END AS CPCIPKU
,CASE WHEN PCCPKU	 = '0' THEN B.CA_PACKAGE_UNIT
           WHEN PCCPKU	 IS NULL  THEN B.CA_PACKAGE_UNIT ELSE PCCPKU	 END AS CPCCPKU
,CASE WHEN PCFLOR	 = '' THEN B.ITEM_OK_IN_FLOW_RACK
           WHEN PCFLOR IS NULL  THEN B.ITEM_OK_IN_FLOW_RACK ELSE PCFLOR END AS CPCFLOW
,CASE WHEN PCTOTE	 	 = '' THEN B.ITEM_OK_IN_TOTE 
           WHEN PCTOTE	  IS NULL  THEN B.ITEM_OK_IN_TOTE ELSE PCTOTE	  END AS CPCTOTE
,CASE WHEN PCSHLF	 = '' THEN B.ITEM_OK_ON_SHELF
           WHEN PCSHLF		 IS NULL  THEN B.ITEM_OK_ON_SHELF ELSE PCSHLF		 END AS CPCSHLF
,CASE WHEN PCEROT	 = '' THEN B.EA_ALLOW_ROTATION
           WHEN PCEROT	 IS NULL  THEN B.EA_ALLOW_ROTATION ELSE PCEROT		 END AS CPCROTA
,CASE WHEN PCESTA = '0' THEN B.EA_STACKABLE
           WHEN PCESTA	 IS NULL  THEN B.EA_STACKABLE ELSE PCESTA	 END AS CPCESTK
,CASE WHEN PCLIQU	 = '0' THEN B.PRODUCT_LIQUID
           WHEN PCLIQU	 IS NULL  THEN B.PRODUCT_LIQUID ELSE PCLIQU	 END AS CPCLIQU
,CASE WHEN PCORSH	 = '0' THEN B.ORIENTATION_LIQUID_ON_SHELF 
           WHEN PCORSH IS NULL  THEN B.ORIENTATION_LIQUID_ON_SHELF ELSE PCORSH	 END AS CPCORSH
,B.PRF_ACTIVE_AT_CORPORATE AS CPCPFRC
,CASE WHEN PCPFRA	 = '0' THEN B.PRF_ACTIVE_AT_WAREHOUSE
           WHEN PCPFRA	 IS NULL  THEN B.PRF_ACTIVE_AT_WAREHOUSE ELSE PCPFRA END AS CPCPFRA
,CASE WHEN PCELEN	 = '0' THEN B.EA_LENGTH
           WHEN PCELEN	 IS NULL  THEN B.EA_LENGTH ELSE PCELEN	 END AS CPCELEN
,CASE WHEN PCEHEI		 = '0' THEN B.EA_HEIGHT
           WHEN PCEHEI		 IS NULL  THEN B.EA_HEIGHT ELSE PCEHEI	 END AS CPCEHEI
,CASE WHEN PCEWID = '0' THEN B.EA_WIDTH
           WHEN PCEWID	 IS NULL  THEN B.EA_WIDTH ELSE PCEWID	 END AS CPCEWID
,CASE WHEN PCCLEN	 = '0' THEN B.PL_LENGTH
           WHEN PCCLEN	 IS NULL  THEN B.PL_LENGTH ELSE PCCLEN	 END AS CPCCLEN
,CASE WHEN PCCHEI		 = '0' THEN B.PL_HEIGHT
           WHEN PCCHEI	 IS NULL  THEN B.PL_HEIGHT ELSE PCCHEI		 END AS CPCCHEI
,CASE WHEN PCCWID	 = '0' THEN B.PL_WIDTH
           WHEN PCCWID	 IS NULL  THEN B.PL_WIDTH ELSE PCCWID	 END AS CPCCWID
,CASE WHEN PCNSTV	 	 = '0' THEN B.NEST_INC 
           WHEN PCNSTV	  IS NULL  THEN B.NEST_INC ELSE PCNSTV	  END AS PCNSTV
,CASE WHEN PCCCNV	 = '' THEN B.CASE_CONVEY
           WHEN PCCCNV		 IS NULL  THEN B.CASE_CONVEY ELSE PCCCNV		 END AS CPCCONV
,CASE WHEN PCPPKU	 = '0' THEN B.PL_PACKAGE_UNIT 
           WHEN PCPPKU	 IS NULL  THEN B.PL_PACKAGE_UNIT ELSE PCPPKU	 END AS CPCPPKU
	FROM HSIPCORDTA.NPFLOC C
LEFT JOIN HSIPCORDTA.NPFCPC A ON A.PCITEM = C.LOITEM AND A.PCWHSE = C.LOWHSE

LEFT JOIN 

                                                        (SELECT PCWHSE AS DC
                                                       ,PCITEM AS ITEM
                                                       ,PCEPKU	 AS EA_PACKAGE_UNIT
                                                       ,PCIPKU	 AS IP_UNIT
                                                       ,PCCPKU AS CA_PACKAGE_UNIT
	                                                       ,PCFLOR AS ITEM_OK_IN_FLOW_RACK
	                                                       ,PCTOTE	 AS ITEM_OK_IN_TOTE
                                                       ,PCSHLF	 AS ITEM_OK_ON_SHELF
                                                       ,PCEROT	 AS EA_ALLOW_ROTATION
                                                       ,PCESTA AS EA_STACKABLE
	                                                       ,PCLIQU	 AS PRODUCT_LIQUID
                                                       ,PCORSH	 AS ORIENTATION_LIQUID_ON_SHELF
                                                       ,PCPFRC	 AS PRF_ACTIVE_AT_CORPORATE
                                                       ,PCPFRA AS PRF_ACTIVE_AT_WAREHOUSE
	                                                       ,PCELEN	 AS EA_LENGTH
                                                       ,PCEHEI	  AS EA_HEIGHT
                                                       ,PCEWID	 AS EA_WIDTH
                                                       ,PCCLEN	 AS PL_LENGTH
                                                       ,PCCHEI	 AS PL_HEIGHT
                                                       ,PCCWID AS PL_WIDTH
                                                       ,PCNSTV AS NEST_INC
                                                       ,PCCCNV as CASE_CONVEY
													   ,PCPPKU as PL_PACKAGE_UNIT

                                                        FROM HSIPCORDTA.NPFCPC
                                                          
                                                        WHERE PCWHSE IN ('0')

                                                         ) B ON C.LOITEM = B.ITEM
WHERE LOWHSE = $whsval and LOITEM >= '1000000'

GROUP BY LOWHSE,	LOITEM	,PCEPKU	,PCIPKU	,PCCPKU	,PCFLOR	,PCTOTE	,PCSHLF,PCEROT	,PCESTA	,PCLIQU,	PCORSH,	PCPFRC,	PCPFRA	,PCELEN,	PCEHEI	,PCEWID,	PCCLEN	,PCCHEI	,PCCWID, PCNSTV,PCCCNV, PCPPKU,
EA_PACKAGE_UNIT,IP_UNIT, CA_PACKAGE_UNIT, ITEM_OK_IN_FLOW_RACK,	 ITEM_OK_IN_TOTE , ITEM_OK_ON_SHELF ,EA_ALLOW_ROTATION ,EA_STACKABLE  ,PRODUCT_LIQUID ,ORIENTATION_LIQUID_ON_SHELF,PRF_ACTIVE_AT_CORPORATE,PRF_ACTIVE_AT_WAREHOUSE, EA_LENGTH ,EA_HEIGHT,EA_WIDTH ,PL_LENGTH ,PL_HEIGHT  ,PL_WIDTH,NEST_INC,CASE_CONVEY, PL_PACKAGE_UNIT");
    $cpcresult->execute();
    $NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


    $maxrange = 999;
    $counter = 0;
    $rowcount = count($NPFCPC_ALL_array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $CPCWHSE = intval($NPFCPC_ALL_array[$counter]['CPCWHSE']);
            $CPCITEM = intval($NPFCPC_ALL_array[$counter]['CPCITEM']);
            if (!is_numeric($CPCITEM) || $CPCITEM < 1000000) {
                $counter +=1;
                continue;
            }
            $CPCEPKU = intval($NPFCPC_ALL_array[$counter]['CPCEPKU']);
            $CPCIPKU = intval($NPFCPC_ALL_array[$counter]['CPCIPKU']);
            $CPCCPKU = intval($NPFCPC_ALL_array[$counter]['CPCCPKU']);
            $CPCFLOW = $NPFCPC_ALL_array[$counter]['CPCFLOW'];
            $CPCTOTE = $NPFCPC_ALL_array[$counter]['CPCTOTE'];
            $CPCSHLF = $NPFCPC_ALL_array[$counter]['CPCSHLF'];
            $CPCROTA = $NPFCPC_ALL_array[$counter]['CPCROTA'];
            $CPCESTK = intval($NPFCPC_ALL_array[$counter]['CPCESTK']);
            $CPCLIQU = $NPFCPC_ALL_array[$counter]['CPCLIQU'];
            $CPCORSH = $NPFCPC_ALL_array[$counter]['CPCORSH'];
            $CPCPFRC = $NPFCPC_ALL_array[$counter]['CPCPFRC'];
            $CPCPFRA = $NPFCPC_ALL_array[$counter]['CPCPFRA'];
            $CPCELEN = number_format($NPFCPC_ALL_array[$counter]['CPCELEN'], 2, '.', '');
            $CPCEHEI = number_format($NPFCPC_ALL_array[$counter]['CPCEHEI'], 2, '.', '');
            $CPCEWID = number_format($NPFCPC_ALL_array[$counter]['CPCEWID'], 2, '.', '');
            $CPCCLEN = number_format($NPFCPC_ALL_array[$counter]['CPCCLEN'], 2, '.', '');
            $CPCCHEI = number_format($NPFCPC_ALL_array[$counter]['CPCCHEI'], 2, '.', '');
            $CPCCWID = number_format($NPFCPC_ALL_array[$counter]['CPCCWID'], 2, '.', '');
            $CPCNEST = intval($NPFCPC_ALL_array[$counter]['PCNSTV']);
            $CPCCONV = ($NPFCPC_ALL_array[$counter]['CPCCONV']);
            $CPCPPKU = intval($NPFCPC_ALL_array[$counter]['CPCPPKU']);



            $data[] = "($CPCWHSE, $CPCITEM, $CPCEPKU, $CPCIPKU, $CPCCPKU, '$CPCFLOW', '$CPCTOTE', '$CPCSHLF', '$CPCROTA', $CPCESTK, '$CPCLIQU', '$CPCORSH', '$CPCPFRC', '$CPCPFRA', $CPCELEN, $CPCEHEI, $CPCEWID, $CPCCLEN, $CPCCHEI, $CPCCWID, $CPCNEST,'$CPCCONV',$CPCPPKU)";
            $counter +=1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO slotting.npfcpcsettings ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange +=1000;
    } while ($counter <= $rowcount); //end of item by whse loop
}