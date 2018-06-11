<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Settings
 */
class PUM_Admin_Settings {

	/**
	 * @var array
	 */
	public static $notices = array();

	/**
	 *
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		//add_action( 'pum_license_deactivated', array( __CLASS__, 'license_deactivated' ) );
		//add_action( 'pum_license_check_failed', array( __CLASS__, 'license_deactivated' ) );
	}

	// display default admin notice

	/**
	 * Displays any saved admin notices.
	 */
	public static function notices() {

		if ( isset( $_GET['success'] ) && get_option( 'pum_settings_admin_notice' ) ) {
			self::$notices[] = array(
				'type'    => $_GET['success'] ? 'success' : 'error',
				'message' => get_option( 'pum_settings_admin_notice' ),
			);

			delete_option( 'pum_settings_admin_notice' );
		}

		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) { ?>
				<div class="notice notice-<?php esc_attr_e( $notice['type'] ); ?> is-dismissible">
					<p><strong><?php esc_html_e( $notice['message'] ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'popup-maker' ); ?></span>
					</button>
				</div>
			<?php }
		}
	}


	/**
	 * Save settings when needed.
	 */
	public static function save() {
		if ( ! empty( $_POST['pum_settings'] ) && empty( $_POST['pum_license_activate'] ) && empty( $_POST['pum_license_deactivate'] ) ) {

			if ( ! isset( $_POST['pum_settings_nonce'] ) || ! wp_verify_nonce( $_POST['pum_settings_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = self::sanitize_settings( $_POST['pum_settings'] );

			$settings = apply_filters( 'pum_sanitize_settings', $settings );

			if ( PUM_Options::update_all( $settings ) ) {
				self::$notices[] = array(
					'type'    => 'success',
					'message' => __( 'Settings saved successfully!', 'popup-maker' ),
				);

				do_action( 'pum_save_settings', $settings );
			} else {
				self::$notices[] = array(
					'type'    => 'error',
					'message' => __( 'There must have been an error, settings not saved successfully!', 'popup-maker' ),
				);
			}

			return;

			/**
			 * Process licensing if set.
			 *
			 * // We store the key in wp_options for use by the update & licensing system to keep things cleanly detached.
			 * $old_license = get_option( 'pum_license_key' );
			 *
			 * if ( empty( $settings['pum_license_key'] ) ) {
			 * delete_option( 'pum_license_key' ); // empty key, remove existing license info.
			 * delete_option( 'pum_license' ); // empty key, remove existing license info.
			 * } else if ( $old_license != $settings['pum_license_key'] ) {
			 * update_option( 'pum_license_key', $settings['pum_license_key'] );
			 * delete_option( 'pum_license' ); // new license has been entered, so must reactivate
			 *
			 * // Prevent additional calls to licensing.
			 * if ( empty( $_POST['pum_license_activate'] ) ) {
			 * $message = PUM_Licensing::activate();
			 *
			 * if ( $message !== true && ! empty ( $message ) ) {
			 * self::$notices[] = array(
			 * 'type'    => 'error',
			 * 'message' => $message,
			 * );
			 * } else {
			 * self::$notices[] = array(
			 * 'type'    => 'success',
			 * 'message' => __( 'License activated successfully!', 'popup-maker' ),
			 * );
			 * }
			 * }
			 * }
			 */
		}


	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function sanitize_settings( $settings = array() ) {

		$fields = self::fields();

		$fields = PUM_Admin_Helpers::flatten_fields_array( $fields );

		foreach ( $fields as $field_id => $field ) {
			switch ( $field['type'] ) {
				case 'checkbox':

					if ( ! isset( $settings[ $field_id ] ) ) {
						$settings[ $field_id ] = false;
					}
					break;
			}
		}

		foreach ( $settings as $key => $value ) {
			$field = self::get_field( $key );

			if ( $field ) {

				// Sanitize every string value.
				if ( is_string( $value ) ) {
					$settings[ $key ] = sanitize_text_field( $value );
				}

				switch ( $field['type'] ) {
					default:
						$settings[ $key ] = is_string( $value ) ? trim( $value ) : $value;
						break;


					case 'measure':
						$settings[ $key ] .= $settings[ $key . '_unit' ];
						break;

					case 'license_key':
						$old = PUM_Options::get( $key );
						$new = trim( $value );

						if ( $old && $old != $new ) {
							delete_option( str_replace( '_license_key', '_license_active', $key ) );
							call_user_func( $field['options']['activation_callback'] );
						}

						$settings[ $key ] = is_string( $value ) ? trim( $value ) : $value;
						// Activate / deactivate license keys maybe?
						break;
				}
			} else {
				// Some custom field types include multiple additional fields that do not need to be saved, strip out any non-whitelisted fields.
				unset( $settings[ $key ] );
			}
		}

		return $settings;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function get_field( $id ) {
		$tabs = self::fields();

		foreach ( $tabs as $tab => $sections ) {

			if ( PUM_Admin_Helpers::is_field( $sections ) ) {
				$sections = array(
					'main' => array(
						$tab => $sections,
					),
				);
			}

			foreach ( $sections as $section => $fields ) {

				foreach ( $fields as $key => $args ) {
					if ( $key == $id ) {
						return $args;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Returns array of pum settings fields.
	 *
	 * @return mixed
	 */
	public static function fields() {

		static $fields;

		if ( ! isset( $fields ) ) {


			$fields = array(
				'general' => array(
					'main' => array(),
				),
			);

			// TODO Remove or move externally of this location later.
			if ( ! ( class_exists( 'PUM_MCI' ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) ) ) {
				$fields['subscriptions'] = array(
					'main' => apply_filters( 'pum_newsletter_settings', array(
						'newsletter_default_provider'        => array(
							'label'   => __( 'Default Newsletter Provider', 'popup-maker' ),
							'desc'    => __( 'The default mailing provider used for the subscription form.', 'popup-maker' ),
							'type'    => 'select',
							'options' => array_merge( PUM_Newsletter_Providers::dropdown_list(), array(
								'none' => __( 'None', 'popup-maker' ),
							) ),
						),
						'default_success_message'            => array(
							'label' => __( 'Success Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user when successfuly subscribed.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'You have been subscribed!', 'popup-maker' ),
						),
						'default_empty_email_message'        => array(
							'label' => __( 'Empty Email Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user when no email is entered.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'Please enter a valid email.', 'popup-maker' ),
						),
						'default_invalid_email_message'      => array(
							'label' => __( 'Invalid Email Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user when an invalid email is entered.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'Email provided is not a valid email address.', 'popup-maker' ),
						),
						'default_error_message'              => array(
							'label' => __( 'Error Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user when an error has occurred.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'Error occurred when subscribing. Please try again.', 'popup-maker' ),
						),
						'default_already_subscribed_message' => array(
							'label' => __( 'Already Subscribed Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user who is already subscribed.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'You are already a subscriber.', 'popup-maker' ),
						),
						'default_consent_required_message'   => array(
							'label' => __( 'Consent Required Message', 'popup-maker' ),
							'desc'  => __( 'Message to show user who is already subscribed.', 'popup-maker' ),
							'type'  => 'text',
							'std'   => __( 'You must agree to continue.', 'popup-maker' ),
						),
					) ),
				);
			}

			$fields = array_merge( $fields, array(
				'extensions' => array(
					'main' => array(),
				),
				'licenses'   => array(
					'main' => array(),
				),
				'privacy'    => array(
					'main'  => array(
						'disable_popup_open_tracking' => array(
							'type'  => 'checkbox',
							'label' => __( 'Disables popup open tracking?', 'popup-maker' ),
							'desc'  => __( 'This will disable the built in analytics functionality.', 'popup-maker' ),
						),
					),
					'forms' => array(
						'forms_disclaimer'                     => array(
							'type'    => 'html',
							'content' => "<strong>" . __( 'Disclaimer', 'popup-maker' ) . ":</strong> " . __( 'These settings only pertain to usage of the Popup Maker built in subscription form shortcode, not 3rd party form plugins.', 'popup-maker' ),
						),
						'privacy_consent_always_enabled'       => array(
							'label'   => __( 'Always enable consent field on subscription forms.', 'popup-maker' ),
							'type'    => 'select',
							'options' => array(
								'yes' => __( 'Yes', 'popup-maker' ),
								'no'  => __( 'No', 'popup-maker' ),
							),
							'std'     => 'yes',
						),
						'default_privacy_consent_label'        => array(
							'label'        => __( 'Consent Text', 'popup-maker' ),
							'type'         => 'text',
							'std'          => __( 'Notify me about related content and special offers.', 'popup-maker' ),
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
							),
						),
						'default_privacy_consent_type'         => array(
							'label'        => __( 'Consent Field Type', 'popup-maker' ),
							'desc'         => __( 'Radio forces the user to make a choice, often resulting in more opt-ins.', 'popup-maker' ),
							'type'         => 'select',
							'options'      => array(
								'radio'    => __( 'Radio', 'popup-maker' ),
								'checkbox' => __( 'Checkbox', 'popup-maker' ),
							),
							'std'          => 'radio',
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
							),
						),
						'default_privacy_consent_required'     => array(
							'label'        => __( 'Consent Required', 'popup-maker' ),
							'type'         => 'checkbox',
							'std'          => pum_get_option( 'default_privacy_consent_required' ),
							'private'      => true,
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
							),
						),
						'default_privacy_consent_radio_layout' => array(
							'label'        => __( 'Consent Radio Layout', 'popup-maker' ),
							'type'         => 'select',
							'options'      => array(
								'inline'  => __( 'Inline', 'popup-maker' ),
								'stacked' => __( 'Stacked', 'popup-maker' ),
							),
							'std'          => __( 'Yes', 'popup-maker' ),
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
								'default_privacy_consent_type'   => 'radio',
							),
						),
						'default_privacy_consent_yes_label'    => array(
							'label'        => __( 'Consent Yes Label', 'popup-maker' ),
							'type'         => 'text',
							'std'          => __( 'Yes', 'popup-maker' ),
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
								'default_privacy_consent_type'   => 'radio',
							),
						),
						'default_privacy_consent_no_label'     => array(
							'label'        => __( 'Consent No Label', 'popup-maker' ),
							'type'         => 'text',
							'std'          => __( 'No', 'popup-maker' ),
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
								'default_privacy_consent_type'   => 'radio',
							),
						),
						'default_privacy_usage_text'           => array(
							'label'        => __( 'Consent Usage Text', 'popup-maker' ),
							'desc'         => function_exists( 'get_privacy_policy_url' ) ? sprintf( __( 'You can use %1$%2$s to insert a link to your privacy policy. To customize the link text use %1$s:Link Text%2$s', 'popup-maker' ), '{{privacy_link', '}}' ) : '',
							'type'         => 'text',
							'std'          => __( 'If you opt in above we use this information send related content, discounts and other special offers.', 'popup-maker' ),
							'dependencies' => array(
								'privacy_consent_always_enabled' => 'yes',
							),
						),
					),
				),

				'misc' => array(
					'main'   => array(
						'disabled_admin_bar'                   => array(
							'type'  => 'checkbox',
							'label' => __( 'Disable Popups Admin Bar', 'popup-maker' ),
							'desc'  => __( 'This will disable the admin Popups menu item.', 'popup-maker' ),
						),
						'debug_mode'                           => array(
							'type'  => 'checkbox',
							'label' => __( 'Enable Debug Mode', 'popup-maker' ),
							'desc'  => __( 'This will turn on multiple debug tools used to quickly find issues.', 'popup-maker' ),
						),
						'enable_easy_modal_compatibility_mode' => array(
							'type'  => 'checkbox',
							'label' => __( 'Enable Easy Modal v2 Compatibility Mode', 'popup-maker' ),
							'desc'  => __( 'This will automatically make any eModal classes you have added to your site launch the appropriate Popup after import.', 'popup-maker' ),
						),
						'disable_admin_support_widget'         => array(
							'type'  => 'checkbox',
							'label' => __( 'Hide Admin Support Widget', 'popup-maker' ),
							'desc'  => __( 'This will hide the support widget on all popup maker admin pages.', 'popup-maker' ),
						),
						'disable_popup_category_tag'           => array(
							'type'  => 'checkbox',
							'label' => __( 'Disable categories & tags?', 'popup-maker' ),
							'desc'  => __( 'This will disable the popup tags & categories.', 'popup-maker' ),
						),
						'disable_cache'                        => array(
							'type'  => 'checkbox',
							'label' => __( 'Disable object caching', 'popup-maker' ),
							'desc'  => __( 'If you are seeing issues with settings not saving or popups not rendering changes immediately, try this option.', 'popup-maker' ),
						),
						'disable_asset_caching'                => array(
							'type'  => 'checkbox',
							'label' => __( 'Disable asset caching.', 'popup-maker' ),
							'desc'  => __( 'By default Popup Maker caches a single JS & CSS file in your Uploads folder. These files include core, extension & user customized styles & scripts in a single set of files.', 'popup-maker' ),
						),
						'disable_shortcode_ui'                 => array(
							'type'  => 'checkbox',
							'label' => __( 'Disable the Popup Maker shortcode button', 'popup-maker' ),
						),
					),
					'assets' => array(
						'disable_google_font_loading'     => array(
							'type'  => 'checkbox',
							'label' => __( "'Don't Load Google Fonts", 'popup-maker' ),
							'desc'  => __( 'Check this disable loading of google fonts, useful if the fonts you chose are already loaded with your theme.', 'popup-maker' ),
						),
						'disable_popup_maker_core_styles' => array(
							'type'  => 'checkbox',
							'label' => __( 'Don\'t load Popup Maker core stylesheet.', 'popup-maker' ),
							'desc'  => __( 'Check this if you have copied the Popup Maker core styles to your own stylesheet or are using custom styles.', 'popup-maker' ),
						),
						'disable_popup_theme_styles'      => array(
							'type'  => 'checkbox',
							'label' => __( 'Don\'t load popup theme styles to the head.', 'popup-maker' ),
							'desc'  => __( 'Check this if you have copied the popup theme styles to your own stylesheet or are using custom styles.', 'popup-maker' ),
						),
						'output_pum_styles'               => array(
							'id'      => 'output_pum_styles',
							'type'    => 'html',
							'content' => popmake_output_pum_styles(),
						),
					),
				),
			) );

			$fields = apply_filters( 'pum_settings_fields', $fields );

			$fields = PUM_Admin_Helpers::parse_tab_fields( $fields, array(
				'has_subtabs' => true,
				'name'        => 'pum_settings[%s]',
			) );
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public static function user_role_options() {
		global $wp_roles;

		$options = array();
		foreach ( $wp_roles->roles as $role => $labels ) {
			$options[ $role ] = $labels['name'];
		}

		return $options;
	}

	/**
	 * Render settings page with tabs.
	 */
	public static function page() {

		$settings = PUM_Options::get_all();

		if ( empty( $settings ) ) {
			$settings = self::defaults();
		}

		?>

		<div class="wrap">

			<form id="pum-settings" method="post" action="">

				<?php wp_nonce_field( basename( __FILE__ ), 'pum_settings_nonce' ); ?>

				<button class="right top button-primary"><?php _e( 'Save', 'popup-maker' ); ?></button>

				<h1><?php _e( 'Popup Maker Settings', 'popup-maker' ); ?></h1>

				<div id="pum-settings-container" class="pum-settings-container">
					<div class="pum-no-js" style="padding: 0 12px;">
						<p><?php printf( __( 'If you are seeing this, the page is still loading or there are Javascript errors on this page. %sView troubleshooting guide%s', 'popup-maker' ), '<a href="https://docs.wppopupmaker.com/article/373-checking-for-javascript-errors" target="_blank">', '</a>' ); ?></p>
					</div>
				</div>

				<script type="text/javascript">
                    window.pum_settings_editor = <?php echo PUM_Utils_Array::safe_json_encode( apply_filters( 'pum_settings_editor_args', array(
						'form_args'      => array(
							'id'       => 'pum-settings',
							'tabs'     => self::tabs(),
							'sections' => self::sections(),
							'fields'   => self::fields(),
							'maintabs' => array(
								'meta' => array(
									'data-min-height' => 0,
								),
							),
						),
						'active_tab'     => self::get_active_tab(),
						'active_section' => self::get_active_section(),
						'current_values' => self::parse_values( $settings ),
					) ) ); ?>;
				</script>

				<button class="button-primary bottom right"><?php _e( 'Save', 'popup-maker' ); ?></button>

			</form>
		</div>

		<?php
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		$tabs = self::fields();

		$defaults = array();

		foreach ( $tabs as $section_id => $fields ) {
			foreach ( $fields as $key => $field ) {
				$defaults[ $key ] = isset( $field['std'] ) ? $field['std'] : null;
			}
		}

		return $defaults;
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function tabs() {
		static $tabs;

		if ( ! isset( $tabs ) ) {
			$tabs = apply_filters( 'pum_settings_tabs', array(
				'general'       => __( 'General', 'popup-maker' ),
				'subscriptions' => __( 'Subscriptions', 'popup-maker' ),
				'extensions'    => __( 'Extensions', 'popup-maker' ),
				'licenses'      => __( 'Licenses', 'popup-maker' ),
				'privacy'       => __( 'Privacy', 'popup-maker' ),
				'misc'          => __( 'Misc', 'popup-maker' ),
			) );

			/** @deprecated 1.7.0 */
			$tabs = apply_filters( 'popmake_settings_tabs', $tabs );
		}


		return $tabs;
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function sections() {
		return apply_filters( 'pum_settings_tab_sections', array(
			'general'       => array(
				'main' => __( 'General', 'popup-maker' ),
			),
			'subscriptions' => array(
				'main' => __( 'General', 'popup-maker' ),
			),
			'extensions'    => array(
				'main' => __( 'Extension Settings', 'popup-maker' ),
			),
			'licenses'      => array(
				'main' => __( 'Licenses', 'popup-maker' ),
			),
			'privacy'       => array(
				'main'  => __( 'General', 'popup-maker' ),
				'forms' => __( 'Subscription Forms', 'popup-maker' ),
			),
			'misc'          => array(
				'main'   => __( 'Misc', 'popup-maker' ),
				'assets' => __( 'Assets', 'popup-maker' ),
			),
		) );
	}

	/**
	 * @return int|null|string
	 */
	public static function get_active_tab() {
		$tabs = self::tabs();

		return isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? sanitize_text_field( $_GET['tab'] ) : key( $tabs );
	}

	/**
	 * @return bool|int|null|string
	 */
	public static function get_active_section() {
		$active_tab = self::get_active_tab();
		$sections   = self::sections();

		$tab_sections = ! empty( $sections[ $active_tab ] ) ? $sections[ $active_tab ] : false;

		if ( ! $tab_sections ) {
			return false;
		}

		return isset( $_GET['section'] ) && array_key_exists( $_GET['section'], $tab_sections ) ? sanitize_text_field( $_GET['section'] ) : key( $tab_sections );
	}

	/**
	 * Parse values for form rendering.
	 *
	 * Add additional data for license_key fields, split the measure fields etc.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public static function parse_values( $settings ) {

		foreach ( $settings as $key => $value ) {
			$field = self::get_field( $key );


			if ( $field ) {

				/**
				 * Process fields with specific types.
				 */
				switch ( $field['type'] ) {
					case 'measure':
						break;
					case 'license_key':
						$license = get_option( $field['options']['is_valid_license_option'] );

						$settings[ $key ] = array(
							'key'      => trim( $value ),
							'status'   => PUM_Licensing::get_status( $license, ! empty( $value ) ),
							'messages' => PUM_Licensing::get_status_messages( $license, trim( $value ) ),
							'expires'  => PUM_Licensing::get_license_expiration( $license ),
							'classes'  => PUM_Licensing::get_status_classes( $license ),
						);
						break;
				}

				/**
				 * Process fields with specific ids.
				 */
				switch ( $field['id'] ) {
					/*
					case 'pum_license_status':
						$settings[ $key ] = Licensing::get_status();
						break;
					*/
				}

			}
		}

		return $settings;
	}

	/**
	 *
	 */
	public static function license_deactivated() {

	}

	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	public static function sanitize_objects( $meta = array() ) {
		if ( ! empty( $meta ) ) {

			foreach ( $meta as $key => $value ) {

				if ( is_string( $value ) ) {
					try {
						$value = json_decode( stripslashes( $value ) );
					} catch ( Exception $e ) {
					};
				}

				$meta[ $key ] = PUM_Admin_Helpers::object_to_array( $value );
			}
		}

		return $meta;
	}


}
