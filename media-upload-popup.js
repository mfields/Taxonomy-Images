jQuery( document ).ready( function( $ ) {

	var tt_id = TaxonomyImagesGetTT_ID();

	if ( isNaN( tt_id ) || 0 == tt_id ) {
		return;
	}

	/* Add hidden input to search form. */
	if ( tt_id > 0 ) {
		$( '#filter' ).prepend( '<input type="hidden" name="taxonomy_images_plugin" value="' + tt_id + '" />' );
	}

	/* Show taxonomy button. */
	$( '.taxonomy-image-button' ).css( 'display', 'inline' );

	/* Hide "Insert into Post" button */
	$( '.savesend input' ).hide();

} );

var TaxonomyImagesGetTT_ID, TaxonomyImagesCreateAssociation;

( function( $ ) {
	TaxonomyImagesGetTT_ID = function() {
		var Taxonomy_Images_Window = window.dialogArguments || opener || parent || top;
		if ( 'undefined' == typeof Taxonomy_Images_Window.taxonomyImagesPlugin ) {
			return 0;
		}
		if ( 'undefined' == typeof Taxonomy_Images_Window.taxonomyImagesPlugin.tt_id ) {
			return 0;
		}
		return parseInt( Taxonomy_Images_Window.taxonomyImagesPlugin.tt_id );
	}
} )( jQuery );

( function( $ ) {
	TaxonomyImagesCreateAssociation = function( image_id, nonce ) {
		var tt_id = TaxonomyImagesGetTT_ID();
		if ( isNaN( tt_id ) || 0 == tt_id ) {
			return;
		}
		$.ajax( {
			url      : ajaxurl,
			type     : "POST",
			dataType : 'json',
			data: {
				'action'           : 'taxonomy_image_create_association',
				'wp_nonce'         : nonce,
				'attachment_id'    : parseInt( image_id ),
				'term_taxonomy_id' : parseInt( tt_id ),
				},
			success: function ( response ) {
				if ( 'good' === response.status ) {
					var selector = parent.document.getElementById( 'taxonomy-image-control-' + tt_id );

					/* Update the image on the screen below */
					$( selector ).find( '.taxonomy-image-thumbnail img' ).each( function ( i, e ) {
						$( e ).attr( 'src', response.attachment_thumb_src );
					} );

					/* Show delete control on the screen below */
					$( selector ).find( '.remove' ).each( function ( i, e ) {
						$( e ).removeClass( 'hide' );
					} );

					/* Close Thickbox */
					self.parent.tb_remove();
				}
				else if ( 'bad' === response.status ) {
					alert( response.why );
				}
			}
		});
		return false;
	}
} )( jQuery );

