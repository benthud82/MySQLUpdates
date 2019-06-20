<?php
//updates table slotting.pkgu_percent

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connections/conn_slotting.php';
$autoid = 0;

if (isset($var_whse)) {
    $sqldelete = "DELETE FROM slotting.pkgu_percent WHERE Whse = $var_whse";
    $whsefilter = 'WAREHOUSE = ' . $var_whse;
} else {
    $sqldelete = "TRUNCATE TABLE slotting.pkgu_percent";
}

$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'idpkgu_percent, PERC_WHSE, PERC_ITEM, PERC_PKGU, PERC_PKGTYPE, PERC_SHIPQTY, PERC_PERC';


$cpcresult = $conn1->prepare("SELECT 
                                                            a.WAREHOUSE,
                                                            a.ITEM_NUMBER,
                                                            a.PACKAGE_UNIT,
                                                            a.PACKAGE_TYPE,
                                                            Sum(case
                                                                when AVGD_BTW_SLE >= 365 then 0
                                                                when DAYS_FRM_SLE >= 180 then 0
                                                                when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                                                when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then SHIP_QTY_MN
                                                                when AVGD_BTW_SLE = 0 then (SHIP_QTY_MN / DAYS_FRM_SLE)
                                                                else (SHIP_QTY_MN / AVGD_BTW_SLE)
                                                            end) as TOTQTY,
                                                            Sum(case
                                                                when AVGD_BTW_SLE >= 365 then 0
                                                                when DAYS_FRM_SLE >= 180 then 0
                                                                when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                                                when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then SHIP_QTY_MN
                                                                when AVGD_BTW_SLE = 0 then (SHIP_QTY_MN / DAYS_FRM_SLE)
                                                                else (SHIP_QTY_MN / AVGD_BTW_SLE)
                                                            end) / (SELECT 
                                                                    Sum(case
                                                                            when AVGD_BTW_SLE >= 365 then 0
                                                                            when DAYS_FRM_SLE >= 180 then 0
                                                                            when PICK_QTY_MN > SHIP_QTY_MN then SHIP_QTY_MN / AVGD_BTW_SLE
                                                                            when AVGD_BTW_SLE = 0 and DAYS_FRM_SLE = 0 then SHIP_QTY_MN
                                                                            when AVGD_BTW_SLE = 0 then (SHIP_QTY_MN / DAYS_FRM_SLE)
                                                                            else (SHIP_QTY_MN / AVGD_BTW_SLE)
                                                                        end)
                                                                FROM
                                                                    slotting.mysql_nptsld t
                                                                WHERE
                                                                    t.ITEM_NUMBER = a.ITEM_NUMBER
                                                                        and t.WAREHOUSE = a.WAREHOUSE  and t.NBR_SHIP_OCC >= 4) as PERC_PERC
                                                        FROM
                                                            slotting.mysql_nptsld a
                                                        WHERE
                                                            a.NBR_SHIP_OCC >= 4
                                                        GROUP BY a.WAREHOUSE , a.ITEM_NUMBER , a.PACKAGE_UNIT , a.PACKAGE_TYPE
");
$cpcresult->execute();
$NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 9999;
$counter = 0;
$rowcount = count($NPFCPC_ALL_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WAREHOUSE = intval($NPFCPC_ALL_array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($NPFCPC_ALL_array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($NPFCPC_ALL_array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $NPFCPC_ALL_array[$counter]['PACKAGE_TYPE'];
        $PERC_SHIPQTY = $NPFCPC_ALL_array[$counter]['TOTQTY'];
        $PERC_PERC = $NPFCPC_ALL_array[$counter]['PERC_PERC'];
        if($PERC_PERC === NULL){
            $PERC_PERC = 1;
        }


        $data[] = "($autoid, $WAREHOUSE, $ITEM_NUMBER, $PACKAGE_UNIT, '$PACKAGE_TYPE', '$PERC_SHIPQTY', '$PERC_PERC')";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO slotting.pkgu_percent ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=10000;
} while ($counter <= $rowcount); //end of item by whse loop




