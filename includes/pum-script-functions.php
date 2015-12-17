<?php
/**
 * Scripts
 *
 * @package     PUM
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, Wizard Internet Solutions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_footer_scripts() {

	$scripts = get_transient( 'pum_footer_scripts' );
	if ( ! $scripts ) {
		ob_start();

		do_action( 'pum_footer_scripts' );

		$scripts = ob_get_clean();

		set_transient( 'pum_footer_scripts', $scripts, 7 * DAY_IN_SECONDS );
	}


	if ( ! empty ( $scripts ) ) { ?>
		<script type="text/javascript" id="pum_footer_scripts">
			(function ($) {
				<?php echo $scripts; ?>
			}(jQuery));
		</script><?php
	}

}

add_action( 'wp_footer', 'pum_footer_scripts', 20 );
