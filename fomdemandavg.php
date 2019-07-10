

<?php

//code to update fomaverage mysql table
//This takes 2+ hours to update.  Can this be streamlined
//Need to update this through weekend nightstream

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

include "../connections/conn_slotting.php";


$sqldelete = "TRUNCATE slotting.fomaverage";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


$whsearray = array(2, 3, 6, 7, 9,11,12,16);
$columns = 'FOMAVGWHSE, FOMAVGITEM, FOMAVGCSLS, FOMAVGPKGU, FOMAVGAVG, FOMAVGSTD, FOMAVGCOUNT';
foreach ($whsearray as $whse) {


    $result1 = $conn1->prepare("SELECT FOMWHSE, FOMITEM, FOMCSLS, FOMPKGU, AVG(FOMPQTY) as AVGQTY, STD(FOMPQTY) as STDQTY, COUNT(FOMPQTY) as COUNTQTY FROM fomraw WHERE ISFOM = 'Y' and FOMWHSE = $whse GROUP BY FOMWHSE, FOMITEM, FOMCSLS, FOMPKGU ORDER BY FOMWHSE ASC, FOMITEM ASC, FOMCSLS ASC, FOMPKGU ASC");
    $result1->execute();
    $resultarray = $result1->fetchAll();

    $values = array();

    $maxrange = 9999;
    $counter = 0;
    $rowcount = count($resultarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
            $whse = intval($resultarray[$counter]['FOMWHSE']);
            $item = intval($resultarray[$counter]['FOMITEM']);
            $csls = ($resultarray[$counter]['FOMCSLS']);
            $pkgu = intval($resultarray[$counter]['FOMPKGU']);
            $avg = intval($resultarray[$counter]['AVGQTY']);
            $std = intval($resultarray[$counter]['STDQTY']);
            $count = intval($resultarray[$counter]['COUNTQTY']);

            $data[] = "($whse, $item, '$csls', $pkgu, $avg, $std, $count)";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT INTO slotting.fomaverage ($columns) VALUES $values ON DUPLICATE KEY UPDATE FOMAVGAVG=values(FOMAVGAVG), FOMAVGSTD=values(FOMAVGSTD), FOMAVGCOUNT=values(FOMAVGCOUNT)";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 10000;
    } while ($counter <= $rowcount);
}//end of whse array loop