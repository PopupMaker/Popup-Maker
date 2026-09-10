/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
( function ( $ ) {
	'use strict';

	var select2 = {
		init: function () {
			$( '.pum-field-select2 select' )
				.filter( ':not(.pumselect2-initialized)' )
				.each( function () {
					var $this = $( this ),
						current = $this.data( 'current' ) || $this.val(),
						object_type = $this.data( 'objecttype' ),
						object_key = $this.data( 'objectkey' ),
						object_excludes =
							$this.data( 'objectexcludes' ) || null,
						options = {
							width: '100%',
							multiple: false,
							dropdownParent: $this.parent(),
						};

					if ( $this.attr( 'multiple' ) ) {
						options.multiple = true;
					}

					if ( object_type && object_key ) {
						options = $.extend( options, {
							ajax: {
								url: ajaxurl,
								dataType: 'json',
								delay: 250,
								data: function ( params ) {
									return {
										action: 'pum_object_search',
										nonce: pum_admin_vars.object_search_nonce,
										s: params.term, // search term
										paged: params.page,
										object_type: object_type,
										object_key: object_key,
										exclude: object_excludes,
									};
								},
								processResults: function ( data, params ) {
									// Decode server-escaped labels before Select2 safely renders them as text.
									params.page = params.page || 1;

									return {
										results: $.map(
											data.items,
											function ( item ) {
												item.text =
													select2.decodeObjectText(
														item.text
													);

												return item;
											}
										),
										pagination: {
											more:
												params.page * 10 <
												data.total_count,
										},
									};
								},
								cache: true,
							},
							cache: true,
							maximumInputLength: 20,
							closeOnSelect: ! options.multiple,
							templateResult: PUM_Admin.select2.formatObject,
							templateSelection:
								PUM_Admin.select2.formatObjectSelection,
						} );
					}

					$this
						.addClass( 'pumselect2-initialized' )
						.pumselect2( options );

					if ( current !== null && current !== undefined ) {
						if (
							options.multiple &&
							'object' !== typeof current &&
							current !== ''
						) {
							current = [ current ];
						} else if ( ! options.multiple && current === '' ) {
							current = null;
						}
					} else {
						current = null;
					}

					if (
						object_type &&
						object_key &&
						current !== null &&
						( typeof current === 'number' || current.length )
					) {
						$.ajax( {
							url: ajaxurl,
							data: {
								action: 'pum_object_search',
								nonce: pum_admin_vars.object_search_nonce,
								object_type: object_type,
								object_key: object_key,
								exclude: object_excludes,
								include:
									current && current.length
										? typeof current === 'string' ||
										  typeof current === 'number'
											? [ current ]
											: current
										: null,
							},
							dataType: 'json',
							success: function ( data ) {
								$.each( data.items, function ( key, item ) {
									item.text = select2.decodeObjectText(
										item.text
									);

									// Add any option that doesn't already exist
									if (
										! $this.find(
											'option[value="' + item.id + '"]'
										).length
									) {
										$this.prepend(
											$( '<option>' )
												.val( item.id )
												.text( item.text )
										);
									}
								} );
								// Update the options
								$this.val( current ).trigger( 'change' );
							},
						} );
					} else if (
						current &&
						( ( options.multiple && current.length ) ||
							( ! options.multiple && current !== '' ) )
					) {
						$this.val( current ).trigger( 'change' );
					} else if ( current === null ) {
						$this.val( current ).trigger( 'change' );
					}
				} );
		},
		formatObject: function ( object ) {
			return object.text;
		},
		formatObjectSelection: function ( object ) {
			return object.text || object.text;
		},
		decodeObjectText: function ( text ) {
			var textarea = document.createElement( 'textarea' );
			textarea.innerHTML = text;

			return textarea.value;
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.select2 = select2;

	$( document ).on( 'pum_init', function () {
		PUM_Admin.select2.init();
	} );
} )( jQuery );
