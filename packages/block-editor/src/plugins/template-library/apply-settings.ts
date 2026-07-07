/**
 * Bridge to the popup settings metabox (jQuery app).
 *
 * Recommended trigger & cookie presets are applied through the metabox's
 * own row APIs (`PUM_Admin.triggers.rows.add` / `PUM_Admin.cookies.rows.add`)
 * so they live in the form state and persist through the normal save flow.
 * Writing them to the REST API instead would be overwritten by the metabox
 * form on the next save.
 */

import type { TemplateRecommended } from './types';

interface PumRowsApi {
	rows: {
		add: ( editor: Element, row: Record< string, unknown > ) => void;
	};
}

interface PumAdminApi {
	triggers?: PumRowsApi;
	cookies?: PumRowsApi;
}

function getPumAdmin(): PumAdminApi | undefined {
	return ( window as unknown as { PUM_Admin?: PumAdminApi } ).PUM_Admin;
}

function getTriggerEditor(): Element | null {
	return document.querySelector( '.pum-popup-trigger-editor' );
}

function getCookieEditor(): Element | null {
	return document.querySelector( '.pum-popup-cookie-editor' );
}

/**
 * Whether the settings metabox is present & scriptable on this screen.
 *
 * @return {boolean} True when recommended settings can be applied.
 */
export function canApplySettings(): boolean {
	const pumAdmin = getPumAdmin();

	return !! (
		pumAdmin?.triggers?.rows?.add &&
		pumAdmin?.cookies?.rows?.add &&
		getTriggerEditor() &&
		getCookieEditor()
	);
}

/**
 * Replace `{popup_id}` tokens in preset settings with the real post ID.
 *
 * @param {Record<string, unknown>} settings Preset settings.
 * @param {number}                  popupId  Current popup ID.
 *
 * @return {Record<string, unknown>} Settings with tokens resolved.
 */
function resolveTokens(
	settings: Record< string, unknown >,
	popupId: number
): Record< string, unknown > {
	return JSON.parse(
		JSON.stringify( settings ).replace( /\{popup_id\}/g, `${ popupId }` )
	);
}

/**
 * Apply a template's recommended triggers & cookies to the settings metabox.
 *
 * @param {TemplateRecommended} recommended Template recommendation meta.
 * @param {number}              popupId     Current popup ID.
 *
 * @return {{triggers: number, cookies: number}} Counts of applied rows.
 */
export function applyRecommendedSettings(
	recommended: TemplateRecommended,
	popupId: number
): { triggers: number; cookies: number } {
	const pumAdmin = getPumAdmin();
	const applied = { triggers: 0, cookies: 0 };

	if ( ! pumAdmin ) {
		return applied;
	}

	const triggerEditor = getTriggerEditor();
	const cookieEditor = getCookieEditor();

	if ( triggerEditor && pumAdmin.triggers?.rows?.add ) {
		( recommended.triggers ?? [] ).forEach( ( trigger ) => {
			pumAdmin.triggers?.rows.add( triggerEditor, {
				type: trigger.type,
				settings: resolveTokens( trigger.settings ?? {}, popupId ),
			} );
			applied.triggers++;
		} );
	}

	if ( cookieEditor && pumAdmin.cookies?.rows?.add ) {
		( recommended.cookies ?? [] ).forEach( ( cookie ) => {
			pumAdmin.cookies?.rows.add( cookieEditor, {
				event: cookie.event,
				settings: resolveTokens( cookie.settings ?? {}, popupId ),
			} );
			applied.cookies++;
		} );
	}

	return applied;
}
