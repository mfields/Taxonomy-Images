jQuery( document ).ready( function( $ ) {
	$( '.taxonomy-image-control .control' ).hover(
		function() { $( this ).css( 'cursor', 'pointer' ); },
		function() { $( this ).css( 'cursor', 'default' ); }
		);
	$( '.taxonomy-image-control .upload' ).hover(
		function() { $( this ).css( 'background-position', '-15px 0' ); },
		function() { $( this ).css( 'background-position', '0 0' ); }
		);
	$( '.taxonomy-image-control .library' ).hover(
		function() { $( this ).css( 'background-position', '-75px 0' ); },
		function() { $( this ).css( 'background-position', '-60px 0' ); }
		);
	$( '.taxonomy-image-control .remove' )
		.hover(
			function() { $( this ).css( 'background-position', '-45px 0' ); },
			function() { $( this ).css( 'background-position', '-30px 0' ); }
			)
		.click( function () {
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
		} );
} );