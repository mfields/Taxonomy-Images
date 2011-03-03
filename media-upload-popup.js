jQuery( document ).ready( function( $ ) {

	/* Return early if upload modal was NOT opened from edit-tags.php. */
	if ( 'undefined' == typeof taxonomyImagesPlugin ) {
		return;
	}

	/* Only show button when media upload modal is opened from edit-tags.php. */
	$( '.taxonomy-image-button' ).show();

	/*
	 * Loop over all anchors in the media upload iframe and add
	 * a query var for those links that do not already possess
	 * one.
	 */
	$.each( $( 'a' ), function ( order, img ) {
		var href = $( this ).attr( 'href' );
		if( -1 === href.indexOf( taxonomyImagesPlugin.attr_slug ) && href !== '#' ) {
			var append = '&' + taxonomyImagesPlugin.attr;
			if( -1 === href.indexOf( '?' ) ) {
				append = '?' + taxonomyImagesPlugin.attr;
			}
			$( this ).attr( 'href', href + append );
		}
	});

	$( '.' + taxonomyImagesPlugin.locale ).live( 'click', function () {
		$.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				'action' : 'taxonomy_image_create_association',
				'wp_nonce' : taxonomyImagesPlugin.nonce,
				'attachment_id' : $( this ).attr( 'rel' ),
				'term_taxonomy_id' : taxonomyImagesPlugin.term_taxonomy_id,
				},
			cache: false,
			success: function ( data, textStatus ) {
				data = eval( data );
				if ( 'good' === data.status ) {
					/* Refresh the image on the screen below */
					$( parent.document.getElementById( taxonomyImagesPlugin.locale + '_' + taxonomyImagesPlugin.term_taxonomy_id ) ).attr( 'src', data.attachment_thumb_src );
					$( parent.document.getElementById( 'remove-' + taxonomyImagesPlugin.term_taxonomy_id ) ).removeClass( 'hide' );
					/* Close Thickbox */
					self.parent.tb_remove();
				}
				else if ( 'bad' === data.status ) {
					alert( data.why );
				}
			}
		});
		return false;
	} ); 
} );