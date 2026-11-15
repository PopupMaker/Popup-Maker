<?php
/**
 * Campaign tags on popup editor upgrade tips.
 *
 * @package Popup_Maker
 */

/** Popup editor tips report the popup editor as their source. */
class PUM_Upsell_Utm_Test extends WP_UnitTestCase {
	/** The Close tab tip lives in the popup editor, not the theme editor. */
	public function test_close_tab_tip_uses_popup_editor_source() {
		$tabs = PUM_Upsell::popup_promotional_fields( [] );

		if ( ! isset( $tabs['close']['button']['fi_promotion'] ) ) {
			$this->markTestSkipped( 'Close tab tip hidden because Forced Interaction or Pro is enabled.' );
		}

		$content = $tabs['close']['button']['fi_promotion']['content'];
		$this->assertStringContainsString( 'utm_source=plugin-popup-editor', $content );
		$this->assertStringContainsString( 'utm_content=close-button-settings', $content );
		$this->assertStringNotContainsString( 'plugin-theme-editor', $content );
	}
}
