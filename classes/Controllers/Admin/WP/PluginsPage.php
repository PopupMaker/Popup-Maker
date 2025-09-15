<?php
/**
 * Admin Plugins Page
 *
 * @package PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers\Admin\WP;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Plugins Page
 *
 * @since 1.21.0
 */
class PluginsPage extends Controller {

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
            $docs_url = add_query_arg( $utm_args, 'https://wppopupmaker.com/docs/' );

			$row_meta = [
				// 'review'     => '<a href="https://wordpress.org/support/plugin/popup-maker/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">★ ' . __( 'Rate 5 stars', 'popup-maker' ) . ' ★</a>',
				'documentation'    => '<a href="' . $docs_url . '" target="_blank" rel="noopener noreferrer">' . __( 'Documentation', 'popup-maker' ) . '</a>',
				'support'    => '<a href="' . $support_url . '" target="_blank" rel="noopener noreferrer">' . __( 'Get Support', 'popup-maker' ) . '</a>',
				// 'extensions' => '<a href="' . admin_url( 'edit.php?post_type=popup&page=pum-extensions' ) . '">' . __( 'Extensions', 'popup-maker' ) . '</a>',
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * Better branding.
	 *
	 * @return void
	 */
	public function footer_scripts() {
		// If is the plugins page /wp-admin/plugins.php
		global $pagenow;
		if ( 'plugins.php' === $pagenow ) {
			?>
			<script type="text/javascript" id="pum-branding">
                document.addEventListener('DOMContentLoaded', function() {
                    // Constants
                    const PRIORITY_LIST = ['Pro', 'LMS Popups', 'Ecommerce Popups'];
                    const LOGO_HTML = '<img class="pum-plugin-icon" src="<?php echo esc_url( plugins_url( 'assets/images/mark.svg', POPMAKE ) ); ?>" alt="Popup Maker Logo" />';
                    const TOGGLE_HTML = '<span class="pum-toggle-icon dashicons dashicons-arrow-down-alt2"></span>';

                    // Helper functions
                    const cleanText = text => text.replace('Popup Maker', '').replace(/[-:]/g, '').trim();
                    const getName = element => cleanText(element.querySelector('.plugin-title strong').textContent);
                    const getPriorityIndex = name => PRIORITY_LIST.findIndex(item => cleanText(item) === name);
                    const isActive = element => element.querySelector('.active') !== null;
                    const addLogo = element => {
                        const title = element.querySelector('.plugin-title');
                        title.innerHTML = LOGO_HTML + title.innerHTML;
                    };
                    const standardizeTitle = element => {
                        const name = getName(element);
                        if (!name || name === 'Pro') return;
                        element.querySelector('.plugin-title strong').textContent = `Popup Maker: ${name}`;
                    };

                    // Process main plugin if present
                    const mainPlugin = document.querySelector('tr[data-slug="popup-maker"]');

                    // Store update notices keyed by their plugin slug
                    const updateNotices = new Map();
                    document.querySelectorAll('tr.plugin-update-tr[data-slug^="popup-maker-"], tr.plugin-update-tr[data-slug^="pum-"]').forEach(notice => {
                        updateNotices.set(notice.getAttribute('data-slug'), notice);
                    });

                    // Setup main plugin toggle if present
                    if (mainPlugin) {
                        mainPlugin.classList.add('pum-main-plugin');
                        addLogo(mainPlugin);

                        const titleStrong = mainPlugin.querySelector('.plugin-title strong');
                        titleStrong.innerHTML += TOGGLE_HTML;
                        titleStrong.style.cursor = 'pointer';

                        const icon = titleStrong.querySelector('.pum-toggle-icon');
                        icon.classList.add('dashicons-arrow-down-alt2');

                        titleStrong.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isCollapsed = icon.classList.toggle('dashicons-arrow-up-alt2');
                            icon.classList.toggle('dashicons-arrow-down-alt2', !isCollapsed);

                            const display = isCollapsed ? 'none' : 'table-row';
                            document.querySelectorAll('.pum-addon-plugin').forEach(addon => {
                                addon.style.display = display;
                                const notice = updateNotices.get(addon.getAttribute('data-slug'));
                                if (notice) notice.style.display = display;
                            });
                        });
                    }

                    // Get and process addons
                    const addons = Array.from(document.querySelectorAll('tr[data-slug^="popup-maker-"]:not(.plugin-update-tr), tr[data-slug^="pum-"]:not(.plugin-update-tr)'));
                    if (!addons.length) return;

                    // Initial addon setup
                    addons.forEach(addon => {
                        addon.classList.add('pum-addon-plugin');
                        if (!mainPlugin) addon.classList.add('no-main-plugin');
                        addLogo(addon);
                        standardizeTitle(addon);
                    });

                    const insertionPoint = mainPlugin || addons[0].previousElementSibling;

                    // Sort addons.
                    addons.sort((a, b) => {
                        // Active plugins come first
                        if (isActive(a) !== isActive(b)) {
                            return isActive(b) ? -1 : 1;
                        }

                        const nameA = getName(a);
                        const nameB = getName(b);
                        const priorityA = getPriorityIndex(nameA);
                        const priorityB = getPriorityIndex(nameB);

                        // Priority list items come first
                        if (priorityA !== -1 || priorityB !== -1) {
                            return (priorityA || 999) - (priorityB || 999);
                        }

                        // Alphabetical sort
                        return nameB.localeCompare(nameA);
                    });

                    // Process each addon
                    addons.forEach(addon => {
                        addon.remove();
                        insertionPoint.parentNode.insertBefore(addon, insertionPoint.nextElementSibling);

                        const notice = updateNotices.get(addon.getAttribute('data-slug'));
                        if (notice) {
                            addon.parentNode.insertBefore(notice, addon.nextSibling);
                        }
                    });

                });
			</script>
			<?php
		}
	}

}
