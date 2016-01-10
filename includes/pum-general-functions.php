<?php
/**
 * General Functions
 *
 * @package      PUM
 * @subpackage   Functions/General
 * @copyright    Copyright (c) 2016, Daniel Iser
 * @license      http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since        1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_force_theme_css_refresh() {
	delete_site_transient( 'popmake_theme_styles' );
}