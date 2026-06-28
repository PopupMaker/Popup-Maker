<?php
/**
 * Auto-generated "What's new in X.Y" release notification provider.
 *
 * Maintains a single notification slot that tracks the most recent
 * major.minor release the user has seen the changelog for. When a
 * newer release ships before the user dismisses the previous
 * announcement, the slot is overwritten but the oldest-unseen version
 * is carried forward as the "since" floor so the user can still catch
 * up on everything they missed in one view.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services\Notifications;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * WhatsNew notification provider.
 *
 * @since 1.23.0
 */
class WhatsNew extends Service implements Provider {

	/**
	 * Option storing { latest: string, since: string|null } for the
	 * pending release notification slot.
	 *
	 * @var string
	 */
	const SLOT_OPTION = 'pum_whats_new_slot';

	/**
	 * Option storing the last major.minor release the user dismissed.
	 *
	 * @var string
	 */
	const LAST_SEEN_OPTION = 'pum_whats_new_last_seen';

	/**
	 * Prefix for the alert code. Actual code is suffixed with the current
	 * major.minor (e.g. `pm_whats_new_release_1_22`) so dismissing one
	 * version's notification doesn't silently suppress future versions —
	 * each release gets its own dismissal entry in `_pum_dismissed_alerts`.
	 *
	 * @var string
	 */
	const ALERT_CODE_PREFIX = 'pm_whats_new_release';

	/**
	 * Maximum highlight bullets shown inline.
	 *
	 * @var int
	 */
	const HIGHLIGHT_LIMIT = 6;

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'popup_maker/update_version', [ $this, 'on_version_update' ], 10, 2 );
		add_filter( 'pum_alert_list', [ $this, 'register_alert' ], 12 );
		add_action( 'pum_alert_dismissed', [ $this, 'on_dismiss' ], 10, 2 );
	}

	/**
	 * Handle a plugin version bump.
	 *
	 * @param string $old_version Previous version string.
	 * @param string $new_version New version string.
	 * @return void
	 */
	public function on_version_update( $old_version, $new_version ) {
		$new_mm = $this->major_minor( $new_version );
		$old_mm = $this->major_minor( $old_version );

		if ( '' === $new_mm ) {
			return;
		}

		// Ignore patch-only bumps — panel cares about major.minor granularity.
		if ( $new_mm === $old_mm ) {
			return;
		}

		$slot = $this->get_slot();

		/*
		 * Carry forward the "since" floor in priority order:
		 *   1. Pending slot already has a since (oldest unseen wins).
		 *   2. User's previous running version.
		 *   3. Last dismissed major.minor (survives activate/deactivate
		 *      cycles where `$old_version` resets to empty).
		 */
		$since = $slot['since'] ? $slot['since'] : $old_mm;
		if ( ! $since ) {
			$last_seen = (string) get_option( self::LAST_SEEN_OPTION, '' );
			$since     = $this->major_minor( $last_seen );
		}

		$this->set_slot( [
			'latest' => $new_mm,
			'since'  => $since ? $since : null,
		] );
	}

	/**
	 * Register the release notification if the slot is populated.
	 *
	 * @param array $alerts Current alert list.
	 * @return array
	 */
	public function register_alert( $alerts ) {
		$slot = $this->get_slot();

		if ( empty( $slot['latest'] ) ) {
			return $alerts;
		}

		$latest = (string) $slot['latest'];
		$since  = isset( $slot['since'] ) ? (string) $slot['since'] : '';

		if ( $since && $since !== $latest ) {
			/* translators: %s: version number they last dismissed notes for. */
			$subtitle = sprintf( __( 'since v%s', 'popup-maker' ), $since );
		} else {
			/* translators: %s: new version. */
			$subtitle = sprintf( __( 'v%s', 'popup-maker' ), $latest );
		}

		$lead = $since && $since !== $latest
			? sprintf(
				/* translators: 1: new version, 2: last-seen version */
				__( 'Popup Maker <strong>v%1$s</strong> is here — plus everything added since v%2$s.', 'popup-maker' ),
				$latest,
				$since
			)
			: sprintf(
				/* translators: %s: new version. */
				__( 'Popup Maker <strong>v%s</strong> is here.', 'popup-maker' ),
				$latest
			);

		$highlights = $this->parse_highlights( $latest, $since );
		$message    = '<p>' . $lead . '</p>' . $highlights;

		$alerts[] = [
			'code'        => $this->alert_code_for( $latest ),
			'category'    => 'feature',
			'priority'    => 90,
			'title'       => sprintf(
				/* translators: %s: version number. */
				__( 'What\'s new in Popup Maker %s', 'popup-maker' ),
				$latest
			),
			'message'     => $message,
			'subtitle'    => $subtitle,
			'icon'        => 'star-filled',
			'dismissible' => true,
			'actions'     => $this->build_actions(),
		];

		return $alerts;
	}

	/**
	 * Build the action list for the release notification.
	 *
	 * Picks a target appropriate for the current user's capabilities —
	 * WordPress's plugin-information screen requires `install_plugins`,
	 * so users without it (e.g. editors with a custom `edit_popups`
	 * grant) get the public changelog on wppopupmaker.com instead.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function build_actions() {
		if ( current_user_can( 'install_plugins' ) ) {
			$view = [
				'text'    => __( 'View changelog', 'popup-maker' ),
				'type'    => 'iframe',
				'action'  => '',
				'href'    => $this->changelog_url(),
				'primary' => true,
			];
		} else {
			$view = [
				'text'     => __( 'View changelog', 'popup-maker' ),
				'type'     => 'link',
				'action'   => '',
				'href'     => $this->public_changelog_url(),
				'primary'  => true,
				'external' => true,
			];
		}

		return [
			$view,
			[
				'text'   => __( 'Dismiss', 'popup-maker' ),
				'type'   => 'action',
				'action' => 'dismiss',
			],
		];
	}

	/**
	 * Persist "last seen" when the release slot is dismissed.
	 *
	 * @param string $code   Alert code that was dismissed.
	 * @param string $action Action name.
	 * @return void
	 */
	public function on_dismiss( $code, $action = '' ) {
		unset( $action );

		if ( 0 !== strpos( (string) $code, self::ALERT_CODE_PREFIX . '_' ) ) {
			return;
		}

		$slot = $this->get_slot();

		if ( ! empty( $slot['latest'] ) ) {
			update_option( self::LAST_SEEN_OPTION, (string) $slot['latest'], false );
		}

		delete_option( self::SLOT_OPTION );
	}

	/**
	 * Build the version-scoped alert code for a given major.minor.
	 *
	 * @param string $major_minor Version string like "1.22".
	 * @return string
	 */
	protected function alert_code_for( $major_minor ) {
		return self::ALERT_CODE_PREFIX . '_' . str_replace( '.', '_', (string) $major_minor );
	}

	/**
	 * Reduce a version string to major.minor.
	 *
	 * @param string $version Version string like "1.22.3".
	 * @return string "1.22" or '' if unparseable.
	 */
	protected function major_minor( $version ) {
		if ( ! is_string( $version ) || '' === $version ) {
			return '';
		}

		$parts = explode( '.', $version );

		if ( count( $parts ) < 2 ) {
			return '';
		}

		$major = (int) $parts[0];
		$minor = (int) $parts[1];

		if ( $major < 0 || $minor < 0 ) {
			return '';
		}

		return $major . '.' . $minor;
	}

	/**
	 * Get current slot state.
	 *
	 * @return array{latest:string,since:string|null}
	 */
	protected function get_slot() {
		$slot = get_option( self::SLOT_OPTION, [] );

		if ( ! is_array( $slot ) ) {
			return [
				'latest' => '',
				'since'  => null,
			];
		}

		return [
			'latest' => isset( $slot['latest'] ) ? (string) $slot['latest'] : '',
			'since'  => isset( $slot['since'] ) && '' !== $slot['since'] ? (string) $slot['since'] : null,
		];
	}

	/**
	 * Persist the slot.
	 *
	 * @param array $slot {latest:string, since:string|null}.
	 * @return void
	 */
	protected function set_slot( array $slot ) {
		update_option( self::SLOT_OPTION, $slot, false );
	}

	/**
	 * Render Features + Improvements bullets from readme.txt for versions
	 * between $since and $latest as an HTML fragment.
	 *
	 * @param string $latest Target major.minor version.
	 * @param string $since  Last-seen major.minor version, or ''.
	 * @return string HTML fragment, empty when nothing to show.
	 */
	protected function parse_highlights( $latest, $since ) {
		$locale    = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$cache_key = 'pum_whats_new_highlights_' . md5( $latest . '|' . $since . '|' . $locale );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$items = $this->collect_highlight_items( $latest, $since );
		$html  = $items ? $this->render_highlights( $items ) : '';

		/*
		 * Short TTL when empty so a readme tweak surfaces quickly; long
		 * TTL when we have content (invalidated naturally by cache key
		 * changing whenever $latest/$since does, i.e. on version bump).
		 */
		set_transient( $cache_key, $html, $html ? DAY_IN_SECONDS : HOUR_IN_SECONDS );

		return $html;
	}

	/**
	 * Collect all Features + Improvements bullets from readme.txt for
	 * every major.minor section within [since, latest].
	 *
	 * @param string $latest Target major.minor version.
	 * @param string $since  Floor major.minor version.
	 * @return array<int,string>
	 */
	protected function collect_highlight_items( $latest, $since ) {
		$path = $this->readme_path();
		if ( '' === $path || ! is_readable( $path ) ) {
			return [];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $path );
		if ( ! $raw ) {
			return [];
		}

		if ( ! preg_match_all(
			'/^=\s*(\d+\.\d+(?:\.\d+)?)[^=]*=\s*$(.*?)(?=^=\s*\d+\.\d+|\z)/ms',
			$raw,
			$matches,
			PREG_SET_ORDER
		) ) {
			return [];
		}

		$floor = ( $since && $since !== $latest ) ? $since : $latest;
		$items = [];

		foreach ( $matches as $match ) {
			$section_mm = $this->major_minor( $match[1] );

			if ( '' === $section_mm
				|| version_compare( $section_mm, $floor, '<' )
				|| version_compare( $section_mm, $latest, '>' )
			) {
				continue;
			}

			foreach ( [ 'Features', 'Improvements' ] as $heading ) {
				$items = array_merge( $items, $this->extract_bullets( $match[2], $heading ) );
			}
		}

		return $items;
	}

	/**
	 * Render a list of highlight strings as HTML.
	 *
	 * @param array<int,string> $items All collected bullet strings.
	 * @return string
	 */
	protected function render_highlights( array $items ) {
		$shown     = array_slice( $items, 0, self::HIGHLIGHT_LIMIT );
		$remaining = count( $items ) - count( $shown );

		$html = '<ul><li>' . implode( '</li><li>', $shown ) . '</li></ul>';

		if ( $remaining > 0 ) {
			$html .= '<p><em>' . sprintf(
				/* translators: %d: additional changes not shown. */
				_n( '…and %d more change.', '…and %d more changes.', $remaining, 'popup-maker' ),
				$remaining
			) . '</em></p>';
		}

		return $html;
	}

	/**
	 * Extract bullets under a given section heading.
	 *
	 * @param string $body    Version section body.
	 * @param string $heading Section heading.
	 * @return array<int,string>
	 */
	protected function extract_bullets( $body, $heading ) {
		/*
		 * The lookahead must accept multi-word bold headings like
		 * "**Bug Fixes**" or "**Breaking Changes**" — the original
		 * `[A-Za-z]+` terminated after one word, letting a Features block
		 * swallow later sections whole.
		 */
		$pattern = '/\*\*' . preg_quote( $heading, '/' ) . '\*\*(.*?)(?=\*\*[A-Za-z][A-Za-z0-9 \'\-]*\*\*|\z)/s';
		if ( ! preg_match( $pattern, $body, $section_match ) ) {
			return [];
		}

		$section = $section_match[1];
		$out     = [];

		if ( preg_match_all( '/^-\s+(.+?)$/m', $section, $bullet_matches ) ) {
			foreach ( $bullet_matches[1] as $bullet ) {
				$line = trim( $bullet );
				if ( '' === $line ) {
					continue;
				}

				// Trim at first sentence break.
				if ( preg_match( '/^(.+?[.!?])\s/', $line, $sentence ) ) {
					$line = $sentence[1];
				}

				$line = preg_replace_callback(
					'/\[([^\]]+)\]\(([^)]+)\)/',
					function ( $m ) {
						return '<a href="' . esc_url( $m[2] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $m[1] ) . '</a>';
					},
					$line
				);

				$out[] = $line;
			}
		}

		return $out;
	}

	/**
	 * Path to the plugin's readme.txt file.
	 *
	 * @return string
	 */
	protected function readme_path() {
		$path = $this->container->get_path( 'readme.txt' );

		return is_string( $path ) ? $path : '';
	}

	/**
	 * Build the WordPress plugin-information changelog URL.
	 *
	 * `TB_iframe=true` tells WordPress to render the lightweight iframe
	 * variant of the plugin information screen (no full admin chrome).
	 * We render this inside our own Modal iframe, so we don't need the
	 * actual Thickbox JS library on the parent page.
	 *
	 * @return string
	 */
	protected function changelog_url() {
		return add_query_arg(
			[
				'tab'       => 'plugin-information',
				'plugin'    => 'popup-maker',
				'section'   => 'changelog',
				'TB_iframe' => 'true',
				'width'     => 900,
				'height'    => 700,
			],
			self_admin_url( 'plugin-install.php' )
		);
	}

	/**
	 * Fallback changelog URL for users who can't access the WP plugin
	 * information screen (no `install_plugins` capability). Opens the
	 * public changelog on wppopupmaker.com in a new tab.
	 *
	 * @return string
	 */
	protected function public_changelog_url() {
		return add_query_arg(
			[
				'utm_source'   => 'pm-notifications',
				'utm_medium'   => 'panel',
				'utm_campaign' => 'whats-new',
			],
			'https://wppopupmaker.com/changelog/'
		);
	}
}
