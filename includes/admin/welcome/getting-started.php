<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_getting_started_page() {
	list( $display_version ) = explode( '-', POPMAKE_VERSION ); ?>
	<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Popup Maker %s', 'popup-maker' ), $display_version ); ?></h1>

	<div class="about-text"><?php _e( 'Thank you for updating to the latest version! Are you ready to destroy your old conversion rates and transform your website? We sure are!', 'popup-maker' ); ?></div>
	<div class="popmake-badge"><?php printf( __( 'Version %s', 'popup-maker' ), $display_version ); ?></div>

	<?php popmake_welcome_page_tabs(); ?>


	<p class="about-description"><?php _e( 'Use the tips below to get started using Popup Maker. You will have those high performance popups up and running in no time!', 'popup-maker' ); ?></p>

	<div class="changelog">
		<h3><?php _e( 'Creating Your First Popup Maker Popup', 'popup-maker' ); ?></h3>

		<div class="feature-section">


			<h4><?php printf( __( '<a href="%s">%s &rarr; Add New</a>', 'popup-maker' ), admin_url( 'post-new.php?post_type=popup' ), popmake_get_label_plural() ); ?></h4>
			<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/getting-started-1.jpg'; ?>" class="popmake-welcome-screenshots" width="540"/>

			<p><?php printf( __( 'The %s menu is your access point for all aspects of your Popup Maker product creation and setup. To create your first popup, simply click Add New and then choose from many available options to get it just right.', 'popup-maker' ), popmake_get_label_plural() ); ?></p>

			<h4><?php _e( 'Display Options', 'popup-maker' ); ?></h4>

			<p><?php _e( 'Display options control how your popup shows and how it behaves, respectively. Set the size, how the background operates, animation options, and positioning positioning.', 'popup-maker' ); ?></p>

			<h4><?php _e( 'Close Option', 'popup-maker' ); ?></h4>

			<p><?php _e( 'These settings allow you to control how a user is able to close your popups: clicking on the background overlay, pressing ESC, or pressing F4. You can enable or disable all of these settings, or pick and choose based on your preference. Also, you can prevent users from closing your popups using our Forced Interaction Extension.', 'popup-maker' ); ?></p>

			<h4><?php _e( 'Targeting Conditions', 'popup-maker' ); ?></h4>

			<p><?php _e( 'Use Targeting Conditions to load your popups wherever you please! If you’re not running some type of auto open popup extension (Scroll Triggered, Exit Intent, or Auto Open), then remember, you have to call your popup using the <em>popmake class</em> on your HTML element (more information below.)', 'popup-maker' ); ?></p>

			<h4><?php _e( 'Theme Settings', 'popup-maker' ); ?></h4>

			<p><?php _e( 'Choose your theme from the drop down to customize how your popup looks. Without Unlimited Themes, only the Default Theme will appear, which you can customize and change the name to suit your needs.', 'popup-maker' ); ?></p>

		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Calling Your Popups Anywhere', 'popup-maker' ); ?></h3>

		<div class="feature-section">

			<img src="<?php echo POPMAKE_URL . '/assets/images/welcome/calling-popup.jpg'; ?>" class="popmake-welcome-screenshots" width="540"/>

			<h4><?php _e( 'Using the Popup Maker CSS Tag', 'popup-maker' ); ?></h4>

			<p><?php _e( 'In the <em>All Popups Menu</em>, you can find all of your popups and their various attributes. One of the most important attributes is the CSS Class of your popups. You will use this class wherever you want to call your popups!', 'popup-maker' ); ?></p>

			<p>Examples:</p>
			<ul class="inline">
				<li><code>&lt;a class=”popmake-####”><?php _e( 'Read More', 'popup-maker' ); ?>&lt;/a></code></li>
				<li><code>&lt;button class=”popmake-####”><?php _e( 'Sign Up Now!', 'popup-maker' ); ?>
						&lt;/button></code></li>
				<li><code>&lt;img class=”popmake-####”/></code></li>
			</ul>

			<h4><?php _e( 'Using Popup Maker Short Codes', 'popup-maker' ); ?></h4>

			<p><?php _e( 'You can also use shortcodes to create popups directly inline with the content of your posts and pages. This is useful if you wanna have unique content in your popups for each page/post.', 'popup-maker' ); ?></p>

			<p>For Example:</p>
			<ul class="inline">
				<li><code>&lsqb;popup id=The-Pop-Up-Name size="small"]Put your content and other &lsqb;shortcodes] here.&lsqb;/modal]</code>
				</li>
			</ul>
		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Need Help?', 'popup-maker' ); ?></h3>

		<div class="feature-section">

			<h4><?php _e( 'Top-Notch Support', 'popup-maker' ); ?></h4>

			<p>
				<?php printf(
					__( 'We provide top-notch support! If you encounter a problem or have a question, post a question in the %sWordPress Support Forums%s, or if you’ve purchased an extension, the %sSupport Page%s.', 'popup-maker' ),
					'<a href="https://wordpress.org/support/plugin/popup-maker" target="_blank">', '</a>',
					'<a href="https://wppopupmaker.com/support?utm_source=WP+Welcome+Getting+Started&utm_medium=Text+Link&utm_campaign=Extension+Support" target="_blank">', '</a>'
				); ?>
			</p>
		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Stay Up-to-Date', 'popup-maker' ); ?></h3>

		<div class="feature-section">

			<h4><?php _e( 'Get Notified of Extension Releases', 'popup-maker' ); ?></h4>

			<p><?php
				printf(
					__( 'New extensions that make Popup Maker even more powerful are released nearly every single week. <a href="%s" target="_blank">Subscribe to the newsletter</a> to stay up to date with our latest releases. Signup now to ensure you do not miss a release!', 'popup-maker' ),
					'https://wppopupmaker.com/newsletter-sign-up?utm_source=WP+Welcome+Getting+Started&utm_medium=Text+Link&utm_campaign=Newsletter+Signup'
				); ?>
			</p>

			<h4><?php _e( 'Get Alerted About New Tutorials', 'popup-maker' ); ?></h4>

			<p><?php
				printf(
					__( '<a href="%s" target="_blank">Signup now</a> to hear about the latest tutorial releases that explain how to take Popup Maker further.', 'popup-maker' ),
					'https://wppopupmaker.com/newsletter-sign-up?utm_source=WP+Welcome+Getting+Started&utm_medium=Text+Link&utm_campaign=Newsletter+Signup'
				); ?>
			</p>

		</div>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Extensions for Everything', 'popup-maker' ); ?></h3>

		<div class="feature-section">

			<h4><?php _e( '9 Extensions and Counting...', 'popup-maker' ); ?></h4>

			<p><?php _e( 'Add-on plugins are available that greatly extend the default functionality of Popup Maker. There are extensions enhancing the Theme Builder capabilities, extensions for marketing your most precious content like Auto Open and Exit Intent, plus much more now, and even more to come.', 'popup-maker' ); ?></p>

			<p><?php _e( 'We have over 25 more extensions in works, and over 35 integration extensions in the works as well. Stay updated and tuned in to be a part of what is going to be the robust Popup Maker tool on the market.', 'popup-maker' ); ?></p>

			<h4><?php _e( 'Visit the Extension Store', 'popup-maker' ); ?></h4>

			<p><?php
				printf(
					__( '<a href="%s" target="_blank">The Extensions store</a> has a list of all available extensions, including convenient category filters so you can find exactly what you are looking for.', 'popup-maker' ),
					'https://wppopupmaker.com/extensions?utm_source=WP+Welcome+Getting+Started&utm_medium=Text+Link&utm_campaign=Extensions'
				); ?>
			</p>

		</div>
	</div>

	<div class="return-to-dashboard">
		<a href="<?php echo esc_url( admin_url( 'post.php?post=' . popmake_get_default_popup_theme() . '&action=edit' ) ); ?>"><?php _e( 'Customize Your First Theme', 'popup-maker' ); ?></a> &middot;
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup' ) ); ?>"><?php _e( 'Create a Modal', 'popup-maker' ); ?></a> &middot;
		<a href="<?php echo esc_url( 'http://docs.wppopupmaker.com/collection/1-getting-started?utm_source=WP+Welcome+Getting+Started&utm_medium=Text+Link&utm_campaign=Getting+Started' ); ?>" target="_blank"><?php _e( 'View the Full Getting Started Guide', 'popup-maker' ); ?></a>
	</div>
	</div><?php
}