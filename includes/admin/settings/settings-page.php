<?php
/**
 * Settings Page
 *
 * Renders the settings page contents.
 *
 * @access      private
 * @since 		1.0
 * @return      void
*/
function popmake_settings_page() {
	global $popmake_options;
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], popmake_get_settings_tabs() ) ? $_GET[ 'tab' ] : 'general';
	ob_start();?>
	<div class="wrap">
		<h2><?php esc_html_e( __( 'Popup Maker Settings', 'popup-maker' ) );?></h2>
		<h2 id="popmake-tabs" class="nav-tab-wrapper"><?php
			foreach( popmake_get_settings_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab' => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}?>
		</h2>
		<form id="popmake-settings-editor" method="post" action="options.php">
			<?php do_action('popmake_form_nonce');?>
				<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div id="tab_container">
							<table class="form-table">
							<?php
							settings_fields( 'popmake_settings' );
							do_settings_fields( 'popmake_settings_' . $active_tab, 'popmake_settings_' . $active_tab );
							?>
							</table>
							<?php submit_button(); ?>
						</div><!-- #tab_container-->
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div class="postbox " id="submitdiv">
							<h3 class="hndle"><span><?php _e( 'Save', 'popup-maker' );?></span></h3>
							<div class="inside">
								<div id="submitpost" class="submitbox">
									<div id="major-publishing-actions" class="submitbox">
										<div id="publishing-action">
											<span class="spinner"></span>
											<input type="submit" accesskey="p" value="<?php _e( 'Save', 'popup-maker' );?>" class="button button-primary button-large" id="publish" name="publish">
										</div>
										<div class="clear"></div>
									</div>
								</div>
								<div class="clear"></div>
							</div>
						</div>
						<div class="postbox " id="supportdiv">
							<h3 class="hndle"><span><?php _e( 'Support', 'popup-maker' );?></span></h3>
							<div class="inside">
								<?php popmake_render_support_meta_box();?>
								<div class="clear"></div>
							</div>
						</div>
						<div id="sharediv">
							<div class="inside">
								<?php popmake_render_share_meta_box();?>
								<div class="clear"></div>
							</div>
						</div>
						<?php do_action('popmake_admin_sidebar');?>
					</div>
				</div>
				<br class="clear"/>
			</div>
		</form>
	</div><?php
	echo ob_get_clean();
}


//add_action('popmake_settings_page_licenses_tab_settings', 'popmake_settings_page_licenses_tab_access_key', 10);
function popmake_settings_page_licenses_tab_access_key() { ?>
	<tr class="form-field">
		<th scope="row">
			<label for="access_key"><?php _e( 'Access Key', 'popup-maker' );?></label>
		</th>
		<td>
			<input type="<?php echo popmake_get_option('access_key') ? 'password' : 'text'?>" id="access_key" name="access_key" value="<?php esc_attr_e(popmake_get_option('access_key'))?>" class="regular-text"/>
			<p class="description"><?php _e( 'Enter your access key to unlock extensions.', 'popup-maker' )?></p>
		</td>
	</tr><?php
}