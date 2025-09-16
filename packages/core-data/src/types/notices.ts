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
	status?: 'warning' | 'success' | 'error' | 'info' | string;
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
 * Notice object from WordPress core.
 */
export interface Notice extends WPNotice {
	/**
	 * Notice context. Unused?.
	 */
	context?: string | undefined;
	/**
	 * Notice icon.
	 */
	icon?: string | undefined;
	/**
	 * Notice explicit dismiss.
	 */
	explicitDismiss?: boolean | undefined;
	/**
	 * Notice on dismiss.
	 */
	onDismiss?: Function | undefined;
	/**
	 * Notice close delay.
	 */
	closeDelay?: number | undefined;
}

/**
 * WP notice action.
 */
export type NoticeAction = {
	/**
	 * Notice action label.
	 */
	label: string;
	/**
	 * Notice action url.
	 */
	url: string | null;
	/**
	 * Notice action onClick.
	 */
	onClick: Function | null;
};
