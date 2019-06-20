<!--Code to update the MySQL tables that count ASO and AUTO moves by day-->

<?php
set_time_limit(99999);
include '../connections/conn_slotting.php';

//INDY
$sql1 = "DROP TABLE IF EXISTS 2dailymovecount";
$sql2 = "CREATE TABLE 2dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql3 = "INSERT INTO 2dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 2moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql1);
    $query->execute();
	
	$query = $conn1->prepare($sql2);
    $query->execute();
	
	$query = $conn1->prepare($sql3);
    $query->execute();





//RENO
$sql4 = "DROP TABLE IF EXISTS 3dailymovecount";
$sql5 = "CREATE TABLE 3dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql6 = "INSERT INTO 3dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 3moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql4);
    $query->execute();
	
	$query = $conn1->prepare($sql5);
    $query->execute();
	
	$query = $conn1->prepare($sql6);
    $query->execute();
//DENVER
$sql7 = "DROP TABLE IF EXISTS 6dailymovecount";
$sql8 = "CREATE TABLE 6dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql9 = "INSERT INTO 6dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 6moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql7);
    $query->execute();
	
	$query = $conn1->prepare($sql8);
    $query->execute();
	
	$query = $conn1->prepare($sql9);
    $query->execute();
//DALLAS
$sql10 = "DROP TABLE IF EXISTS 7dailymovecount";
$sql11 = "CREATE TABLE 7dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql12 = "INSERT INTO 7dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 7moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql10);
    $query->execute();
	
	$query = $conn1->prepare($sql11);
    $query->execute();
	
	$query = $conn1->prepare($sql12);
    $query->execute();
//JAX
$sql13 = "DROP TABLE IF EXISTS 9dailymovecount";
$sql14 = "CREATE TABLE 9dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql15 = "INSERT INTO 9dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 9moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql13);
    $query->execute();
	
	$query = $conn1->prepare($sql14);
    $query->execute();
	
	$query = $conn1->prepare($sql15);
    $query->execute();
//NOTL
$sql16 = "DROP TABLE IF EXISTS 11dailymovecount";
$sql17 = "CREATE TABLE 11dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql18 = "INSERT INTO 11dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 11moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql16);
    $query->execute();
	
	$query = $conn1->prepare($sql17);
    $query->execute();
	
	$query = $conn1->prepare($sql18);
    $query->execute();
//VANC
$sql19 = "DROP TABLE IF EXISTS 12dailymovecount";
$sql20 = "CREATE TABLE 12dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql21 = "INSERT INTO 12dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 12moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql19);
    $query->execute();
	
	$query = $conn1->prepare($sql20);
    $query->execute();
	
	$query = $conn1->prepare($sql21);
    $query->execute();
//Calgary
$sql22 = "DROP TABLE IF EXISTS 16dailymovecount";
$sql23 = "CREATE TABLE 16dailymovecount(MoveDate DATE, ASOCount INT, AUTOCount INT)";
$sql24 = "INSERT INTO 16dailymovecount(MoveDate, ASOCount, AUTOCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM 16moves GROUP BY MVDATE";
    $query = $conn1->prepare($sql22);
    $query->execute();
	
	$query = $conn1->prepare($sql23);
    $query->execute();
	
	$query = $conn1->prepare($sql24);
    $query->execute();

