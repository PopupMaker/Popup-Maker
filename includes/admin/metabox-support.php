<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popup Maker Support Metabox
 *
 * Extensions (as well as the core plugin) can add items to the popup support
 * metabox via the `popmake_support_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function popmake_render_support_meta_box() { ?>
	<div id="popmake_support_fields" class="popmake_meta_table_wrap">
	<?php do_action( 'popmake_support_meta_box_fields' ); ?>
	</div><?php
}


add_action( 'popmake_support_meta_box_fields', 'popmake_support_meta_box_links', 10 );
function popmake_support_meta_box_links() {
	global $pagenow;

	$source = $pagenow;

	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], popmake_get_settings_tabs() ) ? $_GET['tab'] : null;

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'pum-settings' ) {
		$source = 'plugin-settings-page' . ( ! empty( $active_tab ) ? '-' . $active_tab . '-tab' : '' );
	} elseif ( isset( $_GET['page'] ) && $_GET['page'] == 'pum-tools' ) {
		$source = 'plugin-tools-page' . ( ! empty( $active_tab ) ? '-' . $active_tab . '-tab' : '' );
	}
	?>
	<ul class="popmake-support-links">
		<li>
			<a href="http://docs.wppopupmaker.com/?utm_medium=support-sidebar&utm_campaign=ContextualHelp&utm_source=<?php echo $source; ?>&utm_content=documentation">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/support-pane-docs-icon.png" />
				<span><?php _e( 'Documentation', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wordpress.org/support/plugin/popup-maker">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/support-pane-wpforums-icon.png" />
				<span><?php _e( 'Free Support Forums', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wppopupmaker.com/support/?utm_medium=support-sidebar&utm_campaign=ContextualHelp&utm_source=<?php echo $source; ?>&utm_content=extension-support">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/support-pane-extensions-icon.png" />
				<span><?php _e( 'Extension Support', 'popup-maker' ); ?></span>
			</a>
		</li>
	</ul>
	<?php
}
