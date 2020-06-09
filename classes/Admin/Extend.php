<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Extend
 */
class PUM_Admin_Extend {

	/**
	 *
	 */
	public static function append_count_to_menu_item() {
		global $submenu;

		$count = self::append_unseen_count();

		if ( empty( $count ) || ! isset( $submenu['edit.php?post_type=popup'] ) || empty( $submenu['edit.php?post_type=popup'] ) ) {
			return;
		}

		foreach ( $submenu['edit.php?post_type=popup'] as $key => $item ) {
			if ( $item[2] == 'pum-extensions' ) {
				$submenu['edit.php?post_type=popup'][ $key ][0] .= $count;
			}
		}
	}

	/**
	 * @return string|null
	 */
	public static function append_unseen_count() {
	    $is_integration_page = isset( $_GET['post_type'] ) && $_GET['post_type'] === 'popup' && isset( $_GET['page'] ) && $_GET['page'] === 'pum-extensions';
	    $active_tab    = isset( $_GET['tab'] ) ? $_GET['tab'] : 'extensions';

        if ( $is_integration_page ) {
            self::mark_extensions_viewed( $active_tab );
        }

		$count = 0;

		foreach ( self::unseen_extension_counts() as $subtab => $unseen_count ) {
			$count = $count + $unseen_count;
		}

		return 0 === $count ? null : ' <span class="update-plugins count-' . $count . '"><span class="plugin-count pum-alert-count" aria-hidden="true">' . $count . '</span></span>';

	}

	/**
	 * @return array
	 */
	protected static function unseen_extension_counts() {
		$viewed = self::viewed_extension_counts();
		$unseen = array();

		foreach ( self::actual_extension_counts() as $subtab => $count ) {
			if ( ! isset( $viewed[ $subtab ] ) ) {
				$unseen[ $subtab ] = $count;
			} else if ( $viewed[ $subtab ] < $count ) {
				$unseen[ $subtab ] = $count - $viewed[ $subtab ];
			}
		}

		return $unseen;
	}

	/**
	 * @return array|mixed|void
	 */
	protected static function viewed_extension_counts() {
		$viewed = get_option( 'pum_extend_viewed_extensions' );

		if ( false === $viewed ) {
			$viewed = array();
			update_option( 'pum_extend_viewed_extensions', array() );
		}

		return $viewed;
	}

	/**
	 * @return array
	 */
	protected static function actual_extension_counts() {
		$actual = array();

		foreach ( self::subtabs() as $subtab => $label ) {
			switch ( $subtab ) {
				case 'extensions':
					$actual[ $subtab ] = count( self::available_extensions() );
					break;
				case 'forms':
					$actual[ $subtab ] = count( self::form_plugins() );
					break;
				case 'page-builders':
					$actual[ $subtab ] = count( self::page_builder_plugins() );
					break;
				case 'other':
					$actual[ $subtab ] = count( self::other_plugins() );
					break;
			}
		}

		return $actual;
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
			'page-builders'      => __( 'Page Builders', 'popup-maker' ),
			'other'      => __( 'Other', 'popup-maker' ),
		) );
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
	 * Return array of form plugins that integrate well with Popup Maker
	 *
	 * @return array
	 */
	public static function form_plugins() {
		$form_plugins = array(
			array(
				'slug' => 'gravity-forms',
				'name' => __( 'Gravity Forms', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/gravity-forms',
				'desc' => __( 'Gravity Forms is one of the most popular form building plugins.', 'popup-maker' ),
			),
			array(
				'slug' => 'contact-form-7',
				'name' => __( 'Contact Form 7', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recoomends/contact-form-7',
				'desc' => __( 'CF7 is one of the most downloaded plugins on the WordPress repo. Make simple forms with ease and plenty of free addons available.', 'popup-maker' ),
			),
			array(
				'slug' => 'mc4wp',
				'name' => __( 'MailChimp For WordPress', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/mailchimp-for-wordpress',
				'desc' => __( 'Allowing your visitors to subscribe to your newsletter should be easy. With this plugin, it finally is.', 'popup-maker' ),
			),
			array(
				'slug' => 'caldera-forms',
				'name' => __( 'Caldera Forms', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/caldera-forms',
				'desc' => __( 'Responsive form builder for contact forms, user registration and login forms, Mailchimp, PayPal Express and more.', 'popup-maker' ),
			),
			array(
				'slug' => 'wp-forms',
				'name' => __( 'WP Forms', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/wp-forms',
				'desc' => __( 'Drag & Drop online form builder that helps you create beautiful contact forms with just a few clicks.', 'popup-maker' ),
			),
		);

		shuffle( $form_plugins );

		array_unshift( $form_plugins, array(
			'slug' => 'ninja-forms',
			'name' => __( 'Ninja Forms', 'popup-maker' ),
			'url'  => 'https://wppopupmaker.com/recommends/ninja-forms',
			'desc' => __( 'Ninja Forms has fast become the most extensible form plugin available. Build super custom forms and integrate with your favorite services.', 'popup-maker' ),
		) );

		return apply_filters( 'pum_extend_form_plugins', $form_plugins );
	}

	/**
	 * Return array of form plugins that integrate well with Popup Maker
	 *
	 * @return array
	 */
	public static function page_builder_plugins() {
		$page_builder_plugins = array(
			array(
				'slug' => 'beaver-builder',
				'name' => __( 'Beaver Builder', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/beaver-builder',
				'desc' => __( "Easily insert saved templates into your popups for a one of a kind popup design.", 'popup-maker' ),
			),
		);

		shuffle( $page_builder_plugins );

		return apply_filters( 'pum_extend_page_builder_plugins', $page_builder_plugins );
	}

	/**
	 * Return array of other plugins that integrate with Popup Maker
	 *
	 * @return array
	 */
	public static function other_plugins() {
		$other_plugins = array(
			array(
				'slug' => 'user-menus',
				'name' => __( 'User Menus', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/user-menus',
				'desc' => __( "Show/hide menu items to logged in users, logged out users or specific user roles. Display logged in user details in menu. Add a logout link to menu.", 'popup-maker' ),
			),
			array(
				'slug' => 'content-control',
				'name' => __( 'Content Control', 'popup-maker' ),
				'url'  => 'https://wppopupmaker.com/recommends/content-control',
				'desc' => __( "	Restrict content to logged in/out users or specific user roles. Restrict access to certain parts of a page/post. Control the visibility of widgets.", 'popup-maker' ),
			),
		);

		shuffle( $other_plugins );

		return apply_filters( 'pum_extend_page_other_plugins', $other_plugins );
	}

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
			<h1><?php _e( 'Extensions & Integrations for Popup Maker', 'popup-maker' ) ?></h1>
			<?php PUM_Upsell::display_addon_tabs(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        <div class="pum-add-ons-view-wrapper">
							<?php self::render_subtabs(); ?>
                        </div>

						<br class="clear" />
						<a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=<?php echo $campaign; ?>&utm_content=browse-all" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>
                        <br class="clear" />

                        <div class="pum-tabs-container">
							<?php if ( 'forms' === $active_tab ) {
								self::render_forms_list();
							} elseif ( 'page-builders' === $active_tab ) {
								self::render_page_builders_list();
							} elseif ( 'other' === $active_tab ) {
								self::render_other_list();
							} else { ?>

								<?php self::render_extension_list(); ?>

                                <br class="clear" />

                                <a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=<?php echo $campaign; ?>&utm_content=browse-all-bottom" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>

                                <br class="clear" /> <br class="clear" /> <br class="clear" />
                                <hr class="clear" /><br class="clear" />

							<?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * Render extension page subtabs.
	 */
	public static function render_subtabs() {
		$actual_counts = self::actual_extension_counts();
		$unseen_counts = self::unseen_extension_counts();
		$sub_tabs      = self::subtabs();
		$active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $sub_tabs ) ? $_GET['tab'] : 'extensions';

		?>

        <style>
            .nav-tab-wrapper .update-plugins,
            .subsubsub .update-plugins {
                display: inline-block;
                margin: 1px 0 0 2px;
                padding: 0 5px;
                min-width: 7px;
                height: 17px;
                border-radius: 11px;
                background-color: #ca4a1f;
                color: #fff;
                font-size: 9px;
                line-height: 17px;
                text-align: center;
                z-index: 26;
            }
        </style>

        <ul class="subsubsub"><?php

			$total_tabs = count( $sub_tabs );
			$i          = 1;

			foreach ( $sub_tabs as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id,
				) );

				$active = $active_tab == $tab_id ? 'current' : '';


				$unseen_count = null;
				if ( isset( $unseen_counts[ $tab_id ] ) && $unseen_counts[ $tab_id ] > 0 ) {
					$unseen_count = $unseen_counts[ $tab_id ];
				}

				$count = null;
				switch ( $tab_id ) {
					case 'extensions':
						$count = ( $actual_counts[ $tab_id ] - 1 ) . '+';
						break;
					default:
						$count = $actual_counts[ $tab_id ];
						break;
				}

				if ( ! $count && empty( $active ) ) {
					continue;
				}

				if ( $i > 1 ) {
					echo ' | ';
				}

				echo '<li class="' . $tab_id . '">';
				echo '<a href="' . esc_url( $tab_url ) . '" class="' . $active . '">';
				echo esc_html( $tab_name );
				echo '</a>';

				if ( isset( $count ) ) {
					echo ' <span class="count">(' . $count . ')</span>';
				}

				if ( isset( $unseen_count ) ) {
					echo ' <span class="update-plugins count-' . $unseen_count . '"><span class="plugin-count pum-alert-count" aria-hidden="true">' . $unseen_count . '</span></span>';
				}


				echo '</li>';

				$i ++;
			}
			?>
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
                <li class="available-extension-inner <?php echo esc_attr( $plugin['slug'] ); ?>">
                    <h3>
                        <a target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>?utm_campaign=<?php echo $campaign; ?>&utm_source=plugin-extend-page&utm_medium=form-banner&utm_content=<?php echo $plugin['slug']; ?>">
							<?php echo esc_html( $plugin['name'] ); ?>
                        </a>
                    </h3>
                    <img alt="<?php echo esc_html( $plugin['name'] ); ?>" class="extension-thumbnail" src="<?php echo esc_attr( POPMAKE_URL . '/assets/images/plugins/' . $plugin['slug'] . '.png' ) ?>" />

                    <p><?php echo esc_html( $plugin['desc'] ); ?></p>

                    <span class="action-links">
					<a class="button" target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>"><?php _e( 'Check it out', 'popup-maker' ); ?></a>
				</span>
                </li>
				<?php
				$i ++;
			endforeach; ?>
        </ul>


		<?php
	}

	/**
	 * Render extensions tab page_builder list.
	 */
	public static function render_page_builders_list() {
		// Set a new campaign for tracking purposes
		$campaign = isset( $_GET['view'] ) && strtolower( $_GET['view'] ) === 'integrations' ? 'PUMFormIntegrationsPage' : 'PUMExtensionsFormPage';

		$page_builder_plugins = self::page_builder_plugins();

		?>

        <h4><?php _e( 'These page builder plugins work in our popups out of the box.', 'popup-maker' ); ?></h4>

        <ul class="extensions-available">
			<?php

			$i = 1;

			foreach ( $page_builder_plugins as $plugin ) : ?>
                <li class="available-extension-inner <?php echo esc_attr( $plugin['slug'] ); ?>">
                    <h3>
                        <a target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>?utm_campaign=<?php echo $campaign; ?>&utm_source=plugin-extend-page&utm_medium=page-builder-banner&utm_content=<?php echo $plugin['slug']; ?>">
							<?php echo esc_html( $plugin['name'] ) ?>
                        </a>
                    </h3>

                    <img class="extension-thumbnail" src="<?php echo esc_attr( POPMAKE_URL . '/assets/images/plugins/' . $plugin['slug'] . '.png' ) ?>" />

                    <p><?php echo esc_html( $plugin['desc'] ); ?></p>

                    <span class="action-links">
					<a class="button" target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>"><?php _e( 'Check it out', 'popup-maker' ); ?></a>
				</span>
                </li>
				<?php
				$i ++;
			endforeach; ?>
        </ul>


		<?php
	}

	/**
	 * Renders extensions tab other plugins list.
	 *
	 * @since 1.10.0
	 */
	public static function render_other_list() {
		$recommended_plugins = self::other_plugins();
		?>
		<h4><?php _e( 'These plugins work great alongside our popups!', 'popup-maker' ); ?></h4>

		<ul class="extensions-available">
			<?php
			foreach ( $recommended_plugins as $plugin ) : ?>
				<li class="available-extension-inner <?php echo esc_attr( $plugin['slug'] ); ?>">
					<h3>
						<a target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>">
							<?php echo esc_html( $plugin['name'] ) ?>
						</a>
					</h3>

					<img class="extension-thumbnail" src="<?php echo esc_attr( POPMAKE_URL . '/assets/images/plugins/' . $plugin['slug'] . '.png' ) ?>" />

					<p><?php echo esc_html( $plugin['desc'] ); ?></p>

					<span class="action-links">
					<a class="button" target="_blank" href="<?php echo esc_attr( $plugin['url'] ); ?>"><?php _e( 'Check it out', 'popup-maker' ); ?></a>
				</span>
				</li>
				<?php
			endforeach; ?>
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

				$i = 0;

				foreach ( $extensions as $extension ) : ?>
                    <li class="available-extension-inner <?php echo esc_attr( $extension['slug'] ); ?>">
                        <h3>
                            <a target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-title-<?php echo $i; ?>&utm_campaign=<?php echo $campaign; ?>&utm_content=<?php echo esc_attr( urlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>">
								<?php echo esc_html( $extension['name'] ) ?>
                            </a>
                        </h3>
						<?php $image = in_array( $extension['slug'], $existing_extension_images ) ? POPMAKE_URL . '/assets/images/extensions/' . $extension['slug'] . '.png' : $extension['image']; ?>
                        <img class="extension-thumbnail" src="<?php echo esc_attr( $image ) ?>" />

                        <p><?php echo esc_html( $extension['excerpt'] ); ?></p>

                        <span class="action-links">
						<a class="button" target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-button-<?php echo $i; ?>&utm_campaign=<?php echo $campaign; ?>&utm_content=<?php echo esc_attr( urlencode( str_replace( ' ', '+', $extension['name'] ) ) ); ?>"><?php _e( 'Get this Extension', 'popup-maker' ); ?></a>
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

	/**
	 * @param $subtab
	 */
	protected static function mark_extensions_viewed( $subtab ) {
		$viewed = self::viewed_extension_counts();
		$actual = self::actual_extension_counts();

		if ( ! isset( $actual[ $subtab ] ) ) {
			return;
		}

		$viewed[ $subtab ] = $actual[ $subtab ];

		update_option( 'pum_extend_viewed_extensions', $viewed );
	}

}
