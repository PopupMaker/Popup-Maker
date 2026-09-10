<?php
/**
 * Form integration query tests.
 *
 * @package Popup_Maker
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, PSR2.Classes.PropertyDeclaration.Underscore

if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
	/**
	 * Minimal Ninja Forms action base used by the popup-choice provider fixture.
	 */
	abstract class NF_Abstracts_Action {

		/**
		 * Action settings.
		 *
		 * @var array<string,mixed>
		 */
		protected $_settings = [];

		/**
		 * Action label.
		 *
		 * @var string
		 */
		protected $_nicename = '';

		/**
		 * Initialize the action fixture.
		 */
		public function __construct() {}
	}
}

require_once dirname( __DIR__, 3 ) . '/includes/integrations/class-pum-cf7.php';
require_once dirname( __DIR__, 3 ) . '/includes/integrations/class-pum-gravity-forms.php';
require_once dirname( __DIR__, 3 ) . '/includes/integrations/ninja-forms/Actions/OpenPopup.php';

/**
 * Verify form selector queries avoid unused cache priming.
 */
class Form_Integration_Query_Test extends WP_UnitTestCase {

	/**
	 * Contact Form 7 fixture IDs.
	 *
	 * @var int[]
	 */
	private $form_ids = [];

	/**
	 * Popup fixture IDs.
	 *
	 * @var int[]
	 */
	private $popup_ids = [];

	/**
	 * Set up form fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'PUM_Integration_Form_ContactForm7' ) ) {
			require_once dirname( __DIR__, 3 ) . '/classes/Integration/Form/ContactForm7.php';
		}

		register_post_type( 'wpcf7_contact_form' );

		$this->form_ids = self::factory()->post->create_many(
			20,
			[
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
			]
		);

		foreach ( $this->form_ids as $form_id ) {
			update_post_meta( $form_id, '_form', 'fixture' );
		}

		$older_popup_id = self::factory()->post->create(
			[
				'post_type'     => 'popup',
				'post_status'   => 'publish',
				'post_title'    => 'Older popup',
				'post_date'     => '2025-01-01 12:00:00',
				'post_date_gmt' => '2025-01-01 12:00:00',
			]
		);
		$newer_popup_id = self::factory()->post->create(
			[
				'post_type'     => 'popup',
				'post_status'   => 'publish',
				'post_title'    => 'Newer popup',
				'post_date'     => '2025-02-01 12:00:00',
				'post_date_gmt' => '2025-02-01 12:00:00',
			]
		);

		$this->popup_ids = [ $older_popup_id, $newer_popup_id ];
	}

	/**
	 * Remove form fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->form_ids as $form_id ) {
			wp_delete_post( $form_id, true );
		}

		foreach ( $this->popup_ids as $popup_id ) {
			wp_delete_post( $popup_id, true );
		}

		unregister_post_type( 'wpcf7_contact_form' );

		parent::tearDown();
	}

	/**
	 * Fetching titles does not prime unused post metadata.
	 */
	public function test_contact_form_selector_uses_one_query() {
		global $wpdb;

		wp_cache_flush();
		get_option( 'posts_per_page' );

		$query_count = $wpdb->num_queries;
		$forms       = ( new PUM_Integration_Form_ContactForm7() )->get_forms();

		$this->assertCount( 20, $forms );
		$this->assertSame( 1, $wpdb->num_queries - $query_count );
		$this->assertFalse( wp_cache_get( $this->form_ids[0], 'post_meta' ) );
	}

	/**
	 * Popup-list integrations retain their provider contracts through the shared projection.
	 */
	public function test_popup_list_providers_preserve_sentinels_shape_order_and_filters() {
		wp_cache_flush();

		$expected_date_order = [ $this->popup_ids[1], $this->popup_ids[0] ];
		$cf7                 = PUM_CF7_Integration::get_popup_list();
		$gravity             = PUM_Gravity_Forms_Integation::get_popup_list();
		$ninja               = ( new NF_PUM_Actions_OpenPopup() )->get_popup_list();

		$this->assertSame(
			[
				'value' => 0,
				'label' => 'Select a popup',
			],
			$cf7[0]
		);
		$this->assertSame(
			[
				'value' => '',
				'label' => 'Select a popup',
			],
			$gravity[0]
		);
		$this->assertSame(
			[
				'value' => '',
				'label' => 'Select a popup',
			],
			$ninja[0]
		);
		$this->assertSame( $expected_date_order, array_column( array_slice( $cf7, 1 ), 'value' ) );
		$this->assertSame( $expected_date_order, array_column( array_slice( $gravity, 1 ), 'value' ) );
		$this->assertSame( [ 'value', 'label' ], array_keys( $cf7[1] ) );
		$this->assertFalse( wp_cache_get( $this->popup_ids[0], 'posts' ) );
		$this->assertFalse( wp_cache_get( $this->popup_ids[0], 'post_meta' ) );

		$title_filter = static function ( $posts ) {
			foreach ( $posts as $post ) {
				if ( $post instanceof WP_Post && 'popup' === $post->post_type ) {
					$post->post_title = 'Filtered: ' . $post->post_title;
				}
			}

			return $posts;
		};

		add_filter( 'posts_results', $title_filter );

		try {
			$filtered = PUM_CF7_Integration::get_popup_list();
		} finally {
			remove_filter( 'posts_results', $title_filter );
		}

		$this->assertSame( 'Filtered: Newer popup', $filtered[1]['label'] );
	}
}
