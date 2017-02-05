<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support Page
 *
 * Renders the support page contents.
 *
 * @since        1.5.0
 */
function pum_settings_page() { ?>
	<style>
		.pum-secure-notice {
			position: fixed;
			top: 32px;
			left: 160px;
			right: 0;
			background: #ebfdeb;
			padding: 10px 20px;
			color: green;
			z-index: 9999;
			box-shadow: 0 2px 2px rgba(6, 113, 6, 0.3);
			opacity: 0.95;
			filter: alpha(opacity=95);
		}

		#pum-support-frame {
			margin: 40px 0 -65px -20px;
		}

		#pum-support-frame iframe {
			width: 100%;
			border: 0;
			transition: scroll .5s;
		}
	</style>
	<div class="pum-secure-notice">
		<i class="dashicons dashicons-lock"></i>
		<span><?php _e( '<b>Secure HTTPS contact page</b>, running via iframe from external domain', 'popup-maker' ); ?> </span>
		<i class="dashicons dashicons-info" title="https://api.wppopupmaker.com/dashboard-support/"></i>
	</div>
	<div id="pum-support-frame" class="wrap">
		<script type="text/javascript">
			(function ($) {
				var frame = $('<iframe scrolling="no">')
					.css({height: '535px'})
					.attr('src', '<?php echo 'https://api.wppopupmaker.com/dashboard-support/?' . build_query( pum_support_assist_args() ); ?>')
					.appendTo('#pum-support-frame');

				frame.iFrameResize({
					checkOrigin: false
				});
			})(jQuery);
		</script>
	</div>

	<?php
}
