<?php
/**
 * Jalali (Shamsi) Date Conversion Functions
 * Based on the algorithm by Kazimierz M. Borkowski
 * Accurate for practical use in WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Convert Gregorian to Jalali
 *
 * @param int $gy Year
 * @param int $gm Month
 * @param int $gd Day
 * @return array [jy, jm, jd]
 */
function ferdowsi_gregorian_to_jalali( $gy, $gm, $gd ) {
    $g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
    $gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
    $days = 355666 + ( 365 * $gy ) + ( (int) ( ( $gy2 + 3 ) / 4 ) ) - ( (int) ( ( $gy2 + 99 ) / 100 ) ) + ( (int) ( ( $gy2 + 399 ) / 400 ) ) + $gd + $g_d_m[ $gm - 1 ];
    $jy = -1595 + ( 33 * ( (int) ( $days / 12053 ) ) );
    $days %= 12053;
    $jy += 4 * ( (int) ( $days / 1461 ) );
    $days %= 1461;
    if ( $days > 365 ) {
        $jy += (int) ( ( $days - 1 ) / 365 );
        $days = ( $days - 1 ) % 365;
    }
    if ( $days < 186 ) {
        $jm = 1 + (int) ( $days / 31 );
        $jd = 1 + ( $days % 31 );
    } else {
        $jm = 7 + (int) ( ( $days - 186 ) / 30 );
        $jd = 1 + ( ( $days - 186 ) % 30 );
    }
    return array( $jy, $jm, $jd );
}

/**
 * Convert Jalali to Gregorian
 *
 * @param int $jy Year
 * @param int $jm Month
 * @param int $jd Day
 * @return array [gy, gm, gd]
 */
function ferdowsi_jalali_to_gregorian( $jy, $jm, $jd ) {
    $jy += 1595;
    $days = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + ( (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) ) + $jd + ( ( $jm < 7 ) ? ( ( $jm - 1 ) * 31 ) : ( ( ( $jm - 7 ) * 30 ) + 186 ) );
    $gy = 400 * ( (int) ( $days / 146097 ) );
    $days %= 146097;
    if ( $days > 36524 ) {
        $gy += 100 * ( (int) ( --$days / 36524 ) );
        $days %= 36524;
        if ( $days >= 365 ) {
            $days++;
        }
    }
    $gy += 4 * ( (int) ( $days / 1461 ) );
    $days %= 1461;
    if ( $days > 365 ) {
        $gy += (int) ( ( $days - 1 ) / 365 );
        $days = ( $days - 1 ) % 365;
    }
    $gd = $days + 1;
    $sal_a = array( 0, 31, ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    for ( $gm = 1; $gm < 13 && $gd > $sal_a[ $gm ]; $gm++ ) {
        $gd -= $sal_a[ $gm ];
    }
    return array( $gy, $gm, $gd );
}

/**
 * Check if Jalali year is leap
 */
function ferdowsi_is_jalali_leap( $jy ) {
    return ( ( ( ( $jy + 12 ) % 33 ) % 4 ) === 1 );
}

/**
 * Get Persian month names
 */
function ferdowsi_get_month_names( $short = false ) {
    if ( $short ) {
        return array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        );
    }
    return array(
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    );
}

/**
 * Get Persian weekday names
 */
function ferdowsi_get_weekday_names( $short = false ) {
    if ( $short ) {
        return array( 'ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش' ); // Starting from Sunday? Adjust based on need
    }
    // Index 0 = Sunday for PHP date('w')
    return array(
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
        6 => 'شنبه',
    );
}

/**
 * Format a timestamp to Jalali date string
 *
 * @param int    $timestamp Unix timestamp
 * @param string $format    Format string (compatible with date() but Jalali aware)
 * @param bool   $persian_digits Convert digits to Persian
 * @return string
 */
function ferdowsi_jdate( $timestamp = null, $format = 'Y/m/d', $persian_digits = true ) {
    if ( null === $timestamp ) {
        $timestamp = current_time( 'timestamp' );
    }
    if ( ! is_numeric( $timestamp ) ) {
        $timestamp = strtotime( $timestamp );
    }

    $gy = (int) date( 'Y', $timestamp );
    $gm = (int) date( 'n', $timestamp );
    $gd = (int) date( 'j', $timestamp );
    $h  = date( 'H', $timestamp );
    $i  = date( 'i', $timestamp );
    $s  = date( 's', $timestamp );
    $w  = (int) date( 'w', $timestamp );

    list( $jy, $jm, $jd ) = ferdowsi_gregorian_to_jalali( $gy, $gm, $gd );

    $months     = ferdowsi_get_month_names();
    $weekdays   = ferdowsi_get_weekday_names();
    $months_short = ferdowsi_get_month_names( true );

    $replacements = array(
        'Y' => $jy,
        'y' => substr( (string) $jy, -2 ),
        'm' => str_pad( $jm, 2, '0', STR_PAD_LEFT ),
        'n' => $jm,
        'd' => str_pad( $jd, 2, '0', STR_PAD_LEFT ),
        'j' => $jd,
        'F' => $months[ $jm ],
        'M' => $months_short[ $jm ],
        'l' => $weekdays[ $w ],
        'D' => $weekdays[ $w ], // short could be improved
        'H' => $h,
        'i' => $i,
        's' => $s,
        'A' => ( (int) $h < 12 ) ? 'ق.ظ' : 'ب.ظ',
        'a' => ( (int) $h < 12 ) ? 'ق.ظ' : 'ب.ظ',
        'g' => ( (int) $h % 12 ) ?: 12,
        'G' => (int) $h,
    );

    $result = '';
    $len = strlen( $format );
    for ( $k = 0; $k < $len; $k++ ) {
        $char = $format[ $k ];
        if ( isset( $replacements[ $char ] ) ) {
            $result .= $replacements[ $char ];
        } else {
            $result .= $char;
        }
    }

    if ( $persian_digits ) {
        $result = ferdowsi_to_persian_digits( $result );
    }

    return $result;
}

/**
 * Convert English digits to Persian digits
 */
function ferdowsi_to_persian_digits( $str ) {
    $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
    $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
    return str_replace( $en, $fa, $str );
}

/**
 * Convert Persian digits to English
 */
function ferdowsi_to_english_digits( $str ) {
    $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
    $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
    return str_replace( $fa, $en, $str );
}

/**
 * Convert Arabic characters to Persian
 */
function ferdowsi_arabic_to_persian( $str ) {
    $arabic = array( 'ي', 'ك', 'ة' );
    $persian = array( 'ی', 'ک', 'ه' );
    return str_replace( $arabic, $persian, $str );
}
