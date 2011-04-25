jQuery( document ).ready( function( $ ) {
	$( '.taxonomy-image-control .remove' ).click( function () {
		var term_taxonomy_id = parseInt( $( this ).attr( 'rel' ) );
		$.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				'action' : 'taxonomy_image_plugin_remove_association',
				'wp_nonce' : taxonomyImagesPluginEditTags.nonce,
				'term_taxonomy_id' : term_taxonomy_id
				},
			cache: false,
			success: function ( data ) {
				data = eval( data );
				if ( 'good' === data.status ) {
					$( '#remove-' + term_taxonomy_id ).addClass( 'hide' );
					$( '#taxonomy_image_plugin_' + term_taxonomy_id ).attr( 'src', taxonomyImagesPluginEditTags.img_src );
				}
				else if ( 'bad' === data.status ) {
					alert( data.why );
				}
			}
		});
		return false;
	} );
} );