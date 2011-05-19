<?php
/**
 * Tests for various filters.
 */


exit;


/* Default usage. */
$img = apply_filters( 'taxonomy-images-queried-term-image', '' );
if ( ! empty( $img ) ) {
	print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';
}


/* Return the associated image's id. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'id'
	) );
print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';


/* Return the associated image's url. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'url',
	) );
print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';


/* Return the associated image's url - unrecognized image size. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'url',
	'size'   => 'this-is-not-real-size-probably-I-hope'
	) );
print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';


/* Return the associated image in an html tag. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'html'
	) );
print '<pre>' . gettype( $img ) . ' - ' . htmlentities( $img ) . '</pre>';


/* Return the associated image in an html tag. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'html',
	'size'   => 'medium'
	) );
print '<pre>' . gettype( $img ) . ' - ' . htmlentities( $img ) . '</pre>';


/* Return the associated image's url - unrecognized image size. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'url',
	'size'   => 'this-is-not-real-size-probably-I-hope'
	) );
print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';


/* Return an object representing the associated image. */
$img = apply_filters( 'taxonomy-images-queried-term-image', array(
	'return' => 'object'
	) );
print '<pre>' . gettype( $img ) . ' - ' . print_r( $img, true ) . '</pre>';
