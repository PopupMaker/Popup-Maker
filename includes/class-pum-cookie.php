<?php
/**
 * Cookie
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Cookie
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Cookie extends PUM_Fields {

	public $id;

	public $labels = array();

	public $field_prefix = 'cookie_settings';

	public $field_name_format = '{$prefix}{$section}[{$field}]';

	/**
	 * Sets the $id of the Cookie and returns the parent __cunstruct()
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->id = $args['id'];

		if ( ! empty( $args['labels'] ) ) {
			$this->set_labels( $args['labels'] );
		}

		return parent::__construct( $args );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_labels( $labels = array() ) {
		$this->labels = wp_parse_args( $labels, array(
			'name' => __( 'Cookie', 'popup-maker' ),
			'modal_title' => __( 'Cookie Settings', 'popup-maker' ),
			'settings_column' => sprintf( '%s%s%s',
				'<# if (typeof data.session === "undefined" || data.session !== "1") { print(data.time); } else { print("',
				__( 'Sessions', 'popup-maker' ),
				'"); } #>'
			),
		) );
	}

	public function get_label( $key ) {
		return isset( $this->labels[ $key ] ) ? $this->labels[ $key ] : null;
	}

	public function get_labels() {
		return $this->labels;
	}

	public function get_field_name( $field ) {
		return str_replace(
			array(
				'{$prefix}',
				'{$section}',
				'{$field}'
			),
			array(
				$this->field_prefix,
				$field['section'] != 'general' ? "[{$field['section']}]" : '',
				$field['id']
			),
			$this->field_name_format
		);
	}

	public function field_before( $class = '' ) {
		?><div class="field <?php esc_attr_e( $class ); ?>"><?php
	}

	public function field_after() {
		?></div><?php
	}


	/**
	 * Heading Callback
	 *
	 * Renders the heading.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function heading_callback( $args ) { ?>
		<h2 class="pum-setting-heading"><?php esc_html_e( $args['desc'] ); ?></h2>
		<hr/><?php
	}

	/**
	 * Cookie Key Callback
	 *
	 * Renders cookie key fields.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function cookiekey_callback( $args, $value ) {

		$class = 'cookiekey ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! $value ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular'; ?>

		<div class="cookie-key">
			<button type="button" class="reset dashicons-before dashicons-image-rotate" title="<?php _e( 'Reset Cookie Key', 'popup-maker' ); ?>"></button>
			<input type="text" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $size ); ?>-text dashicons-before dashicons-image-rotate" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php esc_attr_e( stripslashes( $value ) ); ?>"/>
		</div><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}

	public function cookiekey_templ_callback( $args ) {
		$templ_name = $this->get_templ_name( $args );

		$class = 'cookiekey ' . $args['id'];

		if ( ! empty ( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}

		$this->field_before( $class );

		if ( ! empty( $args['label'] ) ) { ?>
			<label for="<?php esc_attr_e( $args['id'] ); ?>"><?php esc_html_e( $args['label'] ); ?></label><?php
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular'; ?>

		<div class="cookie-key">
			<button type="button" class="reset dashicons-before dashicons-image-rotate" title="<?php _e( 'Reset Cookie Key', 'popup-maker' ); ?>"></button>
			<input type="text" placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>" class="<?php esc_attr_e( $size ); ?>-text" id="<?php esc_attr_e( $args['id'] ); ?>" name="<?php esc_attr_e( $args['name'] ); ?>" value="<?php echo $templ_name; ?>"/>
		</div><?php

		if ( $args['desc'] != '' ) { ?>
			<p class="desc"><?php esc_html_e( $args['desc'] ); ?></p><?php
		}

		$this->field_after();
	}


	/**
	 * Sanitize fields
	 *
	 * @param array $values
	 *
	 * @return string $input Sanitized value
	 * @internal param array $input The value inputted in the field
	 *
	 */
	function sanitize_fields( $values = array() ) {

		$sanitized_values = array();

		foreach ( $this->get_all_fields() as $section => $fields ) {
			foreach ( $fields as $field ) {

				if ( $section != 'general' ) {
					$value = isset( $values[ $section ][ $field['id'] ] ) ? $values[ $section ][ $field['id'] ] : null;
				}
				else {
					$value = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : null;
				}

				$value = $this->sanitize_field( $field, $value );

				if ( ! is_null( $value ) ) {
					if ( $section != 'general' ) {
						$sanitized_values[ $section ][ $field['id'] ] = $value;
					}
					else {
						$sanitized_values[ $field['id'] ] = $value;
					}
				}
			}
		}

		return $sanitized_values;
	}

}
