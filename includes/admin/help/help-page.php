<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help Page
 *
 * Renders the extensions page contents.
 *
 * @access      private
 * @since        1.0
 * @return      void
 */
function popmake_help_page() { ?>
	<div class="wrap">
	<h2><?php esc_html_e( __( 'Popup Maker Help & Documentation', 'popup-maker' ) ); ?></h2>

	<h2 id="popmake-tabs" class="nav-tab-wrapper">

		<a href="#general" id="general-tab" class="nav-tab popmake-tab"><?php _e( 'General Usage', 'popup-maker' ); ?></a>

	</h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="tabwrapper">

					<div id="general" class="popmake-tab-content">
						<h4><?php _e( 'Copy the class to the link/button you want to open this popup.', 'popup-maker' ); ?>
							<span class="desc"><?php _e( 'Will start with popmake- and end with a # of the popup you want to open.', 'popup-maker' ); ?></span>
						</h4>

						<div class="tab-box">
							<h4><?php _e( 'Link Example', 'popup-maker' ); ?></h4>
							<a href="#" onclick="return false;" class="popmake-1"><?php _e( 'Open Popup', 'popup-maker' ); ?></a>
							<pre>&lt;a href="#" class="popmake-1"><?php _e( 'Open Popup', 'popup-maker' ); ?>
								&lt;/a></pre>
						</div>
						<div class="tab-box">
							<h4><?php _e( 'Button Example', 'popup-maker' ); ?></h4>
							<button onclick="return false;" class="popmake-1"><?php _e( 'Open Popup', 'popup-maker' ); ?></button>
							<pre>&lt;button class="popmake-1"><?php _e( 'Open Popup', 'popup-maker' ); ?>
								&lt;/button></pre>
						</div>
						<div class="tab-box">
							<h4><?php _e( 'Image Example', 'popup-maker' ); ?></h4>
							<img style="cursor:pointer;" src="<?php echo POPMAKE_URL ?>/assets/images/admin/popup-maker-icon.png" onclick="return false;" class="popmake-1"/>
							<pre>&lt;img src="popup-maker-icon.png" class="popmake-1" /></pre>
						</div>
					</div>

				</div>
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables ui-sortable" id="side-sortables">
					<?php do_action( 'popmake_admin_sidebar' ); ?>
				</div>
			</div>
		</div>
		<br class="clear"/>
	</div>
	</div><?php
}