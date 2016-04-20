<?php
/**
 * Cookies
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Cookies
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Cookies {

	public static $instance;

	public $cookies = array();

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Cookies ) ) {
			self::$instance = new PUM_Cookies;
		}

		return self::$instance;
	}

	public function add_cookies( $cookies = array() ) {
		foreach ( $cookies as $key => $cookie ) {

			if ( ! $cookie instanceof PUM_Cookie && is_array( $cookie ) ) {
				if ( empty( $cookie['id'] ) && ! is_numeric( $key ) ) {
					$cookie['id'] = $key;
				}

				$cookie = new PUM_Cookie( $cookie );
			}

			$this->add_cookie( $cookie );

		}
	}

	public function add_cookie( $cookie = null ) {
		if ( ! $cookie instanceof PUM_Cookie ) {
			return;
		}

		if ( ! isset ( $this->cookies[ $cookie->id ] ) ) {
			$this->cookies[ $cookie->id ] = $cookie;
		}

		return;
	}

	public function get_cookies() {
		return $this->cookies;
	}

	public function get_cookie( $cookie = null ) {
		return isset( $this->cookies[ $cookie ] ) ? $this->cookies[ $cookie ] : null;
	}

	public function get_defaults( $cookie = null ) {
		$defaults = array();

		if ( ! $cookie ) {
			foreach ( $this->get_cookies() as $cookie ) {
				foreach ( $cookie->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $cookie->get_id() ][ $section ][ $field['id'] ] = $field['std'];
						}
						else {
							$defaults[ $cookie->get_id() ][ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		}
		else {
			$cookie = $this->get_cookie( $cookie );
			if ( $cookie ) {
				foreach ( $cookie->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $section ][ $field['id'] ] = $field['std'];
						}
						else {
							$defaults[ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		}

		return $defaults;
	}

	public function get_labels( $cookie = null ) {
		$labels = array();

		if ( ! $cookie ) {
			foreach ( $this->get_cookies() as $cookie ) {
				$labels[ $cookie->get_id() ] = $cookie->get_labels();
			}
		}
		else {
			$cookie = $this->get_cookie( $cookie );
			if ( $cookie ) {
				$labels = $cookie->get_labels();
			}
		}

		return $labels;
	}

	public function validate_cookie( $cookie = null, $settings = array() ) {
		if ( ! $cookie || empty( $settings ) ) {
			return $settings;
		}

		$cookie = $this->get_cookie( $cookie );
		if ( $cookie ) {
			$settings = $cookie->sanitize_fields( $settings );
		}

		return $settings;
	}

}
