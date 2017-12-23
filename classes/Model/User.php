<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

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
 * @property int $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 */
abstract class PUM_User {

	/**
	 * The user's ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * @var \WP_User
	 */
	public $user;

	/**
	 * The user meta array.
	 */
	public $meta;
	public $core_data_keys = array(
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
	);

	/**
	 * The required permission|user_role|capability|user_level of the user.
	 */
	protected $required_permission = false;
	/**
	 * @var array Known meta keys for a user object.
	 */
	protected $_meta_keys = array();
	/**
	 * Whether the object is valid.
	 */
	protected $valid = true;

	/**
	 * Get things going
	 *
	 * @param int $id
	 * @param bool $autoload_meta
	 */
	public function __construct( $id = null, $autoload_meta = true ) {
		if ( $id instanceof WP_User && ! isset( $id->data->ID ) ) {
			$id->data->ID = 0;
		}

		$user = new WP_User( $id );
		$this->_setup( $user, $autoload_meta );
	}

	/**
	 * Given the user data, let's set the variables
	 *
	 * @param WP_User $user The User Object
	 * @param bool $autoload_meta
	 */
	private function _setup( WP_User $user, $autoload_meta = true ) {
		if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) || ( $this->required_permission && ! $user->has_cap( $this->required_permission ) ) ) {
			$this->valid = false;

			return;
		}

		$this->user = $user;

		$this->ID = $this->user->ID;

		if ( $autoload_meta ) {
			$this->get_user_meta( true );
		}

		$this->setup();
	}

	/**
	 * Set/get the user meta for this object
	 *
	 * The $force parameter is in place to prevent hitting the database each time the method is called
	 * when we already have what we need in $this->meta
	 *
	 * @link    https://developer.wordpress.org/reference/functions/get_user_meta
	 *
	 * @param bool $force Whether to force load the user meta (helpful if $this->meta is already an array).
	 *
	 * @return array
	 * @since    1.0.0
	 */
	public function get_user_meta( $force = false ) {
		# make sure we have an ID
		if ( ! $this->ID ) {
			return array();
		}
		# if $this->meta is already an array
		if ( is_array( $this->meta ) ) {

			# return the array if we're not forcing the user meta to load
			if ( ! $force ) {
				return $this->meta;
			}
		} # if $this->meta isn't an array yet, initialize it as one
		else {
			$this->meta = array();
		}
		# get all user meta for the post
		$user_meta = get_user_meta( $this->ID );
		# if we found nothing
		if ( ! $user_meta ) {
			return $this->meta;
		}
		# loop through and clean up singleton arrays
		foreach ( $user_meta as $k => $v ) {
			# need to grab the first item if it's a single value
			if ( count( $v ) == 1 ) {
				$this->meta[ $k ] = maybe_unserialize( $v[0] );
			} # or store them all if there are multiple
			else {
				$this->meta[ $k ] = $v;
			}
		}

		return $this->meta;
	}

	public function setup() {
	}

	public static function insert( $user_arr = array(), $meta = array(), $return_object = true ) {

		// TODO Rewrite this.

		$id = wp_insert_post( $user_arr );

		if ( $id ) {
			if ( ! empty( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					add_post_meta( $id, $key, $value, true );
				}
			}

			return $return_object ? static::instance( $id ) : $id;
		}

		return $id;
	}

	public static $instances = array();

	/**
	 * Retrieve WP_User instance.
	 *
	 * @param int $user_id User ID.
	 * @param bool $autoload_meta
	 * @param bool $force
	 *
	 * @return User|bool
	 */
	public static function instance( $user_id, $autoload_meta = true, $force = false ) {
		// `model_user` passed to ahoy_cache_* will be appended with ahoy_ and stores a unique object for each post ID in any valid model.
		$cache_group = str_replace( array( '\\', 'ahoy_' ), array( '_', '' ), strtolower( get_called_class() ) );

		if ( ! is_numeric( $user_id ) ) {
			$user    = new WP_User( $user_id );
			$user_id = $user->ID;
		}

		/**
		 * @var User|false $user
		 */
		$user = ahoy_cache_get( $user_id, $cache_group );

		if ( $user === false || $force ) {
			if ( ! ( $_user = new \WP_User( $user_id ) ) ) {
				// Post doesn't exist.
				return false;
			}

			/**
			 * @var User $user
			 */
			$user = new static( $_user, $autoload_meta );

			if ( ! $user->is_valid() ) {
				// Post isn't correct post type for the called class. Do not cache it.
				return false;
			}

			if ( $force ) {
				ahoy_cache_set( $user_id, $user, $cache_group );
			} else {
				ahoy_cache_add( $user_id, $user, $cache_group );
			}
		}

		return $user->is_valid() ? $user : false;
	}

	public static function get_by( $field, $value, $autoload_post_meta = true ) {
		$user = WP_User::get_data_by( $field, $value );

		return static::instance( $user ? $user['ID'] : 0, $autoload_post_meta );
	}

	/**
	 * Get an array of new instances of this class (or an extension class), as a wrapper for a new WP_Query
	 *
	 * @param $query_args
	 * @param bool $autoload_user_meta
	 *
	 * @return array
	 */
	public static function query( $query_args, $autoload_user_meta = true ) {
		$defaults = array(
			'number' => - 1,
		);

		$query_args = wp_parse_args( $query_args, $defaults );

		$query = new \WP_User_Query( $query_args );

		$results = $query->__get( 'results' );

		foreach ( $results as $key => $user ) {
			$results[ $key ] = new static( $user, $autoload_user_meta );
		}

		$query->__set( 'results', $results );

		return $query;
	}


	public function __isset( $key ) {
		if ( in_array( $key, $this->core_data_keys ) ) {
			return isset( $this->user->$key );
		}
	}

	public function __unset( $key ) {
		if ( in_array( $key, $this->core_data_keys ) ) {
			unset( $this->user->$key );
		}
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0.0
	 *
	 * @param $key
	 *
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {
		if ( in_array( $key, $this->core_data_keys ) ) {

			return $this->user->$key;

		} elseif ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			$meta = get_user_meta( $this->ID, $key, true );

			if ( $meta ) {
				return $meta;
			}

			return new WP_Error( 'user-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );

		}
	}

	public function __set( $key, $value ) {
		if ( in_array( $key, $this->core_data_keys ) ) {
			return $this->user->$key = $value;
		}
	}

	public function __call( $name, $arguments ) {
		if ( method_exists( $this->user, $name ) ) {
			return call_user_func_array( array( $this->user, $name ), $arguments );
		}
	}

	/**
	 * Is object valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool.
	 */
	public function is_valid() {
		return $this->valid;
	}

	public function get_option( $key ) {
		return get_user_option( $key, $this->ID );
	}

	public function update_option( $key, $value ) {
		return update_user_option( $this->ID, $key, $value );
	}

	public function delete_option( $key ) {
		return delete_user_option( $this->ID, $key );
	}

	public function get_meta( $key, $single = true ) {
		if ( isset ( $this->meta[ $key ] ) ) {
			return $this->meta[ $key ];
		}

		return get_user_meta( $this->ID, $key, $single );
	}

	public function update_meta( $key, $value ) {
		return update_user_meta( $this->ID, $key, $value );
	}

	/**
	 * @param int $size
	 *
	 * @return false|string
	 */
	public function avatar( $size = 35 ) {
		return get_avatar( $this->ID, $size );
	}

	public function id() {
		return $this->ID;
	}

	/**
	 * Convert object to array.
	 *
	 * @since 1.0.0
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
