<!--Code to update the MySQL table "2shortsdetail"-->
<?php
//Load data from A-System to an array
include '../connections/conn_slotting.php';
$tbl_name = "slotting.6shortsdetail"; // Table name

include'../globalincludes/voice_6.php';



$msresult = $dbh->prepare("SELECT
       cast(T.WaveNumber as int) as Batch                          ,
       'X'                       as ItemFlag                       ,
       T.LocationString          as Location                       ,
       QuantityPicked + QuantityShorted as QtyOrder                                               ,
       QuantityPicked as QtyPick                                   ,
       ProductCode as ItemCode                                               ,
       CONVERT(VARCHAR(19),LastEventOccurredDateTimeLocal,120) as PICKTIME ,
       CONVERT(VARCHAR(19),LastEventOccurredDateTimeLocal,120)      as PRINTTIME,
       CONVERT(VARCHAR(19),LastEventOccurredDateTimeLocal,120)                        as ShortDate
FROM
           Local_PickingSupervisor.dbo.TaskState TS (nolock)
           INNER JOIN
                      Local_PickingSupervisor.dbo.Task T (nolock)
                      on
                                 TS.TaskID = T.TaskID
");
$msresult->execute();
foreach ($msresult as $msrow) {


    $picktimediff = strtotime($msrow['PICKTIME']);
    $currentdate = time();
    $datediff = $currentdate - $picktimediff;
    $days = floor($datediff / 86400);
    
    if($days >= 8){
        continue;
    }

    $batch = intval($msrow['Batch']);
    $itemMC = ("'" . $msrow['ItemFlag'] . "'");
    $loc = ("'" . $msrow['Location'] . "'");
    $ordered = intval($msrow['QtyOrder']);
    $pick = intval($msrow['QtyPick']);
    $item = ("'" . $msrow['ItemCode'] . "'");
    $picktime = ("'" . $msrow['PICKTIME'] . "'");
    $printtime = ( "'" . $msrow['PRINTTIME'] . "'");
    $date = "'" . date('Y-m-d', strtotime($msrow['PICKTIME'])) . "'";

//    echo ("'" . date('Y-m-d', strtotime($msrow['PICKTIME'])) . "'");
//    echo gettype("'" . date('Y-m-d', strtotime($msrow['PICKTIME'])) . "'"), "<br>";
//    echo ("'".$msrow['ItemCode']."'");
//    echo gettype($msrow['ItemCode']), "<br>";
//    echo ("'" . $msrow['Location'] . "'");
//    echo gettype($msrow['Location']), "<br>";
//    echo("'" . $msrow['ItemFlag'] . "'");
//    echo gettype($msrow['ItemFlag']), "<br>";
//    echo (intval($msrow['Batch']));
//    echo gettype($msrow['Batch']), "<br>";
//    echo (intval($msrow['QtyOrder']));
//    echo gettype(intval($msrow['QtyOrder'])), "<br>";
//    echo (intval($msrow['QtyPick']));
//    echo gettype(intval($msrow['QtyPick'])), "<br>";
//    echo ("'" . $msrow['PICKTIME'] . "'");
//    echo gettype("'" . $msrow['PICKTIME'] . "'"), "<br>";
//    echo ( "'" . $msrow['PRINTTIME'] . "'");
//    echo gettype( "'" . $msrow['PRINTTIME'] . "'"), "<br>";

    $sql = "INSERT IGNORE INTO $tbl_name (ShortDate, Item, Location, ItemMC, Batch, QtyOrdered, QtyPicked, PickTime, PrintTime) VALUES ($date, $item, $loc, $itemMC, $batch, $ordered, $pick, $picktime, $printtime) ";
//    $sql = "INSERT INTO $tbl_name (Date, Item, Location, ItemMC, Batch, QtyOrdered, QtyPicked, Description, PickTime, PrintTime) VALUES ('2014-01-01', '1000000', 'A111111', 'A', 11111, 1, 1, 'DESCHERE', '2014-06-30 15:13:00', '2014-06-30 15:13:00') ";
$result2 = $conn1->prepare($sql);
$result2->execute();
}

?>

