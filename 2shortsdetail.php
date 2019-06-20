<!--Code to update the MySQL table "2shortsdetail"-->
<?php
//Load data from A-System to an array
include '../connections/conn_slotting.php';
$tbl_name = "2shortsdetail"; // Table name

include'../globalincludes/voice_2.php';



$msresult = $dbh->prepare("SELECT cast(Pick.Batch_Num as int) as Batch, Pick.ItemFlag, Pick.Location, Pick.QtyOrder, Pick.QtyPick,  Pick.ItemCode, CONVERT(VARCHAR(19),Pick.DateTimeFirstPick,120) as PICKTIME, CONVERT(VARCHAR(19),Pick.DATECREATED,120) as PRINTTIME, Pick.DateTimeFirstPick as ShortDate FROM HenrySchein.dbo.Pick Pick WHERE (Pick.Short_Status<>0) ");
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

