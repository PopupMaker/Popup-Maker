import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@popup-maker/i18n';
import { useDebounce } from '@wordpress/compose';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

import SmartTokenControl from '../smart-token-control';

import type { Post, Taxonomy } from '@wordpress/core-data';

import type { Props as SmartTokenControlProps } from '../smart-token-control';

export type Props< T extends number | number[] | string > = Omit<
	SmartTokenControlProps,
	'value' | 'onChange' | 'suggestions'
> & {
	id?: string;
	value?: T;
	onChange: ( value: T ) => void;
	multiple?: boolean;
	entityKind: 'postType' | 'taxonomy';
	entityType?: string;
	forceRefresh?: boolean;
};

interface ObjectOption extends Post< 'edit' >, Taxonomy< 'edit' > {}

const EntitySelectControl = <
	T extends number | number[] = number | number[],
>( {
	id,
	label,
	value,
	onChange,
	placeholder,
	entityKind = 'postType',
	entityType = 'post',
	multiple = false,
	forceRefresh = false,
	...inputProps
}: Props< T > ) => {
	const [ queryText, setQueryText ] = useState( '' );

	const updateQueryText = useDebounce( ( text: string ) => {
		setQueryText( text );
	}, 300 );

	const { prefill = [] } = useSelect(
		( select ) => ( {
			prefill: value
				? ( select( coreDataStore ).getEntityRecords(
						entityKind,
						entityType,
						{
							context: 'view',
							include: value,
							per_page: -1,
						}
				  ) as ObjectOption[] )
				: [],
		} ),
		[ value, entityKind, entityType, forceRefresh ]
	);

	const { suggestions = [], isSearching = false } = useSelect(
		( select ) => ( {
			suggestions: select( coreDataStore ).getEntityRecords(
				entityKind,
				entityType,
				{
					context: 'view',
					search: queryText,
					per_page: -1,
				}
			) as ObjectOption[],
			// @ts-ignore This exists and is being used as documented.
			isSearching: select( 'core/data' ).isResolving(
				'core',
				'getEntityRecords',
				[
					entityKind,
					entityType,
					{ context: 'view', search: queryText, per_page: -1 },
				]
			),
		} ),
		[ entityKind, entityType, queryText, forceRefresh ]
	);

	const findSuggestion = ( _id: number | string ) => {
		const found =
			suggestions &&
			suggestions.find(
				( suggestion ) => suggestion.id.toString() === _id.toString()
			);

		if ( found ) {
			return found;
		}

		return (
			prefill &&
			prefill.find(
				( suggestion ) => suggestion.id.toString() === _id.toString()
			)
		);
	};

	const values: string[] = ( () => {
		if ( ! value ) {
			return [];
		}

		const val = Array.isArray( value ) ? value : [ value ];

		return val.map( ( v ) => v.toString() );
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
				id={ id }
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
				multiple={ multiple }
				placeholder={
					placeholder
						? placeholder
						: sprintf(
								// translators: %s: entity type.
								__( 'Select %s(s)', 'popup-maker' ),
								entityType.replace( /_/g, ' ' ).toLowerCase()
						  )
				}
				{ ...inputProps }
				tokenOnComma={ true }
				value={ values }
				onInputChange={ updateQueryText }
				onChange={ ( newValue ) => {
					const val = newValue
						.map( ( v ) => {
							const tokenValue = getTokenValue( v );
							// Check if it's a string (likely from extraOptions)
							if (
								typeof tokenValue === 'string' &&
								! tokenValue.match( /^\d+$/ )
							) {
								return tokenValue;
							}
							// Otherwise treat as numeric ID
							const numericValue = parseInt( tokenValue, 10 );
							return isNaN( numericValue ) ? null : numericValue;
						} )
						.filter( ( v ): v is number | string => v !== null );

					onChange( ( multiple ? val : val[ 0 ] ) as T );
				} }
				renderToken={ ( token ) => {
					const suggestion = findSuggestion( getTokenValue( token ) );

					if ( ! suggestion ) {
						return getTokenValue( token );
					}

					return 'postType' === entityKind
						? decodeEntities(
								suggestion.title.rendered ??
									suggestion.title.raw
						  )
						: suggestion.name;
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
										suggestion.title.rendered ??
											suggestion.title.raw
								  )
								: suggestion.name }
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

export default EntitySelectControl;
