<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_about_page() {
	list( $display_version ) = explode( '-', POPMAKE_VERSION ); ?>
	<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Popup Maker %s', 'popup-maker' ), $display_version ); ?></h1>

	<div class="about-text"><?php _e( 'Thank you for updating to the latest version! Are you ready to destroy your old conversion rates and transform your website? We sure are!', 'popup-maker' ); ?></div>
	<div class="popmake-badge"><?php printf( __( 'Version %s', 'popup-maker' ), $display_version ); ?></div>

	<?php popmake_welcome_page_tabs(); ?>

	<div class="changelog">
		<h3><?php _e( 'Targeting Conditions', 'popup-maker' ); ?></h3>
		<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/targeting-conditions.png'; ?>" class="popmake-welcome-screenshots"/>

		<div class="feature-section">
			<h4><?php _e( 'Target specific users for your popups!', 'popup-maker' ); ?></h4>

			<p><?php printf( __( 'Our %sTargeting Conditions%s feature allows you to tailor your popups to specific users by giving you the ability to use popups exactly where you want within your website.', 'popup-maker' ), '<strong>', '</strong>' ); ?></p>
			<h4><?php _e( 'Destroy Old Conversion Rates with Auto Open!', 'popup-maker' ); ?></h4>

			<p><?php printf( __( 'One of the absolute best ways to market your most valuable and precious content, our %sAuto Open Popups%s Feature allows you to choose the delay before opening, when the cookie is set, how long the cookie will last, even reset cookies for a popup.', 'popup-maker' ), '<strong>', '</strong>' ); ?></p>
			<h4><?php _e( 'Drive Conversions!', 'popup-maker' ); ?></h4>

			<p>
				<?php printf(
					__( 'Use %sScroll Triggered%s & %sExit Intent Popup%s Extensions to enhance your popups’ effectiveness and easily convert users into cash.', 'popup-maker' ),
					'<a href="https://wppopupmaker.com/extensions/scroll-triggered-popups?utm_source=WP+Welcome+About&utm_medium=Text+Link&utm_campaign=Scroll+Triggered" target="_blank">', '</a>',
					'<a href="https://wppopupmaker.com/extensions/exit-intent-popups?utm_source=WP+Welcome+About&utm_medium=Text+Link&utm_campaign=Exit+Intent" target="_blank">', '</a>'
				); ?>
			</p>
		</div>
		<h3><?php _e( 'Google Font Integration', 'popup-maker' ); ?></h3>

		<div class="feature-section">
			<p><?php _e( 'Easily plug and play all of your favorite fonts from Google Fonts all within a few clicks!', 'popup-maker' ); ?></p>
		</div>
	</div>
	<div class="changelog">
	</div>


	<div class="changelog">
		<h3><?php _e( 'WordPress Form Plug-In Integrations', 'popup-maker' ); ?></h3>

		<div class="feature-section">
			<p><?php _e( 'Use any of your forms from the most popular form plugins out-of-the-box inside your popups with ease and efficiency. 100% seamless compatibility with:', 'popup-maker' ); ?></p>
			<ul class="inline">
				<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/ninja.jpg'; ?>"/>
				<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/gravity.jpg'; ?>"/>
				<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/cf7.jpg'; ?>"/>
			</ul>
		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Easy Theme Builder', 'popup-maker' ); ?></h3>

		<div class="feature-section">
			<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/easy-theme-builder.png'; ?>" class="popmake-welcome-screenshots"/>

			<p><?php _e( 'Our theme builder allows you to create a high performing theme in no time for your popups. Use our color picker to grab the perfect colors to meet your needs and find pixel perfect sizes with ease for a plethora of additional theme settings.', 'popup-maker' ); ?></p>
			<h4><?php _e( 'Make it Your Own!', 'popup-maker' ); ?></h4>

			<p>
				<?php printf(
					__( 'You may be interested in our %sAdvanced Theme Builder%s Extension, which allows you to add background images to many elements of your popup within a couple of clicks.', 'popup-maker' ),
					'<a href="https://wppopupmaker.com/extensions/advanced-theme-builder?utm_source=WP+Welcome+About&utm_medium=Text+Link&utm_campaign=Advanced+Theme+Builder" target="_blank">', '</a>'
				); ?>
			</p>
		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Further Enhancements & Updates', 'popup-maker' ); ?></h3>

		<div class="feature-section col three-col">
			<div>
				<h4><?php _e( 'Exit Intent', 'popup-maker' ); ?></h4>

				<p>
					<?php printf(
						__( 'Easily convert abandoning users into cash with our Exit Intent Extension. Exit Intent now comes with Hard Exit Technology. Learn more %shere%s.', 'popup-maker' ),
						'<a href="https://wppopupmaker.com/extensions/exit-intent-popups?utm_source=WP+Welcome+About&utm_medium=Text+Link&utm_campaign=Exit+Intent" target="_blank">', '</a>'
					); ?>
				</p>
			</div>

			<div>
				<h4><?php _e( 'Lightweight & Dependable', 'popup-maker' ); ?></h4>

				<p><?php _e( 'Speed matters, and at only 5.9kb, our popups won’t slow you down. Our optimization techniques and program enhancement means your popups will perform at a consistently high level with 100% uptime.', 'popup-maker' ); ?></p>
			</div>

			<div class="last-feature">
				<h4><?php _e( 'Popup Tags & Categories', 'popup-maker' ); ?></h4>

				<p><?php _e( 'Popup Maker allows you to categorize and tag your popups for easy organization and recognition.', 'popup-maker' ); ?></p>
			</div>
		</div>
	</div>

	<div class="return-to-dashboard">
		<a href="<?php echo esc_url( admin_url( 'post.php?post=' . popmake_get_default_popup_theme() . '&action=edit' ) ); ?>"><?php _e( 'Customize Your First Theme', 'popup-maker' ); ?></a> &middot;
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup' ) ); ?>"><?php _e( 'Create a Modal', 'popup-maker' ); ?></a> &middot;
		<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pum-changelog' ), 'index.php' ) ) ); ?>"><?php _e( 'View the Full Changelog', 'popup-maker' ); ?></a>
	</div>
	</div><?php
}