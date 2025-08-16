import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@popup-maker/i18n';
import { useDebounce } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { store as coreDataStore } from '@wordpress/core-data';
import { SmartTokenControl } from '@popup-maker/components';
import { fetchFromWPApi } from '@popup-maker/core-data';

import type {
	ObjectSelectFieldProps,
	PostSelectFieldProps,
	TaxonomySelectFieldProps,
	UserSelectFieldProps,
	WithOnChange,
} from '../types';

interface ObjectOption {
	id: number;
	title?: {
		rendered?: string;
		raw?: string;
	};
	name?: string;
}

const ObjectSelectField = ( {
	label,
	value,
	onChange,
	entityKind = 'postType',
	entityType = 'post',
	multiple = false,
}: WithOnChange<
	| ObjectSelectFieldProps
	| PostSelectFieldProps
	| TaxonomySelectFieldProps
	| UserSelectFieldProps
> ) => {
	const [ queryText, setQueryText ] = useState( '' );
	const [ usePopupMakerAPI, setUsePopupMakerAPI ] = useState( false );
	const [ apiData, setApiData ] = useState< {
		prefill: ObjectOption[];
		suggestions: ObjectOption[];
	} >( {
		prefill: [],
		suggestions: [],
	} );

	const updateQueryText = useDebounce( ( text: string ) => {
		setQueryText( text );
	}, 300 );

	const { prefill = [] } = useSelect(
		( select ) => ( {
			prefill: ( () => {
				if ( usePopupMakerAPI ) {
					return apiData.prefill;
				}

				if ( ! value ) {
					return [];
				}

				const records = select( coreDataStore ).getEntityRecords(
					entityKind,
					entityType,
					{
						context: 'view',
						include: value,
						per_page: -1,
					}
				) as ObjectOption[];

				// If core-data returns null and we haven't switched to popup-maker API yet, switch now.
				if (
					records === null &&
					! usePopupMakerAPI &&
					entityKind === 'postType'
				) {
					setUsePopupMakerAPI( true );
				}

				return records || [];
			} )(),
		} ),
		[ value, entityKind, entityType, usePopupMakerAPI, apiData.prefill ]
	);

	const { suggestions = [], isSearching = false } = useSelect(
		( select ) => ( {
			suggestions: ( () => {
				if ( usePopupMakerAPI ) {
					return apiData.suggestions;
				}

				if ( entityKind === 'user' ) {
					return (
						select( coreDataStore )
							// @ts-ignore This exists and is being used as documented.
							.getUsers( {
								context: 'view',
								search: queryText,
								per_page: -1,
							} ) as ObjectOption[]
					);
				}

				const records = select( coreDataStore ).getEntityRecords(
					entityKind,
					entityType,
					{
						context: 'view',
						search: queryText,
						per_page: -1,
					}
				) as ObjectOption[];

				// If core-data returns null and we haven't switched to popup-maker API yet, switch now.
				if (
					records === null &&
					! usePopupMakerAPI &&
					entityKind === 'postType'
				) {
					setUsePopupMakerAPI( true );
				}

				return records;
			} )(),
			// @ts-ignore This exists and is being used as documented.
			isSearching: ( () => {
				if ( usePopupMakerAPI ) {
					return false; // We handle popup-maker API loading separately.
				}

				if ( entityKind === 'user' ) {
					return (
						select( 'core/data' )
							// @ts-ignore This exists and is being used as documented.
							.isResolving( 'core', 'getUsers', [
								entityKind,
								entityType,
								{
									context: 'view',
									search: queryText,
									per_page: -1,
								},
							] )
					);
				}

				return (
					select( 'core/data' )
						// @ts-ignore This exists and is being used as documented.
						.isResolving( 'core', 'getEntityRecords', [
							entityKind,
							entityType,
							{
								context: 'view',
								search: queryText,
								per_page: -1,
							},
						] )
				);
			} )(),
		} ),
		[
			queryText,
			entityKind,
			entityType,
			usePopupMakerAPI,
			apiData.suggestions,
		]
	);

	// Single effect to handle popup-maker API calls for both prefill and suggestions.
	useEffect( () => {
		if ( ! usePopupMakerAPI ) {
			return;
		}

		const fetchApiData = async () => {
			try {
				// Build API URL with optional include and search parameters.
				let apiUrl = `popup-maker/v1/object-search?object_type=post_type&object_key=${ entityType }`;

				// Always include selected values to guarantee prefill data.
				if ( value ) {
					const includeIds = Array.isArray( value ) ? value : [ value ];
					apiUrl += `&include=${ includeIds.join( ',' ) }`;
				}

				// Add search parameter if provided.
				if ( queryText ) {
					apiUrl += `&s=${ queryText }`;
				}

				const response = await fetchFromWPApi< {
					items: Array< { id: number; text: string } >;
					total_count: number;
				} >( apiUrl );

				// Map popup-maker API response to core-data format.
				const mapItems = (
					items: Array< { id: number; text: string } >
				): ObjectOption[] =>
					items.map( ( item ) => ( {
						id: item.id,
						title: {
							rendered: item.text,
						},
					} ) );

				const allOptions = mapItems( response.items );

				// Extract prefill data from the same response if we have selected values.
				let prefillData: ObjectOption[] = [];
				if ( value ) {
					const includeIds = Array.isArray( value ) ? value : [ value ];
					prefillData = allOptions.filter( ( item ) =>
						includeIds.includes( item.id )
					);
				}

				setApiData( {
					prefill: prefillData,
					suggestions: allOptions,
				} );
			} catch ( error ) {
				// Silently fail and set empty data - API fallback failed.
				setApiData( { prefill: [], suggestions: [] } );
			}
		};

		fetchApiData();
	}, [ usePopupMakerAPI, value, queryText, entityType ] );

	const findSuggestion = ( id: number | string ) => {
		const found =
			suggestions &&
			suggestions.find(
				( suggestion ) => suggestion.id.toString() === id.toString()
			);

		if ( found ) {
			return found;
		}

		return (
			prefill &&
			prefill.find(
				( suggestion ) => suggestion.id.toString() === id.toString()
			)
		);
	};

	const values = ( () => {
		if ( ! value ) {
			return [];
		}

		return typeof value === 'number' || typeof value === 'string'
			? [ value ]
			: value;
	} )();

	const getTokenValue = ( token: string | { value: string } ) => {
		if ( typeof token === 'object' ) {
			return token.value;
		}

		return token;
	};

	return (
		<div className="pum-object-search-field">
			<SmartTokenControl
				label={
					label
						? label
						: sprintf(
								// translators: %s: entity type.
								__( '%s(s)', 'popup-maker' ),
								entityType
									.replace( /_/g, ' ' )
									// uppercase first letter.
									.charAt( 0 )
									.toUpperCase() +
									entityType.replace( /_/g, ' ' ).slice( 1 )
						  )
				}
				hideLabelFromVision={ true }
				multiple={ multiple }
				placeholder={ sprintf(
					// translators: %s: entity type.
					__( 'Select %s(s)', 'popup-maker' ),
					entityType.replace( /_/g, ' ' ).toLowerCase()
				) }
				tokenOnComma={ true }
				value={ values.map( ( v ) => v.toString() ) }
				onInputChange={ updateQueryText }
				onChange={ ( newValue ) => {
					onChange(
						newValue
							.map( ( v ) => parseInt( getTokenValue( v ), 10 ) )
							.filter( ( v ) => ! isNaN( v ) )
					);
				} }
				renderToken={ ( token ) => {
					const suggestion = findSuggestion( getTokenValue( token ) );

					if ( ! suggestion ) {
						return getTokenValue( token );
					}

					return 'postType' === entityKind
						? decodeEntities( suggestion.title?.rendered || '' )
						: suggestion.name || '';
				} }
				renderSuggestion={ ( item ) => {
					const suggestion = findSuggestion( item );

					if ( ! suggestion ) {
						return item;
					}
					return (
						<>
							{ 'postType' === entityKind
								? decodeEntities(
										( suggestion.title?.rendered ??
											suggestion.title?.raw ) ||
											''
								  )
								: suggestion.name || '' }
						</>
					);
				} }
				suggestions={
					suggestions
						? suggestions.map( ( option ) => {
								return option?.id.toString() ?? false;
						  } )
						: []
				}
				messages={
					isSearching
						? {
								noSuggestions: __(
									'Searching…',
									'popup-maker'
								),
						  }
						: undefined
				}
			/>
		</div>
	);
};

export default ObjectSelectField;
