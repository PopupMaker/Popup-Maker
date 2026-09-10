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
		pumBuilderOwnedCanvas?: BuilderOwnedCanvasSettings;
	}

	interface BuilderOwnedCanvasSettings {
		canvas_selector: string;
		iframe_selector?: string;
		popup_id: number | string;
		overlay_classes: string;
		container_classes: string;
		content_classes: string;
		title_text: string;
		title_classes: string;
		size: string;
		location: string;
		custom_width: string;
		custom_height_auto: boolean | string;
		custom_height: string;
		responsive_min_width: string;
		responsive_max_width: string;
		position_top: string;
		position_bottom: string;
		position_left: string;
		position_right: string;
		position_fixed: boolean | string;
		scrollable: boolean | string;
		show_close: boolean | string;
		close_content: string;
		close_classes: string;
		close_label: string;
		style_selectors?: string[];
	}
}

export {};
