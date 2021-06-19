//import Select from 'react-select/src/Select';
/**
 * WordPress dependencies
 */
import {
	TextControl,
	SelectControl,
	RadioControl,
	RangeControl,
	ToggleControl,
	CheckboxControl,
	AnglePickerControl,
	CustomSelectControl,
	TabPanel,
	Panel,
	PanelBody,
	PanelRow,
	PanelHeader,
	ColorPicker,
	ColorIndicator,
	ColorPalette,
	DateTimePicker,
	DatePicker,
	TimePicker,
	ExternalLink,
	FontSizePicker,
	FocalPointPicker,
	Modal,
	Spinner,
	createSlotFill,
	Slot,
	Fill,
	SlotFillProvider,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal vars.
 */
const { popups } = window.pum_block_editor_vars;

export default class Fields extends Component {
	constructor() {
		super( ...arguments );

		this.getFieldValue = this.getFieldValue.bind( this );
		this.onChangeInputValue = this.onChangeInputValue.bind( this );
		this.mapFieldComponents = this.mapFieldComponents.bind( this );
	}

	/**
	 * Maps input changes to value handlers.
	 *
	 * @param {string} fieldId Key identifier for the field.
	 * @param {*} value New value for the field.
	 */
	onChangeInputValue( fieldId, value ) {
		if ( undefined !== this.props.setAttributes ) {
			this.props.setAttributes( { [ fieldId ]: value } );
		}
	}

	/**
	 * Get field value.
	 *
	 * @param {string} fieldId Key identifier for the field.
	 * @param {*} defaultValue Default value for the field.
	 */
	getFieldValue( fieldId, defaultValue = false ) {
		return this.props.values[ fieldId ] || defaultValue;
	}

	/**
	 * Converts array of field declarations to JSX components.
	 *
	 * @param {Array} fields Array of field definitions.
	 *
	 * @return {Component[]} Array of component objects.
	 */
	mapFieldComponents( fields ) {
		return Object.entries( fields.general ).map( ( [ fieldId, field ] ) => {
			field.id = fieldId;
			const type = field.type || false;
			const value = this.getFieldValue( fieldId, field.std || null );
			const onChange = ( newValue ) => {
				this.onChangeInputValue( fieldId, newValue );
			};

			let Field;

			switch ( type ) {
				case 'select':
					Field = (
						<SelectControl
							multiple={
								undefined !== field.multiple &&
								!! field.multiple
							}
							label={ field.label }
							help={ field.desc }
							value={ value } // e.g: value = [ 'a', 'c' ]
							onChange={ onChange }
							options={ Object.entries( field.options || {} ).map(
								( [ optValue, optLabel ] ) => {
									return {
										value: optValue,
										label: optLabel,
									};
								}
							) }
						/>
					);
					break;

				case 'number':
					Field = <TextControl type="number" />;
					break;

				case 'checkbox':
					Field = (
						<CheckboxControl
							heading={ field.desc } // TODO REVIEW whether this is needed.
							label={ field.label }
							help={ field.desc }
							checked={ !! value }
							onChange={ onChange }
						/>
					);
					break;
			}

			return Field;
		} );
	}

	render() {
		const fieldComponents = this.mapFieldComponents( this.props.fields );

		return fieldComponents.map( ( Field, index ) => (
			<div key={ index }>{ Field }</div>
		) );
	}
}
