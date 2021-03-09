/*******************************************************************************
 * Copyright (c) 2020, Code Atlantic LLC
 ******************************************************************************/
( function( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};

	window.pum_popup_settings_editor = window.pum_popup_settings_editor || {
		form_args: {},
		current_values: {},
	};

	$( document )
		.on( 'keydown', '#popup-title', function( event ) {
			var keyCode = event.keyCode || event.which;
			if ( 9 === keyCode ) {
				event.preventDefault();
				$( '#title' ).focus();
			}
		} )
		.on( 'keydown', '#title, #popup-title', function( event ) {
			var keyCode = event.keyCode || event.which,
				target;
			if ( ! event.shiftKey && 9 === keyCode ) {
				event.preventDefault();
				target =
					$( this ).attr( 'id' ) === 'title'
						? '#popup-title'
						: '#insert-media-button';
				$( target ).focus();
			}
		} )
		.on( 'keydown', '#popup-title, #insert-media-button', function(
			event
		) {
			var keyCode = event.keyCode || event.which,
				target;
			if ( event.shiftKey && 9 === keyCode ) {
				event.preventDefault();
				target =
					$( this ).attr( 'id' ) === 'popup-title'
						? '#title'
						: '#popup-title';
				$( target ).focus();
			}
		} );

		// Initiate when ready.
		$( function() {
			$( this ).trigger( 'pum_init' );

			$( '#title' ).prop( 'required', true );

			var $container = $( '#pum-popup-settings-container' ),
				args = pum_popup_settings_editor.form_args || {},
				values = pum_popup_settings_editor.current_values || {};

			if ( $container.length ) {
				$container.find( '.pum-no-js' ).hide();
				PUM_Admin.forms.render( args, values, $container );
			}

			$( 'a.page-title-action' )
				.clone()
				.attr( 'target', '_blank' )
				.attr(
					'href',
					pum_admin_vars.homeurl +
						'?popup_preview=true&popup=' +
						$( '#post_ID' ).val()
				)
				.text( pum_admin_vars.I10n.preview_popup )
				.insertAfter( 'a.page-title-action' );

			// TODO Can't figure out why this is needed, but it looks stupid otherwise when the first condition field defaults to something other than the placeholder.
			$( '#pum-first-condition, #pum-first-trigger, #pum-first-cookie' )
				.val( null )
				.trigger( 'change' );

			// Add event handler to detect when opening sound is change and play the sound to allow admin to preview it.
			document
				.querySelector( '#pum-popup-settings-container' )
				.addEventListener( 'change', function( e ) {
					if ( 'open_sound' === e.target.id ) {
						// Only play if the sound selected is not None or Custom.
						var notThese = [ 'none', 'custom' ];
						if ( notThese.indexOf( e.target.value ) === -1 ) {
							var audio = new Audio(
								pum_admin_vars.pm_dir_url +
									'/assets/sounds/' +
									e.target.value
							);
							audio.addEventListener(
								'canplaythrough',
								function() {
									this.play().catch( function( reason ) {
										console.warn(
											'Sound was not able to play when selected. Reason: ' +
												reason
										);
									} );
								}
							);
							audio.addEventListener( 'error', function() {
								console.warn(
									'Error occurred when trying to load popup opening sound.'
								);
							} );
						}
					}
				} );

			// Dynamically switches example click trigger from popup-{popup-id} to using real ID.
			$( document ).on( 'pum_init', function() {
				$(
					'#pum-default-click-trigger-class:not(.pum-click-trigger-initialized)'
				).each( function() {
					$( this )
						.addClass( 'pum-click-trigger-initialized' )
						.text( $( '#popup-id' ).data( 'popup-id' ) );
				} );
			} );

			document
				.querySelector( '#pum-popup-settings-container' )
				.addEventListener( 'click', function( e ) {
					if (
						Array.from( e.target.classList ).includes(
							'popup-type'
						) ||
						Array.from( e.target.parentElement.classList ).includes(
							'popup-type'
						)
					) {
						var $container = jQuery(
							'#pum-popup-settings-container'
						);
						if ( 1 === $container.length ) {
							// Our initial presets. As we add more, consider creating JSON import system and moving to there.
							var popupTypes = {
								'center-popup': {
									size: 'medium',
									responsive_min_width: '0%',
									responsive_max_width: '100%',
									animation_type: 'fade',
									animation_speed: 350,
									location: 'center',
									position_fixed: false,
									position_from_trigger: false,
									overlay_disabled: false,
									stackable: false,
									disable_reposition: false,
								},
								'left-bottom-notice': {
									size: 'tiny',
									responsive_min_width: '0%',
									responsive_max_width: '100%',
									animation_type: 'fade',
									animation_speed: 350,
									animation_origin: 'left bottom',
									location: 'left bottom',
									position_bottom: 10,
									position_left: 10,
									position_from_trigger: false,
									position_fixed: true,
									overlay_disabled: true,
									stackable: true,
									disable_reposition: false,
								},
								'top-bar': {
									size: 'custom',
									custom_width: '100%',
									custom_height_auto: true,
									animation_type: 'fadeAndSlide',
									animation_speed: 300,
									animation_origin: 'top',
									location: 'center top',
									position_top: 0,
									position_from_trigger: false,
									position_fixed: true,
									overlay_disabled: true,
									stackable: true,
									disable_reposition: false,
								},
								'right-bottom-slidein': {
									size: 'custom',
									custom_width: '300px',
									custom_height_auto: true,
									animation_type: 'slide',
									animation_speed: 350,
									animation_origin: 'bottom',
									location: 'right bottom',
									position_bottom: 10,
									position_right: 10,
									position_from_trigger: false,
									position_fixed: true,
									overlay_disabled: true,
									stackable: true,
									disable_reposition: false,
								},
							};
							var popupType =
								e.target.dataset.popupType ||
								e.target.parentElement.dataset.popupType ||
								'';

							// Gather our values needed for creating new settings object.
							var presetValues = popupTypes.hasOwnProperty(
								popupType
							)
								? popupTypes[ popupType ]
								: {};
							var args =
								pum_popup_settings_editor.form_args || {};
							var originalValues =
								pum_popup_settings_editor.current_values || {};
							var currentValues = $container.pumSerializeObject();

							// pumSerializeObject returns the trigger/cookie settings as strings instead of objects.
							// Cycle through each trigger and cookie and convert to objects.
							if ( currentValues.popup_settings.triggers ) {
								for (
									var i = 0;
									i <
									currentValues.popup_settings.triggers
										.length;
									i++
								) {
									currentValues.popup_settings.triggers[
										i
									].settings = JSON.parse(
										currentValues.popup_settings.triggers[
											i
										].settings
									);
								}
							}
							if ( currentValues.popup_settings.cookies ) {
								for (
									var j = 0;
									j <
									currentValues.popup_settings.cookies.length;
									j++
								) {
									currentValues.popup_settings.cookies[
										j
									].settings = JSON.parse(
										currentValues.popup_settings.cookies[
											j
										].settings
									);
								}
							}

							var newValues = Object.assign(
								{},
								originalValues,
								currentValues.popup_settings,
								presetValues
							);

							// Re-render form using updated settings.
							PUM_Admin.forms.render(
								args,
								newValues,
								$container
							);

							// Click to 'Display' so they don't jump to 'Targeting' tab upon render.
							document
								.querySelector(
									'a[href="#pum-popup-settings_display"]'
								)
								.click();

							// Adds a notice into 'Display Presets' tab telling admin the settings have been applied.
							var notice = document.createElement( 'div' );
							notice.classList.add( 'notice', 'updated' );
							notice.insertBefore(
								document.createElement( 'p' ),
								notice.firstChild
							);
							notice.firstChild.innerText =
								'Display settings have been updated with the ' +
								popupType +
								' preset';
							var parent = document.querySelector(
								'#pum-popup-settings-display-subtabs_preset'
							);
							parent.insertBefore( notice, parent.firstChild );
						}
					}
				} );
		} );
} )( jQuery );

( function( $, document, undefined ) {
	'use strict';

	$( document ).on( 'click', '#popup_reset_open_count', function() {
		var $this = $( this );
		if (
			$this.is( ':checked' ) &&
			! confirm( pum_admin_vars.I10n.confirm_count_reset )
		) {
			$this.prop( 'checked', false );
		}
	} );
} )( jQuery, document );

( function( $ ) {
	'use strict';

	var conditions = {
		get_conditions: function() {
			return window.pum_popup_settings_editor.conditions_selectlist;
		},
		not_operand_checkbox: function( $element ) {
			$element = $element || $( '.pum-not-operand' );

			return $element.each( function() {
				var $this = $( this ),
					$input = $this.find( 'input' );

				$input.prop( 'checked', ! $input.is( ':checked' ) );

				conditions.toggle_not_operand( $this );
			} );
		},
		toggle_not_operand: function( $element ) {
			$element = $element || $( '.pum-not-operand' );

			return $element.each( function() {
				var $this = $( this ),
					$input = $this.find( 'input' ),
					// $is        = $this.find('.is'),
					// $not       = $this.find('.not'),
					$container = $this.parents( '.facet-target' );

				if ( $input.is( ':checked' ) ) {
					// $is.hide();
					// $not.show();
					$container.addClass( 'not-operand-checked' );
				} else {
					// $is.show();
					// $not.hide();
					$container.removeClass( 'not-operand-checked' );
				}
			} );
		},
		template: {
			editor: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						groups: [],
					},
					args
				);

				data.groups = PUM_Admin.utils.object_to_array( data.groups );

				return PUM_Admin.templates.render(
					'pum-condition-editor',
					data
				);
			},
			group: function( args ) {
				var data = $.extend(
						true,
						{},
						{
							index: '',
							facets: [],
						},
						args
					),
					i;

				data.facets = PUM_Admin.utils.object_to_array( data.facets );

				for ( i = 0; data.facets.length > i; i++ ) {
					data.facets[ i ].index = i;
					data.facets[ i ].group = data.index;
				}

				return PUM_Admin.templates.render(
					'pum-condition-group',
					data
				);
			},
			facet: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						group: '',
						index: '',
						target: '',
						not_operand: false,
						settings: {},
					},
					args
				);

				return PUM_Admin.templates.render(
					'pum-condition-facet',
					data
				);
			},
			settings: function( args, values ) {
				var fields = [],
					data = $.extend(
						true,
						{},
						{
							index: '',
							group: '',
							target: null,
							fields: [],
						},
						args
					);

				if (
					! data.fields.length &&
					pum_popup_settings_editor.conditions[ args.target ] !==
						undefined
				) {
					data.fields =
						pum_popup_settings_editor.conditions[
							args.target
						].fields;
				}

				if ( undefined === values ) {
					values = {};
				}

				// Replace the array with rendered fields.
				_.each( data.fields, function( field, fieldID ) {
					field = PUM_Admin.models.field( field );

					if ( typeof field.meta !== 'object' ) {
						field.meta = {};
					}

					if ( undefined !== values[ fieldID ] ) {
						field.value = values[ fieldID ];
					}

					field.name =
						'popup_settings[conditions][' +
						data.group +
						'][' +
						data.index +
						'][settings][' +
						fieldID +
						']';

					if ( field.id === '' ) {
						field.id =
							'popup_settings_conditions_' +
							data.group +
							'_' +
							data.index +
							'_settings_' +
							fieldID;
					}

					fields.push( PUM_Admin.templates.field( field ) );
				} );

				// Render the section.
				return PUM_Admin.templates.section( {
					fields: fields,
				} );
			},
			selectbox: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						id: null,
						name: null,
						type: 'select',
						group: '',
						index: '',
						value: null,
						select2: true,
						classes: [],
						options: conditions.get_conditions(),
					},
					args
				);

				if ( data.id === null ) {
					data.id =
						'popup_settings_conditions_' +
						data.group +
						'_' +
						data.index +
						'_target';
				}

				if ( data.name === null ) {
					data.name =
						'popup_settings[conditions][' +
						data.group +
						'][' +
						data.index +
						'][target]';
				}

				return PUM_Admin.templates.field( data );
			},
		},
		groups: {
			add: function( editor, target, not_operand ) {
				var $editor = $( editor ),
					data = {
						index: $editor.find( '.facet-group-wrap' ).length,
						facets: [
							{
								target: target || null,
								not_operand: not_operand || false,
								settings: {},
							},
						],
					};

				$editor
					.find( '.facet-groups' )
					.append( conditions.template.group( data ) );
				$editor.addClass( 'has-conditions' );
			},
			remove: function( $group ) {
				var $editor = $group.parents( '.facet-builder' );

				$group
					.prev( '.facet-group-wrap' )
					.find( '.and .add-facet' )
					.removeClass( 'disabled' );
				$group.remove();

				conditions.renumber();

				if ( $editor.find( '.facet-group-wrap' ).length === 0 ) {
					$editor.removeClass( 'has-conditions' );

					$( '#pum-first-condition' )
						.val( null )
						.trigger( 'change' );
				}
			},
		},
		facets: {
			add: function( $group, target, not_operand ) {
				var data = {
					group: $group.data( 'index' ),
					index: $group.find( '.facet' ).length,
					target: target || null,
					not_operand: not_operand || false,
					settings: {},
				};

				$group
					.find( '.facet-list' )
					.append( conditions.template.facet( data ) );
			},
			remove: function( $facet ) {
				var $group = $facet.parents( '.facet-group-wrap' );

				$facet.remove();

				if ( $group.find( '.facet' ).length === 0 ) {
					conditions.groups.remove( $group );
				} else {
					conditions.renumber();
				}
			},
		},
		renumber: function() {
			$( '.facet-builder .facet-group-wrap' ).each( function() {
				var $group = $( this ),
					groupIndex = $group
						.parent()
						.children()
						.index( $group );

				$group
					.data( 'index', groupIndex )
					.find( '.facet' )
					.each( function() {
						var $facet = $( this ),
							facetIndex = $facet
								.parent()
								.children()
								.index( $facet );

						$facet
							.data( 'index', facetIndex )
							.find( '[name]' )
							.each( function() {
								this.name = this.name.replace(
									/popup_settings\[conditions\]\[\d*?\]\[\d*?\]/,
									'popup_settings[conditions][' +
										groupIndex +
										'][' +
										facetIndex +
										']'
								);
								this.id = this.id.replace(
									/popup_settings_conditions_\d*?_\d*?_/,
									'popup_settings_conditions_' +
										groupIndex +
										'_' +
										facetIndex +
										'_'
								);
							} );
					} );
			} );
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.conditions = conditions;

	$( document )
		.on( 'pum_init', function() {
			conditions.renumber();
			conditions.toggle_not_operand();
		} )
		.on(
			'select2:select pumselect2:select',
			'#pum-first-condition',
			function( event ) {
				var $field = $( this ),
					$editor = $field.parents( '.facet-builder' ).eq( 0 ),
					target = $field.val(),
					$operand = $editor.find( '#pum-first-facet-operand' ),
					not_operand = $operand.is( ':checked' );

				conditions.groups.add( $editor, target, not_operand );

				$field.val( null ).trigger( 'change' );

				$operand
					.prop( 'checked', false )
					.parents( '.facet-target' )
					.removeClass( 'not-operand-checked' );
				$( document ).trigger( 'pum_init' );
			}
		)
		.on( 'click', '.facet-builder .pum-not-operand', function() {
			conditions.not_operand_checkbox( $( this ) );
		} )
		.on( 'change', '.facet-builder .facet-target select', function(
			event
		) {
			var $this = $( this ),
				$facet = $this.parents( '.facet' ),
				target = $this.val(),
				data = {
					target: target,
				};

			if ( target === '' || target === $facet.data( 'target' ) ) {
				return;
			}

			$facet
				.data( 'target', target )
				.find( '.facet-settings' )
				.html( conditions.template.settings( data ) );
			$( document ).trigger( 'pum_init' );
		} )
		.on(
			'click',
			'.facet-builder .facet-group-wrap:last-child .and .add-facet',
			function() {
				conditions.groups.add(
					$( this )
						.parents( '.facet-builder' )
						.eq( 0 )
				);
				$( document ).trigger( 'pum_init' );
			}
		)
		.on(
			'click',
			'.facet-builder .add-or .add-facet:not(.disabled)',
			function() {
				conditions.facets.add(
					$( this )
						.parents( '.facet-group-wrap' )
						.eq( 0 )
				);
				$( document ).trigger( 'pum_init' );
			}
		)
		.on( 'click', '.facet-builder .remove-facet', function() {
			conditions.facets.remove(
				$( this )
					.parents( '.facet' )
					.eq( 0 )
			);
			$( document ).trigger( 'pum_init' );
		} );
} )( jQuery );

var cookies;
( function( $, document, undefined ) {
	'use strict';

	var I10n = pum_admin_vars.I10n,
		current_editor,
		cookies = {
			get_cookies: function() {
				return window.pum_popup_settings_editor.cookies;
			},
			get_cookie: function( event ) {
				var cookies = this.get_cookies(),
					cookie =
						cookies[ event ] !== 'undefined'
							? cookies[ event ]
							: false;

				if ( ! cookie ) {
					return false;
				}

				if (
					cookie &&
					typeof cookie === 'object' &&
					typeof cookie.fields === 'object' &&
					Object.keys( cookie.fields ).length
				) {
					cookie = this.parseFields( cookie );
				}

				return cookie;
			},
			getCookieDefaults: function( event ) {
				var cookie = cookies.get_cookie( event );
				var defaultSettings = {};
				for ( var tab in cookie.fields ) {
					if ( cookie.fields.hasOwnProperty( tab ) ) {
						for ( var setting in cookie.fields[ tab ] ) {
							if (
								cookie.fields[ tab ].hasOwnProperty( setting )
							) {
								defaultSettings[ setting ] =
									cookie.fields[ tab ][ setting ].std;
							}
						}
					}
				}
				defaultSettings.name = 'pum-' + $( '#post_ID' ).val();
				return defaultSettings;
			},
			parseFields: function( cookie ) {
				_.each( cookie.fields, function( fields, tabID ) {
					_.each( fields, function( field, fieldID ) {
						cookie.fields[ tabID ][ fieldID ].name =
							'cookie_settings[' + fieldID + ']';

						if ( cookie.fields[ tabID ][ fieldID ].id === '' ) {
							cookie.fields[ tabID ][ fieldID ].id =
								'cookie_settings_' + fieldID;
						}
					} );
				} );

				return cookie;
			},
			parseValues: function( values, type ) {
				return values;
			},
			select_list: function() {
				var i,
					_cookies = PUM_Admin.utils.object_to_array(
						cookies.get_cookies()
					),
					options = {};

				for ( i = 0; i < _cookies.length; i++ ) {
					options[ _cookies[ i ].id ] = _cookies[ i ].name;
				}

				return options;
			},
			/**
			 * @deprecated
			 *
			 * @param event
			 */
			getLabel: function( event ) {
				var cookie = cookies.get_cookie( event );

				if ( ! cookie ) {
					return false;
				}

				return cookie.name;
			},
			/**
			 * @param event
			 * @param values
			 */
			getSettingsDesc: function( event, values ) {
				var cookie = cookies.get_cookie( event );

				if ( ! cookie ) {
					return false;
				}

				return PUM_Admin.templates.renderInline(
					cookie.settings_column,
					values
				);
			},
			/**
			 * Refresh all cookie row descriptions.
			 */
			refreshDescriptions: function() {
				$( '.pum-popup-cookie-editor table.list-table tbody tr' ).each(
					function() {
						var $row = $( this ),
							event = $row
								.find( '.popup_cookies_field_event' )
								.val(),
							values = JSON.parse(
								$row
									.find(
										'.popup_cookies_field_settings:first'
									)
									.val()
							);

						$row.find( 'td.settings-column' ).html(
							cookies.getSettingsDesc( event, values )
						);
					}
				);
			},
			/**
			 * Insert a new cookie when needed.
			 *
			 * @param $editor
			 * @param args
			 */
			insertCookie: function( $editor, args ) {
				var defaultSettings = cookies.getCookieDefaults( args.event );
				args = $.extend(
					true,
					{},
					{
						event: 'on_popup_close',
						settings: defaultSettings,
					},
					args
				);
				cookies.rows.add( $editor, args );
			},
			template: {
				form: function( event, values, callback ) {
					var cookie = cookies.get_cookie( event ),
						modalID = 'pum_cookie_settings',
						firstTab = Object.keys( cookie.fields )[ 0 ];

					values = values || {};
					values.event = event;
					values.index = values.index >= 0 ? values.index : null;

					// Add hidden index & event fields.
					cookie.fields[ firstTab ] = $.extend(
						true,
						cookie.fields[ firstTab ],
						{
							index: {
								type: 'hidden',
								name: 'index',
							},
							event: {
								type: 'hidden',
								name: 'event',
							},
						}
					);

					if ( typeof values.key !== 'string' || values.key === '' ) {
						delete cookie.fields.advanced.key;
					}

					PUM_Admin.modals.reload(
						'#' + modalID,
						PUM_Admin.templates.modal( {
							id: modalID,
							title: cookie.modal_title || cookie.name,
							classes: 'tabbed-content',
							save_button:
								values.index !== null ? I10n.update : I10n.add,
							content: PUM_Admin.forms.render(
								{
									id: 'pum_cookie_settings_form',
									tabs: cookie.tabs || {},
									fields: cookie.fields || {},
								},
								values || {}
							),
						} )
					);

					$( '#' + modalID + ' form' ).on(
						'submit',
						callback ||
							function( e ) {
								e.preventDefault();
								PUM_Admin.modals.closeAll();
							}
					);
				},
				editor: function( args ) {
					var data = $.extend(
						true,
						{},
						{
							cookies: [],
							name: '',
						},
						args
					);

					data.cookies = PUM_Admin.utils.object_to_array(
						data.cookies
					);

					return PUM_Admin.templates.render(
						'pum-cookie-editor',
						data
					);
				},
				row: function( args ) {
					var data = $.extend(
						true,
						{},
						{
							index: '',
							event: '',
							name: '',
							settings: {
								name: '',
								key: '',
								session: null,
								path: null,
								time: '30 days',
							},
						},
						args
					);

					return PUM_Admin.templates.render( 'pum-cookie-row', data );
				},
				selectbox: function( args ) {
					var data = $.extend(
						true,
						{},
						{
							id: null,
							name: null,
							type: 'select',
							group: '',
							index: '',
							value: null,
							select2: true,
							classes: [],
							options: cookies.select_list(),
						},
						args
					);

					if ( data.id === null ) {
						data.id =
							'popup_settings_cookies_' + data.index + '_event';
					}

					if ( data.name === null ) {
						data.name =
							'popup_settings[cookies][' +
							data.index +
							'][event]';
					}

					return PUM_Admin.templates.field( data );
				},
			},
			rows: {
				add: function( editor, cookie ) {
					var $editor = $( editor ),
						data = {
							index:
								cookie.index !== null && cookie.index >= 0
									? cookie.index
									: $editor.find(
											'table.list-table tbody tr'
									  ).length,
							event: cookie.event,
							name: $editor.data( 'field_name' ),
							settings: cookie.settings || {},
						},
						$row = $editor.find( 'tbody tr' ).eq( data.index ),
						$new_row = PUM_Admin.templates.render(
							'pum-cookie-row',
							data
						);

					if ( $row.length ) {
						$row.replaceWith( $new_row );
					} else {
						$editor.find( 'tbody' ).append( $new_row );
					}

					$editor.addClass( 'has-list-items' );

					cookies.rows.renumber();
					cookies.refreshDescriptions();
				},
				/**
				 * Remove a cookie editor table row.
				 *
				 * @param $cookie
				 */
				remove: function( $cookie ) {
					var $editor = $cookie.parents( '.pum-popup-cookie-editor' );

					$cookie.remove();
					cookies.rows.renumber();

					if (
						$editor.find( 'table.list-table tbody tr' ).length === 0
					) {
						$editor.removeClass( 'has-list-items' );

						$( '#pum-first-cookie' )
							.val( null )
							.trigger( 'change' );
					}
				},
				/**
				 * Renumber all rows for all editors.
				 */
				renumber: function() {
					$(
						'.pum-popup-cookie-editor table.list-table tbody tr'
					).each( function() {
						var $this = $( this ),
							index = $this
								.parent()
								.children()
								.index( $this );

						$this
							.attr( 'data-index', index )
							.data( 'index', index );

						$this.find( ':input, [name]' ).each( function() {
							if ( this.name && this.name !== '' ) {
								this.name = this.name.replace(
									/\[\d*?\]/,
									'[' + index + ']'
								);
							}
						} );
					} );
				},
			},
		};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.cookies = cookies;

	$( document )
		.on( 'pum_init', function() {
			cookies.refreshDescriptions();
		} )
		.on(
			'select2:select pumselect2:select',
			'#pum-first-cookie',
			function() {
				var $this = $( this ),
					$editor = $this.parents( '.pum-popup-cookie-editor' ),
					event = $this.val(),
					values = {
						indes: $editor.find( 'table.list-table tbody tr' )
							.length,
						name: 'pum-' + $( '#post_ID' ).val(),
					};

				$this.val( null ).trigger( 'change' );

				cookies.template.form( event, values, function( e ) {
					var $form = $( this ),
						event = $form.find( 'input#event' ).val(),
						index = $form.find( 'input#index' ).val(),
						values = $form.pumSerializeObject();

					e.preventDefault();

					if ( ! index || index < 0 ) {
						index = $editor.find( 'tbody tr' ).length;
					}

					cookies.rows.add( $editor, {
						index: index,
						event: event,
						settings: values.cookie_settings,
					} );

					PUM_Admin.modals.closeAll();
				} );
			}
		)
		.on( 'click', '.pum-popup-cookie-editor .pum-add-new', function() {
			current_editor = $( this ).parents( '.pum-popup-cookie-editor' );
			var template = wp.template( 'pum-cookie-add-event' );
			PUM_Admin.modals.reload(
				'#pum_cookie_add_event_modal',
				template( { I10n: I10n } )
			);
		} )
		.on( 'click', '.pum-popup-cookie-editor .edit', function( e ) {
			var $this = $( this ),
				$editor = $this.parents( '.pum-popup-cookie-editor' ),
				$row = $this.parents( 'tr:first' ),
				event = $row.find( '.popup_cookies_field_event' ).val(),
				values = _.extend(
					{},
					JSON.parse(
						$row.find( '.popup_cookies_field_settings:first' ).val()
					),
					{
						index: $row
							.parent()
							.children()
							.index( $row ),
						event: event,
					}
				);

			e.preventDefault();

			cookies.template.form( event, values, function( e ) {
				var $form = $( this ),
					event = $form.find( 'input#event' ).val(),
					index = $form.find( 'input#index' ).val(),
					values = $form.pumSerializeObject();

				e.preventDefault();

				if ( index === false || index < 0 ) {
					index = $editor.find( 'tbody tr' ).length;
				}

				cookies.rows.add( $editor, {
					index: index,
					event: event,
					settings: values.cookie_settings,
				} );

				PUM_Admin.modals.closeAll();
			} );
		} )
		.on( 'click', '.pum-popup-cookie-editor .remove', function( e ) {
			var $this = $( this ),
				$row = $this.parents( 'tr:first' );

			e.preventDefault();

			if ( window.confirm( I10n.confirm_delete_cookie ) ) {
				cookies.rows.remove( $row );
			}
		} )
		.on( 'click', '.pum-field-cookie_key button.reset', function( e ) {
			var $this = $( this ),
				newKey = new Date().getTime().toString( 16 );

			$this.siblings( 'input[type="text"]:first' ).val( newKey );
		} )
		.on( 'submit', '#pum_cookie_add_event_modal .pum-form', function( e ) {
			var $editor = current_editor,
				event = $( '#popup_cookie_add_event' ).val(),
				values = {
					index: $editor.find( 'table.list-table tbody tr' ).length,
					name: 'pum-' + $( '#post_ID' ).val(),
					path: '1',
				};

			e.preventDefault();

			cookies.template.form( event, values, function( e ) {
				var $form = $( this ),
					event = $form.find( 'input#event' ).val(),
					index = $form.find( 'input#index' ).val(),
					values = $form.pumSerializeObject();

				e.preventDefault();

				if ( index === false || index < 0 ) {
					index = $editor.find( 'tbody tr' ).length;
				}

				cookies.rows.add( $editor, {
					index: index,
					event: event,
					settings: values.cookie_settings,
				} );

				PUM_Admin.modals.closeAll();

				if (
					typeof PUM_Admin.triggers !== 'undefined' &&
					PUM_Admin.triggers.new_cookie !== false &&
					PUM_Admin.triggers.new_cookie >= 0
				) {
					var $trigger = PUM_Admin.triggers.current_editor
							.find( 'tbody tr' )
							.eq( PUM_Admin.triggers.new_cookie )
							.find( '.popup_triggers_field_settings:first' ),
						trigger_settings = JSON.parse( $trigger.val() );

					if ( typeof trigger_settings.cookie_name === 'string' ) {
						trigger_settings.cookie_name = trigger_settings.cookie_name.replace(
							'add_new',
							values.cookie_settings.name
						);
					} else {
						trigger_settings.cookie_name[
							trigger_settings.cookie_name.indexOf( 'add_new' )
						] = values.cookie_settings.name;
						trigger_settings.cookie_name = trigger_settings.cookie_name.filter(
							function( element, index, array ) {
								return element in this
									? false
									: ( this[ element ] = true );
							},
							{}
						);
					}

					$trigger.val( JSON.stringify( trigger_settings ) );

					PUM_Admin.triggers.new_cookie = false;
					PUM_Admin.triggers.refreshDescriptions();
				}
			} );
		} );
} )( jQuery, document );

( function( $, document, undefined ) {
	'use strict';

	var I10n = pum_admin_vars.I10n;

	var triggers = {
		current_editor: null,
		new_cookie: false,
		get_triggers: function() {
			return window.pum_popup_settings_editor.triggers;
		},
		get_trigger: function( type ) {
			var triggers = this.get_triggers(),
				trigger =
					triggers[ type ] !== 'undefined' ? triggers[ type ] : false;

			if ( ! trigger ) {
				return false;
			}

			if (
				trigger &&
				typeof trigger === 'object' &&
				typeof trigger.fields === 'object' &&
				Object.keys( trigger.fields ).length
			) {
				trigger = this.parseFields( trigger );
			}

			return trigger;
		},
		parseFields: function( trigger ) {
			_.each( trigger.fields, function( fields, tabID ) {
				_.each( fields, function( field, fieldID ) {
					trigger.fields[ tabID ][ fieldID ].name =
						'trigger_settings[' + fieldID + ']';

					if ( trigger.fields[ tabID ][ fieldID ].id === '' ) {
						trigger.fields[ tabID ][ fieldID ].id =
							'trigger_settings_' + fieldID;
					}
				} );
			} );

			return trigger;
		},
		parseValues: function( values, type ) {
			for ( var key in values ) {
				if ( ! values.hasOwnProperty( key ) ) {
					continue;
				}

				// Clean measurement fields.
				if ( values.hasOwnProperty( key + '_unit' ) ) {
					values[ key ] += values[ key + '_unit' ];
					delete values[ key + '_unit' ];
				}
			}

			return values;
		},
		select_list: function() {
			var i,
				_triggers = PUM_Admin.utils.object_to_array(
					triggers.get_triggers()
				),
				options = {};

			for ( i = 0; i < _triggers.length; i++ ) {
				options[ _triggers[ i ].id ] = _triggers[ i ].name;
			}

			return options;
		},
		rows: {
			add: function( editor, trigger ) {
				var $editor = $( editor ),
					data = {
						index:
							trigger.index !== null && trigger.index >= 0
								? trigger.index
								: $editor.find( 'table.list-table tbody tr' )
										.length,
						type: trigger.type,
						name: $editor.data( 'field_name' ),
						settings: trigger.settings || {},
					},
					$row = $editor.find( 'tbody tr' ).eq( data.index ),
					$new_row = PUM_Admin.templates.render(
						'pum-trigger-row',
						data
					);

				if ( $row.length ) {
					$row.replaceWith( $new_row );
				} else {
					$editor.find( 'tbody' ).append( $new_row );
				}

				$editor.addClass( 'has-list-items' );

				triggers.renumber();
				triggers.refreshDescriptions();
			},
			remove: function( $trigger ) {
				var $editor = $trigger.parents( '.pum-popup-trigger-editor' );

				$trigger.remove();
				triggers.renumber();

				if (
					$editor.find( 'table.list-table tbody tr' ).length === 0
				) {
					$editor.removeClass( 'has-list-items' );

					$( '#pum-first-trigger' )
						.val( null )
						.trigger( 'change' );
				}
			},
		},
		template: {
			form: function( type, values, callback ) {
				var trigger = triggers.get_trigger( type ),
					modalID = 'pum_trigger_settings',
					firstTab = Object.keys( trigger.fields )[ 0 ],
					$cookies = $( '.pum-field-cookies .list-table tbody tr' );

				values = values || {};
				values.type = type;
				values.index = values.index >= 0 ? values.index : null;

				// Add hidden index & type fields.
				trigger.fields[ firstTab ] = $.extend(
					true,
					trigger.fields[ firstTab ],
					{
						index: {
							type: 'hidden',
							name: 'index',
						},
						type: {
							type: 'hidden',
							name: 'type',
						},
					}
				);

				$cookies.each( function() {
					var settings = JSON.parse(
						$( this )
							.find( '.popup_cookies_field_settings:first' )
							.val()
					);
					if (
						typeof trigger.fields[ firstTab ].cookie_name.options[
							settings.name
						] === 'undefined'
					) {
						trigger.fields[ firstTab ].cookie_name.options[
							settings.name
						] = settings.name;
					}
				} );

				PUM_Admin.modals.reload(
					'#' + modalID,
					PUM_Admin.templates.modal( {
						id: modalID,
						title: trigger.modal_title || trigger.name,
						classes: 'tabbed-content',
						save_button:
							values.index !== null ? I10n.update : I10n.add,
						content: PUM_Admin.forms.render(
							{
								id: 'pum_trigger_settings_form',
								tabs: trigger.tabs || {},
								fields: trigger.fields || {},
							},
							values || {}
						),
					} )
				);

				$( '#' + modalID + ' form' ).on(
					'submit',
					callback ||
						function( event ) {
							event.preventDefault();
							PUM_Admin.modals.closeAll();
						}
				);
			},
			editor: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						triggers: [],
						name: '',
					},
					args
				);

				data.triggers = PUM_Admin.utils.object_to_array(
					data.triggers
				);

				return PUM_Admin.templates.render( 'pum-trigger-editor', data );
			},
			row: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						index: '',
						type: '',
						name: '',
						settings: {
							cookie_name: '',
						},
					},
					args
				);

				return PUM_Admin.templates.render( 'pum-trigger-row', data );
			},
			selectbox: function( args ) {
				var data = $.extend(
					true,
					{},
					{
						id: null,
						name: null,
						type: 'select',
						group: '',
						index: '',
						value: null,
						select2: true,
						classes: [],
						options: triggers.select_list(),
					},
					args
				);

				if ( data.id === null ) {
					data.id = 'popup_settings_triggers_' + data.index + '_type';
				}

				if ( data.name === null ) {
					data.name =
						'popup_settings[triggers][' + data.index + '][type]';
				}

				return PUM_Admin.templates.field( data );
			},
		},
		/* @deprecated */
		getLabel: function( type ) {
			var trigger = triggers.get_trigger( type );

			if ( ! trigger ) {
				return false;
			}

			return trigger.name;
		},
		getSettingsDesc: function( type, values ) {
			var trigger = triggers.get_trigger( type );

			if ( ! trigger ) {
				return false;
			}

			return PUM_Admin.templates.renderInline(
				trigger.settings_column,
				values
			);
		},
		renumber: function() {
			$( '.pum-popup-trigger-editor table.list-table tbody tr' ).each(
				function() {
					var $this = $( this ),
						index = $this
							.parent()
							.children()
							.index( $this );

					$this.attr( 'data-index', index ).data( 'index', index );

					$this.find( ':input, [name]' ).each( function() {
						if ( this.name && this.name !== '' ) {
							this.name = this.name.replace(
								/\[\d*?\]/,
								'[' + index + ']'
							);
						}
					} );
				}
			);
		},
		refreshDescriptions: function() {
			$( '.pum-popup-trigger-editor table.list-table tbody tr' ).each(
				function() {
					var $row = $( this ),
						type = $row.find( '.popup_triggers_field_type' ).val(),
						values = JSON.parse(
							$row
								.find( '.popup_triggers_field_settings:first' )
								.val()
						),
						cookie_text = PUM_Admin.triggers.cookie_column_value(
							values.cookie_name
						);

					$row.find( 'td.settings-column' ).html(
						PUM_Admin.triggers.getSettingsDesc( type, values )
					);
					$row.find( 'td.cookie-column code' ).text( cookie_text );
				}
			);
		},
		cookie_column_value: function( cookie_name ) {
			var cookie_text = I10n.no_cookie;

			if ( cookie_name instanceof Array ) {
				cookie_text = cookie_name.join( ', ' );
			} else if (
				cookie_name !== null &&
				cookie_name !== undefined &&
				cookie_name !== ''
			) {
				cookie_text = cookie_name;
			}
			return cookie_text;
		},
		append_click_selector_presets: function() {
			var $field = $( '#extra_selectors' ),
				template,
				$presets;

			if (
				! $field.length ||
				$field.hasClass( 'pum-click-selector-presets-initialized' )
			) {
				return;
			}

			template = PUM_Admin.templates.render(
				'pum-click-selector-presets'
			);
			$presets = $field
				.parents( '.pum-field' )
				.find( '.pum-click-selector-presets' );

			if ( ! $presets.length ) {
				$field.before( template );
				$field.addClass( 'pum-click-selector-presets-initialized' );
				$presets = $field
					.parents( '.pum-field' )
					.find( '.pum-click-selector-presets' );
			}

			$presets.position( {
				my: 'right center',
				at: 'right center',
				of: $field,
			} );
		},
		toggle_click_selector_presets: function() {
			$( this )
				.parent()
				.toggleClass( 'open' );
		},
		reset_click_selector_presets: function( e ) {
			if (
				e !== undefined &&
				$( e.target ).parents( '.pum-click-selector-presets' ).length
			) {
				return;
			}

			$( '.pum-click-selector-presets' ).removeClass( 'open' );
		},
		insert_click_selector_preset: function() {
			var $this = $( this ),
				$input = $( '#extra_selectors' ),
				val = $input.val();

			if ( val !== '' ) {
				val = val + ', ';
			}

			$input.val( val + $this.data( 'preset' ) );
			PUM_Admin.triggers.reset_click_selector_presets();
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.triggers = triggers;

	$( document )
		.on( 'pum_init', function() {
			PUM_Admin.triggers.append_click_selector_presets();
			PUM_Admin.triggers.refreshDescriptions();
		} )
		.on(
			'click',
			'.pum-click-selector-presets > span',
			PUM_Admin.triggers.toggle_click_selector_presets
		)
		.on(
			'click',
			'.pum-click-selector-presets li',
			PUM_Admin.triggers.insert_click_selector_preset
		)
		.on( 'click', PUM_Admin.triggers.reset_click_selector_presets )
		/**
		 * @deprecated 1.7.0
		 */
		.on(
			'select2:select pumselect2:select',
			'#pum-first-trigger',
			function() {
				var $this = $( this ),
					$editor = $this.parents( '.pum-popup-trigger-editor' ),
					type = $this.val(),
					values = {};

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				if ( type !== 'click_open' ) {
					values.cookie_name = 'pum-' + $( '#post_ID' ).val();
				}

				triggers.template.form( type, values, function( event ) {
					var $form = $( this ),
						type = $form.find( 'input#type' ).val(),
						values = $form.pumSerializeObject(),
						trigger_settings = triggers.parseValues(
							values.trigger_settings || {}
						),
						index = parseInt( values.index );

					event.preventDefault();

					if ( index === false || index < 0 ) {
						index = $editor.find( 'tbody tr' ).length;
					}

					triggers.rows.add( $editor, {
						index: index,
						type: type,
						settings: trigger_settings,
					} );

					PUM_Admin.modals.closeAll();

					if (
						trigger_settings.cookie_name !== undefined &&
						trigger_settings.cookie_name !== null &&
						( trigger_settings.cookie_name === 'add_new' ||
							trigger_settings.cookie_name.indexOf( 'add_new' ) >=
								0 )
					) {
						PUM_Admin.triggers.new_cookie = values.index;
						$(
							'#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new'
						).trigger( 'click' );
					}
				} );

				$this.val( null ).trigger( 'change' );
			}
		)
		// Add New Triggers
		.on( 'click', '.pum-popup-trigger-editor .pum-add-new', function() {
			PUM_Admin.triggers.current_editor = $( this ).parents(
				'.pum-popup-trigger-editor'
			);
			var template = wp.template( 'pum-trigger-add-type' );
			PUM_Admin.modals.reload(
				'#pum_trigger_add_type_modal',
				template( { I10n: I10n } )
			);
		} )
		.on( 'click', '.pum-popup-trigger-editor .edit', function( event ) {
			var $this = $( this ),
				$editor = $this.parents( '.pum-popup-trigger-editor' ),
				$row = $this.parents( 'tr:first' ),
				type = $row.find( '.popup_triggers_field_type' ).val(),
				values = _.extend(
					{},
					JSON.parse(
						$row
							.find( '.popup_triggers_field_settings:first' )
							.val()
					),
					{
						index: $row
							.parent()
							.children()
							.index( $row ),
						type: type,
					}
				);

			event.preventDefault();

			triggers.template.form( type, values, function( event ) {
				var $form = $( this ),
					type = $form.find( 'input#type' ).val(),
					index = $form.find( 'input#index' ).val(),
					values = $form.pumSerializeObject(),
					trigger_settings = triggers.parseValues(
						values.trigger_settings || {}
					);

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				event.preventDefault();

				if ( index === false || index < 0 ) {
					index = $editor.find( 'tbody tr' ).length;
				}

				triggers.rows.add( $editor, {
					index: index,
					type: type,
					settings: trigger_settings,
				} );

				PUM_Admin.modals.closeAll();

				if (
					trigger_settings.cookie_name !== undefined &&
					trigger_settings.cookie_name !== null &&
					( trigger_settings.cookie_name === 'add_new' ||
						trigger_settings.cookie_name.indexOf( 'add_new' ) >= 0 )
				) {
					PUM_Admin.triggers.new_cookie = values.index;
					$(
						'#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new'
					).trigger( 'click' );
				}
			} );
		} )
		.on( 'click', '.pum-popup-trigger-editor .remove', function( event ) {
			var $this = $( this ),
				$editor = $this.parents( '.pum-popup-trigger-editor' ),
				$row = $this.parents( 'tr:first' );

			// Set Current Editor.
			PUM_Admin.triggers.current_editor = $editor;

			event.preventDefault();

			if ( window.confirm( I10n.confirm_delete_trigger ) ) {
				triggers.rows.remove( $row );
			}
		} )
		.on( 'submit', '#pum_trigger_add_type_modal .pum-form', function(
			event
		) {
			var $editor = PUM_Admin.triggers.current_editor,
				$cookie_editor = $editor
					.parents( '#pum-popup-settings-triggers-subtabs_main' )
					.find( '.pum-field-cookies .pum-popup-cookie-editor' ),
				type = $( '#popup_trigger_add_type' ).val(),
				add_cookie = $( '#popup_trigger_add_cookie' ).is( ':checked' ),
				add_cookie_event = $( '#popup_trigger_add_cookie_event' ).val(),
				values = {};

			event.preventDefault();

			if ( add_cookie ) {
				values.cookie_name = 'pum-' + $( '#post_ID' ).val();
				PUM_Admin.cookies.insertCookie( $cookie_editor, {
					event: add_cookie_event,
					settings: {
						time: '1 month',
						path: '1',
						name: values.cookie_name,
					},
				} );
			}

			triggers.template.form( type, values, function( event ) {
				var $form = $( this ),
					type = $form.find( 'input#type' ).val(),
					values = $form.pumSerializeObject(),
					trigger_settings = triggers.parseValues(
						values.trigger_settings || {}
					),
					index = parseInt( values.index );

				// Set Current Editor.
				PUM_Admin.triggers.current_editor = $editor;

				event.preventDefault();

				if ( ! index || index < 0 ) {
					index = $editor.find( 'tbody tr' ).length;
				}

				triggers.rows.add( $editor, {
					index: index,
					type: type,
					settings: trigger_settings,
				} );

				PUM_Admin.modals.closeAll();

				if (
					trigger_settings.cookie_name !== undefined &&
					trigger_settings.cookie_name !== null &&
					( trigger_settings.cookie_name === 'add_new' ||
						trigger_settings.cookie_name.indexOf( 'add_new' ) >= 0 )
				) {
					PUM_Admin.triggers.new_cookie = values.index;
					$(
						'#pum-popup-settings-container .pum-popup-cookie-editor button.pum-add-new'
					).trigger( 'click' );
				}
			} );
		} );
} )( jQuery, document );
