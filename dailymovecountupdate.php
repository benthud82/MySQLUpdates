<!--Code to update the MySQL tables that count ASO and AUTO moves by day-->

<?php
set_time_limit(99999);
include '../connections/conn_slotting.php';

//INDY
$sql3 = "INSERT INTO slotting.2dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.2moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql3);
$query->execute();

//RENO
$sql6 = "INSERT INTO slotting.3dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.3moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql6);
$query->execute();

//DENVER
$sql9 = "INSERT INTO slotting.6dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.6moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql9);
$query->execute();

//DALLAS
$sql12 = "INSERT INTO slotting.7dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.7moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql12);
$query->execute();

//JAX
$sql15 = "INSERT INTO slotting.9dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.9moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql15);
$query->execute();

//NOTL
$sql18 = "INSERT INTO slotting.11dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.11moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql18);
$query->execute();

//VANC
$sql21 = "INSERT INTO slotting.12dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.12moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql21);
$query->execute();

//Calgary
$sql24 = "INSERT INTO slotting.16dailymovecount(MoveDate, ASOCount, AUTOCount, CONSOLCount) SELECT MVDATE, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='CM' THEN 1 ELSE 0 END) FROM slotting.16moves GROUP BY MVDATE ON DUPLICATE KEY UPDATE ASOCount = values(ASOCount), AUTOCount = values(AUTOCount), CONSOLCount = values(CONSOLCount)";
$query = $conn1->prepare($sql24);
$query->execute();

