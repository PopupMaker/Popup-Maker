<?php
/**
 * I18n Internationalization
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\WP;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class I18n
 *
 * @since 1.21.0
 */
class I18n extends Controller {

	/**
	 * Initializes this module.
	 */
	public function init() {
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Internationalization.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( $this->container['text_domain'], false, $this->container->get_path( 'languages' ) );
	}
}
