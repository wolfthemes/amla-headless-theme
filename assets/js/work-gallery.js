/**
 * ML Archi — Work gallery picker.
 *
 * Wires the "Galerie du projet" metabox to the WP media library: multi-select
 * add, drag-to-reorder (jQuery UI sortable), per-thumb remove. The ordered
 * attachment IDs are mirrored into a hidden CSV input that PHP saves as
 * `_work_gallery`.
 */
( function ( $ ) {
	'use strict';

	var l10n = window.mlArchiWorkGallery || {};

	function init( container ) {
		var $container = $( container );
		var $input = $container.find( '.ml-archi-gallery-ids' );
		var $preview = $container.find( '.ml-archi-gallery-preview' );
		var frame;

		// Rebuild the CSV from the current DOM order.
		function syncInput() {
			var ids = $preview
				.find( '.ml-archi-gallery-item' )
				.map( function () {
					return $( this ).data( 'id' );
				} )
				.get();

			$input.val( ids.join( ',' ) );
		}

		// Current IDs as an array of numbers.
		function currentIds() {
			return String( $input.val() || '' )
				.split( ',' )
				.map( function ( id ) {
					return parseInt( id, 10 );
				} )
				.filter( function ( id ) {
					return ! isNaN( id );
				} );
		}

		function makeItem( id, url ) {
			var $li = $( '<li>', {
				'class': 'ml-archi-gallery-item',
			} ).attr( 'data-id', id );

			$( '<img>', { src: url, alt: '' } ).appendTo( $li );

			$( '<button>', {
				type: 'button',
				'class': 'ml-archi-gallery-remove',
				'aria-label': l10n.removeLabel || 'Remove',
				text: '×',
			} ).appendTo( $li );

			return $li;
		}

		// Open (or reopen) the media frame seeded with the current selection.
		function openFrame() {
			if ( frame ) {
				frame.open();
				preselect();
				return;
			}

			frame = window.wp.media( {
				title: l10n.frameTitle || 'Gallery',
				button: { text: l10n.buttonText || 'Add' },
				library: { type: 'image' },
				multiple: 'add',
			} );

			frame.on( 'open', preselect );

			frame.on( 'select', function () {
				var selection = frame.state().get( 'selection' );

				$preview.empty();

				selection.each( function ( attachment ) {
					var a = attachment.toJSON();
					var url =
						( a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url ) ||
						a.url;

					$preview.append( makeItem( a.id, url ) );
				} );

				syncInput();
			} );

			frame.open();
		}

		// Reflect the saved IDs into the frame's selection when it opens.
		function preselect() {
			var selection = frame.state().get( 'selection' );

			selection.reset();

			currentIds().forEach( function ( id ) {
				var attachment = window.wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		}

		$container.on( 'click', '.ml-archi-gallery-add', function ( e ) {
			e.preventDefault();
			openFrame();
		} );

		$container.on( 'click', '.ml-archi-gallery-remove', function ( e ) {
			e.preventDefault();
			$( this ).closest( '.ml-archi-gallery-item' ).remove();
			syncInput();
		} );

		if ( $preview.sortable ) {
			$preview.sortable( {
				update: syncInput,
			} );
		}
	}

	$( function () {
		$( '[data-ml-archi-gallery]' ).each( function () {
			init( this );
		} );
	} );
} )( jQuery );
