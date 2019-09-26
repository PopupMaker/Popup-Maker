<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Modules_Menu
 *
 * This class handles the menu editor fields & adds popup classes to menu items.
 */
class PUM_Modules_Menu {

	/**
	 * Initializes this module.
	 */
	public static function init() {
		add_filter( 'popmake_settings_misc', array( __CLASS__, 'settings' ) );

		if ( PUM_Utils_Options::get( 'disabled_menu_editor', false ) ) {
			return;
		}

		// Merge Menu Item Options
		add_filter( 'wp_setup_nav_menu_item', array( __CLASS__, 'merge_item_data' ) );
		// Admin Menu Editor
		add_filter( 'wp_edit_nav_menu_walker', array( __CLASS__, 'nav_menu_walker' ), 999999999 );
		// Admin Menu Editor Fields.
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'fields' ), 10, 4 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_nav-menus_columns', array( __CLASS__, 'nav_menu_columns' ), 11 );
	}

	public static function settings( $settings ) {
		return array_merge( $settings, array(
			'disabled_menu_editor' => array(
				'id'   => 'disabled_menu_editor',
				'name' => __( 'Disable Popups Menu Editor', 'popup-maker' ),
				'desc' => sprintf(
					_x( 'Use this if there is a conflict with your theme or another plugin in the nav menu editor. %sSee Details%s', '%s represent opening and closing link html', 'popup-maker' ),
					'<a href="https://docs.wppopupmaker.com/article/297-popup-maker-is-overwriting-my-menu-editor-functions-how-can-i-fix-this" target="_blank">',
					'</a>'
				),
				'type' => 'checkbox',
			),

		) );
	}

	public static function nav_menu_columns( $columns = array() ) {
		$columns['popup_id'] = __( 'Popup', 'popup-maker' );

		return $columns;
	}

	/**
	 * Override the Admin Menu Walker
	 *
	 * @param $walker
	 *
	 * @return string
	 */
	public static function nav_menu_walker( $walker ) {
		global $wp_version;

		if ( doing_filter( 'plugins_loaded' ) ) {
			return $walker;
		}

		if ( $walker == 'Walker_Nav_Menu_Edit_Custom_Fields' ) {
			return $walker;
		}

		if ( ! class_exists( 'Walker_Nav_Menu_Edit_Custom_Fields' ) ) {
			if ( version_compare( $wp_version, '3.6', '>=' ) ) {
				require_once POPMAKE_DIR . '/includes/modules/menus/class-nav-menu-edit-custom-fields.php';
			} else {
				require_once POPMAKE_DIR . '/includes/modules/menus/class-nav-menu-edit-custom-fields-deprecated.php';
			}
		}

		return 'Walker_Nav_Menu_Edit_Custom_Fields';
	}

	/**
	 * Merge Item data into the $item object.
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public static function merge_item_data( $item ) {

		if ( ! is_object( $item ) || ! isset( $item->ID ) || $item->ID <= 0 ) {
			return $item;
		}

		// Merge Rules.
		foreach ( PUM_Modules_Menu::get_item_options( $item->ID ) as $key => $value ) {
			$item->$key = $value;
		}

		if ( is_admin() ) {
			return $item;
		}

		if ( isset( $item->popup_id ) ) {
			$item->classes[] = 'popmake-' . $item->popup_id;
		}

		return $item;
	}

	/**
	 * @param int $item_id
	 *
	 * @return array
	 */
	public static function get_item_options( $item_id = 0 ) {

		// Fetch all rules for this menu item.
		$item_options = get_post_meta( $item_id, '_pum_nav_item_options', true );

		return PUM_Modules_Menu::parse_item_options( $item_options );
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	public static function parse_item_options( $options = array() ) {

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return wp_parse_args( $options, array(
			'popup_id' => null,
		) );
	}

	/**
	 * Adds custom fields to the menu item editor.
	 *
	 * @param $item_id
	 * @param $item
	 * @param $depth
	 * @param $args
	 */
	public static function fields( $item_id, $item, $depth, $args ) {

		wp_nonce_field( 'pum-menu-editor-nonce', 'pum-menu-editor-nonce' ); ?>

		<p class="field-popup_id  description  description-wide">

			<label for="edit-menu-item-popup_id-<?php echo $item->ID; ?>">
				<?php _e( 'Trigger a Popup', 'popup-maker' ); ?><br />

				<select name="menu-item-pum[<?php echo $item->ID; ?>][popup_id]" id="edit-menu-item-popup_id-<?php echo $item->ID; ?>" class="widefat  edit-menu-item-popup_id">
					<option value=""></option>
					<?php foreach ( PUM_Modules_Menu::popup_list() as $option => $label ) : ?>
						<option value="<?php echo $option; ?>" <?php selected( $option, $item->popup_id ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<span class="description"><?php _e( 'Choose a popup to trigger when this item is clicked.', 'popup-maker' ); ?></span>
			</label>

		</p>

		<?php
	}

	/**
	 * Returns a list of popups for a dropdown.
	 *
	 * @return array
	 */
	public static function popup_list() {

		static $popup_list;

		if ( ! isset( $popup_list ) ) {

			$popup_list = array();

			$popups = pum_get_all_popups();

			if ( ! empty( $popups ) ) {
				foreach ( $popups as $popup ) {
					$popup_list[ $popup->ID ] = $popup->post_title;
				}
			}

		}

		return $popup_list;
	}

	/**
	 * Processes the saving of menu items.
	 *
	 * @param $menu_id
	 * @param $item_id
	 */
	public static function save( $menu_id, $item_id ) {

		$popups = PUM_Modules_Menu::popup_list();

		$allowed_popups = wp_parse_id_list( array_keys( $popups ) );

		if ( ! isset( $_POST['pum-menu-editor-nonce'] ) || ! wp_verify_nonce( $_POST['pum-menu-editor-nonce'], 'pum-menu-editor-nonce' ) ) {
			return;
		}

		/**
		 * Return early if there are no settings.
		 */
		if ( empty( $_POST['menu-item-pum'][ $item_id ] ) ) {
			delete_post_meta( $item_id, '_pum_nav_item_options' );

			return;
		}

		/**
		 * Parse options array for valid keys.
		 */
		$item_options = PUM_Modules_Menu::parse_item_options( $_POST['menu-item-pum'][ $item_id ] );

		/**
		 * Check for invalid values.
		 */
		if ( ! in_array( $item_options['popup_id'], $allowed_popups ) || $item_options['popup_id'] <= 0 ) {
			unset( $item_options['popup_id'] );
		}

		/**
		 * Remove empty options to save space.
		 */
		$item_options = array_filter( $item_options );

		/**
		 * Save options or delete if empty.
		 */
		if ( ! empty( $item_options ) ) {
			update_post_meta( $item_id, '_pum_nav_item_options', $item_options );
		} else {
			delete_post_meta( $item_id, '_pum_nav_item_options' );
		}
	}
}

PUM_Modules_Menu::init();
