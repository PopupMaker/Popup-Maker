<?php
/**
 * Admin Subscribers Table Handler
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
	public function __construct( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'plural'   => 'subscribers',    // Plural value used for labels and the objects being listed.
				'singular' => 'subscriber',        // Singular label for an object being listed, e.g. 'post'.
				'ajax'     => false,        // If true, the parent class will call the _js_vars() method in the footer
			]
		);

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

		$limit = $this->get_items_per_page( 'pum_subscribers_per_page' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$query_args = [
			's'       => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : null,
			'limit'   => $limit,
			'page'    => $this->get_pagenum(),
			'orderby' => isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : null,
			'order'   => isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : null,
		];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->items = PUM_DB_Subscribers::instance()->query( $query_args, 'ARRAY_A' );

		$total_subscribers = PUM_DB_Subscribers::instance()->total_rows( $query_args );

		$this->set_pagination_args(
			[
				'total_items' => $total_subscribers,
				'per_page'    => $limit,
				'total_pages' => ceil( $total_subscribers / $limit ),
			]
		);
	}


	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'pum_subscribers_table_columns',
			[
				'cb'       => '<input type="checkbox" />', // to display the checkbox.
				'email'    => __( 'Email', 'popup-maker' ),
				'name'     => __( 'Full Name', 'popup-maker' ),
				'fname'    => __( 'First Name', 'popup-maker' ),
				'lname'    => __( 'Last Name', 'popup-maker' ),
				'popup_id' => __( 'Popup', 'popup-maker' ),
				// 'user_id'  => __( 'User ID', 'popup-maker' ),
				'created'  => _x( 'Subscribed On', 'column name', 'popup-maker' ),
			]
		);
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
		return apply_filters(
			'pum_subscribers_table_columns',
			[
				'email'    => 'email',
				'fname'    => 'fname',
				'lname'    => 'lname',
				'popup_id' => 'popup_id',
				'created'  => 'created',
			]
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'email';
	}


	/**
	 * Text displayed when no user data is available
	 */
	public function no_items() {
		esc_html_e( 'No subscribers available.', 'popup-maker' );
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
			case 'created':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[ $column_name ] ) );
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
		$label = sprintf(
			'<label class="screen-reader-text" for="subscriber_%d">%s</label>',
			$item['ID'],
			sprintf(
				/* translators: %s is the name of the subscriber. */
				__( 'Select %s', 'popup-maker' ),
				$item['name']
			)
		);

		$input = sprintf( '<input type="checkbox" name="%1$s[]" id="subscriber_%2$d" value="%2$d" />', $this->_args['singular'], $item['ID'] );

		return sprintf( '%s%s', $label, $input );
	}

	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	public function column_email( $item ) {

		$url = add_query_arg(
			[
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'page'       => isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : null,
				'subscriber' => $item['ID'],
				'_wpnonce'   => wp_create_nonce( 'pum_subscribers_table_action_nonce' ),
			],
			admin_url( 'edit.php?page=pum-subscribers&post_type=popup' )
		);

		$edit_url = add_query_arg(
			[
				'action' => 'edit',
			],
			$url
		);

		$delete_url = add_query_arg(
			[
				'action' => 'delete',
			],
			$url
		);

		// Build row actions
		$actions = [
			// 'edit'   => sprintf( '<a href="%s">Edit</a>', $edit_url ),
			'delete' => sprintf( '<a href="%s">Delete</a>', $delete_url ),
		];

		// Return the title contents
		return sprintf(
			'%1$s <span style="color:silver">(id:%2$s)</span>%3$s', /*$1%s*/
			$item['email'], /*$2%s*/
			$item['ID'], /*$3%s*/
			$this->row_actions( $actions )
		);
	}


	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	public function column_name( $item ) {
		$user_id = $item['user_id'] > 0 ? absint( $item['user_id'] ) : null;

		if ( $user_id ) {
			$url = admin_url( "user-edit.php?user_id=$user_id" );

			// Return the title contents
			return sprintf( '%s<br/><small style="color:silver">(%s: <a href="%s">#%s</a>)</small>', $item['name'], __( 'User ID', 'popup-maker' ), $url, $item['user_id'] );
		} else {
			return $item['name'];
		}
	}


	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	public function column_popup_id( $item ) {
		$popup_id = $item['popup_id'] > 0 ? absint( $item['popup_id'] ) : null;

		$popup = pum_get_popup( $popup_id );

		if ( $popup_id && pum_is_popup( $popup ) ) {
			$url = admin_url( "post.php?post={$popup_id}&action=edit" );

			// Return the title contents
			return sprintf( '%s<br/><small style="color:silver">(%s: <a href="%s">#%s</a>)</small>', $popup->post_title, __( 'ID', 'popup-maker' ), $url, $item['popup_id'] );
		} else {
			return __( 'N/A', 'popup-maker' );
		}
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
		$actions = [
			'bulk-delete' => __( 'Delete', 'popup-maker' ),
		];

		return $actions;
	}

	/**
	 * Process actions triggered by the user
	 *
	 * @since    1.0.0
	 */
	public function handle_table_actions() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : null;

		if ( ! $nonce ) {
			return;
		}

		// Detect when a bulk action is being triggered...
		$action1 = $this->current_action();

		if ( in_array( $action1, [ 'delete', 'bulk-delete' ], true ) ) {

			// verify the nonce.
			if ( ! wp_verify_nonce( $nonce, 'delete' === $action1 ? 'pum_subscribers_table_action_nonce' : 'bulk-subscribers' ) ) {
				$this->invalid_nonce_redirect();
			} else {
				$subscribers = isset( $_REQUEST['subscriber'] ) ? sanitize_key( wp_unslash( $_REQUEST['subscriber'] ) ) : [];

				if ( is_numeric( $subscribers ) ) {
					$subscribers = [ $subscribers ];
				}

				$subscribers = wp_parse_id_list( $subscribers );

				if ( $subscribers ) {
					$status = [];

					foreach ( $subscribers as $subscriber_id ) {
						$status[] = PUM_DB_Subscribers::instance()->delete( $subscriber_id );
					}

					if ( ! in_array( false, $status, true ) ) {
						wp_die(
							sprintf(
								esc_attr(
								/* translators: %d is the number of subscribers deleted. */
								_n( '%d Subscriber deleted!', '%d Subscribers deleted!', count( $subscribers ), 'popup-maker' ) ),
								count( $subscribers )
							),
							esc_attr__( 'Success', 'popup-maker' ),
							[
								'response'  => 200,
								'back_link' => esc_url( admin_url( 'edit.php?page=pum-subscribers&post_type=popup' ) ),
							]
						);
					} else {
						$succeeded = count( array_filter( $status ) );
						$failed    = count( $subscribers ) - $succeeded;

						if ( count( $subscribers ) === 1 ) {
							wp_die(
								esc_html__( 'Deleting subscriber failed.', 'popup-maker' ),
								esc_html__( 'Error', 'popup-maker' ),
								[
									'response'  => 200,
									'back_link' => esc_url( admin_url( 'edit.php?page=pum-subscribers&post_type=popup' ) ),
								]
							);
						} else {
							wp_die(
								esc_html(
									sprintf(
										/* translators: %1$d is the number of subscribers deleted, %2$d is the number of subscribers that failed to delete. */
										__( '%1$d Subscribers deleted, %2$d failed', 'popup-maker' ),
										$succeeded, $failed
									)
								),
								esc_html__( 'Error', 'popup-maker' ),
								[
									'response'  => 200,
									'back_link' => esc_url( admin_url( 'edit.php?page=pum-subscribers&post_type=popup' ) ),
								]
							);
						}
					}
				}

				wp_die(
					esc_html__( 'Uh oh, the subscribers was not deleted successfully!', 'popup-maker' ),
					esc_html__( 'Error', 'popup-maker' ),
					[
						'response'  => 200,
						'back_link' => esc_url( admin_url( 'edit.php?page=pum-subscribers&post_type=popup' ) ),
					]
				);

				exit;
			}
		}
	}

	/**
	 * Die when the nonce check fails.
	 */
	public function invalid_nonce_redirect() {
		wp_die(
			esc_html__( 'Invalid Nonce', 'popup-maker' ),
			esc_html__( 'Error', 'popup-maker' ),
			[
				'response'  => 403,
				'back_link' => esc_url( admin_url( 'edit.php?page=pum-subscribers&post_type=popup' ) ),
			]
		);
	}
}
