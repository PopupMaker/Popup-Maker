import { applyFilters } from '@wordpress/hooks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';
import { callToActionStore, defaultCtaValues } from '@popup-maker/core-data';

import type { CallToAction } from '@popup-maker/core-data';
import type { Updatable } from '@wordpress/core-data';

export type FieldDef = {
	component: JSX.Element;
	id: keyof CallToAction[ 'settings' ];
	priority: number;
};

/**
 * The current values for the call to action in the edit store format.
 */
type CurrentValues = Updatable< CallToAction< 'edit' > >;
type CurrentSettings = CurrentValues[ 'settings' ];

const useFields = () => {
	const { updateEditorValues: updateValues } =
		useDispatch( callToActionStore );

	const { values } = useSelect( ( select ) => {
		const storeSelectors = select( callToActionStore );
		const values = storeSelectors.currentEditorValues();
		const isEditorActive = storeSelectors.isEditorActive();

		return {
			values,
			isEditorActive,
		};
	}, [] );

	const currentValues: CurrentValues = useMemo(
		() => values ?? defaultCtaValues,
		[ values ]
	);

	const { settings } = currentValues;

	/**
	 * Update settings for the given call to action.
	 *
	 * @param {Partial< CurrentValues[ 'settings' ] >} newSettings Updated settings.
	 */
	const updateSettings = useCallback(
		( newSettings: Partial< CurrentSettings > ) => {
			updateValues( {
				...currentValues,
				settings: {
					...currentValues.settings,
					...newSettings,
				},
			} );
		},
		[ updateValues, currentValues ]
	);

	const updateField = < T extends keyof CurrentSettings >(
		field: T,
		value: CurrentSettings[ T ]
	) => {
		updateSettings( {
			[ field ]: value,
		} );
	};

	const fieldIsVisible = (
		field: keyof CurrentSettings,
		tab: string
	): boolean => {
		/**
		 * Allow external overrides via a filter with null default.
		 *
		 * @param {boolean|undefined}        show     The current value of the field.
		 * @param {string}                   field    The field name.
		 * @param {CurrentSettings}          settings The current settings.
		 * @param {string}                   tab      The current tab name.
		 * @return {boolean|undefined} The new value of the field.
		 */
		const show = applyFilters(
			'popupMaker.callToActionEditor.fieldIsVisible',
			undefined,
			field,
			settings,
			tab // Tab name.
		) as boolean | undefined;

		// If the filter returned a value, use it.
		return show !== undefined ? show : true;
	};

	/**
	 * Define the fields to show in each tab.
	 *
	 * This should only be done once, so we memoize the result.
	 */
	const tabFields = useMemo( () => {
		/**
		 * Allow external overrides via a filter with null default.
		 *
		 * @param {Record<string, FieldDef[]>} fields         The current fields.
		 * @param {CurrentSettings}            settings       The current settings.
		 * @param {Function}                   updateSettings The updateSettings function.
		 *
		 * @return {Record<string, FieldDef[]>} The new fields.
		 */
		return applyFilters(
			'popupMaker.callToActionEditor.tabFields',
			{},
			settings,
			updateSettings
		) as Record< string, FieldDef[] >;
	}, [ settings, updateSettings ] );

	/**
	 * Get the fields to render.
	 *
	 * @param {string} tab The current tab name.
	 * @return {FieldDef[]} The field components.
	 */
	const getTabFields = ( tab: string ): FieldDef[] => {
		const fields = applyFilters(
			`popupMaker.callToActionEditor.tabFields.${ tab }`,
			tabFields[ tab ] ?? []
		) as FieldDef[];

		return fields
			.sort( ( a, b ) => a.priority - b.priority )
			.filter( ( field ) => fieldIsVisible( field.id, tab ) )
			.map( ( field ) => {
				/**
				 * Allow external overrides via a filter with null default.
				 *
				 * @param {JSX.Element} component The current field component.
				 * @param {string}      id        The field name.
				 * @param {string}      tab       The current tab name.
				 * @return {JSX.Element} The new field component.
				 */
				const component = applyFilters(
					'popupMaker.callToActionEditor.renderField',
					field.component,
					field.id,
					tab
				) as JSX.Element;

				return {
					...field,
					component,
				};
			} );
	};

	return {
		values,
		fieldIsVisible,
		getTabFields,
		updateSettings,
		updateField,
	};
};

export default useFields;
