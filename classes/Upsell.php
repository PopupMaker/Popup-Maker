<?php
/**
 * Class for Upsell
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

/**
 * Handles displaying promotional text throughout plugin UI
 */
class PUM_Upsell {

	/**
	 * Hooks any needed methods
	 */
	public static function init() {
		add_filter( 'views_edit-popup', [ __CLASS__, 'addon_tabs' ], 10, 1 );
		add_filter( 'views_edit-popup_theme', [ __CLASS__, 'addon_tabs' ], 10, 1 );
		add_filter( 'pum_popup_settings_fields', [ __CLASS__, 'popup_promotional_fields' ] );
		add_filter( 'pum_theme_settings_fields', [ __CLASS__, 'theme_promotional_fields' ] );
		add_action( 'in_admin_header', [ __CLASS__, 'notice_bar_display' ] );
	}

	/**
	 * Adds a small notice bar in PM admin areas when not using any extensions
	 *
	 * @since 1.14.0
	 */
	public static function notice_bar_display() {
		if ( pum_is_all_popups_page() && 0 === count( pum_enabled_extensions() ) ) {
			$message = sprintf(
				/* translators: %s - Wraps ending in link to pricing page. */
				esc_html__( 'You are using the free version of Popup Maker. To get even more value, consider %1$supgrading to our premium plans%2$s.', 'popup-maker' ),
				'<a href="https://wppopupmaker.com/pricing/?utm_source=upsell-notice-bar&utm_medium=text-link&utm_campaign=upsell" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
			<div class="pum-notice-bar-wrapper">
				<div class="pum-notice-bar">
					<span class="pum-notice-bar-message">
						<?php
						echo wp_kses(
							$message,
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						);
						?>
					</span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Adds messages throughout Popup Settings UI
	 *
	 * @param array $tabs The tabs/fields for popup settings.
	 * @return array
	 */
	public static function popup_promotional_fields( $tabs = [] ) {
		if ( ! pum_extension_enabled( 'forced-interaction' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Want to disable the close button? Check out <a href="%s" target="_blank">Forced Interaction</a>!', 'popup-maker' ), 'https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=upsell&utm_content=close-button-settings' );

			$tabs['close']['button']['fi_promotion'] = $tabs['close']['forms']['fi_promotion'] = $tabs['close']['alternate_methods']['fi_promotion'] = [
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-forced-interaction.png' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			];
		}

		if ( ! pum_extension_enabled( 'advanced-targeting-conditions' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Need more <a href="%s" target="_blank">advanced targeting</a> options?', 'popup-maker' ), 'https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor' );

			$tabs['targeting']['main']['atc_promotion'] = [
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/logo.png' ) . '" height="28" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			];
		}

		return $tabs;
	}

	/**
	 * Adds messages throughout Popup Theme UI
	 *
	 * @param array $tabs The tabs/fields for popup theme.
	 * @return array
	 */
	public static function theme_promotional_fields( $tabs = [] ) {

		if ( ! pum_extension_enabled( 'advanced-theme-builder' ) && ! class_exists( 'PUM_ATB' ) ) {
			foreach ( [ 'overlay', 'container', 'close' ] as $tab ) {
				/* translators: %s url to product page. */
				$message = __( 'Want to use <a href="%s" target="_blank">background images</a>?', 'popup-maker' );

				$tabs[ $tab ]['background']['atc_promotion'] = [
					'type'     => 'html',
					'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-advanted-theme-builder.png' ) . '" height="28" />' . sprintf( $message, 'https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings' ),
					'priority' => 999,
					'class'    => 'pum-upgrade-tip',
				];
			}
		}

		return $tabs;
	}

	/**
	 * When the Popup or Popup Theme list table loads, call the function to view our tabs.
	 *
	 * @since 1.8.0
	 * @param array $views An array of available list table views.
	 * @return mixed
	 */
	public static function addon_tabs( $views ) {
		self::display_addon_tabs();

		return $views;
	}

	/**
	 * Displays the tabs for 'Popups', 'Popup Themes' and 'Extensions and Integrations'
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
		<nav class="nav-tab-wrapper">
			<?php
			$tabs = [
				'popups'       => [
					'name' => esc_html( $popup_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup' ),
				],
				'themes'       => [
					'name' => esc_html( $theme_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup_theme' ),
				],
				'integrations' => [
					'name' => esc_html__( 'Upgrade', 'popup-maker' ),
					'url'  => admin_url( 'edit.php?post_type=popup&page=pum-extensions&view=integrations' ),
				],
			];

			$tabs = apply_filters( 'pum_add_ons_tabs', $tabs );

			$active_tab = false;

			// Calculate which tab is currently active.
			if ( isset( $_GET['page'] ) && 'pum-extensions' === $_GET['page'] ) {
				$active_tab = 'integrations';
			} elseif ( ! isset( $_GET['page'] ) && isset( $_GET['post_type'] ) ) {
				switch ( $_GET['post_type'] ) {
					case 'popup':
						$active_tab = 'popups';
						break;
					case 'popup_theme':
						$active_tab = 'themes';
						break;
				}
			}

			// Add each tab, marking the current one as active.
			foreach ( $tabs as $tab_id => $tab ) {
				$active = $active_tab === $tab_id ? ' nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( $tab['url'] ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>">
					<?php echo $tab['name']; ?>
				</a>
				<?php
			}
			?>

			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup' ) ); ?>" class="page-title-action">
				<?php echo esc_html( $popup_labels['add_new_item'] ); ?>
			</a>

			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup_theme' ) ); ?>" class="page-title-action">
				<?php echo esc_html( $theme_labels['add_new_item'] ); ?>
			</a>
		</nav>
		<?php
	}

}
