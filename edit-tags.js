jQuery( document ).ready( function( $ ) {

	function set_global( obj ) {
		taxonomyImagesPlugin.tt_id = parseInt( $( obj ).parent().find( 'input.tt_id' ).val() );
		taxonomyImagesPlugin.term_name = $( obj ).parent().find( 'input.term_name' ).val();
		console.log( taxonomyImagesPlugin.tt_id );
		console.log( taxonomyImagesPlugin.term_name );
	}

	$( '.taxonomy-image-control a' ).live( 'click', function () {
		set_global( $( this ) );
	} );

	$( '.taxonomy-image-control .remove' ).live( 'click', function () {
		var term_taxonomy_id = parseInt( $( this ).attr( 'rel' ) );
		$.ajax( {
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				'action' : 'taxonomy_image_plugin_remove_association',
				'wp_nonce' : taxonomyImagesPlugin.nonce,
				'term_taxonomy_id' : term_taxonomy_id
				},
			cache: false,
			success: function ( response ) {
				if ( 'good' === response.status ) {
					$( '#remove-' + term_taxonomy_id ).addClass( 'hide' );
					$( '#taxonomy_image_plugin_' + term_taxonomy_id ).attr( 'src', taxonomyImagesPlugin.img_src );
				}
				else if ( 'bad' === response.status ) {
					alert( response.why );
				}
			}
		} );
		return false;
	} );
} );