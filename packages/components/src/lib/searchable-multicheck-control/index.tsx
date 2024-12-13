import './editor.scss';

import classnames, { Argument } from 'classnames';

import {
	BaseControl,
	Button,
	CheckboxControl,
	Icon,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';

import type { IconType } from '@wordpress/components';

type Props< T extends string | number > = {
	label?: string | React.ReactNode;
	placeholder: string;
	searchIcon?: IconType | undefined;
	value: T[];
	options: { label: string; value: T; keywords?: string }[];
	onChange: ( value: T[] ) => void;
	className: Argument;
};

const SearchableMulticheckControl = < T extends string | number >( {
	label = '',
	placeholder = '',
	searchIcon,
	value = [],
	options = [],
	onChange = () => {},
	className = '',
}: Props< T > ) => {
	const instanceId = useInstanceId( SearchableMulticheckControl );

	const [ searchText, setSearchText ] = useState< string >( '' );
	const [ sortDirection, setSortDirection ] = useState< 'ASC' | 'DESC' >(
		'ASC'
	);

	const isChecked = ( optValue: T ) => value.indexOf( optValue ) !== -1;
	const toggleOption = ( optValue: T ) =>
		onChange(
			isChecked( optValue )
				? [ ...value.filter( ( v ) => v !== optValue ) ]
				: [ ...value, optValue ]
		);

	const filteredOptions = options.filter(
		( { label: optLabel, value: optValue, keywords } ) => {
			return (
				optLabel.includes( searchText ) ||
				( typeof optValue === 'string' &&
					optValue.includes( searchText ) ) ||
				( keywords && keywords.includes( searchText ) )
			);
		}
	);

	const sortedOptions = filteredOptions.sort( ( a, b ) => {
		if ( sortDirection === 'ASC' ) {
			return a.label > b.label ? 1 : -1;
		}
		return b.label > a.label ? 1 : -1;
	} );

	return (
		<BaseControl
			id={ `searchable-multicheck-control-${ instanceId }` }
			label={ label }
			className={ classnames( [
				'component-searchable-multicheck-control',
				className,
			] ) }
		>
			<div className="select-actions">
				<Button
					variant="link"
					text={ __( 'Select All', 'popup-maker' ) }
					onClick={ () => {
						// Get all current options values.
						const selected = filteredOptions.map(
							( { value: optValue } ) => optValue
						);

						// Get combined list of values, previous & all selected.
						const newValue = [ ...value, ...selected ];

						// Make list unique.
						onChange( [ ...new Set( newValue ) ] );
					} }
				/>
				<Button
					variant="link"
					text={ __( 'Deselect All', 'popup-maker' ) }
					onClick={ () => {
						// Get all current options values.
						const unSelected = filteredOptions.map(
							( { value: optValue } ) => optValue
						);

						// Get list of current values - unselected.
						const newValue = [
							...value.filter(
								( v ) => unSelected.indexOf( v ) === -1
							),
						];

						// Make list unique.
						onChange( [ ...new Set( newValue ) ] );
					} }
				/>
			</div>
			<div
				className={ classnames( [ searchIcon ? 'icon-input' : null ] ) }
			>
				<input
					type="text"
					className="components-text-control__input"
					placeholder={ placeholder }
					value={ searchText }
					onChange={ ( event ) =>
						setSearchText( event.target.value )
					}
				/>
				{ searchIcon && <Icon icon={ searchIcon } /> }
			</div>

			<table>
				<thead>
					<tr>
						<th className="label-column">
							<Button
								text={ __( 'Name', 'popup-maker' ) }
								onClick={ () =>
									setSortDirection(
										'DESC' === sortDirection
											? 'ASC'
											: 'DESC'
									)
								}
								icon={
									'DESC' === sortDirection
										? chevronUp
										: chevronDown
								}
								iconPosition="right"
							/>
						</th>
						<td className="cb-column"></td>
					</tr>
				</thead>
				<tbody>
					{ sortedOptions.map(
						( { label: optLabel, value: optValue } ) => (
							<tr key={ optValue.toString() }>
								<td>
									<span
										role="button"
										tabIndex={ -1 }
										onClick={ () =>
											toggleOption( optValue )
										}
										onKeyDown={ () => {} }
									>
										{ optLabel }
									</span>
								</td>
								<th className="cb-column">
									<CheckboxControl
										checked={ isChecked( optValue ) }
										onChange={ () =>
											toggleOption( optValue )
										}
									/>
								</th>
							</tr>
						)
					) }
				</tbody>
			</table>
		</BaseControl>
	);
};

export default SearchableMulticheckControl;
