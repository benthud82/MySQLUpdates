<?php

if ($whssel == 3) { //account for case building
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

