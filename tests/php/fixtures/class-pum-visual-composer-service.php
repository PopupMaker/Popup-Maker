<?php
/**
 * Visual Composer service contract used by test mocks.
 *
 * @package Popup_Maker
 */

/** Visual Composer service methods exercised by the adapter tests. */
abstract class PUM_Visual_Composer_Service {

	/**
	 * @param int $post_id Document ID.
	 * @return void
	 */
	abstract public function addToEnqueueList( $post_id ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors Visual Composer's API.

	/**
	 * @param int $post_id Document ID.
	 * @return bool
	 */
	abstract public function canEdit( $post_id ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors Visual Composer's API.

	/**
	 * @param string   $event    Event name.
	 * @param callable $listener Event listener.
	 * @return void
	 */
	abstract public function listen( $event, $listener );
}
