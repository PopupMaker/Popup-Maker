<?php
/**
 * Tools Page
 *
 * Renders the tools page contents.
 *
 * @access      private
 * @since 		1.0
 * @return      void
*/
function popmake_tools_page() {
	global $popmake_options;
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], popmake_get_tools_tabs() ) ? $_GET[ 'tab' ] : 'import';
	ob_start();?>
	<div class="wrap">
		<h2><?php esc_html_e( __( 'Popup Maker Tools', 'popup-maker' ) );?></h2>
		<?php if( isset( $_GET['imported'] ) ) : ?>
		<div class="updated">
			<p><?php _e( 'Successfully Imported your themes &amp; modals from Easy Modal.' );?></p>
		</div>
		<?php endif; ?>
		<h2 id="popmake-tabs" class="nav-tab-wrapper"><?php
			foreach( popmake_get_tools_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'tools-updated' => false,
					'tab' => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}?>
		</h2>
		<form id="popmake-tools-editor" method="post" action="">
			<?php do_action('popmake_form_nonce');?>
				<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div id="tab_container">
							<?php do_action('popmake_tools_page_tab_' . $tab_id); ?>
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
						<?php /*
						<div id="sharediv">
							<div class="inside">
								<?php popmake_render_share_meta_box();?>
								<div class="clear"></div>
							</div>
						</div>
						*/ ?>
						<?php do_action('popmake_admin_sidebar');?>
					</div>
				</div>
				<br class="clear"/>
			</div>
		</form>
	</div><?php
	echo ob_get_clean();
}

/**
 * Retrieve settings tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function popmake_get_tools_tabs() {

	$tabs = array();
	$tabs['import'] = __('Import / Export', 'popup-maker');
	return apply_filters( 'popmake_tools_tabs', $tabs );
}


function popmake_emodal_v2_import_button() {
	?><button id="popmake_emodal_v2_import" name="popmake_emodal_v2_import" class="button button-large">Import From Easy Modal v2</button><?php
}
add_action('popmake_tools_page_tab_import', 'popmake_emodal_v2_import_button');

function popmake_emodal_admin_init() {
	if( ! isset( $_REQUEST['popmake_emodal_v2_import'] ) ) {
		return;
	}
	popmake_emodal_v2_import();
	wp_redirect( admin_url( 'edit.php?post_type=popup&page=tools&imported=1' ), 302 );
}
add_action('admin_init', 'popmake_emodal_admin_init');