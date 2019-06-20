<?php

//Pull open POs by dc/item

if (!isset($whseprim)) {
    $openpo = $conn1->prepare("SELECT 
                                *,
                                case
                                    when MAXEDIDATE > '1950-01-01' then MAXEDIDATE
                                    else MAXURFDATE
                                end as CONFDATE
                            FROM
                                custaudit.urfdate_est,
                                custaudit.openpo
                            WHERE
                                urfdate_est.OPENPONUM = openpo.OPENPONUM
                                    and urfdate_est.OPENITEM = openpo.OPENITEM
                                    and openpo.OPENITEM = $orditem 
                            ORDER BY CONFDATE asc");

    $openpo->execute();
    $openpoarray = $openpo->fetchAll(PDO::FETCH_ASSOC);
} else {
    $whseprim = intval($whseprim);
    $openpo = $conn1->prepare("SELECT 
                                *,
                                case
                                    when MAXEDIDATE > '1950-01-01' then MAXEDIDATE
                                    else MAXURFDATE
                                end as CONFDATE
                            FROM
                                custaudit.urfdate_est,
                                custaudit.openpo
                            WHERE
                                urfdate_est.OPENPONUM = openpo.OPENPONUM
                                    and urfdate_est.OPENITEM = openpo.OPENITEM
                                    and openpo.OPENWHSE = $whseprim
                                    and openpo.OPENITEM = $orditem 
                            ORDER BY CONFDATE asc");

    $openpo->execute();
    $openpoarray = $openpo->fetchAll(PDO::FETCH_ASSOC);
}