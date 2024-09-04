<?php
/**
 * Integrations class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Integrations
 */
class PUM_Integrations {

	/**
	 * @var PUM_Abstract_Integration[]|PUM_Abstract_Integration_Form[]
	 */
	public static $integrations = [];

	/**
	 * @var bool
	 */
	public static $preload_posts = false;

	public static $form_success;

	public static $form_submission;

	/**
	 * Initializes all form plugin and page builder integrations.
	 */
	public static function init() {
		self::$integrations = apply_filters(
			'pum_integrations',
			[
				// Forms.
				'ninjaforms'      => new PUM_Integration_Form_NinjaForms(),
				'gravityforms'    => new PUM_Integration_Form_GravityForms(),
				'contactform7'    => new PUM_Integration_Form_ContactForm7(),
				'calderaforms'    => new PUM_Integration_Form_CalderaForms(),
				'mc4wp'           => new PUM_Integration_Form_MC4WP(),
				'wpforms'         => new PUM_Integration_Form_WPForms(),
				'wsforms'         => new PUM_Integration_Form_WSForms(),
				'formidableforms' => new PUM_Integration_Form_FormidableForms(),
				'fluentforms'     => new PUM_Integration_Form_FluentForms(),
				// Builders.
				'kingcomposer'    => new PUM_Integration_Builder_KingComposer(),
				'visualcomposer'  => new PUM_Integration_Builder_VisualComposer(),
			]
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::$preload_posts = isset( $_GET['page'] ) && 'pum-settings' === $_GET['page'];

		add_filter( 'pum_settings_fields', [ __CLASS__, 'settings_fields' ] );
		add_action( 'pum_preload_popup', [ __CLASS__, 'enqueue_assets' ] );
		add_filter( 'pum_registered_conditions', [ __CLASS__, 'register_conditions' ] );

		add_filter( 'pum_vars', [ __CLASS__, 'pum_vars' ] );

		add_action( 'init', [ __CLASS__, 'wp_init_late' ], 99 );
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
		add_filter( 'pum_popup_post_type_args', [ __CLASS__, 'popup_post_type_args' ] );
		add_filter( 'pum_generated_js', [ __CLASS__, 'generated_js' ] );
		add_filter( 'pum_generated_css', [ __CLASS__, 'generated_css' ] );
		add_filter( 'pum_popup_settings', [ __CLASS__, 'popup_settings' ], 10, 2 );

		PUM_Integration_GoogleFonts::init();
	}

	/**
	 * Checks if a 3rd party integration should be enabled.
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public static function enabled( $key ) {
		return (bool) isset( self::$integrations[ $key ] ) && self::$integrations[ $key ]->enabled();
	}

	/**
	 * @return PUM_Abstract_Integration_Form[]
	 */
	public static function get_enabled_form_integrations() {
		$enabled_forms = [];

		foreach ( self::$integrations as $object ) {
			if ( $object instanceof PUM_Abstract_Integration_Form && $object->enabled() ) {
				$enabled_forms[ $object->key ] = $object;
			}
		}

		return $enabled_forms;
	}

	/**
	 * Returns an array of value=>labels for select fields containing enabled form plugin integrations.
	 *
	 * @return array
	 */
	public static function get_enabled_forms_selectlist() {
		$enabled_form_integrations = self::get_enabled_form_integrations();

		$form_types = [];

		foreach ( $enabled_form_integrations as $key => $object ) {
			$form_types[ $key ] = $object->label();
		}

		return $form_types;
	}

	/**
	 * @param $key
	 *
	 * @return bool|PUM_Abstract_Integration|PUM_Abstract_Integration_Form
	 */
	public static function get_integration_info( $key ) {
		return isset( self::$integrations[ $key ] ) ? self::$integrations[ $key ] : false;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public static function get_form_provider_forms( $key ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_forms();
	}

	/**
	 * @param $key
	 * @param $id
	 *
	 * @return array|mixed
	 */
	public static function get_form_provider_form( $key, $id ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_form( $id );
	}

	/**
	 * @param $key
	 *
	 * @return array
	 */
	public static function get_form_provider_forms_selectlist( $key ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_form_selectlist();
	}

	/**
	 * Adds additional settings to help better integrate with 3rd party plugins.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function settings_fields( $fields = [] ) {

		foreach ( self::$integrations as $key => $integration ) {
			if ( ! ( $integration instanceof PUM_Interface_Integration_Settings ) || ! $integration->enabled() ) {
				continue;
			}

			// TODO LEFT OFF HERE.
			// TODO Could this be done via add_filter( 'pum_settings_fields', array( $integration, 'append_fields' ) );
			// TODO If so, do we do it inside the __construct for the PUM_Abstract_Integration, or the Integration_{Provider} class itself.
			// TODO Alternatively do we simply loop over all enabled providers during self::init() and add the filters/hooks there instead.
			$fields = $integration->append_fields( $fields );
		}

		return $fields;
	}

	public static function enqueue_assets( $popup_id = 0 ) {

		$popup = pum_get_popup( $popup_id );

		if ( ! pum_is_popup( $popup ) ) {
			return;
		}

		// Do stuff here.
	}

	public static function register_conditions( $conditions = [] ) {

		foreach ( self::$integrations as $key => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			switch ( $key ) {
				default:
					// Modify the conditions array.
					$conditions;
					break;
			}
		}

		return $conditions;
	}

	/**
	 * Runs during init
	 */
	public static function wp_init_late() {

		/**
		 * Force KingComposer support for popups.
		 */
		if ( self::enabled( 'kingcomposer' ) ) {
			global $kc;
			$kc->add_content_type( 'popup' );
		}
	}

	/**
	 * Runs during admin_init
	 */
	public static function admin_init() {
		if ( ! self::enabled( 'visualcomposer' ) &&
			(
				is_admin() &&
				(
					pum_is_popup_editor() ||
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					( isset( $_GET['page'] ) && in_array( $_GET['page'], [ 'vc_settings', 'fl-builder-settings' ], true ) )
				)
			)
		) {
			add_filter( 'vc_role_access_with_post_types_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_backend_editor_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_frontend_editor_get_state', '__return_false' );
			add_filter( 'vc_check_post_type_validation', '__return_true' );
		}
	}

	public static function popup_post_type_args( $args = [] ) {

		if (
			self::enabled( 'kingcomposer' ) &&
			(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				( is_admin() && isset( $_GET['page'] ) && 'kingcomposer' === $_GET['page'] ) ||
				pum_is_popup_editor()
			)
		) {
			$args = array_merge(
				$args,
				[
					'public'              => true,
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_in_nav_menus'   => false,
				]
			);
		}

		if (
			self::enabled( 'visualcomposer' ) &&
			(
				is_admin() &&
				! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
				(
					(
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						isset( $_GET['page'] ) && in_array( $_GET['page'], [ 'vc_settings','fl-builder-settings' ], true )
					) ||
					(
						// phpcs:ignore WordPress.Security.NonceVerification.Missing
						isset( $_POST['option_page'] ) && 'wpb_js_composer_settings_general' === $_POST['option_page']
					) ||
					pum_is_popup_editor()
				)
			)
		) {
			$args = array_merge(
				$args,
				[
					'public'              => true,
					'exclude_from_search' => true,
					'publicly_queryable'  => false, // Was true, verify this isn't a problem.
					'show_in_nav_menus'   => false,
				]
			);
		}

		return $args;
	}


	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public static function generated_js( $js = [] ) {

		foreach ( self::$integrations as $integration ) {
			if ( $integration->enabled() && method_exists( $integration, 'custom_scripts' ) ) {
				$js = $integration->custom_scripts( $js );
			}
		}

		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array $css
	 */
	public static function generated_css( $css = [] ) {

		foreach ( self::$integrations as $integration ) {
			if ( $integration->enabled() && method_exists( $integration, 'custom_styles' ) ) {
				$css = $integration->custom_styles( $css );
			}
		}

		return $css;
	}

	/**
	 * Modify popup settings.
	 *
	 * @param array $settings
	 * @param int   $popup_id
	 *
	 * @return array
	 */
	public static function popup_settings( $settings, $popup_id ) {

		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $settings;
		}

		static $form_popup_id;

		/**
		 * Checks for popup form submission.
		 */
		if ( ! isset( $form_popup_id ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_popup_id = isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false;
		}

		// Should it reopen? Only if all of the following are true.
		$should_reopen = [
			// Form popup was submitted and matches this popup.
			$form_popup_id && $popup_id === $form_popup_id,
			// Form reopen was not marked disable.
			empty( $settings['disable_form_reopen'] ) || ! $settings['disable_form_reopen'],
			// Close on form submission is disbaled, or has a timer larger than 0.
			( empty( $settings['close_on_form_submission'] ) || ! $settings['close_on_form_submission'] || ( $settings['close_on_form_submission'] && $settings['close_on_form_submission_delay'] > 0 ) ),
		];

		/**
		 * If submission exists for this popup remove auto open triggers and add an admin_debug trigger to reshow the popup.
		 */
		if ( ! in_array( false, $should_reopen, true ) ) {
			$triggers = ! empty( $settings['triggers'] ) ? $settings['triggers'] : [];

			foreach ( $triggers as $key => $trigger ) {
				if ( 'auto_open' === $trigger['type'] ) {
					unset( $triggers[ $key ] );
				}
			}

			$settings['triggers'][] = [
				'type' => 'admin_debug',
			];
		}

		return $settings;
	}


	/**
	 * Add various extra global pum_vars js values.
	 *
	 * Primarily used to pass form success options for custom integrations and custom code.
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function pum_vars( $vars = [] ) {

		/**
		 * If a form was submitted via non-ajax methods this checks if a successful submission was reported.
		 */
		if ( isset( self::$form_success ) && ! empty( self::$form_success['popup_id'] ) ) {
			self::$form_success['settings'] = wp_parse_args(
				self::$form_success['settings'],
				[
					'openpopup'        => false,
					'openpopup_id'     => 0,
					'closepopup'       => false,
					'closedelay'       => 0,
					'redirect_enabled' => false,
					'redirect'         => '',
					'cookie'           => false,
				]
			);

			if ( is_array( self::$form_success['settings']['cookie'] ) ) {
				self::$form_success['settings']['cookie'] = wp_parse_args(
					self::$form_success['settings']['cookie'],
					[
						'name'    => 'pum-' . self::$form_success['popup_id'],
						'expires' => '+1 year',
					]
				);
			}

			$vars['form_success'] = self::$form_success;
		}

		if ( ! empty( self::$form_submission ) ) {
			// Remap values from PHP underscore_case to JS camelCase
			$vars['form_submission'] = PUM_Utils_Array::remap_keys(
				self::$form_submission,
				[
					'form_provider'    => 'formProvider',
					'form_id'          => 'formId',
					'form_instance_id' => 'formInstanceId',
					'popup_id'         => 'popupId',
				]
			);
		}

		return $vars;
	}

	/**
	 * Returns array of options for a select field to select an integrated form.
	 *
	 * @return array
	 */
	public static function get_integrated_forms_selectlist() {
		$enabled_form_integrations = self::get_enabled_form_integrations();

		$options = [];

		foreach ( $enabled_form_integrations as $integration ) {
			switch ( $integration->key ) {
				default:
					$group_options = [
						$integration->key . '_any' => sprintf(
							/* translators: 1. Integration label. */
							__( 'Any %s Form', 'popup-maker' ),
							$integration->label()
						),
					];

					foreach ( $integration->get_form_selectlist() as $form_id => $form_label ) {
						// ex. ninjaforms_1, contactform7_55
						$group_options[ $integration->key . '_' . $form_id ] = $form_label;
					}

					$options[ $integration->label() ] = $group_options;

					break;
			}
		}

		return $options;
	}
}
