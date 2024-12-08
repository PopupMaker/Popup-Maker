/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	var forms = {
		init: function () {
			forms.checkDependencies();
		},
		/**
		 * dependencies should look like this:
		 *
		 * {
		 *   field_name_1: value, // Select, radio etc.
		 *   field_name_2: true // Checkbox
		 * }
		 *
		 * Support for Multiple possible values of one field
		 *
		 * {
		 *   field_name_1: [ value_1, value_2 ]
		 * }
		 *
		 */
		checkDependencies: function ( $dependent_fields ) {
			var _fields = $( $dependent_fields );

			// If no fields passed, only do those not already initialized.
			$dependent_fields = _fields.length
				? _fields
				: $(
						'[data-pum-dependencies]:not([data-pum-processed-dependencies])'
				  );

			$dependent_fields.each( function () {
				var $dependent = $( this ),
					dependentID = $dependent.data( 'id' ),
					// The dependency object for this field.
					dependencies =
						$dependent.data( 'pum-processed-dependencies' ) || {},
					// Total number of fields this :input is dependent on.
					requiredCount = Object.keys( dependencies ).length,
					// Current count of fields this :input matched properly.
					count = 0,
					// An array of fields this :input is dependent on.
					dependentFields = $dependent.data( 'pum-dependent-fields' ),
					// Early declarations.
					key;

				// Clean up & pre-process dependencies so we don't need to rebuild each time.
				if ( ! $dependent.data( 'pum-processed-dependencies' ) ) {
					dependencies = $dependent.data( 'pum-dependencies' );
					if ( typeof dependencies === 'string' ) {
						dependencies = JSON.parse( dependencies );
					}

					// Convert each key to an array of acceptable values.
					for ( key in dependencies ) {
						if ( dependencies.hasOwnProperty( key ) ) {
							if ( typeof dependencies[ key ] === 'string' ) {
								// Leave boolean values alone as they are for checkboxes or checking if an input has any value.

								if (
									dependencies[ key ].indexOf( ',' ) !== -1
								) {
									dependencies[ key ] =
										dependencies[ key ].split( ',' );
								} else {
									dependencies[ key ] = [
										dependencies[ key ],
									];
								}
							} else if (
								typeof dependencies[ key ] === 'number'
							) {
								dependencies[ key ] = [ dependencies[ key ] ];
							}
						}
					}

					// Update cache & counts.
					requiredCount = Object.keys( dependencies ).length;
					$dependent
						.data( 'pum-processed-dependencies', dependencies )
						.attr(
							'data-pum-processed-dependencies',
							dependencies
						);
				}

				if ( ! dependentFields ) {
					dependentFields = $.map(
						dependencies,
						function ( value, index ) {
							var $wrapper = $(
								'.pum-field[data-id="' + index + '"]'
							);

							return $wrapper.length ? $wrapper.eq( 0 ) : null;
						}
					);

					$dependent.data( 'pum-dependent-fields', dependentFields );
				}

				$( dependentFields ).each( function () {
					var $wrapper = $( this ),
						$field = $wrapper.find( ':input:first' ),
						id = $wrapper.data( 'id' ),
						value = $field.val(),
						required = dependencies[ id ],
						matched,
						// Used for limiting the fields that get updated when this field is changed.
						all_this_fields_dependents =
							$wrapper.data( 'pum-field-dependents' ) || [];

					if (
						all_this_fields_dependents.indexOf( dependentID ) === -1
					) {
						all_this_fields_dependents.push( dependentID );
						$wrapper.data(
							'pum-field-dependents',
							all_this_fields_dependents
						);
					}

					// If no required values found bail early.
					if (
						typeof required === 'undefined' ||
						required === null
					) {
						$dependent
							.removeClass( 'pum-dependencies-met' )
							.hide( 0 )
							.trigger( 'pumFormDependencyUnmet' );
						// Effectively breaks the .each for this $dependent and hides it.
						return false;
					}

					if ( $wrapper.hasClass( 'pum-field-radio' ) ) {
						value = $wrapper.find( ':input:checked' ).val();
					}

					if ( $wrapper.hasClass( 'pum-field-multicheck' ) ) {
						value = [];
						$wrapper
							.find( ':checkbox:checked' )
							.each( function ( i ) {
								value[ i ] = $( this ).val();

								if (
									typeof value[ i ] === 'string' &&
									! isNaN( parseInt( value[ i ] ) )
								) {
									value[ i ] = parseInt( value[ i ] );
								}
							} );
					}

					// Check if the value matches required values.
					if (
						$wrapper.hasClass( 'pum-field-select' ) ||
						$wrapper.hasClass( 'pum-field-radio' )
					) {
						matched = required && required.indexOf( value ) !== -1;
					} else if ( $wrapper.hasClass( 'pum-field-checkbox' ) ) {
						matched = required === $field.is( ':checked' );
					} else if ( $wrapper.hasClass( 'pum-field-multicheck' ) ) {
						if ( Array.isArray( required ) ) {
							matched = false;
							for ( var i = 0; i < required.length; i++ ) {
								if ( value.indexOf( required[ i ] ) !== -1 ) {
									matched = true;
								}
							}
						} else {
							matched = value.indexOf( required ) !== -1;
						}
					} else {
						matched = Array.isArray( required )
							? required.indexOf( value ) !== -1
							: required == value;
					}

					if ( matched ) {
						count++;
					} else {
						$dependent
							.removeClass( 'pum-dependencies-met' )
							.hide( 0 )
							.trigger( 'pumFormDependencyUnmet' );
						// Effectively breaks the .each for this $dependent and hides it.
						return false;
					}

					if ( count === requiredCount ) {
						$dependent
							.addClass( 'pum-dependencies-met' )
							.show( 0 )
							.trigger( 'pumFormDependencyMet' );
					}
				} );
			} );
		},
		form_check: function () {
			$( document ).trigger( 'pum_form_check' );
		},
		is_field: function ( data ) {
			if ( typeof data !== 'object' ) {
				return false;
			}

			var field_tests = [
				data.type === undefined &&
					( data.label !== undefined || data.desc !== undefined ),
				data.type !== undefined && typeof data.type === 'string',
			];

			return field_tests.indexOf( true ) >= 0;
		},
		flattenFields: function ( data ) {
			var form_fields = {},
				tabs = data.tabs || {},
				sections = data.sections || {},
				fields = data.fields || {};

			if (
				Object.keys( tabs ).length &&
				Object.keys( sections ).length
			) {
				// Loop Tabs
				_.each( fields, function ( subTabs, tabID ) {
					// If not a valid tab or no subsections skip it.
					if (
						typeof subTabs !== 'object' ||
						! Object.keys( subTabs ).length
					) {
						return;
					}

					// Loop Tab Sections
					_.each( subTabs, function ( subTabFields, subTabID ) {
						// If not a valid subtab or no fields skip it.
						if (
							typeof subTabFields !== 'object' ||
							! Object.keys( subTabFields ).length
						) {
							return;
						}

						// Move single fields into the main subtab.
						if ( forms.is_field( subTabFields ) ) {
							var newSubTabFields = {};
							newSubTabFields[ subTabID ] = subTabFields;
							subTabID = 'main';
							subTabFields = newSubTabFields;
						}

						// Loop Tab Section Fields
						_.each( subTabFields, function ( field ) {
							// Store the field by id for easy lookup later.
							form_fields[ field.id ] = field;
						} );
					} );
				} );
			} else if ( Object.keys( tabs ).length ) {
				// Loop Tabs
				_.each( fields, function ( tabFields, tabID ) {
					// If not a valid tab or no subsections skip it.
					if (
						typeof tabFields !== 'object' ||
						! Object.keys( tabFields ).length
					) {
						return;
					}

					// Loop Tab Fields
					_.each( tabFields, function ( field ) {
						// Store the field by id for easy lookup later.
						form_fields[ field.id ] = field;
					} );
				} );
			} else if ( Object.keys( sections ).length ) {
				// Loop Sections
				_.each( fields, function ( sectionFields, sectionID ) {
					// Loop Tab Section Fields
					_.each( sectionFields, function ( field ) {
						// Store the field by id for easy lookup later.
						form_fields[ field.id ] = field;
					} );
				} );
			} else {
				fields = forms.parseFields( fields, values );

				// Replace the array with rendered fields.
				_.each( fields, function ( field ) {
					// Store the field by id for easy lookup later.
					form_fields[ field.id ] = field;
				} );
			}

			return form_fields;
		},
		parseFields: function ( fields, values ) {
			values = values || {};

			_.each( fields, function ( field, fieldID ) {
				fields[ fieldID ] = PUM_Admin.models.field( field );

				if ( typeof fields[ fieldID ].meta !== 'object' ) {
					fields[ fieldID ].meta = {};
				}

				if ( undefined !== values[ fieldID ] ) {
					fields[ fieldID ].value = values[ fieldID ];
				}

				if ( fields[ fieldID ].id === '' ) {
					fields[ fieldID ].id = fieldID;
				}
			} );

			return fields;
		},
		renderTab: function () {},
		renderSection: function () {},
		render: function ( args, values, $container ) {
			var form,
				sections = {},
				section = [],
				form_fields = {},
				data = $.extend(
					true,
					{
						id: '',
						tabs: {},
						sections: {},
						fields: {},
						maintabs: {},
						subtabs: {},
					},
					args
				),
				maintabs = $.extend(
					{
						id: data.id,
						classes: [],
						tabs: {},
						vertical: true,
						form: true,
						meta: {
							'data-min-height': 250,
						},
					},
					data.maintabs
				),
				subtabs = $.extend(
					{
						classes: [ 'link-tabs', 'sub-tabs' ],
						tabs: {},
					},
					data.subtabs
				),
				container_classes = [ 'pum-dynamic-form' ];

			values = values || {};

			if (
				Object.keys( data.tabs ).length &&
				Object.keys( data.sections ).length
			) {
				container_classes.push( 'tabbed-content' );

				// Loop Tabs
				_.each( data.fields, function ( subTabs, tabID ) {
					// If not a valid tab or no subsections skip it.
					if (
						typeof subTabs !== 'object' ||
						! Object.keys( subTabs ).length
					) {
						return;
					}

					// Define this tab.
					if ( undefined === maintabs.tabs[ tabID ] ) {
						maintabs.tabs[ tabID ] = {
							label: data.tabs[ tabID ],
							content: '',
						};
					}

					// Define the sub tabs model.
					subtabs = $.extend( subtabs, {
						id: data.id + '-' + tabID + '-subtabs',
						tabs: {},
					} );

					// Loop Tab Sections
					_.each( subTabs, function ( subTabFields, subTabID ) {
						// If not a valid subtab or no fields skip it.
						if (
							typeof subTabFields !== 'object' ||
							! Object.keys( subTabFields ).length
						) {
							return;
						}

						// Move single fields into the main subtab.
						if ( forms.is_field( subTabFields ) ) {
							var newSubTabFields = {};
							newSubTabFields[ subTabID ] = subTabFields;
							subTabID = 'main';
							subTabFields = newSubTabFields;
						}

						// Define this subtab model.
						if ( undefined === subtabs.tabs[ subTabID ] ) {
							subtabs.tabs[ subTabID ] = {
								label: data.sections[ tabID ][ subTabID ],
								content: '',
							};
						}

						subTabFields = forms.parseFields(
							subTabFields,
							values
						);

						// Loop Tab Section Fields
						_.each( subTabFields, function ( field ) {
							// Store the field by id for easy lookup later.
							form_fields[ field.id ] = field;

							// Push rendered fields into the subtab content.
							subtabs.tabs[ subTabID ].content +=
								PUM_Admin.templates.field( field );
						} );

						// Remove any empty tabs.
						if ( '' === subtabs.tabs[ subTabID ].content ) {
							delete subtabs.tabs[ subTabID ];
						}
					} );

					// If there are subtabs, then render them into the main tabs content, otherwise remove this main tab.
					if ( Object.keys( subtabs.tabs ).length ) {
						maintabs.tabs[ tabID ].content =
							PUM_Admin.templates.tabs( subtabs );
					} else {
						delete maintabs.tabs[ tabID ];
					}
				} );

				if ( Object.keys( maintabs.tabs ).length ) {
					form = PUM_Admin.templates.tabs( maintabs );
				}
			} else if ( Object.keys( data.tabs ).length ) {
				container_classes.push( 'tabbed-content' );

				// Loop Tabs
				_.each( data.fields, function ( tabFields, tabID ) {
					// If not a valid tab or no subsections skip it.
					if (
						typeof tabFields !== 'object' ||
						! Object.keys( tabFields ).length
					) {
						return;
					}

					// Define this tab.
					if ( undefined === maintabs.tabs[ tabID ] ) {
						maintabs.tabs[ tabID ] = {
							label: data.tabs[ tabID ],
							content: '',
						};
					}

					section = [];

					tabFields = forms.parseFields( tabFields, values );

					// Loop Tab Fields
					_.each( tabFields, function ( field ) {
						// Store the field by id for easy lookup later.
						form_fields[ field.id ] = field;

						// Push rendered fields into the subtab content.
						section.push( PUM_Admin.templates.field( field ) );
					} );

					// Push rendered tab into the tab.
					if ( section.length ) {
						// Push rendered sub tabs into the main tabs if not empty.
						maintabs.tabs[ tabID ].content =
							PUM_Admin.templates.section( {
								fields: section,
							} );
					} else {
						delete maintabs.tabs[ tabID ];
					}
				} );

				if ( Object.keys( maintabs.tabs ).length ) {
					form = PUM_Admin.templates.tabs( maintabs );
				}
			} else if ( Object.keys( data.sections ).length ) {
				// Loop Sections
				_.each( data.fields, function ( sectionFields, sectionID ) {
					section = [];

					section.push(
						PUM_Admin.templates.field( {
							type: 'heading',
							desc: data.sections[ sectionID ] || '',
						} )
					);

					sectionFields = forms.parseFields( sectionFields, values );

					// Loop Tab Section Fields
					_.each( sectionFields, function ( field ) {
						// Store the field by id for easy lookup later.
						form_fields[ field.id ] = field;

						// Push rendered fields into the section.
						section.push( PUM_Admin.templates.field( field ) );
					} );

					// Push rendered sections into the form.
					form += PUM_Admin.templates.section( {
						fields: section,
					} );
				} );
			} else {
				data.fields = forms.parseFields( data.fields, values );

				// Replace the array with rendered fields.
				_.each( data.fields, function ( field ) {
					// Store the field by id for easy lookup later.
					form_fields[ field.id ] = field;

					// Push rendered fields into the section.
					section.push( PUM_Admin.templates.field( field ) );
				} );

				// Render the section.
				form = PUM_Admin.templates.section( {
					fields: section,
				} );
			}

			if ( $container !== undefined && $container.length ) {
				$container
					.addClass( container_classes.join( '  ' ) )
					.data( 'form_fields', form_fields )
					.html( form )
					.trigger( 'pum_init' );
			}

			return form;
		},
		parseValues: function ( values, fields ) {
			fields = fields || false;

			if ( ! fields ) {
				return values;
			}

			for ( var key in fields ) {
				if ( ! fields.hasOwnProperty( key ) ) {
					continue;
				}

				// Measure field value corrections.
				if ( values.hasOwnProperty( key + '_unit' ) ) {
					values[ key ] += values[ key + '_unit' ];
					delete values[ key + '_unit' ];
				}

				// If the value key is empty and a checkbox set it to false. Then return.
				if ( typeof values[ key ] === 'undefined' ) {
					if ( fields[ key ].type === 'checkbox' ) {
						values[ key ] = false;
					}
					continue;
				}

				if (
					fields[ key ].allow_html &&
					! PUM_Admin.utils.htmlencoder.hasEncoded( values[ key ] )
				) {
					values[ key ] = PUM_Admin.utils.htmlencoder.htmlEncode(
						values[ key ]
					);
				}
			}

			return values;
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.forms = forms;

	$( document )
		.on( 'pum_init  pum_form_check', function () {
			PUM_Admin.forms.init();
		} )
		.on( 'pumFieldChanged', '.pum-field', function () {
			var $wrapper = $( this ),
				dependent_field_ids =
					$wrapper.data( 'pum-field-dependents' ) || [],
				$fields_with_dependencies = $(),
				i;

			if ( ! dependent_field_ids || dependent_field_ids.length <= 0 ) {
				return;
			}

			for ( i = 0; i < dependent_field_ids.length; i++ ) {
				$fields_with_dependencies = $fields_with_dependencies.add(
					'.pum-field[data-id="' + dependent_field_ids[ i ] + '"]'
				);
			}

			PUM_Admin.forms.checkDependencies( $fields_with_dependencies );
		} )
		.on( 'pumFieldChanged', '.pum-field-dynamic-desc', function () {
			var $this = $( this ),
				$input = $this.find( ':input' ),
				$container = $this.parents( '.pum-dynamic-form:first' ),
				val = $input.val(),
				form_fields = $container.data( 'form_fields' ) || {},
				field = form_fields[ $this.data( 'id' ) ] || {},
				$desc = $this.find( '.pum-desc' ),
				desc = $this.data( 'pum-dynamic-desc' );

			switch ( field.type ) {
				case 'radio':
					val = $this.find( ':input:checked' ).val();
					break;
			}

			field.value = val;

			if ( desc && desc.length ) {
				$desc.html( PUM_Admin.templates.renderInline( desc, field ) );
			}
		} )
		.on( 'change', '.pum-field-select select', function () {
			$( this ).parents( '.pum-field' ).trigger( 'pumFieldChanged' );
		} )
		.on( 'click', '.pum-field-checkbox input', function () {
			$( this ).parents( '.pum-field' ).trigger( 'pumFieldChanged' );
		} )
		.on( 'click', '.pum-field-multicheck input', function () {
			$( this ).parents( '.pum-field' ).trigger( 'pumFieldChanged' );
		} )
		.on( 'click', '.pum-field-radio input', function ( event ) {
			var $this = $( this ),
				$selected = $this.parents( 'li' ),
				$wrapper = $this.parents( '.pum-field' );

			$wrapper.trigger( 'pumFieldChanged' );

			$wrapper.find( 'li.pum-selected' ).removeClass( 'pum-selected' );

			$selected.addClass( 'pum-selected' );
		} );
} )( jQuery );
