declare global {
	interface PUM {
		open: ( popupId: string ) => void;
		close: ( popupId: string ) => void;
		checkConditions: ( popupId: string ) => boolean;
		clearCookies: ( popupId: string ) => void;
	}

	interface Window {
		PUM: PUM;
	}
}

export {};
