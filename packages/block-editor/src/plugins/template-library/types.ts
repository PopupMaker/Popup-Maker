/**
 * Popup template library types.
 */

export interface RecommendedTrigger {
	type: string;
	settings: Record< string, unknown >;
}

export interface RecommendedCookie {
	event: string;
	settings: Record< string, unknown >;
}

export interface TemplateRecommended {
	triggers: RecommendedTrigger[];
	cookies: RecommendedCookie[];
	notes: string;
}

export interface PopupTemplate {
	slug: string;
	name: string;
	description: string;
	category: string;
	tier: 'free' | 'pro' | 'pro_plus';
	keywords: string[];
	viewportWidth: number;
	content: string;
	recommended: TemplateRecommended;
	proRequired: boolean;
	upgradeUrl: string;
}

export interface TemplateLibraryData {
	templates: PopupTemplate[];
	categories: Record< string, string >;
	i10n: {
		proLabel: string;
		proPlusLabel: string;
	};
}

/**
 * Get the localized template library data, if present.
 *
 * Only localized on the popup editor screen.
 */
export function getTemplateLibraryData(): TemplateLibraryData | undefined {
	const vars = (
		window as unknown as {
			popupMakerBlockEditor?: {
				templateLibrary?: TemplateLibraryData;
			};
		}
	 ).popupMakerBlockEditor;

	return vars?.templateLibrary;
}
