export type NotificationCategory =
	| 'announcement'
	| 'feature'
	| 'recommendation'
	| 'warning'
	| 'offer';

export type NotificationType = 'info' | 'success' | 'warning' | 'error';

export interface NotificationAction {
	text: string;
	// link   → plain anchor navigation
	// action → POST to dismissal endpoint (optionally with expires)
	// iframe → open href in an in-panel modal iframe instead of navigating
	type: 'link' | 'action' | 'iframe';
	action: string;
	href?: string;
	primary?: boolean;
	// Explicit opt-in to open the link in a new tab. Internal admin links
	// default to same-tab navigation; set `target: '_blank'` (or `external:
	// true`) on actions that point at docs, upgrade pages, or any off-site
	// URL so we can attach the correct `rel` noopener/noreferrer pair.
	target?: '_blank' | '_self';
	external?: boolean;
	// Relative time expression forwarded to dismissal handler (e.g. "30 days").
	// Empty string / omitted = permanent dismissal.
	expires?: string;
}

export interface Notification {
	code: string;
	type: NotificationType;
	category: NotificationCategory;
	priority: number;
	title: string;
	message: string;
	html: string;
	dismissible: boolean;
	global: boolean;
	actions: NotificationAction[];
	// Optional per-card metadata used by the Hybrid design.
	icon?: string; // Dashicon name without the "dashicons-" prefix.
	subtitle?: string; // Small right-aligned text in the head row (time, version, etc.).
}
