import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@popup-maker/i18n';
import { useDebounce } from '@wordpress/compose';
import { store as coreDataStore } from '@wordpress/core-data';
import { SmartTokenControl } from '@popup-maker/components';

import type { Post, Taxonomy } from '@wordpress/core-data';

import type {
	ObjectSelectFieldProps,
	PostSelectFieldProps,
	TaxonomySelectFieldProps,
	UserSelectFieldProps,
	WithOnChange,
} from '../types';

interface ObjectOption extends Post< 'edit' >, Taxonomy< 'edit' > {}

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
		[ value, entityKind, entityType ]
	);

	const { suggestions = [], isSearching = false } = useSelect(
		( select ) => ( {
			suggestions: ( () => {
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

				return select( coreDataStore ).getEntityRecords(
					entityKind,
					entityType,
					{
						context: 'view',
						search: queryText,
						per_page: -1,
					}
				) as ObjectOption[];
			} )(),
			// @ts-ignore This exists and is being used as documented.
			isSearching: ( () => {
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
		[ queryText, entityKind, entityType ]
	);

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
						? suggestion.title.rendered
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
								? suggestion.title.rendered ??
								  suggestion.title.raw
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

export default ObjectSelectField;
