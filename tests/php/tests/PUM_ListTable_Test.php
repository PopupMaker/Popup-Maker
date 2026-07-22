<?php
/**
 * Tests for PUM_ListTable output escaping.
 *
 * @package Popup_Maker
 */

/**
 * Test request-derived URLs are escaped for HTML output.
 */
class PUM_ListTable_Test extends WP_UnitTestCase {

	/**
	 * Original server state.
	 *
	 * @var array
	 */
	private $server;

	/**
	 * Original request state.
	 *
	 * @var array
	 */
	private $request;

	/**
	 * Set up request state.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$this->server  = $_SERVER;
		$this->request = $_REQUEST;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		$_SERVER['HTTP_HOST']   = 'example.org';
		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php/' . $this->xss_payload() . '?post_type=popup&page=pum-subscribers&paged=3';
		$_REQUEST['paged']      = 3;
	}

	/**
	 * Restore request state.
	 */
	public function tearDown(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_SERVER  = $this->server;
		$_REQUEST = $this->request;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		parent::tearDown();
	}

	/**
	 * Test pagination URLs cannot inject anchor attributes.
	 */
	public function test_pagination_escapes_apostrophes_in_request_url() {
		$table = $this->list_table();
		$table->set_pagination_args(
			[
				'total_items' => 90,
				'per_page'    => 20,
				'total_pages' => 5,
			]
		);

		ob_start();
		$table->pagination( 'bottom' );
		$output = ob_get_clean();

		$this->assertStringContainsString( '&#039;style=&#039;animation-name:rotation', $output );
		$this->assertStringNotContainsString( "/'style='animation-name:rotation", $output );
		$this->assertAnchorsDoNotContainInjectedAttributes( $output );
	}

	/**
	 * Test view switcher URLs cannot inject anchor attributes.
	 */
	public function test_view_switcher_escapes_apostrophes_in_request_url() {
		$table = $this->list_table();

		ob_start();
		$table->view_switcher( 'list' );
		$output = ob_get_clean();

		$this->assertStringContainsString( '&#039;style=&#039;animation-name:rotation', $output );
		$this->assertStringNotContainsString( "/'style='animation-name:rotation", $output );
		$this->assertAnchorsDoNotContainInjectedAttributes( $output );
	}

	/**
	 * Test sortable column URLs escape request URL apostrophes.
	 */
	public function test_sortable_column_urls_escape_apostrophes() {
		$table = $this->list_table();

		ob_start();
		$table->print_column_headers();
		$output = ob_get_clean();

		$this->assertStringContainsString( '&#039;style=&#039;animation-name:rotation', $output );
		$this->assertStringNotContainsString( "/'style='animation-name:rotation", $output );
		$this->assertAnchorsDoNotContainInjectedAttributes( $output );
	}

	/**
	 * Return the subscribers list table.
	 *
	 * @return PUM_Admin_Subscribers_Table
	 */
	private function list_table() {
		return new PUM_Admin_Subscribers_Table( [ 'screen' => 'edit-popup' ] );
	}

	/**
	 * Return the reported attribute injection payload.
	 *
	 * @return string
	 */
	private function xss_payload() {
		return "'style='animation-name:rotation;animation-duration:1s'onanimationstart='alert(document.domain)'x='";
	}

	/**
	 * Assert rendered links do not contain attributes injected from the URL.
	 *
	 * @param string $output Rendered HTML.
	 */
	private function assertAnchorsDoNotContainInjectedAttributes( $output ) {
		$document = new DOMDocument();
		$document->loadHTML( $output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		foreach ( $document->getElementsByTagName( 'a' ) as $anchor ) {
			$this->assertFalse( $anchor->hasAttribute( 'style' ) );
			$this->assertFalse( $anchor->hasAttribute( 'onanimationstart' ) );
		}
	}
}
