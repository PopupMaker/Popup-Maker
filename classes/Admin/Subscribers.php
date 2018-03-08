<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
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
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
						<?php
						self::list_table()->search_box( __( 'Find', 'popup-maker' ), 'pum-subscriber-find');
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
		add_action( 'load-'.PUM_Admin_Pages::$pages[ 'subscribers' ], array( 'PUM_Admin_Subscribers', 'load_user_list_table_screen_options' ) );
	}
	
	public static function load_user_list_table_screen_options() {
		$arguments = array(
			'label'		=>	__( 'Subscribers Per Page', 'popup-maker' ),
			'default'	=>	20,
			'option'	=>	'subscribers_per_page'
		);

		add_screen_option( 'per_page', $arguments );

		/*
		 * Instantiate the User List Table. Creating an instance here will allow the core WP_List_Table class to automatically
		 * load the table columns in the screen options panel
		 */
		self::list_table();
	}
}

