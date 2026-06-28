<?php
/**
 * Admin class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller class.
 *
 * @package PopupMaker\Controllers\Admin
 */
class Admin extends Controller {

	/**
	 * Initialize admin controller.
	 */
	public function init() {
		$this->container->register_controllers( [
			'Admin\Toolbar'              => new \PopupMaker\Controllers\Admin\Toolbar( $this->container ),
			'Admin\ToolbarNotifications' => new \PopupMaker\Controllers\Admin\ToolbarNotifications( $this->container ),
			'Admin\WP\PluginsPage'       => new \PopupMaker\Controllers\Admin\WP\PluginsPage( $this->container ),
			'Admin\CallToActions'        => new \PopupMaker\Controllers\Admin\CallToActions( $this->container ),
		] );

		add_filter( 'popup_maker/layout_vars', [ $this, 'filter_layout_vars' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Register default cross-page nav tabs for `@popup-maker/layout`.
	 *
	 * @param array<string,mixed> $vars Layout vars passed to admin JS.
	 * @return array<string,mixed>
	 */
	public function filter_layout_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			$vars = [];
		}

		if ( ! isset( $vars['navTabs'] ) || ! is_array( $vars['navTabs'] ) ) {
			$vars['navTabs'] = [];
		}

		if ( ! isset( $vars['supportMenuItems'] ) || ! is_array( $vars['supportMenuItems'] ) ) {
			$vars['supportMenuItems'] = [];
		}

		$edit_ctas_cap = $this->container->get_permission( 'edit_ctas' );

		if ( is_string( $edit_ctas_cap ) && $edit_ctas_cap && current_user_can( $edit_ctas_cap ) ) {
			$vars['navTabs'][] = [
				'id'    => 'call-to-actions',
				'title' => __( 'Call to Actions', 'popup-maker' ),
				'href'  => admin_url( 'edit.php?post_type=popup&page=popup-maker-call-to-actions' ),
			];
		}

		$vars['supportMenuItems'] = array_merge(
			[
				[
					'id'     => 'documentation',
					'label'  => __( 'View Documentation', 'popup-maker' ),
					'href'   => 'https://wppopupmaker.com/docs/?utm_campaign=plugin-support&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=view-documentation-link',
					'target' => '_blank',
					'group'  => 'primary',
					'icon'   => 'pages',
				],
				[
					'id'     => 'get-support',
					'label'  => __( 'Get Support', 'popup-maker' ),
					'href'   => 'https://wppopupmaker.com/support/?utm_campaign=plugin-support&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=get-support-link',
					'target' => '_blank',
					'group'  => 'primary',
					'icon'   => 'people',
				],
				[
					'id'    => 'grant-support-access',
					'label' => __( 'Grant Support Access', 'popup-maker' ),
					'href'  => admin_url( 'options-general.php?page=grant-popup-maker-access' ),
					'group' => 'secondary',
					'icon'  => 'login',
				],
			],
			$vars['supportMenuItems']
		);

		$vars['showSupport'] = $vars['showSupport'] ?? true;

		return $vars;
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin_assets() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'popup-maker-admin-marketing' );
		wp_enqueue_script( 'popup-maker-admin-marketing' );
	}
}
