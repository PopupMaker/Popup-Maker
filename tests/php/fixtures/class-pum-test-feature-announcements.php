<?php
/**
 * Feature announcements test double.
 *
 * @package Popup_Maker
 */

/**
 * Expose protected feature checks for query testing.
 */
class PUM_Test_Feature_Announcements extends \PopupMaker\Services\Notifications\FeatureAnnouncements {

	/**
	 * Check whether any popup uses exit intent.
	 *
	 * @return bool
	 */
	public function has_exit_intent_popup() {
		return $this->any_popup_uses_exit_intent();
	}

	/**
	 * Get popup scheduling statistics.
	 *
	 * @return array{total:int,disabled:int,stale:int}
	 */
	public function get_scheduling_stats() {
		return $this->scheduling_stats();
	}
}
