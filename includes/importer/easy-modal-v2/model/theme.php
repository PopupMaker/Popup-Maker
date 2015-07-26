<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EModal_Model_Theme extends EModal_Model {
	protected $_class_name = 'EModal_Model_Theme';
	protected $_table_name = 'em_themes';
	protected $meta;
	protected $_default_fields = array(
		'id'        => null,
		'name'      => 'Default',
		'created'   => '0000-00-00 00:00:00',
		'modified'  => '0000-00-00 00:00:00',
		'is_system' => 0,
		'is_trash'  => 0
	);

	public function __construct( $id = null ) {
		parent::__construct( $id );
		$this->load_meta();

		return $this;
	}

	public function __get( $key ) {
		if ( $key == 'meta' ) {
			return $this->meta;
		} else {
			return parent::__get( $key );
		}
	}

	public function save() {
		if ( ! $this->id ) {
			$this->created = date( 'Y-m-d H:i:s' );
		}
		$this->modified = date( 'Y-m-d H:i:s' );
		parent::save();
		$this->meta->theme_id = $this->id;
		$this->meta->save();
	}

	public function load_meta() {
		if ( empty( $this->meta ) ) {
			$this->meta = new EModal_Model_Theme_Meta( $this->id );
		}

		return $this->meta;
	}

	public function as_array() {
		$array         = parent::as_array();
		$array['meta'] = $this->meta->as_array();

		return $array;
	}

	public function set_fields( array $data ) {
		if ( ! empty( $data['meta'] ) ) {
			$this->meta->set_fields( $data['meta'] );
			unset( $data['meta'] );
		}
		parent::set_fields( $data );
	}
}

if ( ! function_exists( "get_all_modal_themes" ) ) {
	function get_all_modal_themes( $where = "is_trash != 1" ) {
		global $wpdb;

		$themes                  = array();
		$theme_ids               = array();
		$EModal_Model_Theme      = new EModal_Model_Theme;
		$EModal_Model_Theme_Meta = new EModal_Model_Theme_Meta;
		foreach ( $EModal_Model_Theme->load( "SELECT * FROM  {$wpdb->prefix}em_themes" . ( $where ? " WHERE " . $where : '' ) ) as $theme ) {
			$themes[ $theme->id ] = $theme;
			$theme_ids[]          = $theme->id;
		}
		if ( count( $themes ) ) {
			foreach ( $EModal_Model_Theme_Meta->load( "SELECT * FROM  {$wpdb->prefix}em_theme_metas WHERE theme_id IN (" . implode( ',', $theme_ids ) . ")" ) as $meta ) {
				$themes[ $meta->theme_id ]->meta->process_load( $meta->as_array() );
			}
		}

		return $themes;
	}
}

if ( ! function_exists( "get_current_modal_theme" ) ) {
	function get_current_modal_theme( $key = null ) {
		global $current_theme;
		if ( ! $key ) {
			return $current_theme;
		} else {
			$values = $current_theme->as_array();

			return emresolve( $values, $key, false );
		}
	}
}

if ( ! function_exists( "get_current_modal_theme_id" ) ) {
	function get_current_modal_theme_id() {
		global $current_theme;

		return $current_theme->id;
	}
}


if ( ! function_exists( "count_all_modal_themes" ) ) {
	function count_all_modal_themes() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM  {$wpdb->prefix}em_themes WHERE is_trash = 0" );
	}
}

if ( ! function_exists( "count_deleted_modal_themes" ) ) {
	function count_deleted_modal_themes() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM  {$wpdb->prefix}em_themes WHERE is_trash = 1" );
	}
}