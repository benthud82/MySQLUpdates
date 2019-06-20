<!--Code to update the MySQL tables that count shorts by day-->

<?php
set_time_limit(99999);
include '../connections/conn_slotting.php';

//INDY
$sql1 = "DROP TABLE IF EXISTS 2dailyshortcount";
$sql2 = "CREATE TABLE 2dailyshortcount(ShortDate DATE, ShortCount INT)";
$sql3 = "INSERT INTO 2dailyshortcount(ShortDate, ShortCount) SELECT ShortDate, COUNT(QtyOrdered) FROM 2shortsdetail GROUP BY ShortDate";
    $query = $conn1->prepare($sql1);
    $query->execute();
	
	$query = $conn1->prepare($sql2);
    $query->execute();
	
	$query = $conn1->prepare($sql3);
    $query->execute();


//RENO
$sql4 = "DROP TABLE IF EXISTS 3dailyshortcount";
$sql5 = "CREATE TABLE 3dailyshortcount(ShortDate DATE, ShortCount INT)";
$sql6 = "INSERT INTO 3dailyshortcount(ShortDate, ShortCount) SELECT ShortDate, COUNT(QtyOrdered) FROM 3shortsdetail GROUP BY ShortDate";

    $query = $conn1->prepare($sql4);
    $query->execute();
	
	$query = $conn1->prepare($sql5);
    $query->execute();
	
	$query = $conn1->prepare($sql6);
    $query->execute();
//DENVER
$sql7 = "DROP TABLE IF EXISTS 6dailyshortcount";
$sql8 = "CREATE TABLE 6dailyshortcount(ShortDate DATE, ShortCount INT)";
$sql9 = "INSERT INTO 6dailyshortcount(ShortDate, ShortCount) SELECT ShortDate, COUNT(QtyOrdered) FROM 6shortsdetail GROUP BY ShortDate";

    $query = $conn1->prepare($sql7);
    $query->execute();
	
	$query = $conn1->prepare($sql8);
    $query->execute();
	
	$query = $conn1->prepare($sql9);
    $query->execute();
//DALLAS
$sql10 = "DROP TABLE IF EXISTS 7dailyshortcount";
$sql11 = "CREATE TABLE 7dailyshortcount(ShortDate DATE, ShortCount INT)";
$sql12 = "INSERT INTO 7dailyshortcount(ShortDate, ShortCount) SELECT ShortDate, COUNT(QtyOrdered) FROM 7shortsdetail GROUP BY ShortDate";

    $query = $conn1->prepare($sql10);
    $query->execute();
	
	$query = $conn1->prepare($sql11);
    $query->execute();
	
	$query = $conn1->prepare($sql12);
    $query->execute();
//JAX
$sql13 = "DROP TABLE IF EXISTS 9dailyshortcount";
$sql14 = "CREATE TABLE 9dailyshortcount(ShortDate DATE, ShortCount INT)";
$sql15 = "INSERT INTO 9dailyshortcount(ShortDate, ShortCount) SELECT ShortDate, COUNT(QtyOrdered) FROM 9shortsdetail GROUP BY ShortDate";

    $query = $conn1->prepare($sql13);
    $query->execute();
	
	$query = $conn1->prepare($sql14);
    $query->execute();
	
	$query = $conn1->prepare($sql15);
    $query->execute();







?>