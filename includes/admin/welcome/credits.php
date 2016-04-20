<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_credits_page() {
	list( $display_version ) = explode( '-', POPMAKE_VERSION ); ?>
	<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Popup Maker %s', 'popup-maker' ), $display_version ); ?></h1>

	<div class="about-text"><?php _e( 'Thank you for updating to the latest version! Are you ready to destroy your old conversion rates and transform your website? We sure are!', 'popup-maker' ); ?></div>
	<div class="popmake-badge"><?php printf( __( 'Version %s', 'popup-maker' ), $display_version ); ?></div>

	<?php popmake_welcome_page_tabs(); ?>

	<p class="about-description"><?php _e( 'Popup Maker is created by expert WordPress developers who aim to provide the #1 popup marketing platform for converting more users with WordPress.', 'popup-maker' ); ?></p>
	<ul class="wp-people-group">
        <li class="wp-person">
            <a href="https://profiles.wordpress.org/danieliser" title="View danieliser">
                <img src="http://www.gravatar.com/avatar/<?php echo md5( "danieliser@wizardinternetsolutions.com" ); ?>" width="64" height="64" class="gravatar" alt="danieliser"/>
            </a>
            <a class="web" href="https://profiles.wordpress.org/danieliser" target="_blank">danieliser</a>
        </li>
        <li class="wp-person">
            <a href="https://profiles.wordpress.org/fpcorso" title="View danieliser">
                <img src="http://www.gravatar.com/avatar/<?php echo md5( "frank@mylocalwebstop.com" ); ?>" width="64" height="64" class="gravatar" alt="fpcorso"/>
            </a>
            <a class="web" href="https://profiles.wordpress.org/fpcorso" target="_blank">fpcorso</a>
        </li>
	</ul>
	</div><?php
}