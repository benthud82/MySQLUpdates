<!--Code to update the MySQL tables that count ASO and AUTO moves by day-->

<?php
set_time_limit(99999);
include '../connections/conn_slotting.php';

//INDY
$sql1 = "DROP TABLE IF EXISTS slotting.2dailymovecount";
$sql2 = "CREATE TABLE slotting.2dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql3 = "INSERT INTO slotting.2dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.2moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql1);
    $query->execute();
	
	$query = $conn1->prepare($sql2);
    $query->execute();
	
	$query = $conn1->prepare($sql3);
    $query->execute();





//RENO
$sql4 = "DROP TABLE IF EXISTS slotting.3dailymovecount";
$sql5 = "CREATE TABLE slotting.3dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql6 = "INSERT INTO slotting.3dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.3moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql4);
    $query->execute();
	
	$query = $conn1->prepare($sql5);
    $query->execute();
	
	$query = $conn1->prepare($sql6);
    $query->execute();
//DENVER
$sql7 = "DROP TABLE IF EXISTS slotting.6dailymovecount";
$sql8 = "CREATE TABLE slotting.6dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql9 = "INSERT INTO slotting.6dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.6moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql7);
    $query->execute();
	
	$query = $conn1->prepare($sql8);
    $query->execute();
	
	$query = $conn1->prepare($sql9);
    $query->execute();
//DALLAS
$sql10 = "DROP TABLE IF EXISTS slotting.7dailymovecount";
$sql11 = "CREATE TABLE slotting.7dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql12 = "INSERT INTO slotting.7dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.7moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql10);
    $query->execute();
	
	$query = $conn1->prepare($sql11);
    $query->execute();
	
	$query = $conn1->prepare($sql12);
    $query->execute();
//JAX
$sql13 = "DROP TABLE IF EXISTS slotting.9dailymovecount";
$sql14 = "CREATE TABLE slotting.9dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql15 = "INSERT INTO slotting.9dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.9moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql13);
    $query->execute();
	
	$query = $conn1->prepare($sql14);
    $query->execute();
	
	$query = $conn1->prepare($sql15);
    $query->execute();
//NOTL
$sql16 = "DROP TABLE IF EXISTS slotting.11dailymovecount";
$sql17 = "CREATE TABLE slotting.11dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql18 = "INSERT INTO slotting.11dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.11moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql16);
    $query->execute();
	
	$query = $conn1->prepare($sql17);
    $query->execute();
	
	$query = $conn1->prepare($sql18);
    $query->execute();
//VANC
$sql19 = "DROP TABLE IF EXISTS slotting.12dailymovecount";
$sql20 = "CREATE TABLE slotting.12dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql21 = "INSERT INTO slotting.12dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.12moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql19);
    $query->execute();
	
	$query = $conn1->prepare($sql20);
    $query->execute();
	
	$query = $conn1->prepare($sql21);
    $query->execute();
//Calgary
$sql22 = "DROP TABLE IF EXISTS slotting.16dailymovecount";
$sql23 = "CREATE TABLE slotting.16dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql24 = "INSERT INTO slotting.16dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.16moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql22);
    $query->execute();
	
	$query = $conn1->prepare($sql23);
    $query->execute();
	
	$query = $conn1->prepare($sql24);
    $query->execute();

