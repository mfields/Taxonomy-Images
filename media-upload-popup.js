var TaxonomyImagesCreateAssociation;

jQuery( document ).ready( function( $ ) {
	var ID = 0, below;

	/* Get window that opened the thickbox. */
	below = window.dialogArguments || opener || parent || top;

	/* Set the value of ID. */
	if ( null !== below && 'taxonomyImagesPlugin' in below && 'tt_id' in below.taxonomyImagesPlugin ) {
		ID = parseInt( below.taxonomyImagesPlugin.tt_id );
		if ( isNaN( ID ) ) {
			ID = 0;
		}
	}

	if ( 0 < ID ) {
		var buttons = $( '.taxonomy-image-button' );
		$( 'body' ).addClass( 'taxonomy-images-modal' );

		/* Add hidden input to search form. */
		$( '#filter' ).prepend( '<input type="hidden" name="taxonomy_images_plugin" value="' + ID + '" />' );
	}


	TaxonomyImagesCreateAssociation = function( el, image_id, nonce ) {
		var button, text, selector;
		if ( 0 == ID ) {
			return;
		}

		button = $( el );
		button.text( 'Adding ...' );

		/* Show all other buttons. */
		buttons.each( function( i, e ) {
			$( e ).show();
		} );

		$.ajax( {
			url      : ajaxurl,
			type     : "POST",
			dataType : 'json',
			data: {
				'action'           : 'taxonomy_image_create_association',
				'wp_nonce'         : nonce,
				'attachment_id'    : parseInt( image_id ),
				'term_taxonomy_id' : parseInt( ID ),
				},
			success: function ( response ) {
				if ( 'good' === response.status ) {
					var selector = parent.document.getElementById( 'taxonomy-image-control-' + ID );

					/* Update the image on the screen below */
					$( selector ).find( '.taxonomy-image-thumbnail img' ).each( function ( i, e ) {
						$( e ).attr( 'src', response.attachment_thumb_src );
					} );

					/* Show delete control on the screen below */
					$( selector ).find( '.remove' ).each( function ( i, e ) {
						$( e ).removeClass( 'hide' );
					} );

					button.fadeOut( 200, function() {
						$( this ).show().text( 'Successfully added!' );
					} );

					/* Close Thickbox */
				//	self.parent.tb_remove();
				}
				else if ( 'bad' === response.status ) {
					alert( response.why );
				}
			}
		} );
		return false;
	}
} );