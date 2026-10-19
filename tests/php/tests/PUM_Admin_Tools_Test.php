<?php
/**
 * Tests for PUM_Admin_Tools.
 *
 * @package Popup_Maker
 */

/**
 * Test the Easy Modal v2 import request handling in PUM_Admin_Tools.
 */
class PUM_Admin_Tools_Test extends WP_UnitTestCase {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Editor user ID (cannot manage options).
	 *
	 * @var int
	 */
	private static $editor_id;

	/**
	 * Location passed to wp_redirect(), or null when no redirect happened.
	 *
	 * @var string|null
	 */
	private $redirect;

	/**
	 * Set up shared fixtures once for the entire test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id  = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$editor_id = $factory->user->create( [ 'role' => 'editor' ] );
	}

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		// Easy Modal v2 tables, as the Easy Modal plugin left them behind.
		$tables = [
			'em_themes'      => 'name varchar(150) NOT NULL DEFAULT \'\', created datetime NULL, modified datetime NULL, is_system tinyint(1) NOT NULL DEFAULT 0, is_trash tinyint(1) NOT NULL DEFAULT 0',
			'em_theme_metas' => 'theme_id bigint(20) unsigned NOT NULL, overlay longtext, container longtext, close longtext, title longtext, content longtext',
			'em_modals'      => 'theme_id bigint(20) unsigned NOT NULL DEFAULT 1, name varchar(150) NOT NULL DEFAULT \'\', title varchar(255) NOT NULL DEFAULT \'\', content longtext, created datetime NULL, modified datetime NULL, is_sitewide tinyint(1) NOT NULL DEFAULT 0, is_system tinyint(1) NOT NULL DEFAULT 0, is_trash tinyint(1) NOT NULL DEFAULT 0',
			'em_modal_metas' => 'modal_id bigint(20) unsigned NOT NULL, display longtext, close longtext',
		];

		foreach ( $tables as $table => $columns ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "CREATE TABLE {$wpdb->prefix}{$table} ( id bigint(20) unsigned NOT NULL AUTO_INCREMENT, {$columns}, PRIMARY KEY (id) )" );
		}

		$this->redirect = null;
		add_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );

		wp_set_current_user( self::$admin_id );
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		unset( $_REQUEST['popmake_emodal_v2_import'], $_REQUEST['popmake_emodal_v2_import_nonce'] );
		remove_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );

		parent::tearDown();
	}

	/**
	 * Record the redirect location and cancel the redirect.
	 *
	 * @param string $location Redirect location.
	 *
	 * @return false
	 */
	public function capture_redirect( $location ) {
		$this->redirect = $location;

		return false;
	}

	/**
	 * Build a nested array with every given dot-separated path set to a value.
	 *
	 * @param string[] $paths Dot-separated key paths.
	 * @param string   $value Value to set at each path.
	 *
	 * @return array
	 */
	private function nested( array $paths, $value ) {
		$result = [];

		foreach ( $paths as $path ) {
			$ref = &$result;
			foreach ( explode( '.', $path ) as $key ) {
				if ( ! isset( $ref[ $key ] ) ) {
					$ref[ $key ] = [];
				}
				$ref = &$ref[ $key ];
			}
			$ref = $value;
			unset( $ref );
		}

		return $result;
	}

	/**
	 * Insert an Easy Modal v2 theme with its meta.
	 *
	 * @param string $name          Theme name.
	 * @param string $overlay_color Overlay background color.
	 *
	 * @return int Easy Modal theme ID.
	 */
	private function insert_em_theme( $name, $overlay_color ) {
		global $wpdb;

		$box    = [ 'background.color', 'background.opacity', 'border.radius', 'border.style', 'border.color', 'border.width', 'boxshadow.inset', 'boxshadow.horizontal', 'boxshadow.vertical', 'boxshadow.blur', 'boxshadow.spread', 'boxshadow.color', 'boxshadow.opacity' ];
		$shadow = [ 'textshadow.horizontal', 'textshadow.vertical', 'textshadow.blur', 'textshadow.color', 'textshadow.opacity' ];
		$font   = [ 'font.color', 'font.family', 'font.weight', 'font.style' ];

		$overlay                        = $this->nested( [ 'background.opacity' ], '50' );
		$overlay['background']['color'] = $overlay_color;
		$container                      = $this->nested( array_merge( [ 'padding' ], $box ), '1' );
		$title                          = $this->nested( array_merge( [ 'font.size', 'text.align' ], $font, $shadow ), '1' );
		$content                        = $this->nested( $font, '1' );
		$close                          = $this->nested( array_merge( [ 'text', 'padding', 'location', 'position.top', 'position.left', 'position.bottom', 'position.right', 'font.size' ], $font, $box, $shadow ), '1' );
		$close['text']                  = 'Close';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $wpdb->prefix . 'em_themes', [ 'name' => $name ] );
		$theme_id = $wpdb->insert_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'em_theme_metas',
			[
				'theme_id'  => $theme_id,
				'overlay'   => maybe_serialize( $overlay ),
				'container' => maybe_serialize( $container ),
				'close'     => maybe_serialize( $close ),
				'title'     => maybe_serialize( $title ),
				'content'   => maybe_serialize( $content ),
			]
		);

		return $theme_id;
	}

	/**
	 * Insert an Easy Modal v2 modal with its meta.
	 *
	 * @param int    $theme_id Easy Modal theme ID.
	 * @param string $name     Modal name.
	 * @param string $size     Modal display size.
	 *
	 * @return int Easy Modal modal ID.
	 */
	private function insert_em_modal( $theme_id, $name, $size ) {
		global $wpdb;

		$display         = $this->nested( [ 'overlay_disabled', 'custom_width', 'custom_width_unit', 'custom_height', 'custom_height_unit', 'custom_height_auto', 'location', 'position.top', 'position.left', 'position.bottom', 'position.right', 'position.fixed', 'animation.type', 'animation.speed', 'animation.origin' ], '1' );
		$display['size'] = $size;
		$close           = $this->nested( [ 'overlay_click', 'esc_press' ], '1' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'em_modals',
			[
				'theme_id' => $theme_id,
				'name'     => $name,
				'title'    => $name . ' title',
				'content'  => $name . ' content',
			]
		);
		$modal_id = $wpdb->insert_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'em_modal_metas',
			[
				'modal_id' => $modal_id,
				'display'  => maybe_serialize( $display ),
				'close'    => maybe_serialize( $close ),
			]
		);

		return $modal_id;
	}

	/**
	 * Find the post imported from an Easy Modal record.
	 *
	 * @param string $post_type Post type.
	 * @param string $meta_key  Meta key holding the Easy Modal ID.
	 * @param int    $em_id     Easy Modal ID.
	 *
	 * @return WP_Post[]
	 */
	private function get_imported( $post_type, $meta_key, $em_id ) {
		return get_posts(
			[
				'post_type'   => $post_type,
				'post_status' => 'any',
				// phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_key'    => $meta_key,
				// phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => $em_id,
			]
		);
	}

	/**
	 * Get the nonce rendered by the Import tab's form.
	 *
	 * @return string
	 */
	private function get_form_nonce() {
		ob_start();
		PUM_Admin_Tools::import_display();
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/name="popmake_emodal_v2_import_nonce" value="([^"]+)"/', $html );
		preg_match( '/name="popmake_emodal_v2_import_nonce" value="([^"]+)"/', $html, $matches );

		return $matches[1];
	}

	/**
	 * Submitting the Import tab's own form runs the import.
	 */
	public function test_import_runs_with_the_nonce_rendered_by_the_form() {
		$_REQUEST['popmake_emodal_v2_import']       = '';
		$_REQUEST['popmake_emodal_v2_import_nonce'] = $this->get_form_nonce();

		PUM_Admin_Tools::emodal_process_import();

		$this->assertSame( admin_url( 'edit.php?post_type=popup&page=pum-tools&imported=1' ), $this->redirect );
	}

	/**
	 * The Easy Modal models expose the ID of the record they loaded.
	 */
	public function test_easy_modal_models_expose_loaded_record_ids() {
		$theme_id = $this->insert_em_theme( 'Theme', '#000000' );
		$modal_id = $this->insert_em_modal( $theme_id, 'Modal', 'medium' );

		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/functions.php';
		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/model.php';
		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/model/modal.php';
		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/model/theme.php';
		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/model/theme/meta.php';
		require_once POPMAKE_DIR . 'includes/legacy/importer/easy-modal-v2/model/modal/meta.php';

		$themes = get_all_modal_themes( '1 = 1' );
		$modals = get_all_modals( '1 = 1' );

		$this->assertSame( [ $theme_id ], array_map( 'intval', array_keys( $themes ) ) );
		$this->assertSame( $theme_id, (int) $themes[ $theme_id ]->id );
		$this->assertSame( '#000000', $themes[ $theme_id ]->meta->overlay['background']['color'] );

		$this->assertSame( [ $modal_id ], array_map( 'intval', array_keys( $modals ) ) );
		$this->assertSame( $modal_id, (int) $modals[ $modal_id ]->id );
		$this->assertSame( 'medium', $modals[ $modal_id ]->meta->display['size'] );
	}

	/**
	 * Running the import creates a popup theme and popup from Easy Modal data.
	 */
	public function test_import_creates_popups_and_themes_from_easy_modal_data() {
		$theme_id = $this->insert_em_theme( 'Legacy theme', '#123456' );
		$modal_id = $this->insert_em_modal( $theme_id, 'Legacy modal', 'large' );

		$_REQUEST['popmake_emodal_v2_import']       = '';
		$_REQUEST['popmake_emodal_v2_import_nonce'] = $this->get_form_nonce();

		PUM_Admin_Tools::emodal_process_import();

		$themes = $this->get_imported( 'popup_theme', 'popup_theme_old_easy_modal_id', $theme_id );
		$popups = $this->get_imported( 'popup', 'popup_old_easy_modal_id', $modal_id );

		$this->assertCount( 1, $themes );
		$this->assertSame( 'Legacy theme', $themes[0]->post_title );
		$this->assertSame( '#123456', get_post_meta( $themes[0]->ID, 'popup_theme_overlay_background_color', true ) );

		$this->assertCount( 1, $popups );
		$this->assertSame( 'Legacy modal', $popups[0]->post_title );
		$this->assertSame( 'Legacy modal content', $popups[0]->post_content );
		$this->assertSame( 'large', get_post_meta( $popups[0]->ID, 'popup_display_size', true ) );
		$this->assertEquals( $themes[0]->ID, get_post_meta( $popups[0]->ID, 'popup_theme', true ) );
	}

	/**
	 * An invalid nonce is rejected.
	 */
	public function test_import_is_rejected_with_an_invalid_nonce() {
		$_REQUEST['popmake_emodal_v2_import']       = '';
		$_REQUEST['popmake_emodal_v2_import_nonce'] = 'invalid';

		PUM_Admin_Tools::emodal_process_import();

		$this->assertNull( $this->redirect );
	}

	/**
	 * A nonce is required.
	 */
	public function test_import_is_rejected_without_a_nonce() {
		$_REQUEST['popmake_emodal_v2_import'] = '';

		PUM_Admin_Tools::emodal_process_import();

		$this->assertNull( $this->redirect );
	}

	/**
	 * Users who cannot manage options cannot run the import, even with a valid nonce.
	 */
	public function test_import_is_rejected_for_users_who_cannot_manage_options() {
		wp_set_current_user( self::$editor_id );

		$_REQUEST['popmake_emodal_v2_import']       = '';
		$_REQUEST['popmake_emodal_v2_import_nonce'] = $this->get_form_nonce();

		PUM_Admin_Tools::emodal_process_import();

		$this->assertNull( $this->redirect );
	}

	/**
	 * Nothing happens unless the import button was submitted.
	 */
	public function test_import_does_nothing_without_the_import_button() {
		$_REQUEST['popmake_emodal_v2_import_nonce'] = $this->get_form_nonce();

		PUM_Admin_Tools::emodal_process_import();

		$this->assertNull( $this->redirect );
	}
}
