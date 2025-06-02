import { useSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { __, sprintf } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';

import SmartTokenControl from '../smart-token-control';

import type { Props as SmartTokenControlProps } from '../smart-token-control';

export type Props< T extends number | number[] | string | string[] > = Omit<
	SmartTokenControlProps,
	'value' | 'onChange' | 'suggestions'
> & {
	id?: string;
	value?: T;
	onChange: ( value: T ) => void;
	multiple?: boolean;
};

const CallToActionSelectControl = <
	T extends number | number[] = number | number[],
>( {
	id,
	label,
	value,
	onChange,
	placeholder,
	multiple = false,
	...inputProps
}: Props< T > ) => {
	const [ queryText, setQueryText ] = useState( '' );

	const updateQueryText = useDebounce( ( text: string ) => {
		setQueryText( text );
	}, 300 );

	const { suggestions = [], isLoading = false } = useSelect( ( select ) => {
		return {
			suggestions: select( callToActionStore ).getCallToActions() || [],
			isLoading:
				select( callToActionStore ).isResolving( 'getCallToActions' ),
		};
	}, [] );

	const findSuggestion = ( _id: number | string ) => {
		return (
			suggestions &&
			suggestions.find(
				( suggestion ) => suggestion.id.toString() === _id.toString()
			)
		);
	};

	const searchResults = useMemo( () => {
		return suggestions.filter( ( suggestion ) =>
			suggestion.title.rendered
				.toLowerCase()
				.includes( queryText.toLowerCase() )
		);
	}, [ suggestions, queryText ] );

	const values: string[] = ( () => {
		if ( ! value ) {
			return [];
		}
		const vals = ! Array.isArray( value ) ? [ value ] : ( value as T[] );

		return vals.map( ( v ) => v.toString() );
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
								'Call to Action'
						  )
				}
				multiple={ multiple }
				placeholder={
					placeholder
						? placeholder
						: sprintf(
								// translators: %s: entity type.
								__( 'Select %s(s)', 'popup-maker' ),
								'call to action'
						  )
				}
				{ ...inputProps }
				tokenOnComma={ true }
				value={ values }
				onInputChange={ updateQueryText }
				onChange={ ( newValue ) => {
					const newVals =
						multiple && ! Array.isArray( newValue )
							? [ newValue ]
							: newValue;
					const val = newVals
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
					onChange( multiple ? ( val as T ) : ( val[ 0 ] as T ) );
				} }
				renderToken={ ( token ) => {
					const suggestion = findSuggestion( getTokenValue( token ) );
					if ( ! suggestion ) {
						return getTokenValue( token );
					}
					return (
						suggestion.title.rendered ??
						( suggestion.title.raw || suggestion.title.rendered )
					);
				} }
				renderSuggestion={ ( item ) => {
					const suggestion = findSuggestion( item );
					if ( ! suggestion ) {
						return item;
					}
					return (
						<>
							{ suggestion.title.rendered ??
								( suggestion.title.raw ||
									suggestion.title.rendered ) }
						</>
					);
				} }
				suggestions={
					searchResults.length
						? searchResults
								.map( ( option ) => option.id.toString() )
								.filter( ( sugId ) => sugId )
						: []
				}
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

export default CallToActionSelectControl;
