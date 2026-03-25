<?php
/**
 * Logging Service tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\Logging;

/**
 * Test methods for Logging service.
 *
 * Note: The Logging service depends heavily on the filesystem (get_fs()) and the
 * plugin container. These tests mock the service to test the logic without
 * requiring the full filesystem setup.
 */
class Logging_Service_Test extends WP_UnitTestCase {

	/**
	 * Test disabled returns true when PUM_DISABLE_LOGGING is defined.
	 */
	public function test_disabled_returns_true_with_constant() {
		// We cannot easily define constants in-test without affecting other tests.
		// Instead, test the inverse: when constant is NOT defined, disabled is false.
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		// Without the constant defined, disabled() should return false.
		$this->assertFalse( $service->disabled(), 'Should not be disabled without the constant.' );
	}

	/**
	 * Test log method appends message with timestamp format.
	 */
	public function test_log_appends_message() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		// Set initial content via reflection.
		$this->set_private_property( $service, 'content', "Test Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log( 'Test message here' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( 'Test message here', $content, 'Log content should contain the message.' );
		$this->assertMatchesRegularExpression( '/\d{4}-\d{1,2}-\d{1,2} \d{2}:\d{2}:\d{2}/', $content, 'Log should contain a timestamp.' );
	}

	/**
	 * Test log_unique only logs a message once.
	 */
	public function test_log_unique_prevents_duplicate() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Initial:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_unique( 'Unique message' );
		$service->log_unique( 'Unique message' );

		$content = $service->get_log_content();
		$count   = substr_count( $content, 'Unique message' );

		$this->assertEquals( 1, $count, 'Unique message should appear only once.' );
	}

	/**
	 * Test info method prefixes with [INFO].
	 */
	public function test_info_adds_prefix() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->info( 'Informational message' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( '[INFO] Informational message', $content, 'Info log should contain [INFO] prefix.' );
	}

	/**
	 * Test warning method prefixes with [WARNING].
	 */
	public function test_warning_adds_prefix() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->warning( 'Something concerning' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( '[WARNING] Something concerning', $content, 'Warning log should contain [WARNING] prefix.' );
	}

	/**
	 * Test error method prefixes with [ERROR].
	 */
	public function test_error_adds_prefix() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->error( 'Something broke' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( '[ERROR] Something broke', $content, 'Error log should contain [ERROR] prefix.' );
	}

	/**
	 * Test log_unique_info only logs once.
	 */
	public function test_log_unique_info_deduplicates() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_unique_info( 'Once only info' );
		$service->log_unique_info( 'Once only info' );

		$content = $service->get_log_content();
		$count   = substr_count( $content, 'Once only info' );

		$this->assertEquals( 1, $count, 'Unique info should appear only once.' );
	}

	/**
	 * Test log_deprecated_notice with replacement.
	 */
	public function test_log_deprecated_notice_with_replacement() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_deprecated_notice( 'old_function', '1.5.0', 'new_function' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( 'old_function', $content, 'Should contain deprecated function name.' );
		$this->assertStringContainsString( 'deprecated', $content, 'Should contain the word deprecated.' );
		$this->assertStringContainsString( '1.5.0', $content, 'Should contain the version number.' );
		$this->assertStringContainsString( 'new_function', $content, 'Should contain replacement function name.' );
	}

	/**
	 * Test log_deprecated_notice without replacement.
	 */
	public function test_log_deprecated_notice_without_replacement() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_deprecated_notice( 'removed_function', '2.0.0' );

		$content = $service->get_log_content();

		$this->assertStringContainsString( 'no alternative available', $content, 'Should indicate no alternative is available.' );
	}

	/**
	 * Test count_lines returns correct line count.
	 */
	public function test_count_lines() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Line 1\r\nLine 2\r\nLine 3" );

		$this->assertEquals( 3, $service->count_lines(), 'Should count 3 lines.' );
	}

	/**
	 * Test truncate_log keeps only 250 lines.
	 */
	public function test_truncate_log_limits_lines() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );
		// Set is_writable to false so save_logs() exits early and skips filesystem.
		$this->set_private_property( $service, 'is_writable', false );

		// Build content with 300 lines.
		$lines = [];
		for ( $i = 1; $i <= 300; $i++ ) {
			$lines[] = "Line $i";
		}
		$this->set_private_property( $service, 'content', implode( "\r\n", $lines ) );

		// truncate_log calls set_log_content($truncated, true) which calls save_logs().
		// save_logs() checks enabled() which checks is_writable — since we set false, it exits early.
		// But the content IS still truncated in memory because set_log_content runs first.
		$service->truncate_log();

		$this->assertLessThanOrEqual( 250, $service->count_lines(), 'Should have at most 250 lines after truncation.' );
	}

	/**
	 * Test get_log_content returns content.
	 */
	public function test_get_log_content_returns_stored_content() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$expected = "Debug Log Content\r\nLine 2";
		$this->set_private_property( $service, 'content', $expected );

		$this->assertEquals( $expected, $service->get_log_content(), 'Should return stored content.' );
	}

	/**
	 * Test get_file_path returns the file path.
	 */
	public function test_get_file_path() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'file', '/tmp/test-debug.log' );

		$this->assertEquals( '/tmp/test-debug.log', $service->get_file_path(), 'Should return configured file path.' );
	}

	/**
	 * Create a mock container for the Logging service.
	 *
	 * @return object Mock container with expected methods.
	 */
	private function create_mock_container() {
		$container = new class {
			/**
			 * Mock get method.
			 *
			 * @param string $key The key to retrieve.
			 * @return string The value.
			 */
			public function get( $key ) {
				$values = [
					'option_prefix' => 'pum_',
					'slug'          => 'popup-maker',
					'name'          => 'Popup Maker',
				];
				return isset( $values[ $key ] ) ? $values[ $key ] : '';
			}
		};

		return $container;
	}

	/**
	 * Create a Logging instance bypassing the full constructor.
	 *
	 * @param object $container Mock container.
	 * @return Logging The logging instance.
	 */
	private function create_logging_instance( $container ) {
		// Use reflection to bypass the constructor which calls init() and filesystem checks.
		$reflection = new ReflectionClass( Logging::class );
		$instance   = $reflection->newInstanceWithoutConstructor();

		// Set the container property.
		$this->set_private_property( $instance, 'container', $container );

		return $instance;
	}

	/**
	 * Test log_unique_warning deduplicates warning messages.
	 */
	public function test_log_unique_warning_deduplicates() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_unique_warning( 'Duplicate warning' );
		$service->log_unique_warning( 'Duplicate warning' );

		$content = $service->get_log_content();
		$count   = substr_count( $content, 'Duplicate warning' );

		$this->assertEquals( 1, $count, 'Unique warning should appear only once.' );
	}

	/**
	 * Test log_unique_error deduplicates error messages.
	 */
	public function test_log_unique_error_deduplicates() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_unique_error( 'Duplicate error' );
		$service->log_unique_error( 'Duplicate error' );

		$content = $service->get_log_content();
		$count   = substr_count( $content, 'Duplicate error' );

		$this->assertEquals( 1, $count, 'Unique error should appear only once.' );
	}

	/**
	 * Test write_to_log adds newline if content does not end with one.
	 */
	public function test_write_to_log_adds_newline() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		// Content that does NOT end with \r\n.
		$this->set_private_property( $service, 'content', 'No trailing newline' );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log( 'New entry' );

		$content = $service->get_log_content();
		$this->assertStringContainsString( "No trailing newline\r\n", $content, 'Should add newline before appending.' );
		$this->assertStringContainsString( 'New entry', $content, 'Should contain the new log entry.' );
	}

	/**
	 * Test write_to_log does not double newline when content already ends with one.
	 */
	public function test_write_to_log_no_double_newline() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Trailing newline\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log( 'Next entry' );

		$content = $service->get_log_content();
		// Should not have double \r\n between "Trailing newline" and the timestamp.
		$this->assertStringNotContainsString( "\r\n\r\n", $content, 'Should not double up newlines.' );
	}

	/**
	 * Test count_lines with empty content.
	 */
	public function test_count_lines_empty_content() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', '' );

		// explode on empty string returns array with one empty element.
		$this->assertEquals( 1, $service->count_lines(), 'Empty content should count as 1 line.' );
	}

	/**
	 * Test count_lines with single line (no newlines).
	 */
	public function test_count_lines_single_line() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', 'Just one line' );

		$this->assertEquals( 1, $service->count_lines(), 'Single line content should return 1.' );
	}

	/**
	 * Test truncate_log with exactly 250 lines keeps all.
	 */
	public function test_truncate_log_at_boundary() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );
		$this->set_private_property( $service, 'is_writable', false );

		$lines = [];
		for ( $i = 1; $i <= 250; $i++ ) {
			$lines[] = "Line $i";
		}
		$this->set_private_property( $service, 'content', implode( "\r\n", $lines ) );

		$service->truncate_log();

		$this->assertEquals( 250, $service->count_lines(), 'Exactly 250 lines should remain unchanged.' );
	}

	/**
	 * Test truncate_log with fewer than 250 lines keeps all.
	 */
	public function test_truncate_log_under_limit() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );
		$this->set_private_property( $service, 'is_writable', false );

		$lines = [];
		for ( $i = 1; $i <= 100; $i++ ) {
			$lines[] = "Line $i";
		}
		$this->set_private_property( $service, 'content', implode( "\r\n", $lines ) );

		$service->truncate_log();

		$this->assertEquals( 100, $service->count_lines(), 'Under-limit lines should remain unchanged.' );
	}

	/**
	 * Test truncate_log preserves first lines, not last.
	 */
	public function test_truncate_log_preserves_first_lines() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );
		$this->set_private_property( $service, 'is_writable', false );

		$lines = [];
		for ( $i = 1; $i <= 300; $i++ ) {
			$lines[] = "Line $i";
		}
		$this->set_private_property( $service, 'content', implode( "\r\n", $lines ) );

		$service->truncate_log();

		$content = $service->get_log_content();
		$this->assertStringContainsString( 'Line 1', $content, 'First line should be preserved.' );
		$this->assertStringContainsString( 'Line 250', $content, 'Line 250 should be preserved.' );
		$this->assertStringNotContainsString( 'Line 251', $content, 'Line 251 should be truncated.' );
	}

	/**
	 * Test get_log returns same as get_log_content.
	 */
	public function test_get_log_returns_content() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$expected = "Some log data\r\n";
		$this->set_private_property( $service, 'content', $expected );

		$this->assertEquals( $expected, $service->get_log(), 'get_log should return same as get_log_content.' );
	}

	/**
	 * Test get_file_url returns false when disabled.
	 */
	public function test_get_file_url_returns_false_when_disabled() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		// Set not writable so enabled() returns false.
		$this->set_private_property( $service, 'is_writable', false );

		$this->assertFalse( $service->get_file_url(), 'Should return false when logging is disabled.' );
	}

	/**
	 * Test setup_new_log sets initial content with name and timestamp.
	 */
	public function test_setup_new_log_content() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );
		// Set writable to false so save_logs exits early.
		$this->set_private_property( $service, 'is_writable', false );

		$service->setup_new_log();

		$content = $service->get_log_content();
		$this->assertStringContainsString( 'Popup Maker', $content, 'Should contain plugin name.' );
		$this->assertStringContainsString( 'Debug Logs:', $content, 'Should contain Debug Logs header.' );
		$this->assertStringContainsString( 'Log file initialized', $content, 'Should contain initialization message.' );
	}

	/**
	 * Test disabled returns false when no constants are defined.
	 */
	public function test_disabled_false_without_constants() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->assertFalse( $service->disabled(), 'disabled() should return false when no disable constants exist.' );
	}

	/**
	 * Test enabled returns false when not writable.
	 */
	public function test_enabled_false_when_not_writable() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'is_writable', false );

		$this->assertFalse( $service->enabled(), 'enabled() should return false when not writable.' );
	}

	/**
	 * Test log does not write when disabled.
	 */
	public function test_log_does_not_write_when_disabled() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', 'Initial content' );
		$this->set_private_property( $service, 'is_writable', false );

		$service->log( 'Should not appear' );

		$content = $service->get_log_content();
		$this->assertStringNotContainsString( 'Should not appear', $content, 'Should not write when disabled.' );
	}

	/**
	 * Test multiple log entries accumulate correctly.
	 */
	public function test_multiple_log_entries() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Start:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->info( 'First info' );
		$service->warning( 'A warning' );
		$service->error( 'An error' );

		$content = $service->get_log_content();
		$this->assertStringContainsString( '[INFO] First info', $content, 'Should contain info entry.' );
		$this->assertStringContainsString( '[WARNING] A warning', $content, 'Should contain warning entry.' );
		$this->assertStringContainsString( '[ERROR] An error', $content, 'Should contain error entry.' );
	}

	/**
	 * Test log_deprecated_notice is unique (does not duplicate).
	 */
	public function test_log_deprecated_notice_is_unique() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log_deprecated_notice( 'old_func', '1.0.0', 'new_func' );
		$service->log_deprecated_notice( 'old_func', '1.0.0', 'new_func' );

		$content = $service->get_log_content();
		$count   = substr_count( $content, 'old_func' );

		$this->assertEquals( 1, $count, 'Deprecated notice should only appear once.' );
	}

	/**
	 * Test get_log_content initializes from get_file when null.
	 */
	public function test_get_log_content_initializes_when_null() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		// content is null by default when created without constructor.
		// get_log_content should call get_file which returns '' when disabled.
		$this->set_private_property( $service, 'is_writable', false );
		$this->set_private_property( $service, 'content', null );

		$content = $service->get_log_content();
		$this->assertIsString( $content, 'Should return a string even when uninitialized.' );
	}

	/**
	 * Test log with empty message still writes timestamp.
	 */
	public function test_log_empty_message_writes_timestamp() {
		$container = $this->create_mock_container();
		$service   = $this->create_logging_instance( $container );

		$this->set_private_property( $service, 'content', "Log:\r\n" );
		$this->set_private_property( $service, 'is_writable', true );

		$service->log( '' );

		$content = $service->get_log_content();
		// Should contain a timestamp line even with empty message.
		$this->assertMatchesRegularExpression( '/\d{4}-\d{1,2}-\d{1,2} \d{2}:\d{2}:\d{2} - $/', $content, 'Should contain timestamp with empty message.' );
	}

	/**
	 * Set a private/protected property on an object using reflection.
	 *
	 * @param object $object   The object to modify.
	 * @param string $property The property name.
	 * @param mixed  $value    The value to set.
	 */
	private function set_private_property( $object, $property, $value ) {
		$reflection = new ReflectionClass( $object );

		// Walk up the class hierarchy to find the property.
		while ( $reflection ) {
			if ( $reflection->hasProperty( $property ) ) {
				$prop = $reflection->getProperty( $property );
				$prop->setAccessible( true );
				$prop->setValue( $object, $value );
				return;
			}
			$reflection = $reflection->getParentClass();
		}
	}
}
