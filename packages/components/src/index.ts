export * from './lib';

interface Popup {
	ID: number;
	post_title: string;
}

declare global {
	interface Window {
		pum_block_editor_vars: {
			popups: Popup[];
		};
	}
}
