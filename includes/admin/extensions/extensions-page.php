<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Addons Page
 *
 * Renders the extensions page contents.
 *
 * @access      private
 * @since        1.0
 * @return      void
 */
function popmake_extensions_page() { ?>
	<div class="wrap"><h1><?php _e( 'Extend Popup Maker', 'popup-maker' ) ?></h1>

	<div id="poststuff">
	<div id="post-body" class="metabox-holder">
		<div id="post-body-content"><?php
			$extensions = popmake_available_extensions(); ?>
			<hr class="clear" />
			<h2 class="section-heading">
				<?php _e( 'Extensions', 'popup-maker' ) ?>
				&nbsp;&nbsp;<a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=Upsell&utm_content=browse-all" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>
			</h2>
			<p><?php _e( 'These extensions <strong>add extra functionality</strong> to your popups.', 'popup-maker' ); ?></p>
			<ul class="extensions-available">
				<?php
				$plugins           = get_plugins();
				$installed_plugins = array();
				foreach ( $plugins as $key => $plugin ) {
					$is_active                          = is_plugin_active( $key );
					$installed_plugin                   = array(
						'is_active' => $is_active,
					);
					$installerUrl                       = add_query_arg( array(
						'action' => 'activate',
						'plugin' => $key,
						'em'     => 1,
					), network_admin_url( 'plugins.php' ) //admin_url('update.php')
					);
					$installed_plugin["activation_url"] = $is_active ? "" : wp_nonce_url( $installerUrl, 'activate-plugin_' . $key );


					$installerUrl                         = add_query_arg( array(
						'action' => 'deactivate',
						'plugin' => $key,
						'em'     => 1,
					), network_admin_url( 'plugins.php' ) //admin_url('update.php')
					);
					$installed_plugin["deactivation_url"] = ! $is_active ? "" : wp_nonce_url( $installerUrl, 'deactivate-plugin_' . $key );
					$installed_plugins[ $key ]            = $installed_plugin;
				}
				$existing_extension_images = apply_filters( 'popmake_existing_extension_images', array() );
				if ( ! empty( $extensions ) ) {

					shuffle( $extensions );

					foreach ( $extensions as $key => $ext ) {
						unset( $extensions[ $key ] );
						$extensions[ $ext['slug'] ] = $ext;
					}

					$extensions = array_merge( array( 'core-extensions-bundle' => $extensions['core-extensions-bundle'] ), $extensions );

					$i = 0;

					foreach ( $extensions as $extension ) : ?>
						<li class="available-extension-inner <?php esc_attr_e( $extension['slug'] ); ?>">
							<h3>
								<a target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-title-<?php echo $i; ?>&utm_campaign=Upsell&utm_content=<?php esc_attr_e( str_replace( ' ', '+', $extension['name'] ) ); ?>">
									<?php esc_html_e( $extension['name'] ) ?>
								</a>
							</h3>
							<?php $image = in_array( $extension['slug'], $existing_extension_images ) ? POPMAKE_URL . '/assets/images/extensions/' . $extension['slug'] . '.png' : $extension['image']; ?>
							<img class="extension-thumbnail" src="<?php esc_attr_e( $image ) ?>">

							<p><?php esc_html_e( $extension['excerpt'] ); ?></p>
							<?php
							/*
							if(!empty($extension->download_link) && !isset($installed_plugins[$extension->slug.'/'.$extension->slug.'.php']))
							{
								$installerUrl = add_query_arg(
									array(
										'action' => 'install-plugin',
										'plugin' => $extension->slug,
										'edd_sample_plugin' => 1
									),
									network_admin_url('update.php')
									//admin_url('update.php')
								);
								$installerUrl = wp_nonce_url($installerUrl, 'install-plugin_' . $extension->slug)?>
								<span class="action-links"><?php
								printf(
									'<a class="button install" href="%s">%s</a>',
									esc_attr($installerUrl),
									__('Install')
								);?>
								</span><?php
							}
							elseif(isset($installed_plugins[$extension->slug.'/'.$extension->slug.'.php']['is_active']))
							{?>
								<span class="action-links"><?php
									if(!$installed_plugins[$extension->slug.'/'.$extension->slug.'.php']['is_active'])
									{
										printf(
											'<a class="button install" href="%s">%s</a>',
											esc_attr($installed_plugins[$extension->slug.'/'.$extension->slug.'.php']["activation_url"]),
											__('Activate')
										);

									}
									else
									{
										printf(
											'<a class="button install" href="%s">%s</a>',
											esc_attr($installed_plugins[$extension->slug.'/'.$extension->slug.'.php']["deactivation_url"]),
											__('Deactivate')
										);
									}?>
								</span><?php
							}
							else
							{
								?><span class="action-links"><a class="button" target="_blank" href="<?php esc_attr_e($extension->homepage);?>"><?php _e('Get It Now');?></a></span><?php
							}
							*/
							?>

							<span class="action-links">
			                    <a class="button" target="_blank" href="<?php echo esc_url( $extension['homepage'] ); ?>?utm_source=plugin-extension-page&utm_medium=extension-button-<?php echo $i; ?>&utm_campaign=Upsell&utm_content=<?php esc_attr_e( str_replace( ' ', '+', $extension['name'] ) ); ?>"><?php _e( 'Get this Extension', 'popup-maker' ); ?></a>
			                </span>
						</li>
						<?php
						$i ++;
					endforeach;
				} ?>
			</ul>

			<br class="clear" />

			<a href="https://wppopupmaker.com/extensions/?utm_source=plugin-extension-page&utm_medium=text-link&utm_campaign=Upsell&utm_content=browse-all-bottom" class="button-primary" title="<?php _e( 'Browse All Extensions', 'popup-maker' ); ?>" target="_blank"><?php _e( 'Browse All Extensions', 'popup-maker' ); ?></a>

			<br class="clear" />
			<br class="clear" />
			<br class="clear" />
			<hr class="clear" />
			<br class="clear" />

			<h2 class="section-heading">
				<?php _e( 'Other Compatible Plugins', 'popup-maker' ); ?>
			</h2>
			<p><?php _e( 'These plugins should work in popups with no extra setup.', 'popup-maker' ); ?></p>
			<ul class="extensions-available">
				<?php
				$compatible_plugins = array(
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

				shuffle( $compatible_plugins );

				array_unshift( $compatible_plugins, array(
					'slug' => 'ninja-forms',
					'name' => __( 'Ninja Forms', 'popup-maker' ),
					'url'  => 'https://wppopupmaker.com/grab/ninja-forms',
					'desc' => __( 'Ninja Forms has fast become the most extensible form plugin available. Build super custom forms and integrate with your favorite services.', 'popup-maker' ),
				) );

				$i = 1;

				foreach ( $compatible_plugins as $plugin ) : ?>
				<li class="available-extension-inner <?php esc_attr_e( $plugin['slug'] ); ?>">
					<h3>
						<a target="_blank" href="<?php esc_attr_e( $plugin['url'] ); ?>?utm_campaign=FormPlugins&utm_source=plugin-extend-page&utm_medium=form-banner&utm_content=<?php echo $plugin['slug']; ?>">
							<?php esc_html_e( $plugin['name'] ) ?>
						</a>
					</h3>
					<img class="extension-thumbnail" src="<?php esc_attr_e( POPMAKE_URL . '/assets/images/plugins/' . $plugin['slug'] . '.png' ) ?>">

					<p><?php esc_html_e( $plugin['desc'] ); ?></p>
					<span class="action-links">
                                <a class="button" target="_blank" href="<?php echo esc_url( $plugin['url'] ); ?>"><?php _e( 'Check it out', 'popup-maker' ); ?></a>
                            </span>
					</li><?php
					$i ++;
				endforeach; ?>
			</ul>

		</div>

	</div>
	</div><?php
}
