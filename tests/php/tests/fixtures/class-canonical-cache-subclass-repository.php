<?php
/**
 * Extension-style popup repository used to verify subclass preservation.
 *
 * @package PopupMaker
 */

/**
 * Extension-style repository returning a custom model.
 */
class Canonical_Cache_Subclass_Repository extends \PopupMaker\Services\Repository\Popups {

	/**
	 * Instantiate the extension model.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return PUM_Model_Popup|null
	 */
	public function instantiate_model_from_post( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return new Canonical_Cache_Subclass_Popup( $post );
	}
}
