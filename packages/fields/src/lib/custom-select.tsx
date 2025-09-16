import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@popup-maker/i18n';
import { useDebounce } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { SmartTokenControl } from '@popup-maker/components';
import { fetchFromWPApi } from '@popup-maker/core-data';

import type { CustomSelectFieldProps, WithOnChange } from '../types';

interface CustomEntityOption {
	id: string;
	text: string;
}

const CustomSelectField = ( {
	label,
	value,
	onChange,
	entityType,
	multiple = false,
	apiEndpoint = 'popup-maker/v2/object-search',
}: WithOnChange< CustomSelectFieldProps > ) => {
	const [ queryText, setQueryText ] = useState( '' );
	const [ apiData, setApiData ] = useState< {
		prefill: CustomEntityOption[];
		suggestions: CustomEntityOption[];
	} >( {
		prefill: [],
		suggestions: [],
	} );
	const [ isLoading, setIsLoading ] = useState( false );

	const updateQueryText = useDebounce( ( text: string ) => {
		setQueryText( text );
	}, 300 );

	// Fetch data from our custom API endpoint
	useEffect( () => {
		const fetchApiData = async () => {
			setIsLoading( true );
			try {
				// Build API URL
				let apiUrl = `${ apiEndpoint }?object_type=custom_entity&entity_type=${ entityType }`;

				// Include selected values for prefill
				if ( value ) {
					const includeIds = Array.isArray( value )
						? value
						: [ value ];
					apiUrl += `&include=${ includeIds.join( ',' ) }`;
				}

				// Add search parameter if provided
				if ( queryText ) {
					apiUrl += `&s=${ queryText }`;
				}

				const response = await fetchFromWPApi< {
					items: Array< { id: string; text: string } >;
					total_count: number;
				} >( apiUrl );

				// Map API response
				const allOptions: CustomEntityOption[] = response.items.map(
					( item ) => ( {
						id: item.id,
						text: item.text,
					} )
				);

				// Extract prefill data from the same response if we have selected values
				let prefillData: CustomEntityOption[] = [];
				if ( value ) {
					const includeIds = Array.isArray( value )
						? value
						: [ value ];
					prefillData = allOptions.filter( ( item ) =>
						includeIds.includes( item.id )
					);
				}

				setApiData( {
					prefill: prefillData,
					suggestions: allOptions,
				} );
			} catch ( error ) {
				// Silently fail and set empty data
				setApiData( { prefill: [], suggestions: [] } );
			} finally {
				setIsLoading( false );
			}
		};

		fetchApiData();
	}, [ value, queryText, entityType, apiEndpoint ] );

	const findSuggestion = ( id: string ) => {
		const findInList = ( list: CustomEntityOption[] ) => {
			return list.find( ( suggestion ) => suggestion.id === id );
		};

		const found = findInList( apiData.suggestions );
		if ( found ) {
			return found;
		}

		return findInList( apiData.prefill );
	};

	const values = ( () => {
		if ( ! value ) {
			return [];
		}

		return typeof value === 'string' ? [ value ] : value;
	} )();

	const getTokenValue = ( token: string | { value: string } ) => {
		if ( typeof token === 'object' ) {
			return token.value;
		}

		return token;
	};

	return (
		<div className="pum-custom-entity-select-field">
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
				value={ values }
				onInputChange={ updateQueryText }
				onChange={ ( newValue ) => {
					const stringValues = newValue
						.map( ( v ) => getTokenValue( v ) )
						.filter( ( v ) => v !== null && v !== '' );

					onChange(
						multiple ? stringValues : stringValues[ 0 ] || ''
					);
				} }
				renderToken={ ( token ) => {
					const suggestion = findSuggestion( getTokenValue( token ) );

					if ( ! suggestion ) {
						return getTokenValue( token );
					}

					return decodeEntities( suggestion.text );
				} }
				renderSuggestion={ ( item ) => {
					const suggestion = findSuggestion( item );

					if ( ! suggestion ) {
						return item;
					}

					return <>{ decodeEntities( suggestion.text ) }</>;
				} }
				suggestions={ apiData.suggestions.map(
					( option ) => option.id
				) }
				messages={
					isLoading
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

export default CustomSelectField;
