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
	if ( ! get_transient( '_popmake_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient
	delete_transient( '_popmake_activation_redirect' );

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	$upgrade = get_option( 'popmake_version_upgraded_from' );

	if ( ! $upgrade ) { // First time install
		wp_safe_redirect( admin_url( 'index.php?page=popmake-getting-started' ) );
		exit;
	} else { // Update
		wp_safe_redirect( admin_url( 'index.php?page=popmake-about' ) );
		exit;
	}
}

add_action( 'admin_init', 'popmake_welcome_redirect' );


function popmake_welcome_page_tabs() {
	$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'popmake-about';
	?>
	<h2 class="nav-tab-wrapper">
		<a class="nav-tab <?php echo $selected == 'popmake-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'popmake-about' ), 'index.php' ) ) ); ?>">
			<?php _e( "What's New", 'popup-maker' ); ?>
		</a>
		<a class="nav-tab <?php echo $selected == 'popmake-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'popmake-getting-started' ), 'index.php' ) ) ); ?>">
			<?php _e( 'Getting Started', 'popup-maker' ); ?>
		</a>
		<a class="nav-tab <?php echo $selected == 'popmake-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'popmake-credits' ), 'index.php' ) ) ); ?>">
			<?php _e( 'Credits', 'popup-maker' ); ?>
		</a>
	</h2>
	<?php
}

