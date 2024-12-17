export * from './lib';

interface Popup {
	ID: number;
	post_title: string;
}

declare global {
	interface Window {
		popupMakerComponents: {
			popups: Popup[];
		};
	}
}
