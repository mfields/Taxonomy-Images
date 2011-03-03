jQuery( document ).ready( function( $ ) {
	/* Return early if upload modal was NOT opened from edit-tags.php. */
	var Taxonomy_Images_Window = window.dialogArguments || opener || parent || top;
	if ( 'undefined' == typeof Taxonomy_Images_Window.taxonomyImagesPluginEditTags ) {
		return;
	}

	/* Add hidden input to search form. */
	if ( parseInt( taxonomyImagesPlugin.term_taxonomy_id ) > 0 ) {
		$( '#filter' ).prepend( '<input type="hidden" name="taxonomy_images_plugin" value="' + taxonomyImagesPlugin.term_taxonomy_id + '" />' );
	}

	/* Show button. */
	$( '.taxonomy-image-button' ).css( 'display', 'inline' );
} );

var TaxonomyImagesCreateAssociation;
( function( $ ) {
	TaxonomyImagesCreateAssociation = function( image_id, nonce ) {
		$.ajax( {
			url      : ajaxurl,
			type     : "POST",
			dataType : 'json',
			data: {
				'action'           : 'taxonomy_image_create_association',
				'wp_nonce'         : nonce,
				'attachment_id'    : parseInt( image_id ),
				'term_taxonomy_id' : parseInt( taxonomyImagesPlugin.term_taxonomy_id ),
				},
			success: function ( response ) {
				if ( 'good' === response.status ) {
					var selector = parent.document.getElementById( 'taxonomy-image-control-' + taxonomyImagesPlugin.term_taxonomy_id );

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

