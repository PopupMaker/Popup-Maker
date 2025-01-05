<?php
/**
 * Globals service.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Services;

use PUM_Model_Popup as Popup;

defined( 'ABSPATH' ) || exit;

/**
 * Globals service.
 */
class Globals {

	/**
	 * Allowed global properties.
	 *
	 * @var array
	 */
	private $allowed_properties = [
		'current_rule',
		'current_popup',
	];

	/**
	 * Current rule.
	 *
	 * @var \PopupMaker\Models\RuleEngine\Rule|null
	 */
	public $current_rule = null;

	/**
	 * Current popup.
	 *
	 * @var Popup|null
	 */
	public $current_popup = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get context items by key.
	 *
	 * @param string $key Context key.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		return property_exists( $this, $key ) ? $this->$key : $default_value;
	}

	/**
	 * Set context items by key.
	 *
	 * @param string $key Context key.
	 * @param mixed  $value Context value.
	 *
	 * @return void
	 */
	public function set( $key, $value ) {
		if ( property_exists( $this, $key ) ) {
			$this->$key = $value;
		}
	}

	/**
	 * Reset context items by key.
	 *
	 * @param string $key Context key.
	 *
	 * @return void
	 */
	public function reset( $key ) {
		if ( property_exists( $this, $key ) ) {
			$this->$key = null;
		}
	}

	/**
	 * Reset all context items.
	 *
	 * @return void
	 */
	public function reset_all() {
		foreach ( $this->allowed_properties as $key ) {
			$this->reset( $key );
		}
	}

	/**
	 * Push to stack.
	 *
	 * @param string $key Context key.
	 * @param mixed  $value Context value.
	 *
	 * @return void
	 */
	public function push_to_stack( $key, $value ) {
		if ( property_exists( $this, $key ) ) {
			$values = $this->get( $key, [] );

			$values[] = $value;

			$this->set( $key, $values );
		}
	}

	/**
	 * Pop from stack.
	 *
	 * @param string $key Context key.
	 *
	 * @return mixed
	 */
	public function pop_from_stack( $key ) {
		if ( property_exists( $this, $key ) ) {
			$values = $this->get( $key, [] );

			if ( empty( $values ) ) {
				return null;
			}

			$value = array_pop( $values );

			$this->set( $key, $values );

			return $value;
		}

		return null;
	}

	/**
	 * Check if stack is empty.
	 *
	 * @param string $key Context key.
	 *
	 * @return bool
	 */
	public function is_empty( $key ) {
		$value = $this->get( $key );

		return ! isset( $value ) || empty( $value );
	}
}
