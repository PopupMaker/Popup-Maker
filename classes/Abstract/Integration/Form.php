<?php
/**
 * Abstract for Integration Form
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

abstract class PUM_Abstract_Integration_Form extends PUM_Abstract_Integration implements PUM_Interface_Integration_Form {

	/**
	 * @var string
	 */
	public $type = 'form';

	/**
	 * @return array
	 */
	abstract public function get_forms();

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	abstract public function get_form( $id );

	/**
	 * @return array
	 */
	abstract public function get_form_selectlist();

	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}

	/**
	 * Retrieves the popup ID associated with the form, if any
	 *
	 * @return false|int
	 * @since 1.13.0
	 */
	public function get_popup_id() {
		return isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false;
	}

	/**
	 * Increase the conversion count for popup
	 *
	 * @param int $popup_id The ID for the popup.
	 * @since 1.13.0
	 */
	public function increase_conversion( $popup_id ) {
		$popup_id = intval( $popup_id );
		$popup    = pum_get_popup( $popup_id );
		$popup->increase_event_count( 'conversion' );
	}

	/**
	 * Returns whether or now we should process any form submissions
	 *
	 * @return bool True if we should process the form submission
	 * @since 1.13.0
	 */
	public function should_process_submission() {
		if ( wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
			return false;
		}
		return true;
	}
}
