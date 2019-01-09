<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Extend
 */
class PUM_Admin_Extend {

	/**
	 * Support Page
	 *
	 * Renders the support page contents.
	 */
	public static function page() {
		// Set a new campaign for tracking purposes
		$campaign   = isset( $_GET['view'] ) && strtolower( $_GET['view'] ) === 'integrations' ? 'PUMIntegrationsPage' : 'PUMExtensionsPage';
		$sub_tabs   = self::subtabs();
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $sub_tabs ) ? $_GET['tab'] : 'extensions';

		?>
		<div class="wrap">
		<hr class="wp-header-end">
		<?php PUM_Upsell::display_addon_tabs(); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<h1 class="section-heading">
						<?php _e( 'Extensions & Integrations for Popup Maker', 'popup-maker' ) ?>
						&nbsp;&nbsp;<a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=<?php echo $campaign; ?>&utm_content=browse-all" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>
					</h1>

					<div class="pum-add-ons-view-wrapper">
						<?php self::render_subtabs(); ?>
					</div>

					<br class="clear" />

					<div class="pum-tabs-container">
						<?php if ( 'forms' === $active_tab ) {
							self::render_forms_list();
						} elseif ( 'other' === $active_tab ) {

						} else { ?>

							<?php self::render_extension_list(); ?>

							<br class="clear" />

							<a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=<?php echo $campaign; ?>&utm_content=browse-all-bottom" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>

							<br class="clear" /> <br class="clear" /> <br class="clear" />
							<hr class="clear" />
							<br class="clear" />

							<?php
						} ?>
					</div>

				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Return array of subtabs.
	 *
	 * @return mixed
	 */
	public static function subtabs() {
		return apply_filters( 'pum_extend_subtabs', array(
			'extensions' => __( 'Premium Extensions', 'popup-maker' ),
			'forms'      => __( 'Forms', 'popup-maker' ),
			'other'      => __( 'Other', 'popup-maker' ),
		) );
	}

	/**
	 * Return array of form plugins that integrate well with Popup Maker
	 *
	 * @return array
	 */
	public static function form_plugins() {
		$form_plugins = array(
			array(
				'slug' => 'gravity-forms',
				'name' => __( 'Gravity Forms', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/grab/gravity-forms',
				'desc' => __( 'Gravity Forms is one of the most popular form building plugins.', 'popup-maker' ),
			),
			array(
				'slug' => 'contact-form-7',
				'name' => __( 'Contact Form 7', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/grab/contact-form-7',
				'desc' => __( 'CF7 is one of the most downloaded plugins on the WordPress repo. Make simple forms with ease and plenty of free addons available.', 'popup-maker' ),
			),
			array(
				'slug' => 'quiz-survey-master',
				'name' => __( 'Quiz & Survey Master', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/grab/quiz-survey-master',
				'desc' => __( 'If you need more from your forms data look no further, QSM is all about the statistics & collective data, something other form plugins neglect.', 'popup-maker' ),
			),
		);

		shuffle( $form_plugins );

		array_unshift( $form_plugins, array(
			'slug' => 'ninja-forms',
			'name' => __( 'Ninja Forms', 'popup-maker' ),
			'url'  => 'https://wppopupmaker.com/grab/ninja-forms',
			'desc' => __( 'Ninja Forms has fast become the most extensible form plugin available. Build super custom forms and integrate with your favorite services.', 'popup-maker' ),
		) );

		return apply_filters( 'pumextend_form_plugins', $form_plugins );
	}

	/**
	 * Return array of other plugins that integrate with Popup Maker
	 *
	 * @return array
	 */
	public static function other_plugins() {
		return array();
	}

	/**
	 * Return array of Popup Maker extensions.
	 *
	 * @return array|mixed|object
	 */
	public static function available_extensions() {
		$json_data = file_get_contents( Popup_Maker::$DIR . 'includes/extension-list.json' );

		return json_decode( $json_data, true );
	}

	/**
	 * Render extension page subtabs.
	 */
	public static function render_subtabs() {
		$sub_tabs      = self::subtabs();
		$active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $sub_tabs ) ? $_GET['tab'] : 'extensions';
		$form_plugins  = self::form_plugins();
		$extensions    = self::available_extensions();
		$other_plugins = self::other_plugins(); ?>

		<ul class="subsubsub"><?php

			$total_tabs = count( $sub_tabs );
			$i          = 1;

			foreach ( $sub_tabs as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id,
				) );

				$active = $active_tab == $tab_id ? 'current' : '';

				$count = null;

				switch ( $tab_id ) {
					case 'extensions':
						$count = ( count( $extensions ) - 1 ) . '+';
						break;
					case 'forms':
						$count = count( $form_plugins );
						break;
					case 'other';
						$count = count( $other_plugins );
						break;
				}

				if ( ! $count && empty( $active ) ) {
					continue;
				}

				echo '<li class="' . $tab_id . '">';
				echo '<a href="' . esc_url( $tab_url ) . '" class="' . $active . '">';
				echo esc_html( $tab_name );
				echo '</a>';


				if ( isset( $count ) ) {
					echo ' <span class="count">(' . $count . ')</span>';
				}

				echo '</li>';

				if ( $i !== $total_tabs ) {
					echo ' | ';
				}

				$i ++;
			}
			?>
		</ul>

		<?php
	}

	/**
	 * Render extension tab extensions list.
	 */
	public static function render_extension_list() {
		// Set a new campaign for tracking purposes
		$campaign   = isset( $_GET['view'] ) && strtolower( $_GET['view'] ) === 'integrations' ? 'PUMIntegrationsPage' : 'PUMExtensionsPage';
		$extensions = self::available_extensions();

		?>

		<h4><?php _e( 'These extensions add extra functionality to your popups.', 'popup-maker' ); ?></h4>

		<ul class="extensions-available">
			<?php
			//		$plugins           = get_plugins();
			//		$installed_plugins = array();
			//		foreach ( $plugins as $key => $plugin ) {
			//			$is_active                          = is_plugin_active( $key );
			//			$installed_plugin                   = array(
			//				'is_active' => $is_active,
			//			);
			//			$installerUrl                       = add_query_arg( array(
			//				'action' => 'activate',
			//				'plugin' => $key,
			//				'em'     => 1,
			//			), network_admin_url( 'plugins.php' ) //admin_url('update.php')
			//			);
			//			$installed_plugin["activation_url"] = $is_active ? "" : wp_nonce_url( $installerUrl, 'activate-plugin_' . $key );
			//
			//
			//			$installerUrl                         = add_query_arg( array(
			//				'action' => 'deactivate',
			//				'plugin' => $key,
			//				'em'     => 1,
			//			), network_admin_url( 'plugins.php' ) //admin_url('update.php')
			//			);
			//			$installed_plugin["deactivation_url"] = ! $is_active ? "" : wp_nonce_url( $installerUrl, 'deactivate-plugin_' . $key );
			//			$installed_plugins[ $key ]            = $installed_plugin;
			//		}

			$existing_extension_images = self::extensions_with_local_image();

			if ( ! empty( $extensions ) ) {

				shuffle( $extensions );

				foreach ( $extensions as $key => $ext ) {
					unset( $extensions[ $key ] );
					$extensions[ $ext['slug'] ] = $ext;
				}

				// Set core bundle to be first item listed.
				// TODO Replace this with a full width banner instead.
				$extensions = array_merge( array( 'core-extensions-bundle' => $extensions['core-extensions-bundle'] ), $extensions );

				$i = 0;

				foreach ( $extensions as $extension ) : ?>
					<li class="available-extension-inner <?php esc_attr_e( $extension['slug'] ); ?>">
						<h3>
							<a target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-title-<?php echo $i; ?>&utm_campaign=<?php echo $campaign; ?>&utm_content=<?php esc_attr_e( urlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>">
								<?php esc_html_e( $extension['name'] ) ?>
							</a>
						</h3>
						<?php $image = in_array( $extension['slug'], $existing_extension_images ) ? POPMAKE_URL . '/assets/images/extensions/' . $extension['slug'] . '.png' : $extension['image']; ?>
						<img class="extension-thumbnail" src="<?php esc_attr_e( $image ) ?>" />

						<p><?php esc_html_e( $extension['excerpt'] ); ?></p>

						<span class="action-links">
				            <a class="button" target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-button-<?php echo $i; ?>&utm_campaign=<?php echo $campaign; ?>&utm_content=<?php esc_attr_e( urlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>"><?php _e( 'Get this Extension', 'popup-maker' ); ?></a>
						</span>

						<!--					--><?php
						//
						//					if ( ! empty( $extension->download_link ) && ! isset( $installed_plugins[ $extension->slug . '/' . $extension->slug . '.php' ] ) ) {
						//						$installerUrl = add_query_arg( array(
						//							'action'            => 'install-plugin',
						//							'plugin'            => $extension->slug,
						//							'edd_sample_plugin' => 1,
						//						), network_admin_url( 'update.php' ) //admin_url('update.php')
						//						);
						//						$installerUrl = wp_nonce_url( $installerUrl, 'install-plugin_' . $extension->slug ) ?>
						<!--						<span class="action-links">-->
						<!--							--><?php
						//							printf( '<a class="button install" href="%s">%s</a>', esc_attr( $installerUrl ), __( 'Install' ) ); ?>
						<!--						</span>-->
						<!--						--><?php
						//					} elseif ( isset( $installed_plugins[ $extension->slug . '/' . $extension->slug . '.php' ]['is_active'] ) ) {
						//						?>
						<!--						<span class="action-links">-->
						<!--						--><?php
						//						if ( ! $installed_plugins[ $extension->slug . '/' . $extension->slug . '.php' ]['is_active'] ) {
						//							printf( '<a class="button install" href="%s">%s</a>', esc_attr( $installed_plugins[ $extension->slug . '/' . $extension->slug . '.php' ]["activation_url"] ), __( 'Activate' ) );
						//
						//						} else {
						//							printf( '<a class="button install" href="%s">%s</a>', esc_attr( $installed_plugins[ $extension->slug . '/' . $extension->slug . '.php' ]["deactivation_url"] ), __( 'Deactivate' ) );
						//						} ?>
						<!--						</span>-->
						<!--						--><?php
						//					} else {
						//						?>
						<!--						<span class="action-links"><a class="button" target="_blank" href="--><?php //esc_attr_e( $extension->homepage ); ?><!--">--><?php //_e( 'Get It Now' ); ?><!--</a></span>-->
						<!--						--><?php
						//					}
						//					?>

					</li>
					<?php
					$i ++;
				endforeach;
			} ?>
		</ul>

		<?php
	}

	/**
	 * Render extensions tab form list.
	 */
	public static function render_forms_list() {
		// Set a new campaign for tracking purposes
		$campaign = isset( $_GET['view'] ) && strtolower( $_GET['view'] ) === 'integrations' ? 'PUMFormIntegrationsPage' : 'PUMExtensionsFormPage';

		$form_plugins = self::form_plugins();

		?>

		<h4><?php _e( 'These form plugins work in our popups out of the box.', 'popup-maker' ); ?></h4>

		<ul class="extensions-available">
			<?php

			$i = 1;

			foreach ( $form_plugins as $plugin ) : ?>
			<li class="available-extension-inner <?php esc_attr_e( $plugin['slug'] ); ?>">
				<h3>
					<a target="_blank" href="<?php esc_attr_e( $plugin['url'] ); ?>?utm_campaign=<?php echo $campaign; ?>&utm_source=plugin-extend-page&utm_medium=form-banner&utm_content=<?php echo $plugin['slug']; ?>">
						<?php esc_html_e( $plugin['name'] ) ?>
					</a>
				</h3>
				<img class="extension-thumbnail" src="<?php esc_attr_e( POPMAKE_URL . '/assets/images/plugins/' . $plugin['slug'] . '.png' ) ?>" />

				<p><?php esc_html_e( $plugin['desc'] ); ?></p> <span class="action-links">
	                                <a class="button" target="_blank" href="<?php echo esc_url( $plugin['url'] ); ?>"><?php _e( 'Check it out', 'popup-maker' ); ?></a>
	                            </span>
				</li><?php
				$i ++;
			endforeach; ?>
		</ul>


		<?php
	}

	/**
	 * @return array
	 */
	public static function extensions_with_local_image() {
		return apply_filters( 'pum_extensions_with_local_image', array(
			'core-extensions-bundle',
			'aweber-integration',
			'mailchimp-integration',
			'remote-content',
			'scroll-triggered-popups',
			'popup-analytics',
			'forced-interaction',
			'age-verification-modals',
			'advanced-theme-builder',
			'exit-intent-popups',
			'ajax-login-modals',
			'advanced-targeting-conditions',
			'secure-idle-user-logout',
			'terms-conditions-popups',
		) );
	}

}