/**
 * Notice object from WordPress core.
 */
export interface WPNotice {
	/**
	 * Unique identifier of notice.
	 */
	id: string;
	/**
	 * Status of notice, one of `success`, `info`, `error`, or `warning`.
	 * Defaults to `info`.
	 */
	status?: 'warning' | 'success' | 'error' | 'info';
	/**
	 * Notice message.
	 */
	content?: string;
	/**
	 * Audibly announced message text used by assistive technologies.
	 */
	spokenMessage?: string;
	/**
	 * Notice message as raw HTML. Intended to serve primarily for compatibility of
	 * server-rendered notices, and SHOULD NOT be used for notices. It is subject to
	 * removal without notice.
	 */
	__unstableHTML?: string;
	/**
	 * Whether the notice can be dismissed by user.
	 * Defaults to `true`.
	 */
	isDismissible?: boolean;
	/**
	 * Type of notice, typically one of `default` or `snackbar`.
	 * Defaults to `default`.
	 */
	type?: string;
	/**
	 * Whether the notice content should be announced to screen readers.
	 * Defaults to `true`.
	 */
	speak?: boolean;
	/**
	 * User actions to present with notice.
	 */
	actions?: NoticeAction[];
}

/**
 * Options passed through to `@wordpress/notices::createNotice`.
 *
 * Shape matches WP's exported `NoticeOptions` so spreading into the
 * createNotice call stays type-safe. Intentionally NOT extending
 * WPNotice — NoticeOptions is a distinct narrower shape (no `status`,
 * `content`, `spokenMessage`, etc; those are separate args).
 */
export interface Notice {
	/**
	 * Context under which to group notice.
	 */
	context?: string;
	/**
	 * Identifier for notice. Auto-assigned if not specified.
	 */
	id?: string;
	/**
	 * Whether the notice can be dismissed by the user.
	 */
	isDismissible?: boolean;
	/**
	 * Type of notice.
	 */
	type?: 'default' | 'snackbar' | ( string & {} );
	/**
	 * Whether the notice content should be announced to screen readers.
	 */
	speak?: boolean;
	/**
	 * User actions presented with the notice.
	 */
	actions?: NoticeAction[];
	/**
	 * Icon displayed with the notice. Only used when type is `snackbar`.
	 */
	icon?: string | null;
	/**
	 * Whether the notice includes an explicit dismiss button. Only applies
	 * when type is set to `snackbar`.
	 */
	explicitDismiss?: boolean;
	/**
	 * Called when the notice is dismissed.
	 */
	onDismiss?: () => void;
	/**
	 * Auto-close delay (ms). Snackbar-only.
	 */
	closeDelay?: number;
}

/**
 * Predicate that narrows an incoming notice's loose `status: string` to
 * the four known literals WPNotice declares. Use when casting
 * `@wordpress/notices` output where the upstream type is wider than
 * ours and we only want to forward known-safe statuses.
 */
export const isValidNoticeStatus = (
	status: unknown
): status is NonNullable< WPNotice[ 'status' ] > =>
	status === 'warning' ||
	status === 'success' ||
	status === 'error' ||
	status === 'info';

/**
 * WP notice action. Shape matches `@wordpress/notices::NoticeAction`.
 */
export type NoticeAction = {
	/**
	 * Notice action label.
	 */
	label: string;
	/**
	 * Notice action url.
	 */
	url?: string;
	/**
	 * Notice action onClick.
	 */
	onClick?: () => void;
};
