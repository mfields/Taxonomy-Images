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
	$( '.taxonomy-image-control .delete' )
		.hover(
			function() { $( this ).css( 'background-position', '-45px 0' ); },
			function() { $( this ).css( 'background-position', '-30px 0' ); }
			)
		.click( function () {
			$.ajax({
				url: ajaxurl,
				type: "POST",
				dataType: 'json',
				data: {
					'action' : 'taxonomy_images_remove_association',
					'term_id' : parseInt( $( this ).attr( 'rel' ) ),
					'wp_nonce' : taxonomyImagesPlugin.nonce_remove
					},
				cache: false,
				success: function ( data, textStatus ) {
					$( this ).addClass( 'hide' );
					$( this ).parent().find( 'img' ).attr( 'src', taxonomyImagesPlugin.img_src );
				}
			});
		} );
} );