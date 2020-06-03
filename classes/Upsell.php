<?php

class PUM_Upsell {


	public static function init() {
		add_filter( 'views_edit-popup', array( __CLASS__, 'addon_tabs' ), 10, 1 );
		add_filter( 'views_edit-popup_theme', array( __CLASS__, 'addon_tabs' ), 10, 1 );
		add_filter( 'pum_popup_settings_fields', array( __CLASS__, 'popup_promotional_fields' ) );
		add_filter( 'pum_theme_settings_fields', array( __CLASS__, 'theme_promotional_fields' ) );
	}

	/**
	 * @param array $tabs
	 *
	 * @return array
	 */
	public static function popup_promotional_fields( $tabs = array() ) {
		if ( ! pum_extension_enabled( 'forced-interaction' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Want to disable the close button? Check out <a href="%s" target="_blank">Forced Interaction</a>!', 'popup-maker' ), 'https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings' );

			$tabs['close']['button']['fi_promotion'] = $tabs['close']['forms']['fi_promotion'] = $tabs['close']['alternate_methods']['fi_promotion'] = array(
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-forced-interaction.png' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			);
		}

		if ( ! pum_extension_enabled( 'advanced-targeting-conditions' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Need more <a href="%s" target="_blank">advanced targeting</a> options?', 'popup-maker' ), 'https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=Upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor' );

			$tabs['targeting']['main']['atc_promotion'] = array(
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/logo.png' ) . '" height="28" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			);
		}

		return $tabs;
	}

	/**
	 * @param array $tabs
	 *
	 * @return array
	 */
	public static function theme_promotional_fields( $tabs = array() ) {

		if ( ! pum_extension_enabled( 'advanced-theme-builder' ) && ! class_exists( 'PUM_ATB' ) ) {
			foreach ( array( 'overlay', 'container', 'close' ) as $tab ) {
				/* translators: %s url to product page. */
				$message = __( 'Want to use <a href="%s" target="_blank">background images</a>?', 'popup-maker' );

				$tabs[ $tab ]['background']['atc_promotion'] = array(
					'type'     => 'html',
					'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-advanted-theme-builder.png' ) . '" height="28" />' . sprintf( $message, 'https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=Upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings' ),
					'priority' => 999,
					'class'    => 'pum-upgrade-tip',
				);
			}
		}

		return $tabs;
	}

	/**
	 * When the Popup or Popup Theme list table loads, call the function to view our tabs.
	 *
	 * @since 1.8.0
	 *
	 * @param $views
	 *
	 * @return mixed
	 */
	public static function addon_tabs( $views ) {
		self::display_addon_tabs();

		return $views;
	}

	/**
	 * Displays the tabs for 'Popups', 'Popup Themes' and 'Addons and Integrations'
	 *
	 * @since 1.8.0
	 */
	public static function display_addon_tabs() {

		$popup_labels = PUM_Types::post_type_labels( __( 'Popup', 'popup-maker' ), __( 'Popups', 'popup-maker' ) );
		$theme_labels = PUM_Types::post_type_labels( __( 'Popup Theme', 'popup-maker' ), __( 'Popup Themes', 'popup-maker' ) );

		?>


        <style>
            .wrap h1.wp-heading-inline + a.page-title-action {
                display: none;
            }

            .edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action, .edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action, .popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
                top: 7px;
                margin-left: 5px
            }

            @media only screen and (min-width: 0px) and (max-width: 783px) {
                .edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action, .edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action, .popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
                    display: none !important
                }
            }
        </style>


        <h2 class="nav-tab-wrapper">
			<?php
			$tabs = array(
				'popups'       => array(
					'name' => $popup_labels['name'],
					'url'  => admin_url( 'edit.php?post_type=popup' ),
				),
				'themes'       => array(
					'name' => $theme_labels['name'],
					'url'  => admin_url( 'edit.php?post_type=popup_theme' ),
				),
				'integrations' => array(
					'name' => __( 'Addons and Integrations', 'popup-maker' ) . PUM_Admin_Extend::append_unseen_count(),
					'url'  => admin_url( 'edit.php?post_type=popup&page=pum-extensions&view=integrations' ),
				),
			);

			$tabs = apply_filters( 'pum_add_ons_tabs', $tabs );

			$active_tab = false;

			if ( isset( $_GET['page'] ) && $_GET['page'] === 'pum-extensions' ) {
				$active_tab = 'integrations';
			} else if ( ! isset( $_GET['page'] ) && isset( $_GET['post_type'] ) ) {
				switch ( $_GET['post_type'] ) {
					case 'popup':
						$active_tab = 'popups';
						break;
					case 'popup_theme':
						$active_tab = 'themes';
						break;
				}
			}

			foreach ( $tabs as $tab_id => $tab ) {

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab['url'] ) . '" class="nav-tab' . $active . '">';
				echo $tab['name'];
				echo '</a>';
			}
			?>

            <a href="<?php echo admin_url( 'post-new.php?post_type=popup' ); ?>" class="page-title-action">
				<?php echo $popup_labels['add_new_item']; ?>
            </a>

            <a href="<?php echo admin_url( 'post-new.php?post_type=popup_theme' ); ?>" class="page-title-action">
				<?php echo $theme_labels['add_new_item']; ?>
            </a>
        </h2>
		<?php
	}

}
