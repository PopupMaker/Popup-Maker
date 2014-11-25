<?php

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



add_action('popmake_support_meta_box_fields', 'popmake_support_meta_box_links', 10);
function popmake_support_meta_box_links() { ?>
	<ul class="popmake-support-links">
		<li>
			<a href="https://wppopupmaker.com/kb?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=Knowledgebase">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/knowledge-base.png"/>
				<span><?php _e( 'Knowledge Base', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wppopupmaker.com/support?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=Member+Forums">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/member-forums.png"/>
				<span><?php _e( 'Member Forums', 'popup-maker' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://wordpress.org/support/plugin/popup-maker?utm_source=Plugin+Admin&utm_medium=Support+Metabox&utm_campaign=WordPress+Forums">
				<img src="<?php echo POPMAKE_URL; ?>/assets/images/admin/wordpress-forums.png"/>
				<span><?php _e( 'WordPress Forums', 'popup-maker' ); ?></span>
			</a>
		</li>
	</ul>
	<?php
}
