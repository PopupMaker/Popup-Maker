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
