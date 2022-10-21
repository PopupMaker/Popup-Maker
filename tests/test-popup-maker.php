<?php
/**
 * Class PopupMakerTEST
 *
 * @package     Popup Maker
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */


/**
 * Test our main PopupMaker class
 */
class PopupMakerTEST extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `all_popups_main_tour` is valid.
	 */
	public function test_pum() {
		$instance = pum();
		$this->assertInstanceOf( Popup_Maker::class, $instance );

		// Our constants should be defined once the instance is created.
		$this->assertIsString( POPMAKE_VERSION );
	}
}
