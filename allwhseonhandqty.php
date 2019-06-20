<?php

//Pull allcocated qty by whse
if (!isset($aseriesconn)) {
    include_once '../globalincludes/usa_asys.php';
}


if (!isset($whseprim)) {

    $onhandqty = $aseriesconn->prepare("SELECT 
                                    WRSWHS as WHSE,
                                    WRSITM as ITEM,
                                    WRSPOH + WRSROH + WRSIMP as OHQTY
                                FROM
                                    HSIPCORDTA.NPFWRS
                                WHERE
                                    WRSITM = '$orditem'
                                ORDER BY WRSWHS");

    $onhandqty->execute();
    $onhandqtyarray = $onhandqty->fetchAll(PDO::FETCH_ASSOC);
} else {
    $whseprim = intval($whseprim);
    $onhandqty = $aseriesconn->prepare("SELECT 
                                    WRSWHS as WHSE,
                                    WRSITM as ITEM,
                                    WRSPOH + WRSROH + WRSIMP as OHQTY
                                FROM
                                    HSIPCORDTA.NPFWRS
                                WHERE
                                    WRSITM = '$orditem'
                                ORDER BY WRSWHS");

    $onhandqty->execute();
    $onhandqtyarray = $onhandqty->fetchAll(PDO::FETCH_ASSOC);
}


