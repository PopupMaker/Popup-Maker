<?php
/**
 * User Model Handler.
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class used to implement the custom WP_User object.
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int    $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 */
abstract class PUM_Abstract_Model_User {

	/**
	 * The current model version.
	 *
	 * Used for compatibility testing.
	 * 1 - v1.0.0
	 *
	 * @var int
	 */
	public $model_version = 1;

	/**
	 * The version of the data currently stored for the current item.
	 *
	 * 1 - v1.0.0
	 *
	 * @var int
	 */
	public $data_version;

	/**
	 * The user's ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The user.
	 *
	 * @var \WP_User
	 */
	public $user;

	/**
	 * Array of data keys.
	 *
	 * @var array An array of keys that can be accessed via the $this->user (WP_User) object.
	 */
	public $core_data_keys = [
		'nickname',
		'description',
		'user_description',
		'first_name',
		'user_firstname',
		'last_name',
		'user_lastname',
		'user_login',
		'user_pass',
		'user_nicename',
		'user_email',
		'user_url',
		'user_registered',
		'user_activation_key',
		'user_status',
		'user_level',
		'display_name',
		'spam',
		'deleted',
		'locale',
		'data',
		'ID',
		'caps',
		'cap_key',
		'roles',
		'allcaps',
		'filter',
	];

	/**
	 * The required permission|user_role|capability|user_level of the user.
	 *
	 * @var
	 */
	protected $required_permission = '';

	/**
	 * Get things going.
	 *
	 * @param WP_User|int $user  The user.
	 */
	public function __construct( $user ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			$user = new WP_User( $user );
		}

		$this->setup( $user );
	}

	/**
	 * Given the user data, let's set the variables.
	 *
	 * @param WP_User $user The User Object.
	 */
	protected function setup( $user ) {
		if ( ! is_a( $user, 'WP_User' ) || ( $this->required_permission && ! $user->has_cap( $this->required_permission ) ) ) {
			return;
		}

		if ( ! isset( $user->data->ID ) ) {
			$user->data->ID = 0;
		}

		$this->user = $user;

		// Set $this->ID based on the users ID.
		$this->ID = $user->ID;
	}

	/**
	 * Check if set.
	 *
	 * @param string $key  Property to check.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		if ( in_array( $key, $this->core_data_keys, true ) ) {
			return isset( $this->user->$key );
		}
	}

	/**
	 * Method for unsetting a certain field.
	 *
	 * @param string $key  Property to unset.
	 */
	public function __unset( $key ) {
		if ( in_array( $key, $this->core_data_keys, true ) ) {
			unset( $this->user->$key );
		}
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @param string $key Property to be retrieved.
	 *
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {
		if ( in_array( $key, $this->core_data_keys, true ) ) {

			return $this->user->$key;

		} elseif ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( [ $this, 'get_' . $key ] );

		} else {

			$meta = get_user_meta( $this->ID, $key, true );

			if ( $meta ) {
				return $meta;
			}
			/* translators: 1. property name */
			return new WP_Error( 'user-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );

		}
	}

	/**
	 * Function __call.
	 *
	 * @param string $name  Method to call.
	 * @param array  $arguments  Arguments to pass when calling.
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this->user, $name ) ) {
			return call_user_func_array( [ $this->user, $name ], $arguments );
		}
	}

	/**
	 * Get per site or global user options.
	 *
	 * @param string $key  Option to retrieve.
	 *
	 * @return mixed
	 */
	public function get_option( $key ) {
		return get_user_option( $key, $this->ID );
	}

	/**
	 * Used to set per site or global user options.
	 *
	 * @param string $key  Name of the option to update.
	 * @param mixed  $value  Option value. Must be serializable if non-scalar.
	 * @param bool   $global  Whether to load the option when WordPress starts up.
	 *
	 * @return bool|int
	 */
	public function update_option( $key, $value, $global = false ) {
		return update_user_option( $this->ID, $key, $value, $global );
	}

	/**
	 * Used to delete per site or global user options.
	 *
	 * @param string $key  Name of the option to delete.
	 * @param bool   $global  Whether to delete option.
	 *
	 * @return bool
	 */
	public function delete_option( $key, $global = false ) {
		return delete_user_option( $this->ID, $key, $global );
	}

	/**
	 * Get user meta.
	 *
	 * @param string $key  Data to retrieve.
	 * @param bool   $single  If true, return only the first value of the specified.
	 *
	 * @return mixed
	 */
	public function get_meta( $key, $single = true ) {
		return get_user_meta( $this->ID, $key, $single );
	}

	/**
	 * Add user meta.
	 *
	 * @param string $key  Metadata key.
	 * @param mixed  $value  Metadata value.
	 * @param bool   $unique Whether the specified data should be unique for the object.
	 *
	 * @return bool|int
	 */
	public function add_meta( $key, $value, $unique = false ) {
		return add_user_meta( $this->ID, $key, $value, $unique );
	}

	/**
	 * Update user meta.
	 *
	 * @param string $key  Metadata key.
	 * @param string $value  Metadata value.
	 *
	 * @return bool|int
	 */
	public function update_meta( $key, $value ) {
		return update_user_meta( $this->ID, $key, $value );
	}

	/**
	 * Delete user meta.
	 *
	 * @param string $key  Metadata key.
	 * @param string $value  Metadata value.
	 *
	 * @return bool|int
	 */
	public function delete_meta( $key, $value = '' ) {
		return delete_user_meta( $this->ID, $key, $value );
	}

	/**
	 * Retrieve the avatar.
	 *
	 * @param int $size  Height and width of the avatar image file in pixels.
	 *
	 * @return false|string
	 */
	public function get_avatar( $size = 35 ) {
		return get_avatar( $this->ID, $size );
	}

	/**
	 * Convert object to array.
	 *
	 * @return array Object as array.
	 */
	public function to_array() {
		$user = $this->user->to_array();

		foreach ( get_object_vars( $this ) as $k => $v ) {
			$user[ $k ] = $v;
		}

		return $user;
	}
}
