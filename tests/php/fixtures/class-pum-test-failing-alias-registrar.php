<?php
/**
 * Alias registrar stub whose child controller fails to construct.
 *
 * @package Popup_Maker
 */

/**
 * Registrar stub whose revisions controller fails to construct.
 *
 * Only the controller factory is overridden, so the real
 * `register_child_routes()` runs and its `finally` restore is what the test
 * actually exercises.
 */
class PUM_Test_Failing_Alias_Registrar extends \PopupMaker\RestAPI\Alias\Registrar {

	/**
	 * The rest_base observed while the mutation was in effect.
	 *
	 * @var string|null
	 */
	public $observed_base = null;

	/**
	 * Record the mutated value, then fail as a broken controller would.
	 *
	 * @param string $post_type Post type key.
	 *
	 * @throws \RuntimeException Always, simulating a controller that cannot be built.
	 */
	protected function make_revisions_controller( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		$this->observed_base = $post_type_object->rest_base;

		throw new \RuntimeException( 'controller construction failed' );
	}
}
