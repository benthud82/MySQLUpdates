<?php
//Need to add the feet score to each location size.  


if ($whssel == 3) { //account for case building
    //Start here with testing new optimal bay sql logic
//    $casebuild = $conn1->prepare("SELECT 
//                                    case_standardizedbays_building AS BUILDING,
//                                    case_standardizedbays_zone AS ZONE,
//                                    case_standardizedbays_aisle AS AISLE,
//                                    case
//                                        when LMGRD5 like '%D%' then 'DECK'
//                                        when LMGRD5 like '%H%' then 'HALF'
//                                        when LMGRD5 like '%P%' then 'PALLET'
//                                    end as STORAGE_TYPE,
//                                    sum(LMVOL9) AS LOC_VOL
//                                FROM
//                                    slotting.mysql_npflsm
//                                        join
//                                    slotting.case_standardizedbays ON case_standardizedbays_whse = LMWHSE
//                                        and case_standardizedbays_aisle = substring(LMBAY, 1, 3)
//                                WHERE
//                                    LMWHSE = $whssel
//                                        and case_standardizedbays_building = 2
//                                        and LMTIER in ('C03' , 'C05', 'C06', 'C19', 'C20', 'C21')
//                                GROUP BY case_standardizedbays_building , case_standardizedbays_zone , case
//                                    when LMGRD5 like '%D%' then 'DECK'
//                                    when LMGRD5 like '%H%' then 'HALF'
//                                    when LMGRD5 like '%P%' then 'PALLET'
//                                end
//                                ORDER BY case_standardizedbays_zone asc");
//    $casebuild->execute();
//    $casebuildarray = $casebuild->fetchAll(pdo::FETCH_ASSOC);



//    $mainbuild = $conn1->prepare("SELECT 
//                                    case_standardizedbays_building AS BUILDING,
//                                    case_standardizedbays_zone AS ZONE,
//                                    case_standardizedbays_aisle AS AISLE,
//                                    case
//                                        when LMGRD5 like '%D%' then 'DECK'
//                                        when LMGRD5 like '%H%' then 'HALF'
//                                        when LMGRD5 like '%P%' then 'PALLET'
//                                    end AS STORAGE_TYPE,
//                                    sum(LMVOL9) AS LOC_VOL
//                                FROM
//                                    mysql_npflsm
//                                        join
//                                    case_standardizedbays ON case_standardizedbays_whse = LMWHSE
//                                        and case_standardizedbays_aisle = substring(LMBAY, 1, 3)
//                                WHERE
//                                    LMWHSE = $whssel
//                                        and case_standardizedbays_building = 1
//                                        and LMTIER in ('C03' , 'C05', 'C06', 'C19', 'C20', 'C21')
//                                GROUP BY case_standardizedbays_building , case_standardizedbays_zone , case
//                                    when LMGRD5 like '%D%' then 'DECK'
//                                    when LMGRD5 like '%H%' then 'HALF'
//                                    when LMGRD5 like '%P%' then 'PALLET'
//                                end
//                                ORDER BY case_standardizedbays_zone asc");
//    $mainbuild->execute();
//    $mainbuildarray = $mainbuild->fetchAll(pdo::FETCH_ASSOC);




    //OLD LOGIC SQL
    $casestandardbays_pallets = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_pallets
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel and case_standardizedbays_aisle >= 'W40'
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_pallets->execute();
    $casestandardbays_palletsarray = $casestandardbays_pallets->fetchAll(pdo::FETCH_ASSOC);

    $casestandardbays_decks = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_decks
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel and case_standardizedbays_aisle >= 'W40'
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_decks->execute();
    $casestandardbays_decksarray = $casestandardbays_decks->fetchAll(pdo::FETCH_ASSOC);


    $casestandardbays_dogs_pallets = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_pallets
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel and case_standardizedbays_aisle < 'W40'
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_dogs_pallets->execute();
    $casestandardbays_dogs_palletsarray = $casestandardbays_dogs_pallets->fetchAll(pdo::FETCH_ASSOC);

    $casestandardbays_dogs_decks = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_decks
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel and case_standardizedbays_aisle < 'W40'
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_dogs_decks->execute();
    $casestandardbays_dogs_decksarray = $casestandardbays_dogs_decks->fetchAll(pdo::FETCH_ASSOC);
} else {

    //new SQL logic
//    $mainbuild = $conn1->prepare("SELECT 
//                                        LMGRD5,
//                                        case
//                                            when LMTIER in ('C01' , 'C02') then 0
//                                            else VECTOR_FEETSCORE
//                                        end as VECTOR_FEETSCORE,
//                                        count(*) as GRIDCOUNT
//                                    FROM
//                                        slotting.mysql_npflsm L
//                                            JOIN
//                                        slotting.case_vector V ON V.VECTOR_WHSE = L.LMWHSE
//                                            and V.VECTOR_SHELF = substring(L.LMLOC, 1, 6)
//                                    WHERE
//                                        LMWHSE = $whssel and LMTIER like 'C%'
//                                            and LMLOC not like ('I%')
//                                            and LMLOC not like ('Q%')
//                                            and VECTOR_BUILDING = 1
//                                    GROUP BY LMGRD5 , case
//                                        when LMTIER in ('C01' , 'C02') then 0
//                                        else VECTOR_FEETSCORE
//                                    end
//                                    ORDER BY case
//                                        when LMTIER in ('C01' , 'C02') then 0
//                                        else VECTOR_FEETSCORE
//                                    end asc;");
//    $mainbuild->execute();
//    $mainbuildarray = $mainbuild->fetchAll(pdo::FETCH_ASSOC);


    //OLD SQL Logic
    $casestandardbays_pallets = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_pallets
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_pallets->execute();
    $casestandardbays_palletsarray = $casestandardbays_pallets->fetchAll(pdo::FETCH_ASSOC);

    $casestandardbays_decks = $conn1->prepare("SELECT 
                                            case_standardizedbays_aisle,
                                            case_standardizedbays_zone,
                                            case_standardizedbays_decks
                                        FROM
                                            slotting.case_standardizedbays
                                        WHERE
                                            case_standardizedbays_whse = $whssel
                                        ORDER BY case_standardizedbays_zone asc;");
    $casestandardbays_decks->execute();
    $casestandardbays_decksarray = $casestandardbays_decks->fetchAll(pdo::FETCH_ASSOC);
}

