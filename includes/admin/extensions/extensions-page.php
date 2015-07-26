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
	<div class="wrap">
	<h2><?php _e( 'Popup Maker Extensions', 'popup-maker' ) ?></h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content"><?php
				$extensions = popmake_available_extensions(); ?>
				<ul class="extensions-available">
					<?php
					$plugins           = get_plugins();
					$installed_plugins = array();
					foreach ( $plugins as $key => $plugin ) {
						$is_active                          = is_plugin_active( $key );
						$installed_plugin                   = array(
							'is_active' => $is_active
						);
						$installerUrl                       = add_query_arg(
							array(
								'action' => 'activate',
								'plugin' => $key,
								'em'     => 1
							),
							network_admin_url( 'plugins.php' )
						//admin_url('update.php')
						);
						$installed_plugin["activation_url"] = $is_active ? "" : wp_nonce_url( $installerUrl, 'activate-plugin_' . $key );


						$installerUrl                         = add_query_arg(
							array(
								'action' => 'deactivate',
								'plugin' => $key,
								'em'     => 1
							),
							network_admin_url( 'plugins.php' )
						//admin_url('update.php')
						);
						$installed_plugin["deactivation_url"] = ! $is_active ? "" : wp_nonce_url( $installerUrl, 'deactivate-plugin_' . $key );
						$installed_plugins[ $key ]            = $installed_plugin;
					}
					$existing_extension_images = apply_filters( 'popmake_existing_extension_images', array() );
					if ( ! empty( $extensions ) ) {
						foreach ( $extensions as $extension ) :?>
							<li class="available-extension-inner">
								<h3>
									<a target="_blank" href="<?php esc_attr_e( $extension['homepage'] ); ?>?utm_source=Plugin+Admin&amp;utm_medium=Extensions+Page+Extension+Names&amp;utm_campaign=<?php esc_attr_e( str_replace( ' ', '+', $extension['name'] ) ); ?>">
										<?php esc_html_e( $extension['name'] ) ?>
									</a>
								</h3>
								<?php $image = in_array( $extension['slug'], $existing_extension_images ) ? POPMAKE_URL . '/assets/images/extensions/' . $extension['slug'] . '.png' : $extension['image']; ?>
								<img class="extension-thumbnail" src="<?php esc_attr_e( $image ) ?>">
<!--
								<p><?php esc_html_e( $extension['excerpt'] ) ?></p>
								<hr/>
								 -->
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
				                	<a class="button" target="_blank" href="<?php esc_attr_e( $extension['homepage'] ); ?>?utm_source=Plugin+Admin&amp;utm_medium=Extensions+Page+Extension+Buttons&amp;utm_campaign=<?php esc_attr_e( str_replace( ' ', '+', $extension['name'] ) ); ?>"><?php _e( 'Learn More', 'popup-maker' ); ?></a>
				                </span>
							</li>
						<?php endforeach;
					} ?>
				</ul>
			</div>
		</div>
		<br class="clear"/>
	</div>
	</div><?php
}
