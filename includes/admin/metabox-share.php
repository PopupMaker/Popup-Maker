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
function popmake_render_share_meta_box() { ?>
	<div id="popmake_share_fields" class="popmake_meta_table_wrap">
	<?php do_action( 'popmake_share_meta_box_fields' ); ?>
	</div><?php
}


add_action( 'popmake_share_meta_box_fields', 'popmake_share_meta_box_links', 10 );
function popmake_share_meta_box_links() { ?>
	<h3 class="loveit-shareit" style="text-align:center">Love It? <span>Share It!</span></h3>
	<ul class="share-buttons">
		<li>
			<div class="fb-like" data-href="https://wppopupmaker.com" data-width="100" data-ref="true" data-layout="box_count" data-action="like" data-show-faces="false" data-send="true"></div>
		</li>
		<li>
			<a href="https://twitter.com/intent/tweet" class="twitter-share-button" data-text="<?php _e( 'Want to destroy your old conversion rates? Create high performing popups now! #WPPopupMaker', 'popup-maker' ); ?>" data-count="vertical" data-url="https://wppopupmaker.com" data-via="wppopupmaker" data-related="wppopupmaker"><?php _e( 'Tweet', 'popup-maker' ); ?></a>
		</li>
		<li>
			<div class="g-plusone" data-href="https://wppopupmaker.com" data-size="tall"></div>
		</li>
	</ul>
	<br class="clear"/>
	<br class="clear"/>
	<div style="text-align:center">
	<a class="button button-primary rounded" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/popup-maker#postform"><?php _e( 'Rate Popup Maker on WP!', 'popup-maker' ); ?></a>
	</div><?php
}


add_action( 'popmake_admin_footer', 'popmake_admin_footer_social_scripts' );
function popmake_admin_footer_social_scripts() { ?>
	<div id="fb-root"></div>
	<script>
		jQuery(document).ready(function () {
			(function (d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s);
				js.id = id;
				js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=191746824208314";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
			(function () {
				var po = document.createElement('script');
				po.type = 'text/javascript';
				po.async = true;
				po.src = 'https://apis.google.com/js/plusone.js';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(po, s);
			})();
			!function (d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
				if (!d.getElementById(id)) {
					js = d.createElement(s);
					js.id = id;
					js.src = p + '://platform.twitter.com/widgets.js';
					fjs.parentNode.insertBefore(js, fjs);
				}
			}(document, 'script', 'twitter-wjs');
		});
	</script><?php
}
