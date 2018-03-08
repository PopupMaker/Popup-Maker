<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Subscribers_Table
 */
class PUM_Admin_Subscribers_Table extends PUM_ListTable {

	/**
	 * Constructor.
	 *
	 * The child class should call this constructor from its own constructor to override
	 * the default $args.
	 *
	 * @param array|string $args     {
	 *                               Array or string of arguments.
	 *
	 * @type string        $plural   Plural value used for labels and the objects being listed.
	 *                            This affects things such as CSS class-names and nonces used
	 *                            in the list table, e.g. 'posts'. Default empty.
	 * @type string        $singular Singular label for an object being listed, e.g. 'post'.
	 *                            Default empty
	 * @type bool          $ajax     Whether the list table supports Ajax. This includes loading
	 *                            and sorting data, for example. If true, the class will call
	 *                            the _js_vars() method in the footer to provide variables
	 *                            to any scripts handling Ajax events. Default false.
	 * @type string        $screen   String containing the hook name used to determine the current
	 *                            screen. If left null, the current screen will be automatically set.
	 *                            Default null.
	 * }
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'plural'   => 'subscribers',    // Plural value used for labels and the objects being listed.
			'singular' => 'subscriber',        // Singular label for an object being listed, e.g. 'post'.
			'ajax'     => false,        // If true, the parent class will call the _js_vars() method in the footer
		) );

		parent::__construct( $args );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @uses PUM_ListTable::set_pagination_args()
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		// check and process any actions such as bulk actions.
		$this->handle_table_actions();

		$limit = $this->get_items_per_page( 'subscribers_per_page' );

		$query_args = array(
			's'     => isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
			'limit' => $limit,
			'page'  => $this->get_pagenum(),
		);

		$this->items = PUM_DB_Subscribers::instance()->query( $query_args, 'ARRAY_A' );

		$total_subscribers = PUM_DB_Subscribers::instance()->total_rows( $query_args );

		$this->set_pagination_args( array(
			'total_items' => $total_subscribers,
			'per_page'    => $limit,
			'total_pages' => ceil( $total_subscribers / $limit ),
		) );
	}


	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		return apply_filters( 'pum_subscribers_table_columns', array(
			'cb'            => '<input type="checkbox" />', // to display the checkbox.
			'ID'            => 'ID',
			'email'         => __( 'Email', 'popup-maker' ),
			'fname'         => __( 'First Name', 'popup-maker' ),
			'lname'         => __( 'Last Name', 'popup-maker' ),
			'popup_id'      => __( 'Popup ID', 'popup-maker' ),
			'user_id'       => __( 'User ID', 'popup-maker' ),
			'created' => _x( 'Subscribed On', 'column name', 'popup-maker' ),
		) );
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 * \     *
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return apply_filters( 'pum_subscribers_table_columns', array(
			'ID'            => array( 'ID', true ),
			'email'         => 'email',
			'fname'         => 'fname',
			'lname'         => 'lname',
			'popup_id'      => 'popup_id',
			'created' => 'created',
		) );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'ID';
	}


	/**
	 * Text displayed when no user data is available
	 */
	public function no_items() {
		_e( 'No subscribers available.', 'popup-maker' );
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'email':
			case 'fname':
			case 'ID':
				return $item[$column_name];
			default:
				return $item[ $column_name ];
		}
	}


	/**
	 * Get value for checkbox column.
	 *
	 * The special 'cb' column
	 *
	 * @param object $item A row's data
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $item ) {
		return sprintf( '<label class="screen-reader-text" for="subscriber_' . $item['ID'] . '">' . sprintf( __( 'Select %s' ), $item['fname'] . ' ' . $item['lname'] ) . '</label>' . "<input type='checkbox' name='users[]' id='subscriber_{$item['ID']}' value='{$item['ID']}' />" );
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		/*
		 * on hitting apply in bulk actions the url params are set as
		 * ?action=bulk-download&paged=1&action2=-1
		 *
		 * action and action2 are set based on the triggers above or below the table
		 */
		$actions = array(
			'bulk-delete' => __( 'Delete Subscribers', 'popup-maker' ),
		);

		return $actions;
	}


	/**
	 * Process actions triggered by the user
	 *
	 * @since    1.0.0
	 *
	 */
	public function handle_table_actions() {

		/*
		 * Note: Table bulk_actions can be identified by checking $_REQUEST['action'] and $_REQUEST['action2']
		 *
		 * action - is set if checkbox from top-most select-all is set, otherwise returns -1
		 * action2 - is set if checkbox the bottom-most select-all checkbox is set, otherwise returns -1
		 */

		// check for individual row actions
		$the_table_action = $this->current_action();

		if ( 'view_usermeta' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'view_usermeta_nonce' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$this->page_view_usermeta( absint( $_REQUEST['user_id'] ) );
				$this->graceful_exit();
			}
		}

		if ( 'add_usermeta' === $the_table_action ) {
			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'add_usermeta_nonce' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$this->page_add_usermeta( absint( $_REQUEST['user_id'] ) );
				$this->graceful_exit();
			}
		}

		// check for table bulk actions
		if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'bulk-download' ) || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-download' ) ) {

			$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
			// verify the nonce.
			/*
			 * Note: the nonce field is set by the parent class
			 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			 *
			 */
			if ( ! wp_verify_nonce( $nonce, 'bulk-users' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$this->page_bulk_download( $_REQUEST['users'] );
				$this->graceful_exit();
			}
		}

	}


}

