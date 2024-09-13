<?php
/**
 * Importer for easy-modal model theme meta
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
 * Class EModal_Model_Theme_Meta
 *
 * Used to mimic the EModal_Model_Theme_Meta class from Easy Modal plugin.
 *
 * @since 1.0
 */
class EModal_Model_Theme_Meta extends EModal_Model {
	/** @var int */
	protected $theme_id;
	protected $_class_name     = 'EModal_Model_Theme_Meta';
	protected $_table_name     = 'em_theme_metas';
	protected $_pk             = 'theme_id';
	protected $_default_fields = [
		'id'        => null,
		'theme_id'  => null,
		'overlay'   => [],
		'container' => [],
		'close'     => [],
		'title'     => [],
		'content'   => [],
	];

	public function __construct( $id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;
		$class_name = strtolower( $this->_class_name );

		$this->_default_fields['theme_id'] = $id;
		$this->_data                       = apply_filters( "{$class_name}_fields", $this->_default_fields );
		if ( $id && is_numeric( $id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$row = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare( "SELECT * FROM $table_name WHERE theme_id = %d ORDER BY id DESC LIMIT 1", $id ),
				ARRAY_A
			);

			if ( $row[ $this->_pk ] ) {
				$this->process_load( $row );
			}
		} else {
			$this->set_fields( apply_filters( "{$class_name}_defaults", [] ) );
		}
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col( "SELECT id FROM $table_name WHERE theme_id = $this->theme_id ORDER BY id DESC" );
		if ( count( $rows ) ) {
			$this->id = $rows[0];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $table_name, $this->serialized_values(), [ 'id' => $this->id ] );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table_name, $this->serialized_values() );
			$this->id = $wpdb->insert_id;
		}
	}
}
