<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

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

		return 'lifetime' === $license->expires ? 'lifetime' : strtotime( $license->expires, current_time( 'timestamp' ) );
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
					case 'expired' :
						$status = 'expired';
						break;
					case 'revoked' :
					case 'missing' :
					case 'invalid' :
					case 'site_inactive' :
					case 'item_name_mismatch' :
					case 'no_activations_left':
					case 'license_not_activable':
					default :
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
	 * @param string $key
	 *
	 * @return array
	 */
	public static function get_status_messages( $license = null, $key = '' ) {
		$messages = array();

		if ( self::has_license( $license ) ) {

			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {

				switch ( $license->error ) {
					case 'expired' :
						$messages[] = sprintf( __( 'Your license key expired on %s. Please %srenew your license key%s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ), '<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $key . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_medium=expired&utm_content=pum_license">', '</a>' );
						break;
					case 'revoked' :
						$messages[] = sprintf( __( 'Your license key has been disabled. Please %scontact support%s for more information.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=revoked">', '</a>' );
						break;
					case 'missing' :
						$messages[] = sprintf( __( 'Invalid license. Please %svisit your account page%s and verify it.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=missing">', '</a>' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$messages[] = sprintf( __( 'Your %s is not active for this URL. Please %svisit your account page%s to manage your license key URLs.', 'popup-maker' ), Popup_Maker::$NAME, '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=invalid">', '</a>' );
						break;
					case 'item_name_mismatch' :
						$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'popup-maker' ), Popup_Maker::$NAME );
						break;
					case 'no_activations_left':
						$messages[] = sprintf( __( 'Your license key has reached its activation limit. %sView possible upgrades%s now.', 'popup-maker' ), '<a target="_blank" href="https://wppopupmaker.com/account/?tab=licenses&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=no-activations-left">', '</a>' );
						break;
					case 'license_not_activable':
						$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'popup-maker' );
						break;
					default :
						$error      = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'popup-maker' );
						$messages[] = sprintf( __( 'There was an error with this license key: %s. Please %scontact our support team%s.', 'popup-maker' ), $error, '<a target="_blank" href="https://wppopupmaker.com/support/?utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=pum_license&utm_medium=error-contact-support">', '</a>' );
						break;
				}

			} else {

				switch ( $license->license ) {
					case 'valid' :
					default:
						$now        = current_time( 'timestamp' );
						$expiration = strtotime( $license->expires, current_time( 'timestamp' ) );

						if ( 'lifetime' === $license->expires ) {
							$messages[] = __( 'License key never expires.', 'popup-maker' );
						} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
							$messages[] = sprintf( __( 'Your license key expires soon! It expires on %s. %sRenew your license key%s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ), '<a target="_blank" href="https://wppopupmaker.com/checkout/?edd_license_key=' . $key . '&utm_campaign=Licensing&utm_source=plugin-settings-page-licenses-tab&utm_content=Popup+Maker+license&utm_medium=renew">', '</a>' );
						} else {
							$messages[] = sprintf( __( 'Your license key expires on %s.', 'popup-maker' ), date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ) );
						}
						break;
				}

			}
		} else {
			$messages[] = sprintf( __( 'To receive updates, please enter your valid %s license key.', 'popup-maker' ), Popup_Maker::$NAME );;
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
			$now        = current_time( 'timestamp' );
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
