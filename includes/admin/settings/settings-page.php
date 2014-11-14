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
						<div class="meta-box-sortables ui-sortable" id="side-sortables">
							<div class="postbox " id="submitdiv">
								<div title="Click to toggle" class="handlediv"><br></div>
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
							<?php do_action('popmake_admin_sidebar');?>
						</div>
					</div>
				</div>
				<br class="clear"/>
			</div>
		</form>
	</div><?php
	echo ob_get_clean();
}



add_filter('popmake_settings_page_tabs', 'popmake_settings_page_licenses_tab', 10);
function popmake_settings_page_licenses_tab($tabs) {
	$tabs[] = array( 'id' => 'licenses', 'label' => __( 'Licenses', 'popup-maker' ) );
	return $tabs;
}

add_action('popmake_settings_page_tab_licenses', 'popmake_settings_page_licenses_tab_table', 10);
function popmake_settings_page_licenses_tab_table() {
	?><table class="form-table">
		<tbody>
			<?php do_action('popmake_settings_page_licenses_tab_settings');?>
		</tbody>
	</table><?php
}


add_action('popmake_settings_page_licenses_tab_settings', 'popmake_settings_page_licenses_tab_no_licensed_products', 10);
function popmake_settings_page_licenses_tab_no_licensed_products() { ?>
	<tr class="form-field">
		<th colspan="2" scope="row">
			<p><?php _e( 'No licensed extensions detected.', 'popup-maker' )?></p>
		</td>
	</tr><?php
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





add_filter('popmake_settings_page_tabs', 'popmake_settings_page_support_tab', 20);
function popmake_settings_page_support_tab( $tabs ) {
	$tabs[] = array( 'id' => 'support', 'label' => __( 'Support', 'popup-maker' ) );
	return $tabs;
}



add_action('popmake_settings_page_tab_support', 'popmake_settings_page_support_tab_table', 20);
function popmake_settings_page_support_tab_table() {
	?><table class="form-table">
		<tbody>
			<?php do_action('popmake_settings_page_support_tab_settings');?>
		</tbody>
	</table><?php
}

add_action('popmake_settings_page_support_tab_settings', 'popmake_settings_page_support_tab_license', 10);
function popmake_settings_page_support_tab_license() { ?>
	<h3>For use by support only. Reset and Uninstall Will result in loss of data. Only proceed if you are sure you understand what you are doing.</h3>
	<?php if(1==0 && popmake_get_option( 'popup-maker_migration_approval')) : ?>
	<tr class="form-field">
		<th scope="row">
			<label><?php _e( 'Approve Migration', 'popup-maker' );?></label>
		</th>
		<td>
			<button type="submit" name="remove_old_popmake_data"><?php _e( 'Aprove', 'popup-maker' );?></button>
			<p class="description"><?php _e( 'Click this if you are sure your popups, themes and settings imported successfully.', 'popup-maker' )?></p>
		</td>
	</tr>
	<?php endif; ?>
	<tr class="form-field">
		<th scope="row">
			<label><?php _e( 'Reset Popup Maker Database', 'popup-maker' );?></label>
		</th>
		<td>
			<button type="submit" name="reset_popmake_db"><?php _e( 'Reset', 'popup-maker' );?></button>
			<p class="description"><?php _e( 'Use this to reset the database and remove all popups.', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row">
			<label><?php _e( 'Import Old Popup Maker Settings', 'popup-maker' );?></label>
		</th>
		<td>
			<button type="submit" name="migrate_popmake_db"><?php _e( 'Import', 'popup-maker' );?></button>
			<p class="description"><?php _e( 'Use this to import your popups and themes from your older version of easy popup.', 'popup-maker' )?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row">
			<label><?php _e( 'Uninstall Popup Maker Settings', 'popup-maker' );?></label>
		</th>
		<td>
			<button type="submit" name="uninstall_popmake_db"><?php _e( 'Uninstall', 'popup-maker' );?></button>
			<p class="description"><?php _e( 'Use this to reset the database and remove all popups, themes and database tables.', 'popup-maker' )?></p>
		</td>
	</tr><?php
}