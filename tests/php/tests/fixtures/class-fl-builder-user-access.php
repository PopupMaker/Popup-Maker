<?php
/**
 * Beaver Builder user access test double.
 *
 * @package Popup_Maker
 */

/** Test double for Beaver Builder's role-access API. */
class FLBuilderUserAccess {

	/** @var bool */
	public static $can_edit = true;

	/**
	 * @param string $key Access key.
	 *
	 * @return bool
	 */
	public static function current_user_can( $key ) {
		return 'builder_access' === $key && self::$can_edit;
	}
}
