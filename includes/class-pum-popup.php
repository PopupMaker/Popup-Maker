<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Popup' ) ) {

	/**
	 * Class PUM_Popup
	 *
	 * @since 1.4.0
	 */
	class PUM_Popup extends PUM_Post {

		protected $required_post_type = 'popup';

		private $_cookies = null;s
		private $_triggers = null;
		private $_conditions = null;

		public function get_preview_url() {
			$args = apply_filters( 'pum_popup_preview_url_args', array(
				'printable' => true,
			),$this );
			return get_permalink( $this->ID ) . '?' . http_build_query( $args );
		}

		/**
		 * Returns whether or not the popup is visible in the loop.
		 *
		 * @return bool
		 */
		public function is_loadable() {
			if ( ! $this->ID ) {
				$loadable = false;
				// Published/private
			} elseif ( $this->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
				$loadable = false;

				// visibility setting
			} elseif ( 'hidden' === $this->visibility ) {
				$loadable = false;
			} elseif ( 'visible' === $this->visibility ) {
				$loadable = true;

				// Visibility in loop
			} elseif ( is_search() ) {
				$loadable = 'search' === $this->visibility;
			} else {
				$loadable = true;
			}

			return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
		}


	}

}
