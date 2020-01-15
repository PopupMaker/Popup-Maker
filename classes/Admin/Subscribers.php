<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Subscribers
 */
class PUM_Admin_Subscribers {

	/**
	 *
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'after_page_registration' ), 11 );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_option' ), 10, 3 );
	}

	/**
	 * Render settings page with tabs.
	 */
	public static function page() {
		self::list_table()->prepare_items(); ?>

		<div class="wrap">
			<h1><?php _e( 'Subscribers', 'popup-maker' ); ?></h1>
			<div id="pum-subscribers">
				<div id="pum-subscribers-post-body">
					<form id="pum-subscribers-list-form" method="get">
						<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"/>
						<input type="hidden" name="post_type" value="<?php echo esc_attr( $_REQUEST['post_type'] ); ?>"/>
						<?php
						self::list_table()->search_box( __( 'Find', 'popup-maker' ), 'pum-subscriber-find' );
						self::list_table()->display();
						?>
					</form>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * @return PUM_Admin_Subscribers_Table
	 */
	public static function list_table() {
		static $list_table;

		if ( ! isset( $list_table ) ) {
			$list_table = new PUM_Admin_Subscribers_Table();
		}

		return $list_table;
	}

	public static function after_page_registration() {
		add_action( 'load-' . PUM_Admin_Pages::$pages['subscribers'], array( 'PUM_Admin_Subscribers', 'load_user_list_table_screen_options' ) );
	}

	public static function load_user_list_table_screen_options() {
		add_screen_option( 'per_page', array(
			'label'   => __( 'Subscribers Per Page', 'popup-maker' ),
			'default' => 20,
			'option'  => 'pum_subscribers_per_page',
		) );

		/*
		 * Instantiate the User List Table. Creating an instance here will allow the core WP_List_Table class to automatically
		 * load the table columns in the screen options panel
		 */
		self::list_table();
	}

	/**
	 * Force WP to save the option.
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function set_option( $status, $option, $value ) {

		if ( 'pum_subscribers_per_page' == $option ) {
			return $value;
		}

		return $status;

	}
}

