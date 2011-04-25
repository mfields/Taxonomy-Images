jQuery( document ).ready( function( $ ) {

	function set_tt_id( obj ) {
		var tt_id = $( obj ).parent().find( 'input' ).val();
		taxonomyImagesPlugin.tt_id = parseInt( tt_id );
	}

	$( '.taxonomy-image-thumbnail' ).click( function () {
		set_tt_id( $( this ) );
	} );

	$( '.taxonomy-image-control .upload' ).click( function () {
		set_tt_id( $( this ) );
	} );

	$( '.taxonomy-image-control .remove' ).click( function () {
		var term_taxonomy_id = parseInt( $( this ).attr( 'rel' ) );
		$.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				'action' : 'taxonomy_image_plugin_remove_association',
				'wp_nonce' : taxonomyImagesPlugin.nonce,
				'term_taxonomy_id' : term_taxonomy_id
				},
			cache: false,
			success: function ( data ) {
				data = eval( data );
				if ( 'good' === data.status ) {
					$( '#remove-' + term_taxonomy_id ).addClass( 'hide' );
					$( '#taxonomy_image_plugin_' + term_taxonomy_id ).attr( 'src', taxonomyImagesPlugin.img_src );
				}
				else if ( 'bad' === data.status ) {
					alert( data.why );
				}
			}
		});
		return false;
	} );
} );