<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../globalincludes/usa_asys.php';

$sqldelete = "TRUNCATE TABLE slotting.replen_itemcount";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$whsearray = array(2, 3, 6, 7, 9);

foreach ($whsearray as $whse) {
    $table = 'slotting.' . $whse . 'moves';

    $result1 = $aseriesconn->prepare(" INSERT INTO slotting.replen_itemcount
                                                                        SELECT 
                                                                            7, MVITEM, MVTZNE, count(*) as MOVECOUNT
                                                                        FROM
                                                                            $table
                                                                        WHERE
                                                                        MVDATE >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND NOW()
                                                                        GROUP BY MVITEM, MVTZNE");
    $result1->execute();
}


