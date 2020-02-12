<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Alerts
 */
class PUM_Utils_Alerts {

	/**
	 *
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'hooks' ) );
		add_action( 'wp_ajax_pum_alerts_action', array( __CLASS__, 'ajax_handler' ) );
		add_filter( 'pum_alert_list', array( __CLASS__, 'whats_new_alerts' ), 0 );
		add_filter( 'pum_alert_list', array( __CLASS__, 'integration_alerts' ), 5 );
		add_filter( 'pum_alert_list', array( __CLASS__, 'translation_request' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'append_alert_count' ), 999 );
	}

	/**
	 * Gets a count of current alerts.
	 *
	 * @return int
	 */
	public static function alert_count() {
		return count( self::get_alerts() );
	}

	/**
	 * Append alert count to Popup Maker menu item.
	 */
	public static function append_alert_count() {
		global $menu;
		$count = self::alert_count();
		foreach ( $menu as $key => $item ) {
			if ( $item[2] == 'edit.php?post_type=popup' ) {
				$menu[ $key ][0] .= $count ? ' <span class="update-plugins count-' . $count . '"><span class="plugin-count pum-alert-count" aria-hidden="true">' . $count . '</span></span>' : '';
			}
		}
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function translation_request( $alerts = array() ) {

		$version = explode( '.', Popup_Maker::$VER );
		// Get only the major.minor version exclude the point releases.
		$version = $version[0] . '.' . $version[1];

		$code = 'translation_request_' . $version;

		// Bail Early if they have already dismissed.
		if ( self::has_dismissed_alert( $code ) ) {
			return $alerts;
		}

		// Get locales based on the HTTP accept language header.
		$locales_from_header = PUM_Utils_I10n::get_http_locales();

		// Abort early if no locales in header.
		if ( empty( $locales_from_header ) ) {
			return $alerts;
		}

		// Get acceptable non EN WordPress locales based on the HTTP accept language header.
		// Used when the current locale is EN only I believe.
		$non_en_locales_from_header = PUM_Utils_I10n::get_non_en_accepted_wp_locales_from_header();

		// If no additional languages are supported abort
		if ( empty( $non_en_locales_from_header ) ) {
			return $alerts;
		}

		/**
		 * Assume all at this point are possible polyglots.
		 *
		 * Viewing in English!
		 * -- Translation available in one additional language!
		 * ---- Show notice that there other language is available and we need help translating.
		 * -- Translation available in more than one language!
		 * ---- Show notice that their other languages are available and need help translating.
		 * -- Translation not available!
		 * ---- Show notice that plugin is not translated and we need help.
		 * Else If translation for their language(s) exists, but isn't up to date!
		 * -- Show notice that their language is available, but out of date and need help translating.
		 * Else If translations for their language doesn't exist!
		 * -- Show notice that plugin is not translated and we need help.
		 */
		$current_locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();

		// Get the active language packs of the plugin.
		$translation_status = PUM_Utils_I10n::translation_status();
		// Retrieve all the WordPress locales in which the plugin is translated.
		$locales_with_translations   = wp_list_pluck( $translation_status, 'language' );
		$locale_translation_versions = wp_list_pluck( $translation_status, 'version' );

		// Suggests existing langpacks
		$suggested_locales_with_langpack = array_values( array_intersect( $non_en_locales_from_header, $locales_with_translations ) );
		$current_locale_is_suggested     = in_array( $current_locale, $suggested_locales_with_langpack );
		$current_locale_is_translated    = in_array( $current_locale, $locales_with_translations );

		// Last chance to abort early before querying all available languages.
		// We abort here if the user is already using a translated language that is up to date!
		if ( $current_locale_is_suggested && $current_locale_is_translated && version_compare( $locale_translation_versions[ $current_locale ], Popup_Maker::$VER, '>=' ) ) {
			return $alerts;
		}

		// Retrieve all the WordPress locales.
		$locales_supported_by_wordpress = PUM_Utils_I10n::available_locales();

		// Get the native language names of the locales.
		$suggest_translated_locale_names = array();
		foreach ( $suggested_locales_with_langpack as $locale ) {
			$suggest_translated_locale_names[ $locale ] = $locales_supported_by_wordpress[ $locale ]['native_name'];
		}

		$suggest_string = '';

		// If we get this far, they clearly have multiple language available
		// If current locale is english but they have others available, they are likely polyglots.
		$currently_in_english = strpos( $current_locale, 'en' ) === 0;

		// Currently in English.
		if ( $currently_in_english ) {

			// Only one locale suggestion.
			if ( 1 === count( $suggest_translated_locale_names ) ) {
				$language = current( $suggest_translated_locale_names );

				$suggest_string = sprintf( /* translators: %s: native language name. */
					__( 'This plugin is also available in %1$s. <a href="%2$s" target="_blank">Help improve the translation!</a>', 'popup-maker' ), $language, esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );

				// Multiple locale suggestions.
			} elseif ( ! empty( $suggest_translated_locale_names ) ) {
				$primary_language = current( $suggest_translated_locale_names );
				array_shift( $suggest_translated_locale_names );

				$other_suggest = '';
				foreach ( $suggest_translated_locale_names as $language ) {
					$other_suggest .= $language . ', ';
				}

				$suggest_string = sprintf( /* translators: 1: native language name, 2: other native language names, comma separated */
					__( 'This plugin is also available in %1$s (also: %2$s). <a href="%3$s" target="_blank">Help improve the translation!</a>', 'popup-maker' ), $primary_language, trim( $other_suggest, ' ,' ), esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );

				// Non-English locale in header, no translations.
			} elseif ( ! empty( $non_en_locales_from_header ) ) {

				if ( 1 === count( $non_en_locales_from_header ) ) {
					$locale = reset( $non_en_locales_from_header );

					$suggest_string = sprintf( /* translators: 1: native language name, 2: URL to translate.wordpress.org */
						__( 'This plugin is not translated into %1$s yet. <a href="%2$s" target="_blank">Help translate it!</a>', 'popup-maker' ), $locales_supported_by_wordpress[ $locale ]['native_name'], esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );
				} else {
					$primary_locale   = reset( $non_en_locales_from_header );
					$primary_language = $locales_supported_by_wordpress[ $primary_locale ]['native_name'];
					array_shift( $non_en_locales_from_header );

					$other_suggest = '';
					foreach ( $non_en_locales_from_header as $locale ) {
						$other_suggest .= $locales_supported_by_wordpress[ $locale ]['native_name'] . ', ';
					}

					$suggest_string = sprintf( /* translators: 1: native language name, 2: other native language names, comma separated */
						__( 'This plugin is also available in %1$s (also: %2$s). <a href="%3$s" target="_blank">Help improve the translation!</a>', 'popup-maker' ), $primary_language, trim( $other_suggest, ' ,' ), esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );
				}
			}

			// The plugin has no translation for the current locale.
		} elseif ( ! $current_locale_is_suggested && ! $current_locale_is_translated ) {
			$suggest_string = sprintf( __( 'This plugin is not translated into %1$s yet. <a href="%2$s" target="_blank">Help translate it!</a>', 'popup-maker' ), $locales_supported_by_wordpress[ $current_locale ]['native_name'], esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );
			// The plugin has translations for current locale, but they are out of date.
		} elseif ( $current_locale_is_suggested && $current_locale_is_translated && version_compare( $locale_translation_versions[ $current_locale ], Popup_Maker::$VER, '<' ) ) {
			$suggest_string = sprintf( /* translators: %s: native language name. */
				__( 'This plugin\'s translation for %1$s is out of date. <a href="%2$s" target="_blank">Help improve the translation!</a>', 'popup-maker' ), $locales_supported_by_wordpress[ $current_locale ]['native_name'], esc_url( 'https://translate.wordpress.org/projects/wp-plugins/popup-maker' ) );
		}


		if ( ! empty( $suggest_string ) ) {
			$alerts[] = array(
				'code'    => $code,
				'message' => $suggest_string,
				'type'    => 'info',
			);
		}

		return $alerts;
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function whats_new_alerts( $alerts = array() ) {

		$upgraded_from = PUM_Utils_Upgrades::$upgraded_from;

		if ( version_compare( $upgraded_from, '0.0.0', '>' ) ) {

			if ( version_compare( $upgraded_from, '1.8.0', '<' ) ) {
				$alerts[] = array(
					'code'     => 'whats_new_1_8_0',
					'type'     => 'success',
					'message'  => sprintf( '<strong>' . __( 'See whats new in v%s - (%sview all changes%s)', 'popup-maker' ) . '</strong>', '1.8.0', '<a href="' . add_query_arg( array(
							'tab'       => 'plugin-information',
							'plugin'    => 'popup-maker',
							'section'   => 'changelog',
							'TB_iframe' => true,
							'width'     => 722,
							'height'    => 949,
						), admin_url( 'plugin-install.php' ) ) . '" target="_blank">', '</a>' ),
					'html'     => "<ul class='ul-disc'>" . "<li>" . 'New UX for the Popup Theme editor.' . "</li>" . "<li>" . 'New close button positions: top center, bottom center, middle left & middle right.' . "</li>" . "<li>" . 'New option to position close button outside of popup.' . "</li>" . "</ul>",
					'priority' => 100,
				);
			}

		}

		return $alerts;
	}

	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public static function integration_alerts( $alerts = array() ) {

		$integrations = array(
			'buddypress' => array(
				'label'          => __( 'BuddyPress', 'buddypress' ),
				'learn_more_url' => 'https://wppopupmaker.com/works-with/buddypress/',
				'conditions'     => ! class_exists( 'PUM_BuddyPress' ) && ( function_exists( 'buddypress' ) || class_exists( 'BuddyPress' ) ),
				'slug'           => 'popup-maker-buddypress-integration',
				'name'           => 'Popup Maker - BuddyPress Integration',
				'free'           => true,
			),
		);

		foreach ( $integrations as $key => $integration ) {

			if ( $integration['conditions'] ) {

				$path        = "{$integration['slug']}/{$integration['slug']}.php";
				$plugin_data = file_exists( WP_PLUGIN_DIR . '/' . $path ) ? get_plugin_data( WP_PLUGIN_DIR . '/' . $path, false, false ) : false;

				$installed = $plugin_data && ! empty( $plugin_data['Name'] ) && $plugin_data['Name'] === $integration['name'];

				$text = $installed ? __( 'activate it now', 'popup-maker' ) : __( 'install it now', 'popup-maker' );
				$url  = $installed ? esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $path ), 'activate-plugin_' . $path ) ) : esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker-buddypress-integration' ), 'install-plugin_popup-maker-buddypress-integration' ) );

				$alerts[] = array(
					'code'        => $key . '_integration_available',
					'message'     => sprintf( __( '%sDid you know:%s Popup Maker has custom integrations with %s, %slearn more%s or %s%s%s!', 'popup-maker' ), '<strong>', '</strong>', $integration['label'], '<a href="' . $integration['learn_more_url'] . '" target="_blank">', '</a>', '<a href="' . $url . '">', $text, '</a>' ),
					'dismissible' => true,
					'global'      => false,
					'type'        => $installed ? 'warning' : 'info',
				);

			}

		}

		return $alerts;
	}

	/**
	 * Hook into relevant WP actions.
	 */
	public static function hooks() {
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'user_admin_notices', array( __CLASS__, 'admin_notices' ) );
		}
	}

	/**
	 * @return bool
	 */
	public static function should_show_alerts() {
		return in_array( true, array(
			pum_is_admin_page(),
			count( self::get_global_alerts() ) > 0,
		) );
	}

	/**
	 * Render admin alerts if available.
	 */
	public static function admin_notices() {
		if ( ! self::should_show_alerts() ) {
			return;
		}

		$global_only = ! pum_is_admin_page();

		$alerts = $global_only ? self::get_global_alerts() : self::get_alerts();

		$count = count( $alerts );

		if ( ! $count ) {
			return;
		}

		wp_enqueue_script( 'pum-admin-general' );
		wp_enqueue_style( 'pum-admin-general' );

		?>

		<script type="text/javascript">
            window.pum_alerts_nonce = '<?php echo wp_create_nonce( 'pum_alerts_action' ); ?>';
		</script>

		<div class="pum-alerts">

			<h3>
				<img alt="" class="logo" width="30" src="<?php echo Popup_Maker::$URL; ?>assets/images/logo.png" /> <?php printf( '%s%s (%s)', ( $global_only ? __( 'Popup Maker', 'popup-maker' ) . ' ' : '' ), __( 'Notifications', 'popup-maker' ), '<span class="pum-alert-count">' . $count . '</span>' ); ?>
			</h3>

			<p><?php __( 'Check out the following notifications from Popup Maker.', 'popup-maker' ); ?></p>

			<?php foreach ( $alerts as $alert ) : ?>

				<div class="pum-alert-holder" data-code="<?php echo $alert['code']; ?>" class="<?php echo $alert['dismissible'] ? 'is-dismissible' : ''; ?>" data-dismissible="<?php echo esc_attr( $alert['dismissible'] ); ?>">

					<div class="pum-alert <?php echo $alert['type'] != '' ? 'pum-alert__' . $alert['type'] : ''; ?>">

						<?php if ( ! empty( $alert['message'] ) ) : ?>
							<p><?php echo $alert['message']; ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $alert['html'] ) ) : ?>
							<?php echo function_exists( 'wp_encode_emoji' ) ? wp_encode_emoji( $alert['html'] ) : $alert['html']; ?>
						<?php endif; ?>

					</div>

					<?php if ( $alert['dismissible'] ) : ?>

						<button type="button" class="button dismiss pum-dismiss">
							<span class="screen-reader-text"><?php _e( 'Dismiss this item.', 'popup-maker' ); ?></span> <span class="dashicons dashicons-no-alt"></span>
						</button>

					<?php endif; ?>

				</div>

			<?php endforeach; ?>

		</div>

		<?php
	}

	/**
	 * @return array
	 */
	public static function get_global_alerts() {
		$alerts = self::get_alerts();

		$global_alerts = array();

		foreach ( $alerts as $alert ) {
			if ( $alert['global'] ) {
				$global_alerts[] = $alert;
			}
		}

		return $global_alerts;
	}

	/**
	 * @return array
	 */
	public static function get_alerts() {

		static $alert_list;

		if ( ! isset( $alert_list ) ) {
			$alert_list = apply_filters( 'pum_alert_list', array() );
		}

		$alerts = array();

		foreach ( $alert_list as $alert ) {

			// Ignore dismissed alerts.
			if ( self::has_dismissed_alert( $alert['code'] ) ) {
				continue;
			}

			$alerts[] = wp_parse_args( $alert, array(
				'code'        => 'default',
				'priority'    => 10,
				'message'     => '',
				'type'        => 'info',
				'html'        => '',
				'dismissible' => true,
				'global'      => false,
			) );

		}

		// Sort alerts by priority, highest to lowest.
		$alerts = PUM_Utils_Array::sort( $alerts, 'priority', true );

		return $alerts;
	}


	/**
	 *
	 */
	public static function ajax_handler() {
		$args = wp_parse_args( $_REQUEST, array(
			'code'    => '',
			'expires' => '',
		) );

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pum_alerts_action' ) ) {
			wp_send_json_error();
		}

		try {
			$dismissed_alerts                  = self::dismissed_alerts();
			$dismissed_alerts[ $args['code'] ] = ! empty( $args['expires'] ) ? strtotime( '+' . $args['expires'] ) : true;

			$user_id = get_current_user_id();
			update_user_meta( $user_id, '_pum_dismissed_alerts', $dismissed_alerts );
			wp_send_json_success();

		} catch ( Exception $e ) {
			wp_send_json_error( $e );
		}
	}

	/**
	 * @param string $code
	 *
	 * @return bool
	 */
	public static function has_dismissed_alert( $code = '' ) {
		$dimissed_alerts = self::dismissed_alerts();

		$alert_dismissed = array_key_exists( $code, $dimissed_alerts );

		// If the alert was dismissed and has a non true type value, it is an expiry time.
		if ( $alert_dismissed && true !== $dimissed_alerts[ $code ] ) {
			return strtotime( 'now' ) < $dimissed_alerts[ $code ];
		}

		return $alert_dismissed;
	}

	/**
	 * Returns an array of dismissed alert groups.
	 *
	 * @return array
	 */
	public static function dismissed_alerts() {
		$user_id = get_current_user_id();

		$dismissed_alerts = get_user_meta( $user_id, '_pum_dismissed_alerts', true );

		if ( ! is_array( $dismissed_alerts ) ) {
			$dismissed_alerts = array();
			update_user_meta( $user_id, '_pum_dismissed_alerts', $dismissed_alerts );
		}

		return $dismissed_alerts;
	}

}
