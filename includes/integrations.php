<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ninja Forms Integration
require_once 'integrations/class-pum-ninja-forms.php';

function pum_initialize_integrations() {
	// WooCommerce Integration
	if ( function_exists( 'WC' ) || class_exists( 'WooCommerce' ) ) {
		require_once 'integrations/class-pum-woocommerce-integration.php';
		PUM_Woocommerce_Integration::init();
	}

	// BuddyPress Integration
	if ( function_exists( 'buddypress' ) || class_exists( 'BuddyPress' ) ) {
		require_once 'integrations/class-pum-buddypress-integration.php';
		PUM_BuddyPress_Integration::init();
	}

	// CF7 Forms Integration
	if ( class_exists( 'WPCF7' ) || ( defined( 'WPCF7_VERSION' ) && WPCF7_VERSION ) ) {
		require_once 'integrations/class-pum-cf7.php';
		PUM_CF7_Integration::init();
	}

	// Gravity Forms Integration
	if ( class_exists( 'RGForms' ) ) {
		require_once 'integrations/class-pum-gravity-forms.php';
		PUM_Gravity_Forms_Integation::init();
	}

	// WPML Integration
	if ( defined( 'ICL_SITEPRESS_VERSION' ) && ICL_SITEPRESS_VERSION ) {
		require_once 'integrations/class-pum-wpml.php';
		PUM_WPML_Integration::init();
	}
}
add_action( 'init', 'pum_initialize_integrations' );
