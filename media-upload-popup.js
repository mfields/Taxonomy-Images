jQuery( document ).ready( function( $ ) {
	/*
	* Loop over all anchors in the media upload iframe and add
	* a query var for those links that do not already possess
	* one.
	*/
	$.each( $( 'a' ), function ( order, img ) {
		
		/* Capture href attribute for all links on page.*/
		var href = $( this ).attr( 'href' );
		
		/* See if custom attribute already exists. */
		var hasAttr = href.indexOf( taxonomyImagesPlugin.attrSlug );
		
		/* See if there is a question mark in the url. */
		var hasQueryString = href.indexOf( '?' );
		
		/* Set to true if href contains only the hash character. */
		var isHash = ( href == '#' ) ? true : false;
		
		/* Append attribute to all links that do not already posses it. */
		if( hasAttr == -1 && !isHash ) {
			if( hasQueryString == -1 ) {
				href += '?' + taxonomyImagesPlugin.attr;
			}
			else {
				href += '&' + taxonomyImagesPlugin.attr;
			}
		}
		
		/* Replace the href attribute with new value. */
		$( this ).attr( 'href', href );
	});
	$( '.' + taxonomyImagesPlugin.locale ).live( 'click', function () {
		var data = {
			'action' : 'taxonomy_images_create_association',
			'term_id' : taxonomyImagesPlugin.term_id,
			'wp_nonce' : taxonomyImagesPlugin.nonce_create,
			'attachment_id' : $( this ).attr( 'rel' )
			};
		
		/* Process $_POST request */
		$.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',							
			data: data,
			cache: false,
			success: function ( data, textStatus ) {
				/* Vars */
				data = eval( data );
				var tableRowId = 'cat-' + data.term_id;
				
				/* Refresh the image on the screen below */
				if( data.attachment_thumb_src != 'false' ) {
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