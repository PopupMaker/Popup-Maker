<?php
/**
 * Register Settings
 *
 * @package        POPMAKE
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 1.0
 * @return mixed
 */
function popmake_get_option( $key = '', $default = false ) {
	global $popmake_options;
	$value = isset( $popmake_options[ $key ] ) ? $popmake_options[ $key ] : $default;
	$value = apply_filters( 'popmake_get_option', $value, $key, $default );

	return apply_filters( 'popmake_get_option_' . $key, $value, $key, $default );
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array POPMAKE settings
 */
function popmake_get_settings() {

	$settings = get_option( 'popmake_settings' );

	if ( empty( $settings ) ) {

		// Update old settings with new single option

		$license_settings = is_array( get_option( 'popmake_settings_licenses' ) ) ? get_option( 'popmake_settings_licenses' ) : array();

		$settings = array_merge( $license_settings );

		update_option( 'popmake_settings', $settings );

	}

	return apply_filters( 'popmake_get_settings', $settings );
}

/**
 * Add all settings sections and fields
 *
 * @since 1.0
 * @return void
 */
function popmake_register_settings() {

	if ( false == get_option( 'popmake_settings' ) ) {
		add_option( 'popmake_settings', popmake_default_settings() );
	}

	foreach ( popmake_get_registered_settings() as $tab => $settings ) {

		add_settings_section( 'popmake_settings_' . $tab, __return_null(), '__return_false', 'popmake_settings_' . $tab );

		foreach ( $settings as $option ) {

			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field( 'popmake_settings[' . $option['id'] . ']', $name, function_exists( 'popmake_' . $option['type'] . '_callback' ) ? 'popmake_' . $option['type'] . '_callback' : 'popmake_missing_callback', 'popmake_settings_' . $tab, 'popmake_settings_' . $tab, array(
				'section' => $tab,
				'id'      => isset( $option['id'] ) ? $option['id'] : null,
				'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
				'name'    => isset( $option['name'] ) ? $option['name'] : null,
				'size'    => isset( $option['size'] ) ? $option['size'] : null,
				'options' => isset( $option['options'] ) ? $option['options'] : '',
				'std'     => isset( $option['std'] ) ? $option['std'] : '',
				'min'     => isset( $option['min'] ) ? $option['min'] : null,
				'max'     => isset( $option['max'] ) ? $option['max'] : null,
				'step'    => isset( $option['step'] ) ? $option['step'] : null,
			) );
		}

	}

	// Creates our settings in the options table
	register_setting( 'popmake_settings', 'popmake_settings', 'popmake_settings_sanitize' );

}

add_action( 'admin_init', 'popmake_register_settings' );

/**
 * Returns default options
 */
function popmake_default_settings() {
	return array();
}

/**
 * Retrieve the array of plugin settings
 *
 * @since 1.0
 * @return array
 */
function popmake_get_registered_settings() {

	/**
	 * 'Whitelisted' POPMAKE settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */
	$popmake_settings = array(
		/** General Settings */
		'general'    => apply_filters( 'popmake_settings_general', array() ),
		'assets'     => apply_filters( 'popmake_settings_assets', array(
			'disable_google_font_loading'     => array(
				'id'   => 'disable_google_font_loading',
				'name' => __( 'Don\'t Load Google Fonts', 'popup-maker' ),
				'desc' => __( 'Check this disable loading of google fonts, useful if the fonts you chose are already loaded with your theme.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'disable_popup_maker_core_styles' => array(
				'id'   => 'disable_popup_maker_core_styles',
				'name' => __( 'Don\'t load Popup Maker core stylesheet.', 'popup-maker' ),
				'desc' => __( 'Check this if you have copied the Popup Maker core styles to your own stylesheet or are using custom styles.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'disable_popup_theme_styles'      => array(
				'id'   => 'disable_popup_theme_styles',
				'name' => __( 'Don\'t load popup theme styles to the head.', 'popup-maker' ),
				'desc' => __( 'Check this if you have copied the popup theme styles to your own stylesheet or are using custom styles.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'output_pum_styles'               => array(
				'id'   => 'output_pum_styles',
				'type' => 'hook',
			),
		) ),
		/** Extension Settings */
		'extensions' => apply_filters( 'popmake_settings_extensions', array() ),
		'licenses'   => apply_filters( 'popmake_settings_licenses', array() ),
		'misc'       => apply_filters( 'popmake_settings_misc', array(
			'disabled_admin_bar'                   => array(
				'id'   => 'disabled_admin_bar',
				'name' => __( 'Disable Popups Admin Bar', 'popup-maker' ),
				'desc' => __( 'This will disable the admin Popups menu item.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'debug_mode'                           => array(
				'id'   => 'debug_mode',
				'name' => __( 'Enable Debug Mode', 'popup-maker' ),
				'desc' => __( 'This will turn on multiple debug tools used to quickly find issues.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'enable_easy_modal_compatibility_mode' => array(
				'id'   => 'enable_easy_modal_compatibility_mode',
				'name' => __( 'Enable Easy Modal v2 Compatibility Mode', 'popup-maker' ),
				'desc' => __( 'This will automatically make any eModal classes you have added to your site launch the appropriate Popup after import.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'disable_popup_open_tracking'          => array(
				'id'   => 'disable_popup_open_tracking',
				'name' => __( 'Disables popup open tracking?', 'popup-maker' ),
				'desc' => __( 'This will disable the built in analytics functionality.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'disable_admin_support_widget'         => array(
				'id'   => 'disable_admin_support_widget',
				'name' => __( 'Hide Admin Support Widget', 'popup-maker' ),
				'desc' => __( 'This will hide the support widget on all popup maker admin pages.', 'popup-maker' ),
				'type' => 'checkbox',
			),
			'disable_popup_category_tag'           => array(
				'id'   => 'disable_popup_category_tag',
				'name' => __( 'Disable categories & tags?', 'popup-maker' ),
				'desc' => __( 'This will disable the popup tags & categories.', 'popup-maker' ),
				'type' => 'checkbox',
			),
		) ),
	);

	return apply_filters( 'popmake_registered_settings', $popmake_settings );
}


/**
 * Retrieve a list of all published pages
 *
 * On large sites this can be expensive, so only load if on the settings page or $force is set to true
 *
 * @since 1.0
 *
 * @param bool $force Force the pages to be loaded even if not on settings
 *
 * @return array $pages_options An array of the pages
 */
function popmake_get_pages( $force = false ) {

	$pages_options = array( 0 => '' ); // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'pum-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;
}


/**
 * Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input
 *
 * @since 1.0
 *
 * @param array $input The value inputted in the field
 *
 * @return string $input Sanitizied value
 */
function popmake_settings_sanitize( $input = array() ) {

	global $popmake_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = popmake_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'assets';

	$input = $input ? $input : array();

	$input = apply_filters( 'popmake_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;
		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'popmake_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[ $key ] = apply_filters( 'popmake_settings_sanitize', $value, $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[ $tab ] ) ) {
		foreach ( $settings[ $tab ] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( empty( $input[ $key ] ) ) {
				unset( $popmake_options[ $key ] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $popmake_options, $input );
	add_settings_error( 'popmake-notices', '', __( 'Settings updated.', 'popup-maker' ), 'updated' );

	return $output;
}

/**
 * Sanitize text fields
 *
 * @since 1.0
 *
 * @param array $input The field value
 *
 * @return string $input Sanitizied value
 */
function popmake_sanitize_text_field( $input ) {
	return trim( $input );
}

add_filter( 'popmake_settings_sanitize_text', 'popmake_sanitize_text_field' );

/**
 * Retrieve settings tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function popmake_get_settings_tabs() {

	$settings = popmake_get_registered_settings();

	$tabs = array();
	if ( ! empty( $settings['general'] ) ) {
		$tabs['general'] = __( 'General', 'popup-maker' );
	}
	if ( ! empty( $settings['assets'] ) ) {
		$tabs['assets'] = __( 'Assets', 'popup-maker' );
	}
	if ( ! empty( $settings['extensions'] ) ) {
		$tabs['extensions'] = __( 'Extensions', 'popup-maker' );
	}
	if ( ! empty( $settings['licenses'] ) ) {
		$tabs['licenses'] = __( 'Licenses', 'popup-maker' );
	}
	if ( ! empty( $settings['misc'] ) ) {
		$tabs['misc'] = __( 'Misc', 'popup-maker' );
	}

	return apply_filters( 'popmake_settings_tabs', $tabs );
}


/**
 * Section Callback
 *
 * Renders the header.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function popmake_section_callback( $args ) {
	echo '</td></tr></tbody></table>';
	echo $args['desc'];
	echo '<table class="form-table"><tbody><tr style="display:none;"><td colspan="2">';
}


/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function popmake_header_callback( $args ) {
	echo '<hr/>';
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_checkbox_callback( $args ) {
	global $popmake_options;

	$checked = isset( $popmake_options[ $args['id'] ] ) ? checked( 1, $popmake_options[ $args['id'] ], false ) : '';
	$html    = '<input type="checkbox" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_multicheck_callback( $args ) {
	global $popmake_options;

	if ( ! empty( $args['options'] ) ) {
		foreach ( $args['options'] as $key => $option ):
			if ( isset( $popmake_options[ $args['id'] ][ $key ] ) ) {
				$enabled = $option;
			} else {
				$enabled = null;
			}
			echo '<input name="popmake_settings[' . $args['id'] . '][' . $key . ']" id="popmake_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';
			echo '<label for="popmake_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description">' . $args['desc'] . '</p>';
	}
}

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_radio_callback( $args ) {
	global $popmake_options;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $popmake_options[ $args['id'] ] ) && $popmake_options[ $args['id'] ] == $key ) {
			$checked = true;
		} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! isset( $popmake_options[ $args['id'] ] ) ) {
			$checked = true;
		}

		echo '<input name="popmake_settings[' . $args['id'] . ']"" id="popmake_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
		echo '<label for="popmake_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
	endforeach;

	echo '<p class="description">' . $args['desc'] . '</p>';
}


/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_text_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_number_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_textarea_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<textarea class="large-text" cols="50" rows="5" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_password_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function popmake_missing_callback( $args ) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'popup-maker' ), $args['id'] );
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_select_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_color_select_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @global $wp_version WordPress Version
 */
function popmake_rich_editor_callback( $args ) {
	global $popmake_options, $wp_version;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'popmake_settings_' . $args['id'], array(
			'textarea_name' => 'popmake_settings[' . $args['id'] . ']',
			'textarea_rows' => $rows,
		) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text" rows="10" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_upload_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="popmake_settings_upload_button button-secondary" value="' . __( 'Upload File', 'popup-maker' ) . '"/></span>';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
function popmake_color_callback( $args ) {
	global $popmake_options;

	if ( isset( $popmake_options[ $args['id'] ] ) ) {
		$value = $popmake_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="pum-color-picker color-picker" id="popmake_settings[' . $args['id'] . ']" name="popmake_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="popmake_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Descriptive text callback.
 *
 * Renders descriptive text onto the settings field.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function popmake_descriptive_text_callback( $args ) {
	echo esc_html( $args['desc'] );
}

/**
 * Registers the license field callback for Software Licensing
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @global $popmake_options Array of all the POPMAKE Options
 * @return void
 */
if ( ! function_exists( 'popmake_license_key_callback' ) ) {
	function popmake_license_key_callback( $args ) {
		$current_key = PUM_Options::get( $args['id'] );

		$messages = array();
		$license  = get_option( $args['options']['is_valid_license_option'] );

		if ( $current_key ) {
			$value = $current_key;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $license ) && is_object( $license ) ) {

			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {

				switch ( $license->error ) {

					// TODO TEST
					case 'expired' :

						$class      = 'expired';
						$messages[] = sprintf( __( 'Your license key expired on %s. Please %srenew your license key%s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ), '<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $value . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_medium=expired&utm_content=' . $args['id'] . '">', '</a>' );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'revoked' :

						$class      = 'error';
						$messages[] = sprintf( __( 'Your license key has been disabled. Please %scontact support%s for more information.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=revoked">', '</a>' );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'missing' :

						$class      = 'error';
						$messages[] = sprintf( __( 'Invalid license. Please %svisit your account page%s and verify it.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=missing">', '</a>' );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'invalid' :
						// TODO TEST
					case 'site_inactive' :

						$class      = 'error';
						$messages[] = sprintf( __( 'Your %s is not active for this URL. Please %svisit your account page%s to manage your license key URLs.', 'popup-maker' ), $args['name'], '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=invalid">', '</a>' );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'item_name_mismatch' :

						$class      = 'error';
						$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'popup-maker' ), $args['name'] );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'no_activations_left':

						$class      = 'error';
						$messages[] = sprintf( __( 'Your license key has reached its activation limit. %View possible upgrades% now.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=no-activations-left">', '</a>' );

						$license_status = 'license-' . $class . '-notice';

						break;

					// TODO TEST
					case 'license_not_activable':

						$class      = 'error';
						$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'popup-maker' );

						$license_status = 'license-' . $class . '-notice';
						break;

					default :

						$class      = 'error';
						$error      = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'popup-maker' );
						$messages[] = sprintf( __( 'There was an error with this license key: %s. Please %scontact our support team%s.', 'popup-maker' ), $error, '<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=error-contact-support">', '</a>' );

						$license_status = 'license-' . $class . '-notice';
						break;
				}

			} else {

				switch ( $license->license ) {

					case 'valid' :
					default:

						$class = 'valid';

						$now        = current_time( 'timestamp' );
						$expiration = strtotime( $license->expires, current_time( 'timestamp' ) );

						if ( 'lifetime' === $license->expires ) {

							$messages[] = __( 'License key never expires.', 'popup-maker' );

							$license_status = 'license-lifetime-notice';

						} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

							$messages[] = sprintf( __( 'Your license key expires soon! It expires on %s. %sRenew your license key%s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ), '<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $value . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=' . $args['id'] . '&utm_medium=renew">', '</a>' );

							$license_status = 'license-expires-soon-notice';

						} else {

							$messages[] = sprintf( __( 'Your license key expires on %s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ) );

							$license_status = 'license-expiration-date-notice';

						}

						break;

				}

			}

		} else {
			$class = 'empty';

			$messages[] = sprintf( __( 'To receive updates, please enter your valid %s license key.', 'popup-maker' ), $args['name'] );

			$license_status = null;
		}

		if ( ! empty ( $args['field_class'] ) ) {
			$class .= ' ' . popmake_sanitize_html_class( $args['field_class'] );
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="popmake_settings[' . popmake_sanitize_key( $args['id'] ) . ']" name="popmake_settings[' . popmake_sanitize_key( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';

		if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License', 'popup-maker' ) . '"/>';
		}

		$html .= '<label for="popmake_settings[' . popmake_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		if ( ! empty( $messages ) ) {
			foreach ( $messages as $message ) {

				$html .= '<div class="popmake-license-data popmake-license-' . $class . ' ' . $license_status . '">';
				$html .= '<p>' . $message . '</p>';
				$html .= '</div>';

			}
		}

		wp_nonce_field( popmake_sanitize_key( $args['id'] ) . '-nonce', popmake_sanitize_key( $args['id'] ) . '-nonce' );

		echo $html;
	}

}

/**
 * Sanitizes a string key for Popmake Settings
 *
 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are allowed
 *
 * @param  string $key String key
 *
 * @return string Sanitized key
 */
function popmake_sanitize_key( $key ) {
	$raw_key = $key;
	$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	/**
	 * Filter a sanitized key string.
	 *
	 * @param string $key Sanitized key.
	 * @param string $raw_key The key prior to sanitization.
	 */
	return apply_filters( 'popmake_sanitize_key', $key, $raw_key );
}

/**
 * Sanitize HTML Class Names
 *
 * @param  string|array $class HTML Class Name(s)
 *
 * @return string $class
 */
function popmake_sanitize_html_class( $class = '' ) {

	if ( is_string( $class ) ) {
		$class = sanitize_html_class( $class );
	} else if ( is_array( $class ) ) {
		$class = array_values( array_map( 'sanitize_html_class', $class ) );
		$class = implode( ' ', array_unique( $class ) );
	}

	return $class;

}

function popmake_sanitize_license_key_field( $new, $key ) {
	$old = PUM_Options::get( $key );
	if ( $old && $old != $new ) {
		PUM_Options::delete( $key ); // new license has been entered, so must reactivate
		delete_option( str_replace( '_license_key', '_license_active', $key ) );
	}

	return $new;
}

add_filter( 'popmake_settings_sanitize_license_key', 'popmake_sanitize_license_key_field', 10, 2 );

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function popmake_hook_callback( $args ) {
	do_action( 'popmake_' . $args['id'] );
}

function popmake_output_pum_styles() {
	?>
	<button type="button" id="show_pum_styles" onclick="jQuery('#pum_style_output').slideDown();jQuery(this).hide();"><?php _e( 'Show Popup Maker CSS', 'popup-maker' ); ?></button>
	<p class="pum-desc desc">Use this to quickly copy Popup Maker's CSS to your own stylesheet.</p>

	<div id="pum_style_output" style="display:none;">
		<h4><?php _e( 'Core Styles', 'popup-maker' ); ?></h4>
		<textarea wrap="off" style="white-space: pre; width: 100%;">
/* Popup Maker Core Styles */
			<?php include POPMAKE_DIR . 'assets/css/site.min.css'; ?>
		</textarea>

		<h4><?php _e( 'User Theme Styles', 'popup-maker' ); ?></h4>
		<textarea wrap="off" style="white-space: pre; width: 100%; min-height: 200px;"><?php echo popmake_get_popup_theme_styles(); ?></textarea>
	</div>

	<?php
}

add_action( 'popmake_output_pum_styles', 'popmake_output_pum_styles' );