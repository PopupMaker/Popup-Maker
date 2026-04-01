<?php
/**
 * Tests for PUM_Utils_Format.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Utils_Format methods.
 */
class PUM_Utils_Format_Test extends WP_UnitTestCase {

	// ─── time() ────────────────────────────────────────────────────────

	/**
	 * Test time with a valid timestamp returns the integer.
	 */
	public function test_time_valid_timestamp() {
		$ts = 1700000000;
		$this->assertSame( $ts, PUM_Utils_Format::time( $ts ) );
	}

	/**
	 * Test time with default format returns timestamp integer.
	 */
	public function test_time_default_format_is_unix() {
		$ts = 1700000000;
		$this->assertSame( $ts, PUM_Utils_Format::time( $ts, 'U' ) );
	}

	/**
	 * Test time with a date string converts to timestamp.
	 */
	public function test_time_date_string() {
		$expected = strtotime( '2024-01-15 12:00:00' );
		$this->assertSame( $expected, PUM_Utils_Format::time( '2024-01-15 12:00:00' ) );
	}

	/**
	 * Test time with invalid date string returns false.
	 */
	public function test_time_invalid_string() {
		$this->assertFalse( PUM_Utils_Format::time( 'not a date at all!!!' ) );
	}

	/**
	 * Test time with human format delegates to human_time.
	 */
	public function test_time_human_format() {
		$current = time();
		// 30 seconds ago.
		$result = PUM_Utils_Format::time( $current - 30, 'human' );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 's', $result );
	}

	/**
	 * Test time with human-readable format alias.
	 */
	public function test_time_human_readable_format() {
		$current = time();
		$result  = PUM_Utils_Format::time( $current - 30, 'human-readable' );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 's', $result );
	}

	/**
	 * Test time with string timestamp.
	 */
	public function test_time_string_timestamp() {
		$ts = '1700000000';
		$this->assertSame( 1700000000, PUM_Utils_Format::time( $ts ) );
	}

	// ─── number() ──────────────────────────────────────────────────────

	/**
	 * Test number defaults to abbreviated format.
	 */
	public function test_number_default_format() {
		$this->assertEquals( '500', PUM_Utils_Format::number( 500 ) );
	}

	/**
	 * Test number with explicit abbreviated format.
	 */
	public function test_number_abbreviated_format() {
		$this->assertEquals( '1,500', PUM_Utils_Format::number( 1500, 'abbreviated' ) );
	}

	// ─── human_time() ──────────────────────────────────────────────────

	/**
	 * Test seconds display (diff < 60).
	 */
	public function test_human_time_seconds() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - 30, $current );
		$this->assertEquals( '30s', $result );
	}

	/**
	 * Test zero seconds.
	 */
	public function test_human_time_zero_seconds() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current, $current );
		$this->assertEquals( '0s', $result );
	}

	/**
	 * Test 1 second.
	 */
	public function test_human_time_one_second() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - 1, $current );
		$this->assertEquals( '1s', $result );
	}

	/**
	 * Test 59 seconds (boundary before minutes).
	 */
	public function test_human_time_59_seconds() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - 59, $current );
		$this->assertEquals( '59s', $result );
	}

	/**
	 * Test exactly 60 seconds shows 1min.
	 */
	public function test_human_time_exactly_60_seconds() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - MINUTE_IN_SECONDS, $current );
		$this->assertEquals( '1min', $result );
	}

	/**
	 * Test minutes display.
	 */
	public function test_human_time_minutes() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 5 * MINUTE_IN_SECONDS ), $current );
		$this->assertEquals( '5min', $result );
	}

	/**
	 * Test 59 minutes (boundary before hours).
	 */
	public function test_human_time_59_minutes() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 59 * MINUTE_IN_SECONDS ), $current );
		// round(3540/60) = 59.
		$this->assertEquals( '59min', $result );
	}

	/**
	 * Test exactly 1 hour.
	 */
	public function test_human_time_exactly_one_hour() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - HOUR_IN_SECONDS, $current );
		$this->assertEquals( '1hr', $result );
	}

	/**
	 * Test hours display.
	 */
	public function test_human_time_hours() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 5 * HOUR_IN_SECONDS ), $current );
		$this->assertEquals( '5hr', $result );
	}

	/**
	 * Test 23 hours (boundary before days).
	 */
	public function test_human_time_23_hours() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 23 * HOUR_IN_SECONDS ), $current );
		$this->assertEquals( '23hr', $result );
	}

	/**
	 * Test exactly 1 day.
	 */
	public function test_human_time_exactly_one_day() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - DAY_IN_SECONDS, $current );
		$this->assertEquals( '1d', $result );
	}

	/**
	 * Test days display.
	 */
	public function test_human_time_days() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 3 * DAY_IN_SECONDS ), $current );
		$this->assertEquals( '3d', $result );
	}

	/**
	 * Test 6 days (boundary before weeks).
	 */
	public function test_human_time_6_days() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 6 * DAY_IN_SECONDS ), $current );
		$this->assertEquals( '6d', $result );
	}

	/**
	 * Test exactly 1 week.
	 */
	public function test_human_time_exactly_one_week() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - WEEK_IN_SECONDS, $current );
		$this->assertEquals( '1w', $result );
	}

	/**
	 * Test weeks display.
	 */
	public function test_human_time_weeks() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 3 * WEEK_IN_SECONDS ), $current );
		$this->assertEquals( '3w', $result );
	}

	/**
	 * Test beyond month returns empty string.
	 */
	public function test_human_time_beyond_month() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - MONTH_IN_SECONDS, $current );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test beyond month with large diff.
	 */
	public function test_human_time_very_large_diff() {
		$current = 1000000;
		$result  = PUM_Utils_Format::human_time( $current - ( 365 * DAY_IN_SECONDS ), $current );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test absolute value is used (future time).
	 */
	public function test_human_time_future_time() {
		$current = 1000000;
		// Time in the future uses abs().
		$result = PUM_Utils_Format::human_time( $current + 30, $current );
		$this->assertEquals( '30s', $result );
	}

	/**
	 * Test human_time with null current defaults to time().
	 */
	public function test_human_time_null_current() {
		$recent = time() - 10;
		$result = PUM_Utils_Format::human_time( $recent );
		$this->assertIsString( $result );
		// Should be seconds range.
		$this->assertStringContainsString( 's', $result );
	}

	/**
	 * Test the filter is applied.
	 */
	public function test_human_time_filter() {
		$filter_called = false;
		$callback      = function ( $since, $diff, $time, $current ) use ( &$filter_called ) {
			$filter_called = true;
			return 'custom';
		};

		add_filter( 'pum_human_time_diff', $callback, 10, 4 );
		$result = PUM_Utils_Format::human_time( 1000000, 1000030 );
		remove_filter( 'pum_human_time_diff', $callback, 10 );

		$this->assertTrue( $filter_called );
		$this->assertEquals( 'custom', $result );
	}

	// ─── abbreviated_number() ──────────────────────────────────────────

	/**
	 * Test small numbers are formatted with commas.
	 */
	public function test_abbreviated_number_small() {
		$this->assertEquals( '500', PUM_Utils_Format::abbreviated_number( 500 ) );
	}

	/**
	 * Test zero returns formatted zero.
	 */
	public function test_abbreviated_number_zero() {
		$this->assertEquals( '0', PUM_Utils_Format::abbreviated_number( 0 ) );
	}

	/**
	 * Test number with thousands separator.
	 */
	public function test_abbreviated_number_with_separator() {
		$this->assertEquals( '9,999', PUM_Utils_Format::abbreviated_number( 9999 ) );
	}

	/**
	 * Test exactly 10000 shows K format.
	 */
	public function test_abbreviated_number_10k() {
		$this->assertEquals( '10K', PUM_Utils_Format::abbreviated_number( 10000 ) );
	}

	/**
	 * Test 1500 is below 10000 threshold so uses number_format.
	 */
	public function test_abbreviated_number_1500() {
		$this->assertEquals( '1,500', PUM_Utils_Format::abbreviated_number( 1500 ) );
	}

	/**
	 * Test 15000 shows K format.
	 */
	public function test_abbreviated_number_15k() {
		$this->assertEquals( '15K', PUM_Utils_Format::abbreviated_number( 15000 ) );
	}

	/**
	 * Test 15500 shows 15.5K.
	 */
	public function test_abbreviated_number_15_5k() {
		$this->assertEquals( '15.5K', PUM_Utils_Format::abbreviated_number( 15500 ) );
	}

	/**
	 * Test 100000 shows 100K.
	 */
	public function test_abbreviated_number_100k() {
		$this->assertEquals( '100K', PUM_Utils_Format::abbreviated_number( 100000 ) );
	}

	/**
	 * Test 999999 shows K format.
	 */
	public function test_abbreviated_number_999k() {
		// round(999999/1000, 1) = 1000.0, number_format(1000, 0) = '1,000'.
		$this->assertEquals( '1,000K', PUM_Utils_Format::abbreviated_number( 999999 ) );
	}

	/**
	 * Test 1000000 shows M format.
	 */
	public function test_abbreviated_number_1m() {
		$this->assertEquals( '1M', PUM_Utils_Format::abbreviated_number( 1000000 ) );
	}

	/**
	 * Test 1500000 shows 1.5M.
	 */
	public function test_abbreviated_number_1_5m() {
		$this->assertEquals( '1.5M', PUM_Utils_Format::abbreviated_number( 1500000 ) );
	}

	/**
	 * Test 10000000 shows 10M.
	 */
	public function test_abbreviated_number_10m() {
		$this->assertEquals( '10M', PUM_Utils_Format::abbreviated_number( 10000000 ) );
	}

	/**
	 * Test negative number returns 0.
	 */
	public function test_abbreviated_number_negative() {
		$this->assertSame( 0, PUM_Utils_Format::abbreviated_number( -5 ) );
	}

	/**
	 * Test non-numeric string returns 0.
	 */
	public function test_abbreviated_number_non_numeric() {
		// (float)'abc' = 0.0, which is not < 0, and is_numeric(0.0) is true.
		// So 0 < 10000, number_format(0, 0) = '0'.
		$this->assertEquals( '0', PUM_Utils_Format::abbreviated_number( 'abc' ) );
	}

	/**
	 * Test numeric string input.
	 */
	public function test_abbreviated_number_string_number() {
		$this->assertEquals( '5,000', PUM_Utils_Format::abbreviated_number( '5000' ) );
	}

	/**
	 * Test float input.
	 */
	public function test_abbreviated_number_float() {
		$this->assertEquals( '100', PUM_Utils_Format::abbreviated_number( 99.9 ) );
	}

	/**
	 * Test custom decimal point.
	 */
	public function test_abbreviated_number_custom_point() {
		$this->assertEquals( '15,5K', PUM_Utils_Format::abbreviated_number( 15500, ',' ) );
	}

	/**
	 * Test custom separator.
	 */
	public function test_abbreviated_number_custom_separator() {
		$this->assertEquals( '9.999', PUM_Utils_Format::abbreviated_number( 9999, '.', '.' ) );
	}

	/**
	 * Test 1 returns '1'.
	 */
	public function test_abbreviated_number_one() {
		$this->assertEquals( '1', PUM_Utils_Format::abbreviated_number( 1 ) );
	}

	// ─── strip_white_space() ───────────────────────────────────────────

	/**
	 * Test strips tabs.
	 */
	public function test_strip_white_space_tabs() {
		$this->assertEquals( 'helloworld', PUM_Utils_Format::strip_white_space( "hello\tworld" ) );
	}

	/**
	 * Test strips newlines.
	 */
	public function test_strip_white_space_newlines() {
		$this->assertEquals( 'helloworld', PUM_Utils_Format::strip_white_space( "hello\nworld" ) );
	}

	/**
	 * Test strips carriage returns.
	 */
	public function test_strip_white_space_carriage_returns() {
		$this->assertEquals( 'helloworld', PUM_Utils_Format::strip_white_space( "hello\rworld" ) );
	}

	/**
	 * Test strips mixed whitespace characters.
	 */
	public function test_strip_white_space_mixed() {
		$this->assertEquals( 'abc', PUM_Utils_Format::strip_white_space( "a\tb\nc\r" ) );
	}

	/**
	 * Test preserves regular spaces.
	 */
	public function test_strip_white_space_preserves_spaces() {
		$this->assertEquals( 'hello world', PUM_Utils_Format::strip_white_space( 'hello world' ) );
	}

	/**
	 * Test empty string returns empty.
	 */
	public function test_strip_white_space_empty() {
		$this->assertEquals( '', PUM_Utils_Format::strip_white_space( '' ) );
	}

	/**
	 * Test default parameter returns empty.
	 */
	public function test_strip_white_space_default() {
		$this->assertEquals( '', PUM_Utils_Format::strip_white_space() );
	}

	/**
	 * Test with HTML content and whitespace.
	 */
	public function test_strip_white_space_html() {
		$input    = "<div>\n\t<p>Hello</p>\n</div>";
		$expected = '<div><p>Hello</p></div>';
		$this->assertEquals( $expected, PUM_Utils_Format::strip_white_space( $input ) );
	}

	/**
	 * Test with Windows-style line endings.
	 */
	public function test_strip_white_space_crlf() {
		$this->assertEquals( 'line1line2', PUM_Utils_Format::strip_white_space( "line1\r\nline2" ) );
	}
}
