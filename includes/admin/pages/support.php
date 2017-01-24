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
			box-shadow: 0px 2px 2px rgba(6, 113, 6, 0.3);
			opacity: 0.95;
			filter: alpha(opacity=95);
		}

		#pum-support-frame {
			margin: 40px 0 -65px -20px;
		}

		#pum-support-iframe {
			width: 100%;
			border: 0;
			transition: height .5s;
		}
	</style>
	<div class="pum-secure-notice">
		<i class="dashicons dashicons-lock"></i>
		<span><?php _e( '<b>Secure HTTPS contact page</b>, running via iframe from external domain', 'popup-maker' ); ?></span>
	</div>

	<div id="pum-support-frame" class="wrap">
		<iframe style="height: 535px;" id="pum-support-iframe" src="https://wppopupmaker.com/dashboard-support/?nouser&url" scrolling="no"></iframe>
		<script type="text/javascript">
			(function ($) {
				$('#pum-support-iframe').iFrameResize({});
			})(jQuery);
		</script>
	</div>

	<?php
}
