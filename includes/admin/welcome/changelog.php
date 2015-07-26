<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_changelog_page() {
	list( $display_version ) = explode( '-', POPMAKE_VERSION ); ?>
	<div class="wrap about-wrap">
		<h1><?php _e( 'Popup Maker Changelog', 'popup-maker' ); ?></h1>

		<div class="about-text"><?php _e( 'Thank you for updating to the latest version! Are you ready to destroy your old conversion rates and transform your website? We sure are!', 'popup-maker' ); ?></div>
		<div class="popmake-badge"><?php printf( __( 'Version %s', 'popup-maker' ), $display_version ); ?></div>

		<?php popmake_welcome_page_tabs(); ?>

		<div class="changelog">
			<h3><?php _e( 'Full Changelog', 'popup-maker' ); ?></h3>

			<div class="feature-section">
				<?php echo popmake_parse_readme(); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Parse the POPMAKE readme.txt file
 *
 * @since 1.0
 * @return string $readme HTML formatted readme file
 */
function popmake_parse_readme() {
	$file = file_exists( POPMAKE_DIR . '/readme.txt' ) ? POPMAKE_DIR . '/readme.txt' : null;
	if ( ! $file ) {
		$readme = '<p>' . __( 'No valid changlog was found.', 'popup-maker' ) . '</p>';
	} else {
		$readme = file_get_contents( $file );
		$readme = nl2br( esc_html( $readme ) );
		$readme = explode( '== Changelog ==', $readme );
		$readme = end( $readme );

		$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
		$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
		$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
		$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
		$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
	}

	return $readme;
}
