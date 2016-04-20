<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends user to the Welcome page on first activation of Popup Maker as well as each
 * time Popup Maker is upgraded to a new version
 *
 * @access public
 * @since 1.0.0
 * @return void
 */
function popmake_welcome_redirect() {

	// Bail if no activation redirect
	if ( ! get_transient( '_pum_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient
	delete_transient( '_pum_activation_redirect' );

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	$upgrade = get_option( 'pum_ver_upgraded_from' );

	if ( ! $upgrade ) { // First time install
        wp_safe_redirect( admin_url( 'index.php?page=pum-getting-started' ) );
		exit;
	} else { // Update
        wp_safe_redirect( admin_url( 'index.php?page=pum-about' ) );
		exit;
	}
}

// add_action( 'admin_init', 'popmake_welcome_redirect' );


function popmake_welcome_page_tabs() {
    $selected = isset( $_GET['page'] ) ? $_GET['page'] : 'pum-about';
	?>
	<h2 class="nav-tab-wrapper">
        <a class="nav-tab <?php echo $selected == 'pum-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pum-about' ), 'index.php' ) ) ); ?>">
			<?php _e( "What's New", 'popup-maker' ); ?>
		</a>
        <a class="nav-tab <?php echo $selected == 'pum-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pum-getting-started' ), 'index.php' ) ) ); ?>">
			<?php _e( 'Getting Started', 'popup-maker' ); ?>
		</a>
        <a class="nav-tab <?php echo $selected == 'pum-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pum-credits' ), 'index.php' ) ) ); ?>">
			<?php _e( 'Credits', 'popup-maker' ); ?>
		</a>
	</h2>
	<?php
}

