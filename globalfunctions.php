<?php

//The function returns the no. of business days between two dates and it skips the holidays
function getWorkingDays($startDate, $endDate, $holidays) {

    // do strtotime calculations just once
    $endDate = strtotime($endDate);
    $startDate = strtotime($startDate);


    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
    //We add one to inlude both dates in the interval.
    $days = ($endDate - $startDate) / 86400;

    $no_full_weeks = floor($days / 7);
    $no_remaining_days = fmod($days, 7);

    //It will return 1 if it's Monday,.. ,7 for Sunday
    $the_first_day_of_week = date("N", $startDate);
    $the_last_day_of_week = date("N", $endDate);

    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
    if ($the_first_day_of_week <= $the_last_day_of_week) {
        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week)
            $no_remaining_days--;
        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week)
            $no_remaining_days--;
    }
    else {
        // (edit by Tokes to fix an edge case where the start day was a Sunday
        // and the end day was NOT a Saturday)
        // the day of the week for start is later than the day of the week for end
        if ($the_first_day_of_week == 7) {
            // if the start date is a Sunday, then we definitely subtract 1 day
            $no_remaining_days--;

            if ($the_last_day_of_week == 6) {
                // if the end date is a Saturday, then we subtract another day
                $no_remaining_days--;
            }
        } else {
            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
            // so we skip an entire weekend and subtract 2 days
            $no_remaining_days -= 2;
        }
    }

    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
    $workingDays = $no_full_weeks * 5;
    if ($no_remaining_days > 0) {
        $workingDays += $no_remaining_days;
    }

    //We subtract the holidays
    foreach ($holidays as $holiday) {
        $time_stamp = strtotime($holiday);
        //If the holiday doesn't fall in weekend
        if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N", $time_stamp) != 6 && date("N", $time_stamp) != 7)
            $workingDays--;
    }

    if ($workingDays < 0) {
        $workingDays = .01;
    }
    return $workingDays;


    ////Example:
//
//$holidays=array("2008-12-25","2008-12-26","2009-01-01");
//
//echo getWorkingDays("2008-12-22","2009-01-02",$holidays)
//// => will return 7
//
}

//Function to add business days to current date
function add_business_days_no_holidays($start_date, $business_days, $date_format) {
    $ts_start_date = strtotime($start_date);

    $weeks = floor($business_days / 5);
    $days1 = $business_days % 5;

    // Now, the end day is at a weekend?
    $init_day = date('N', $ts_start_date);
    $last_day = $init_day + $days1;
    $days2 = 0;
    if ($last_day > 5) {
//        $days2 = 8 - $last_day;
        $days2 = 2;  //add two days for going over weekend??
        
    }

    $total_days = ( $weeks * 7 ) + ( $days1 + $days2 );

    return date('Y-m-d', strtotime('+' . $total_days . ' days', $ts_start_date));
}

//Add business days taking holidays into account
function add_business_days($start_date, $business_days, $holidays, $date_format) {
    $first_end_date = add_business_days_no_holidays($start_date, $business_days, $date_format);

    // Now, what about holidays?
    $add_more_days = 0;
    foreach ($holidays as $holiday) {
        if (( strtotime($start_date) <= strtotime($holiday) ) && ( strtotime($first_end_date) >= strtotime($holiday) )) {
            $add_more_days++;
        }
    }

    $end_date = $first_end_date;
    if ($add_more_days != 0) {
        $end_date = add_business_days_no_holidays($first_end_date, $add_more_days, $date_format);
    }

    return $end_date;
}

function _searchForKey($id, $array, $searchkey) {
    foreach ($array as $key => $val) {
        if ($val[$searchkey] == $id) {
            return $key;
        }
    }
    return null;
}

function _bosourceofsupply($start_date, $business_days, $holidays, $date_format) {
    
}

function _1yydddtogregdate($date) {
    $a1 = substr($date, 3, 3);
    $a2 = substr($date, 1, 2);
    $converteddate = date("m/d/Y", mktime(0, 0, 0, 1, $a1, $a2));

    return $converteddate;
}

function _stringtimenoseconds($time) {
    if ($time <= 999) {
        $hour = substr($time, 0, 1);
        $min = substr($time, 1, 2);
        $convertedtime = $hour . ':' . $min;
    } else {
        $hour = substr($time, 0, 2);
        $min = substr($time, 2, 2);
        $convertedtime = $hour . ':' . $min;
    }


    return $convertedtime;
}

function _stringtime($time) {
    if ($time <= 99999) {
        $hour = substr($time, 0, 1);
        $min = substr($time, 1, 2);
        $sec = substr($time, 3, 2);
        $convertedtime = $hour . ':' . $min . ':' . $sec;
    } else {
        $hour = substr($time, 0, 2);
        $min = substr($time, 2, 2);
        $sec = substr($time, 4, 2);
        $convertedtime = $hour . ':' . $min . ':' . $sec;
    }


    return $convertedtime;
}


function _jdatetomysqldate($jdate) {
    $year = "20" . substr($jdate, 0, 2);
    $days = substr($jdate, 2, 3);

    $ts = mktime(0, 0, 0, 1, $days, $year);
    $mydate = date('Y-m-d', $ts);
    return $mydate;
}
