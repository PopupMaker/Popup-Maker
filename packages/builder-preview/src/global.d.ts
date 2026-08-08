declare global {
	interface JQuery {
		popmake: ( action: string ) => JQuery;
	}

	interface PUM {
		getPopup: ( popupId: number ) => JQuery;
		hooks: {
			doAction: ( hook: string, ...args: unknown[] ) => void;
		};
		initialized: boolean;
	}

	interface Window {
		PUM?: PUM;
	}
}

export {};
