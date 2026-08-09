declare global {
	interface JQuery {
		popmake: ( action: string ) => JQuery;
	}

	interface PUM {
		getPopup: ( popupId: number ) => JQuery;
		initialized: boolean;
	}

	interface Window {
		PUM?: PUM;
	}
}

export {};
