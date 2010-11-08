jQuery( document ).ready( function( $ ) {
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
				'action' : 'taxonomy_images_create_association',
				'term_id' : taxonomyImagesPlugin.term_id,
				'wp_nonce' : taxonomyImagesPlugin.nonce,
				'attachment_id' : $( this ).attr( 'rel' )
				},
			cache: false,
			success: function ( data, textStatus ) {
				data = eval( data );
				if( data.attachment_thumb_src != 'false' ) {
					/* Refresh the image on the screen below */
					$( parent.document.getElementById( taxonomyImagesPlugin.locale + '_' + taxonomyImagesPlugin.term_id ) ).attr( 'src', data.attachment_thumb_src );
					$( parent.document.getElementById( 'remove-' + taxonomyImagesPlugin.term_id ) ).removeClass( 'hide' );
				}
				/* Close Thickbox */
				self.parent.tb_remove();
			}
		});
		return false;
	} ); 
} );