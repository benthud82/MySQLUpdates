<?php

set_time_limit(99999);
include '../connections/conn_slotting.php';

//Delete Table
$result1 = $conn1->prepare("DROP TABLE IF EXISTS slotting.movesbyitem");
$result1->execute();

//Create Table
$result2 = $conn1->prepare("CREATE TABLE slotting.movesbyitem (KEYValue CHAR(20), MVWHSE INT(2), MVTITM INT(7), MVTPKG INT(5), CORL CHAR(1), ASOCOUNT INT(5), AUTOCOUNT INT(5))");
$result2->execute();

//Indy
$result3 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(2,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '2', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.2moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(2,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '2' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result3->execute();

//Sparks
$result4 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(3,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '3', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.3moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(3,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '3' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result4->execute();

//Denver
$result5 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(6,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '6', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.6moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(6,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '6' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result5->execute();

//Dallas
$result6 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(7,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '7', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.7moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(7,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '7' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result6->execute();

//Jax
$result7 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(9,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '9', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.9moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(9,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '9' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result7->execute();

//NOTL
$result8 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(11,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '11', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.11moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(11,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '11' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result8->execute();

//VANC
$result8 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(12,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '12', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.12moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(12,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '12' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result8->execute();

//Calgary
$result9 = $conn1->prepare("INSERT INTO slotting.movesbyitem(KEYValue, MVWHSE, MVTITM, MVTPKG, CORL, ASOCOUNT, AUTOCOUNT) SELECT CONCAT(16,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '16', MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END, SUM(CASE WHEN MVTYPE IN ('SP','SK','SO','SJ') THEN 1 ELSE 0 END), SUM(CASE WHEN MVTYPE ='RS' THEN 1 ELSE 0 END) FROM slotting.16moves WHERE MVDATE BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW() GROUP BY CONCAT(16,MVITEM,MVTPKG,CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END), '16' , MVITEM, MVTPKG, CASE WHEN MVTZNE IN (7,8) THEN 'C' ELSE 'L' END");
$result9->execute();

$conn1 = NULL;
?>