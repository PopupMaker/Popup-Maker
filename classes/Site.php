<?php

class PUM_Site {

	public static function init() {
		PUM_Site_Assets::init();
		PUM_Site_Popups::init();
		PUM_Analytics::init();
	}
}