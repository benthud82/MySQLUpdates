
<?php

//determine the number of returns per invoice by ship to.  This does not provide detail.  Need to create another file to show indivudal detail.

set_time_limit(99999);
include '../connections/conn_custaudit.php';  //conn1
include '../globalincludes/usa_asys.php';
include '../globalfunctions/custdbfunctions.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

//Find the first day of current month 1yyddd
$roll_month_start_yyyymmdd = _rollmonthyyyymmdd();  //call current month function to find start for for current month for sql
//Find the first day of current quarter 1yyddd
$roll_quarter_start_yyyymmdd = _rollqtryyyymmdd();  //call current quarter function to find start for for current quarter for sql
//Find first day for rolling 12 month 1yyddd
$rolling_12_start_yyyymmdd = _roll12yyyymmdd();  //call rolling start function to find start date for rolling 12 month sql

$current_month_start_yyddd = _rollmonthyyddd();
$rolling_12_start_yyddd = _rolling12startyyddd();

$sqldelete = "TRUNCATE TABLE custaudit.fillratemerge";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//$largecust = $aseriesconn->prepare("SELECT PBAN8 FROM HSIPCORDTA.NOTWPS, HSIPCORDTA.IM0018 WHERE PBAN8 = BILL_TO and PBSHAN = CUSTOMER GROUP BY PBAN8 HAVING (sum(case when PBSHJD >= $current_month_start_yyddd then PBBXVS else 0 end) >= 5000 or sum(case when PBSHJD >= $rolling_12_start_yyddd then PBBXVS else 0 end) >= 60000)");
//$largecust->execute();
//$largecustarray = $largecust->fetchAll(pdo::FETCH_NUM);

//Pull in fill rate issues by type for current month, quarter and rolling 12

$fillrateissues = $aseriesconn->prepare("SELECT 
    BILL_TO,
    CUSTOMER,
    sum(case
        when
            IP_FIL_TYP = 'BO'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_BO,
    sum(case
        when
            IP_FIL_TYP = 'BO'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_BO,
    sum(case
        when
            IP_FIL_TYP = 'BO'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_BO,
    sum(case
        when
            IP_FIL_TYP = 'BE'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_BE,
    sum(case
        when
            IP_FIL_TYP = 'BE'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_BE,
    sum(case
        when
            IP_FIL_TYP = 'BE'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_BE,
    sum(case
        when
            IP_FIL_TYP = 'D'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_D,
    sum(case
        when
            IP_FIL_TYP = 'D'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_D,
    sum(case
        when
            IP_FIL_TYP = 'D'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_D,
    sum(case
        when
            IP_FIL_TYP = 'XD'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_XD,
    sum(case
        when
            IP_FIL_TYP = 'XD'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_XD,
    sum(case
        when
            IP_FIL_TYP = 'XD'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_XD,
    sum(case
        when
            IP_FIL_TYP = 'XE'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_XE,
    sum(case
        when
            IP_FIL_TYP = 'XE'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_XE,
    sum(case
        when
            IP_FIL_TYP = 'XE'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_XE,
    sum(case
        when
            IP_FIL_TYP = 'XS'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_XS,
    sum(case
        when
            IP_FIL_TYP = 'XS'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_XS,
    sum(case
        when
            IP_FIL_TYP = 'XS'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_XS,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_MNT_P_LINES,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
        then
            1
        else 0
    end) as CUR_QTR_P_LINES,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
        then
            1
        else 0
    end) as R12_P_LINES,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $roll_month_start_yyyymmdd . "'
                and IP_FIL_TYP <> ' ' and IP_FIL_TYP <> 'BE'
        then
            1
        else 0
    end) as CUR_MNT_P_FR,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $roll_quarter_start_yyyymmdd . "'
               and IP_FIL_TYP <> ' ' and IP_FIL_TYP <> 'BE'
        then
            1
        else 0
    end) as CUR_QTR_P_FR,
    sum(case
        when
            IM_BRN_TYP = 'P'
                and TR_DATE >= '" . $rolling_12_start_yyyymmdd . "'
                and IP_FIL_TYP <> ' ' and IP_FIL_TYP <> 'BE'
        then
            1
        else 0
    end) as R12_P_FR
FROM
    A.HSIPCORDTA.IM0011
GROUP BY BILL_TO , CUSTOMER");
$fillrateissues->execute();
$fillrateissuesarray = $fillrateissues->fetchAll(pdo::FETCH_NUM);



$columns = 'BILLTO, SHIPTO, CUR_MNT_BO, CUR_QTR_BO, R12_BO, CUR_MNT_BE, CUR_QTR_BE, R12_BE, CUR_MNT_D, CUR_QTR_D, R12_D, CUR_MNT_XD, CUR_QTR_XD, R12_XD, CUR_MNT_XE, CUR_QTR_XE, R12_XE, CUR_MNT_XS, CUR_QTR_XS, R12_XS, CUR_MNT_P_LINES, CUR_QTR_P_LINES, R12_P_LINES, CUR_MNT_P_FR, CUR_QTR_P_FR, R12_P_FR';


$maxrange = 999;
$counter = 0;
$rowcount = count($fillrateissuesarray);


do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }
    $data = array();
    $values = array();

    while ($counter <= $maxrange) {

        $BILLTO = $fillrateissuesarray[$counter][0];
        $SHIPTO = $fillrateissuesarray[$counter][1];
        $CUR_MNT_BO = $fillrateissuesarray[$counter][2];
        $CUR_QTR_BO = $fillrateissuesarray[$counter][3];
        $R12_BO = $fillrateissuesarray[$counter][4];
        $CUR_MNT_BE = $fillrateissuesarray[$counter][5];
        $CUR_QTR_BE = $fillrateissuesarray[$counter][6];
        $R12_BE = $fillrateissuesarray[$counter][7];
        $CUR_MNT_D = $fillrateissuesarray[$counter][8];
        $CUR_QTR_D = $fillrateissuesarray[$counter][9];
        $R12_D = $fillrateissuesarray[$counter][10];
        $CUR_MNT_XD = $fillrateissuesarray[$counter][11];
        $CUR_QTR_XD = $fillrateissuesarray[$counter][12];
        $R12_XD = $fillrateissuesarray[$counter][13];
        $CUR_MNT_XE = $fillrateissuesarray[$counter][14];
        $CUR_QTR_XE = $fillrateissuesarray[$counter][15];
        $R12_XE = $fillrateissuesarray[$counter][16];
        $CUR_MNT_XS = $fillrateissuesarray[$counter][17];
        $CUR_QTR_XS = $fillrateissuesarray[$counter][18];
        $R12_XS = $fillrateissuesarray[$counter][19];
        $CUR_MNT_P_LINES = $fillrateissuesarray[$counter][20];
        $CUR_QTR_P_LINES = $fillrateissuesarray[$counter][21];
        $R12_P_LINES = $fillrateissuesarray[$counter][22];
        $CUR_MNT_P_FR = $fillrateissuesarray[$counter][23];
        $CUR_QTR_P_FR = $fillrateissuesarray[$counter][24];
        $R12_P_FR = $fillrateissuesarray[$counter][25];

        $data[] = "($BILLTO, $SHIPTO, $CUR_MNT_BO, $CUR_QTR_BO, $R12_BO, $CUR_MNT_BE, $CUR_QTR_BE, $R12_BE, $CUR_MNT_D,$CUR_QTR_D, $R12_D, $CUR_MNT_XD, $CUR_QTR_XD,$R12_XD, $CUR_MNT_XE, $CUR_QTR_XE,$R12_XE,$CUR_MNT_XS, $CUR_QTR_XS,$R12_XS, $CUR_MNT_P_LINES, $CUR_QTR_P_LINES, $R12_P_LINES,$CUR_MNT_P_FR,$CUR_QTR_P_FR, $R12_P_FR)";
        $counter +=1;
    }

    $values = implode(',', $data);
    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO custaudit.fillratemerge ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=1000;
} while ($counter <= $rowcount);


$sqldelete1 = "TRUNCATE TABLE custaudit.fillratebyshipto";
$querydelete1 = $conn1->prepare($sqldelete1);
$querydelete1->execute();


$sqlmerge = "INSERT INTO custaudit.fillratebyshipto (BILLTO, SHIPTO, CUR_MNT_BO, CUR_QTR_BO, R12_BO, CUR_MNT_BE, CUR_QTR_BE, R12_BE, CUR_MNT_D, CUR_QTR_D, R12_D, CUR_MNT_XD, CUR_QTR_XD, R12_XD, CUR_MNT_XE, CUR_QTR_XE, R12_XE, CUR_MNT_XS, CUR_QTR_XS, R12_XS, CUR_MNT_P_LINES, CUR_QTR_P_LINES, R12_P_LINES, CUR_MNT_P_FR, CUR_QTR_P_FR, R12_P_FR)
SELECT * FROM custaudit.fillratemerge;";
$querymerge = $conn1->prepare($sqlmerge);
$querymerge->execute();
