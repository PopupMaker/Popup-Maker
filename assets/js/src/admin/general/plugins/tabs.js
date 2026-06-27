/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
( function ( $ ) {
	'use strict';

	let storage = JSON.parse( sessionStorage.getItem( 'pum_tabs' ) );

	if ( null === storage ) {
		storage = {};
	}

	// Scope the stored tab to the post being edited so each popup (or theme)
	// remembers its own last-open tab rather than sharing one global value.
	// On screens without a post (e.g. the Settings page) this is a no-op.
	// See issue #1079.
	const storageKey = function ( id ) {
		const postId = $( '#post_ID' ).val();
		return postId ? id + ':post-' + postId : id;
	};

	const updateStorage = function ( id, tab ) {
		storage[ storageKey( id ) ] = tab;
		sessionStorage.setItem( 'pum_tabs', JSON.stringify( storage ) );
	};

	var tabs = {
		init: function () {
			$( '.pum-tabs-container' )
				.filter( ':not(.pum-tabs-initialized)' )
				.each( function () {
					var $this = $( this ).addClass( 'pum-tabs-initialized' ),
						$tabList = $this.find( '> ul.tabs' ),
						$firstTab = $tabList.find( '> li:first' ),
						forceMinHeight = $this.data( 'min-height' ),
						id = $this.attr( 'id' )
							? $this.attr( 'id' )
							: $this.parents( '[id]' ).attr( 'id' );

					var storedTab = storage[ storageKey( id ) ];

					if ( typeof storedTab !== 'undefined' ) {
						// If we have a stored tab, check if it exists for this trigger type.
						var $storedTab = $tabList
							.find( 'a[href="' + storedTab + '"]' )
							.parent();

						// Only use stored tab if it exists, otherwise fall back to first tab.
						if ( $storedTab.length > 0 ) {
							$firstTab = $storedTab;
						}
					}

					if ( $this.hasClass( 'vertical-tabs' ) ) {
						var minHeight =
							forceMinHeight && forceMinHeight > 0
								? forceMinHeight
								: $tabList.eq( 0 ).outerHeight( true );

						$this.css( {
							minHeight: minHeight + 'px',
						} );

						if ( $this.parent().innerHeight < minHeight ) {
							$this.parent().css( {
								minHeight: minHeight + 'px',
							} );
						}
					}

					// Trigger first tab.
					$firstTab.trigger( 'click' );
				} );
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.tabs = tabs;

	$( document )
		.on( 'pum_init', PUM_Admin.tabs.init )
		.on( 'click', '.pum-tabs-initialized li.tab', function ( e ) {
			var $this = $( this ),
				$container = $this.parents( '.pum-tabs-container:first' ),
				$tabs = $container.find( '> ul.tabs > li.tab' ),
				$tab_contents = $container.find( '> div.tab-content' ),
				link = $this.find( 'a' ).attr( 'href' ),
				id = $this.attr( 'id' )
					? $this.attr( 'id' )
					: $this.parents( '[id]' ).attr( 'id' );

			// Store the tab.
			updateStorage( id, link );

			$tabs.removeClass( 'active' );
			$tab_contents.removeClass( 'active' );

			$this.addClass( 'active' );
			$container.find( '> div.tab-content' + link ).addClass( 'active' );

			e.preventDefault();
		} );
} )( jQuery );
