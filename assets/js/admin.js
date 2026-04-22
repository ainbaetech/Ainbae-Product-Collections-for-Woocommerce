/**
 * Ainbae Product Collections — Admin JS
 *
 * Handles the thumbnail media uploader on the Add New Collection and
 * Edit Collection admin screens.
 */
/* global wp, ainbaeColAdmin, jQuery */
( function ( $, config ) {
	'use strict';

	var mediaFrame;

	/**
	 * Open the WP media modal and pick an image.
	 */
	function openMediaModal( $btn ) {
		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title:    config.title,
			button:   { text: config.button },
			multiple: false,
			library:  { type: 'image' },
		} );

		mediaFrame.on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

			// Fill the hidden ID field.
			$( '#ainbae_col_thumbnail_id' ).val( attachment.id );

			// Show preview.
			var imgUrl = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			var $preview = $( '#ainbae-col-thumb-preview' );
			$preview.html(
				'<img src="' + imgUrl + '" style="max-width:150px;display:block;border-radius:4px;margin-bottom:8px;" alt="">'
			);

			// Show the remove button.
			$( '.ainbae-col-remove-btn' ).show();
		} );

		mediaFrame.open();
	}

	/**
	 * Remove the thumbnail selection.
	 */
	function removeImage() {
		$( '#ainbae_col_thumbnail_id' ).val( '' );
		$( '#ainbae-col-thumb-preview' ).html( '' );
		$( '.ainbae-col-remove-btn' ).hide();
	}

	// Bind events once DOM is ready.
	$( function () {
		$( document ).on( 'click', '.ainbae-col-upload-btn', function ( e ) {
			e.preventDefault();
			openMediaModal( $( this ) );
		} );

		$( document ).on( 'click', '.ainbae-col-remove-btn', function ( e ) {
			e.preventDefault();
			removeImage();
		} );
	} );

} )( jQuery, ainbaeColAdmin );
