
jQuery( document ).ready( function( $ ) {
	
	/* General UI. */
	$( '.taxonomy-image-control .control' ).hover( 
		function() { $( this ).css( 'cursor', 'pointer' ); },
		function() { $( this ).css( 'cursor', 'default' ); }
		);
	$( '.taxonomy-image-control .upload' ).hover( 
		function() { $( this ).css( 'background-position', '-15px 0' ); },
		function() { $( this ).css( 'background-position', '0 0' ); }
		);
	$( '.taxonomy-image-control .delete' ).hover( 
		function() { $( this ).css( 'background-position', '-45px 0' ); },
		function() { $( this ).css( 'background-position', '-30px 0' ); }
		);
	$( '.taxonomy-image-control .library' ).hover( 
		function() { $( this ).css( 'background-position', '-75px 0' ); },
		function() { $( this ).css( 'background-position', '-60px 0' ); }
		);
	
	/* Delete association via ajax. */
	$( '.taxonomy-image-control .delete' ).click( function () {
		var remove = $( this );
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
				$( remove ).addClass( 'hide' );
				$( remove ).parent().find( 'img' ).attr( 'src', taxonomyImagesPlugin.img_src );
			}         
		});
	} );
} );