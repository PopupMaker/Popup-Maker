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
function popmake_support_meta_box_links() { ?>
	<ul class="popmake-support-links">
		<li>
            <a href="http://docs.wppopupmaker.com/?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=Docs">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/knowledge-base.png"/>
                <span><?php _e( 'Documentation', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wordpress.org/support/plugin/popup-maker">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/wordpress-forums.png"/>
				<span><?php _e( 'Free Support Forums', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wppopupmaker.com/support?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=Extension+Support">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/member-forums.png"/>
				<span><?php _e( 'Extension Support', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wppopupmaker.com/support/priority-pricing?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=Priority+Support">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/member-forums.png"/>
				<span><?php _e( 'Priority Support', 'popup-maker' ); ?></span>
			</a>
		</li>
	</ul>
	<?php
}
