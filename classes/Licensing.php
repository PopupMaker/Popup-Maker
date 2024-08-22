<?php
/**
 * Licensing class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Licensing {

	/**
	 * Is the license currently active?
	 *
	 * @param object|bool|null $license
	 *
	 * @return bool
	 */
	public static function license_is_valid( $license = null ) {
		return self::get_status( $license ) === 'valid';
	}

	/**
	 * @param object|bool|null $license
	 *
	 * @return bool
	 */
	public static function has_license( $license = null ) {
		return ! empty( $license ) && is_object( $license );
	}

	/**
	 * @param object|bool|null $license
	 *
	 * @return bool|false|int|string
	 */
	public static function get_license_expiration( $license = null ) {
		if ( ! self::has_license( $license ) || ! self::license_is_valid( $license ) ) {
			return false;
		}

		return 'lifetime' === $license->expires ? 'lifetime' : strtotime( $license->expires, time() );
	}

	/**
	 * Get the current license status.
	 *
	 * @return string
	 */
	public static function get_status( $license = null, $has_key = false ) {
		$status = $has_key ? 'inactive' : 'empty';

		if ( self::has_license( $license ) ) {
			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {
				$error = property_exists( $license, 'error' ) ? $license->error : $status;

				switch ( $error ) {
					case 'expired':
						$status = 'expired';
						break;
					case 'revoked':
					case 'missing':
					case 'invalid':
					case 'site_inactive':
					case 'item_name_mismatch':
					case 'no_activations_left':
					case 'license_not_activable':
					default:
						$status = 'error';
						break;
				}
			} else {
				$status = 'valid';
			}
		}

		return $status;
	}

	/**
	 * @param object|bool|null $license
	 *
	 * @param string           $key
	 *
	 * @return array
	 */
	public static function get_status_messages( $license = null, $key = '' ) {
		$messages = [];

		if ( self::has_license( $license ) ) {

			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {
				switch ( $license->error ) {
					case 'expired':
						$messages[] = sprintf(
							/* translators: 1. Expiration date, 2. Opening HTML link tag, 3. Closing HTML tag. */
							__( 'Your license key expired on %1$s. Please %2$srenew your license key%3$s.', 'popup-maker' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license->expires, time() ) ),
							'<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $key . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_medium=expired&utm_content=pum_license">',
							'</a>'
						);
						break;
					case 'disabled':
					case 'revoked':
						$messages[] = sprintf(
							/* translators: 1. Opening HTML link tag, 2. Closing HTML tag. */
							__( 'Your license key has been disabled. Please %1$scontact support%2$s for more information.', 'popup-maker' ),
							'<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=revoked">',
							'</a>'
						);
						break;
					case 'missing':
						$messages[] = sprintf(
							/* translators: 1. Opening HTML link tag, 2. Closing HTML tag. */
							__( 'Invalid license. Please %1$svisit your account page%2$s and verify it.', 'popup-maker' ),
							'<a target="_blank" href="https://wppopupmaker.com/your-account/license-keys/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=missing">',
							'</a>'
						);
						break;
					case 'invalid':
					case 'site_inactive':
						$messages[] = sprintf(
							/* translators: 1. Plugin name. 2. Opening HTML link tag, 3. Closing HTML tag. */
							__( 'Your %1$s is not active for this URL. Please %2$svisit your account page%3$s to manage your license key URLs.', 'popup-maker' ),
							Popup_Maker::$NAME,
							'<a target="_blank" href="https://wppopupmaker.com/your-account/license-keys/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=invalid">',
							'</a>'
						);
						break;
					case 'item_name_mismatch':
						$messages[] = sprintf(
							/* translators: 1. Plugin name. */
							__( 'This appears to be an invalid license key for %s.', 'popup-maker' ),
							Popup_Maker::$NAME
						);
						break;
					case 'no_activations_left':
						$messages[] = sprintf(
							/* translators: 1. Opening HTML link tag, 2. Closing HTML tag. */
							__( 'Your license key has reached its activation limit. %1$sView possible upgrades%2$s now.', 'popup-maker' ),
							'<a target="_blank" href="https://wppopupmaker.com/your-account/license-keys/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=no-activations-left">',
							'</a>'
						);
						break;
					case 'license_not_activable':
						$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'popup-maker' );
						break;
					default:
						$error      = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'popup-maker' );
						$messages[] = sprintf(
							/* translators: 1. Error message, 2. Opening HTML link tag, 3. Closing HTML tag. */
							__( 'There was an error with this license key: %1$s. Please %2$scontact our support team%3$s.', 'popup-maker' ),
							$error,
							'<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=error-contact-support">',
							'</a>'
						);
						break;
				}
			} else {
				switch ( $license->license ) {
					case 'valid':
					default:
						$now        = time();
						$expiration = strtotime( $license->expires, time() );

						if ( 'lifetime' === $license->expires ) {
							$messages[] = __( 'License key never expires.', 'popup-maker' );
						} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
							$messages[] = sprintf(
								/* translators: 1. Expiration date, 2. Opening HTML link tag, 3. Closing HTML tag. */
								__( 'Your license key expires soon! It expires on %1$s. %2$sRenew your license key%3$s.', 'popup-maker' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license->expires, time() ) ),
								'<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $key . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=Popup+Maker+license&utm_medium=renew">',
								'</a>'
							);
						} else {
							$messages[] = sprintf(
								/* translators: 1. Expiration date. */
								__( 'Your license key expires on %s.', 'popup-maker' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license->expires, time() ) )
							);
						}
						break;
				}
			}
		} else {
			$messages[] = sprintf(
				/* translators: 1. Plugin name. */
				__( 'To receive updates, please enter your valid %s license key.', 'popup-maker' ),
				Popup_Maker::$NAME
			);
		}

		return $messages;
	}

	/**
	 * @param object|bool|null $license
	 *
	 * @return bool|string
	 */
	public static function get_status_classes( $license = null ) {
		$class = false;

		if ( self::has_license( $license ) && false !== $license->success ) {
			$now        = time();
			$expiration = self::get_license_expiration( $license );

			if ( 'lifetime' === $expiration ) {
				$class = 'pum-license-lifetime-notice';
			} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
				$class = 'pum-license-expires-soon-notice';
			} else {
				$class = 'pum-license-expiration-date-notice';
			}
		}

		return $class;
	}
}
