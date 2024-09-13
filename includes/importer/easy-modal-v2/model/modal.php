<?php
/**
 * Importer for easy-modal model modal
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid, PSR2.Classes.PropertyDeclaration.Underscore,Universal.Files.SeparateFunctionsFromOO.Mixed, WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EModal_Model_Modal
 *
 * Used to mimic the EModal_Model_Modal class from Easy Modal plugin.
 *
 * @since 1.0
 */
class EModal_Model_Modal extends EModal_Model {
	protected $_class_name = 'EModal_Model_Modal';
	protected $_table_name = 'em_modals';
	protected $meta;
	protected $_default_fields = [
		'id'          => null,
		'theme_id'    => 1,
		'name'        => '',
		'title'       => '',
		'content'     => '',
		'created'     => '0000-00-00 00:00:00',
		'modified'    => '0000-00-00 00:00:00',
		'is_sitewide' => 0,
		'is_system'   => 0,
		'is_trash'    => 0,
	];

	public function __construct( $id = null ) {
		parent::__construct( $id );
		$this->load_meta();
	}

	public function __get( $key ) {
		if ( 'meta' === $key ) {
			return $this->meta;
		} else {
			return parent::__get( $key );
		}
	}

	public function save() {
		if ( ! $this->id ) {
			$this->created = gmdate( 'Y-m-d H:i:s' );
		}
		$this->modified = gmdate( 'Y-m-d H:i:s' );
		parent::save();
		$this->meta->modal_id = $this->id;
		$this->meta->save();
	}

	public function load_meta() {
		if ( empty( $this->meta ) ) {
			$this->meta = new EModal_Model_Modal_Meta( $this->id );
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
		}
		parent::set_fields( $data );
	}
}

if ( ! function_exists( 'get_all_modals' ) ) {
	function get_all_modals( $where = 'is_trash != 1' ) {
		global $wpdb;
		$modals                  = [];
		$modal_ids               = [];
		$EModal_Model_Modal      = new EModal_Model_Modal();
		$EModal_Model_Modal_Meta = new EModal_Model_Modal_Meta();
		foreach ( $EModal_Model_Modal->load( "SELECT * FROM {$wpdb->prefix}em_modals" . ( $where ? ' WHERE ' . $where : '' ) ) as $modal ) {
			$modals[ $modal->id ] = $modal;
			$modal_ids[]          = $modal->id;
		}
		if ( count( $modals ) ) {
			foreach ( $EModal_Model_Modal_Meta->load( "SELECT * FROM {$wpdb->prefix}em_modal_metas WHERE modal_id IN (" . implode( ',', $modal_ids ) . ')' ) as $meta ) {
				$modals[ $meta->modal_id ]->meta->process_load( $meta->as_array() );
			}
		}

		return $modals;
	}
}

if ( ! function_exists( 'get_current_modal' ) ) {
	function get_current_modal( $key = null ) {
		global $current_modal;
		if ( ! $key ) {
			return $current_modal;
		} else {
			$values = $current_modal->as_array();

			return emresolve( $values, $key, false );
		}
	}
}

if ( ! function_exists( 'get_current_modal_id' ) ) {
	function get_current_modal_id() {
		global $current_modal;

		return $current_modal ? $current_modal->id : null;
	}
}

if ( ! function_exists( 'count_all_modals' ) ) {
	function count_all_modals() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}em_modals WHERE is_trash = 0" );
	}
}

if ( ! function_exists( 'count_deleted_modals' ) ) {
	function count_deleted_modals() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}em_modals WHERE is_trash = 1" );
	}
}
