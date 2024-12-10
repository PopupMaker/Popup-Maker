<?php
/**
 * Admin Plugins Page
 *
 * @package PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Admin\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Plugins Page
 *
 * @since X.X.X
 */
class PluginsPage {

    /**
     * UTM arguments.
     *
     * @var array<string,string>
     */
    private $utm_args = [
        'utm_source' => 'plugins-page',
        'utm_medium' => 'plugin-ui',
        'utm_campaign' => '',
    ];

    /**
     * Register actions.
     */
    public function init() {
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		add_filter( 'network_admin_plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
        add_filter( 'network_admin_plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
        add_action( 'admin_print_footer_scripts', [ $this, 'footer_scripts' ] );
    }

	/**
	 * Render plugin action links.
	 *
	 * @param array<string,string> $links Existing links.
	 * @param string $file Plugin file path.
	 *
	 * @return array<string,string> Filtered links.
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( POPMAKE ) === $file ) {
            $utm_args = wp_parse_args( $this->utm_args, [
                'utm_campaign' => 'action-links',
            ] );

            $settings_url = admin_url( 'edit.php?post_type=popup&page=pum-settings' );
            $upgrade_url = add_query_arg( $utm_args, 'https://wppopupmaker.com/pricing/' );
            $docs_url = add_query_arg( $utm_args, 'https://wppopupmaker.com/docs/' );

			$plugin_action_links = apply_filters(
				'pum_plugin_action_links',
				[
					'settings' => '<a href="' . $settings_url . '">' . __( 'Settings', 'popup-maker' ) . '</a>',
					'docs'     => '<a href="' . $docs_url . '" target="_blank">' . __( 'Docs', 'popup-maker' ) . '</a>',
					'upgrade'  => '<a href="' . $upgrade_url . '" target="_blank" style="color: #00a32a; font-weight: bold;">' . __( 'Upgrade to Pro', 'popup-maker' ) . '</a>',
				]
			);

			if ( is_plugin_active( 'popup-maker-pro/popup-maker-pro.php' ) ) {
				unset( $plugin_action_links['upgrade'] );
			}

			// Check if translation link should be shown
			if ( is_locale_switched() /* && current_user_can( 'install_languages'  ) */ ) {
				$plugin_action_links = array_merge( [ 'translate' => '<a href="' . sprintf( 'https://translate.wordpress.org/locale/%s/default/wp-plugins/popup-maker', substr( get_locale(), 0, 2 ) ) . '" target="_blank">' . __( 'Translate', 'popup-maker' ) . '</a>' ], $plugin_action_links );
			}

			foreach ( $plugin_action_links as $link ) {
				array_unshift( $links, $link );
			}
		}

		return $links;
	}

	/**
	 * Filters the array of row meta for each plugin in the Plugins list table.
	 *
	 * @param array<string,string> $plugin_meta An array of the plugin's metadata.
	 * @param string               $plugin_file Path to the plugin file.
	 *
	 * @return array<string,string> Filtered row meta
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( POPMAKE ) === $plugin_file ) {
            $utm_args = wp_parse_args( $this->utm_args, [
                'utm_campaign' => 'row-meta',
            ] );

            $support_url = add_query_arg( $utm_args, 'https://wppopupmaker.com/support/' );

			$row_meta = [
				'review'     => '<a href="https://wordpress.org/support/plugin/popup-maker/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">' . __( 'Rate 5 stars', 'popup-maker' ) . '</a>',
				'support'    => '<a href="' . $support_url . '" target="_blank" rel="noopener noreferrer">' . __( 'Get Support', 'popup-maker' ) . '</a>',
				'extensions' => '<a href="' . admin_url( 'edit.php?post_type=popup&page=pum-extensions' ) . '">' . __( 'Extensions', 'popup-maker' ) . '</a>',
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * Better branding
	 *
	 * @return void
	 */
	public function footer_scripts() {
		// If is the plugins page /wp-admin/plugins.php
		global $pagenow;
		if ( 'plugins.php' === $pagenow ) {
			?>
			<script type="text/javascript" id="pum-branding">
				document.querySelectorAll('tr[data-slug^="popup-maker"], tr[data-slug^="popup-maker-"], tr[data-slug^="pum-"]').forEach(function(el){
					// Skip the main popup-maker plugin
					if (el.getAttribute('data-slug') !== 'popup-maker') {
						el.style.backgroundColor = '#f9f9f9';
						el.querySelector('td.plugin-title div.name').style.paddingLeft = '1.5rem';
					}
					el.querySelector('td.plugin-title').innerHTML = '<img src="<?php echo esc_url( plugins_url( 'assets/images/mark.svg', POPMAKE ) ); ?>" alt="<?php esc_attr_e( 'Popup Maker', 'popup-maker' ); ?>" style="width: 1rem;height: 1rem;margin-right: -5px;top: 2px;position: relative;">' + el.querySelector('td.plugin-title').innerHTML;
				});
			</script>
			<?php
		}
	}

}