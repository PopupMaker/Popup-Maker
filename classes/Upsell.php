<?php

class PUM_Upsell {


	public static function init() {
		add_filter( 'views_edit-popup', array( __CLASS__, 'addon_tabs' ), 10, 1 );
		add_filter( 'views_edit-popup_theme', array( __CLASS__, 'addon_tabs' ), 10, 1 );

		add_filter( 'pum_popup_close_settings_fields', array( __CLASS__, 'fi_promotion1' ) );
		add_filter( 'pum_popup_close_settings_fields', array( __CLASS__, 'fi_promotion2' ) );
		add_filter( 'pum_popup_targeting_settings_fields', array( __CLASS__, 'atc_promotion' ) );
		add_filter( 'pum_theme_overlay_settings_fields', array( __CLASS__, 'atb_promotion' ) );
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
					'name' => __( 'Addons and Integrations', 'popup-maker' ),
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
				echo esc_html( $tab['name'] );
				echo '</a>';
			}
			?>

			<style>
				.edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action,.edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action,.popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
					top: 7px;
					margin-left: 5px
				}

				@media only screen and (min-width: 0px) and (max-width:783px) {
					.edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action,.edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action,.popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
						display:none!important
					}
				}
			</style>

			<a href="<?php echo admin_url( 'post-new.php?post_type=popup' ); ?>" class="page-title-action">
				<?php echo $popup_labels['add_new_item']; ?>
			</a>

			<a href="<?php echo admin_url( 'post-new.php?post_type=popup_theme' ); ?>" class="page-title-action">
				<?php echo $theme_labels['add_new_item']; ?>
			</a>
		</h2>
		<br />
		<?php
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function fi_promotion1( $fields ) {
		if ( pum_extension_enabled( 'forced-interaction' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf( _x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$key = key( $fields );

		$fields[ $key ]['fi_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 30,
		);

		return $fields;
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function fi_promotion2( $fields ) {
		if ( pum_extension_enabled( 'forced-interaction' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf( _x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$key = key( $fields );

		$fields[ $key ]['fi_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 999,
		);

		return $fields;
	}

	public static function atc_promotion( $fields ) {
		if ( pum_extension_enabled( 'advanced-targeting-conditions' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/logo.png" height="28" />
			<?php printf( __( 'Need more %sadvanced targeting%s options?', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=Upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();
		$key  = key( $fields );


		$fields[ $key ]['atc_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 30,
		);

		return $fields;
	}

	public static function atb_promotion( $fields ) {
		if ( pum_extension_enabled( 'advanced-theme-builder' ) ) {
			return $fields;
		}

		$tab    = 'general';
		$subtab = 'background';
		$pri    = 0;

		switch ( current_filter() ) {
			case 'pum_theme_overlay_settings_fields':
				$tab = 'overlay';
				break;
			case 'pum_theme_container_settings_fields':
				$tab = 'container';
				break;
			case 'pum_theme_title_settings_fields':
				$tab = 'title';
				break;
			case 'pum_theme_content_settings_fields':
				$tab = 'content';
				break;
			case 'pum_theme_close_settings_fields':
				$tab = 'close';
				break;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-advanted-theme-builder.png" height="28" />
			<?php printf( __( 'Want to use %sbackground images%s?', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=Upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$fields[ $tab ][ $subtab ]['atb_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => $pri,
		);

		return $fields;
	}

}