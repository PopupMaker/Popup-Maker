<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

class PUM_Admin {
	public static function init() {
		PUM_Admin_Pages::init();
		PUM_Admin_Ajax::init();
		PUM_Admin_Assets::init();
		PUM_Admin_Popups::init();
		PUM_Admin_Subscribers::init();
		PUM_Admin_Settings::init();
		PUM_Admin_Tools::init();
		PUM_Admin_Shortcode_UI::init();
		PUM_Upsell::init();
	}
}