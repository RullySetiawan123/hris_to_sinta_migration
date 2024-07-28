<?php

function is_valid_date($year, $month, $day) {

    if ($year < 1753 || $year > 9999) {
    return false;
    }

    if ($month < 1 || $month > 12) {
        return false;
    }

    // Check if the day is valid for the given month and year
    return checkdate($month, $day, $year);
}

function validate_date($date_str) {

    $date_pattern = '/^(\d{4})-(\d{2})-(\d{2})$/';
    
    if (preg_match($date_pattern, $date_str, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        
        return is_valid_date($year, $month, $day);
    }

    return false;
}


function extract_date($date_time_str) {
    $date_pattern = '/^(\d{4}-\d{2}-\d{2})/';
    
    if (preg_match($date_pattern, $date_time_str, $matches)) {
        return $matches[1];
    }

    return false;
}
?>
