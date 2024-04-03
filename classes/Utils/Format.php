<?php
/**
 * Format Utility
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Format
 */
class PUM_Utils_Format {

	/**
	 * Removes <p></p> around URLs
	 *
	 * @param string $content
	 *
	 * @return mixed|string
	 */
	public static function unwrap_urls( $content = '' ) {
		$content = preg_replace( '/<\\w+>((([A-Za-z]{3,9}:(?:\\/\\/)?)(?:[-;:&=\\+\$,\\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\\+\$,\\w]+@)[A-Za-z0-9.-]+)((?:\\/[\\+~%\\/.\\w-_]*)?\\??(?:[-\\+=&;%@.\\w_]*)#?(?:[.\\!\\/\\\\w]*))?)<\\/\\w+>/', "$1\n\n", $content );
		$content = str_replace( '</p>', "</p>\n\n", $content );

		return $content;
	}


	/**
	 * @param        $time
	 * @param string $format U|human
	 *
	 * @return false|int|mixed
	 */
	public static function time( $time, $format = 'U' ) {
		if ( ! PUM_Utils_Time::is_timestamp( $time ) ) {
			$time = strtotime( $time );
		}

		switch ( $format ) {
			case 'human':
			case 'human-readable':
				return self::human_time( $time );
				break;

			default:
			case 'U':
				return $time;
				break;
		}
	}


	/**
	 * @param int|float $number
	 * @param string    $format
	 *
	 * @return int|string
	 */
	public static function number( $number, $format = '' ) {
		switch ( $format ) {
			default:
			case 'abbreviated':
				return self::abbreviated_number( $number );
				break;
		}
	}


	/**
	 * Convert the timestamp to a nice time format
	 *
	 * @param int      $time
	 * @param int|null $current
	 *
	 * @return mixed
	 */
	public static function human_time( $time, $current = null ) {
		if ( empty( $current ) ) {
			$current = time();
		}

		$diff = (int) abs( $current - $time );

		if ( $diff < 60 ) {
			$since = sprintf( __( '%ss', 'popup-maker' ), $diff );
		} elseif ( $diff < HOUR_IN_SECONDS ) {
			$mins = round( $diff / MINUTE_IN_SECONDS );
			if ( $mins <= 1 ) {
				$mins = 1;
			}
			$since = sprintf( __( '%smin', 'popup-maker' ), $mins );
		} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
			$hours = round( $diff / HOUR_IN_SECONDS );
			if ( $hours <= 1 ) {
				$hours = 1;
			}
			$since = sprintf( __( '%shr', 'popup-maker' ), $hours );
		} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
			$days = round( $diff / DAY_IN_SECONDS );
			if ( $days <= 1 ) {
				$days = 1;
			}
			$since = sprintf( __( '%sd', 'popup-maker' ), $days );
		} elseif ( $diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
			$weeks = round( $diff / WEEK_IN_SECONDS );
			if ( $weeks <= 1 ) {
				$weeks = 1;
			}
			$since = sprintf( __( '%sw', 'popup-maker' ), $weeks );
		} else {
			$since = '';
		}

		return apply_filters( 'pum_human_time_diff', $since, $diff, $time, $current );
	}

	/**
	 * K, M number formatting
	 *
	 * @param int|float $n
	 * @param string    $point
	 * @param string    $sep
	 *
	 * @return int|string
	 */
	public static function abbreviated_number( $n, $point = '.', $sep = ',' ) {
		if ( $n < 0 ) {
			return 0;
		}

		if ( $n < 10000 ) {
			return number_format( $n, 0, $point, $sep );
		}

		$d = $n < 1000000 ? 1000 : 1000000;
		$f = round( $n / $d, 1 );

		return number_format( $f, $f - intval( $f ) ? 1 : 0, $point, $sep ) . ( 1000 === $d ? 'K' : 'M' );
	}

	/**
	 * Strips line breaks, tabs & carriage returns from html.
	 *
	 * Used to prevent WP from adding <br> and <p> tags.
	 *
	 * @param string $string
	 *
	 * @return mixed
	 */
	public static function strip_white_space( $string = '' ) {
		return str_replace( [ "\t", "\r", "\n" ], '', $string );
	}

}
