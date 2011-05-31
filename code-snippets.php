<?php
/**
 * Tests for various filters.
 */


exit;



/*
 * Append the term images to content + excerpt.
 */
function mytheme_append_the_term_images( $content ) {
	return $content . apply_filters( 'taxonomy-images-list-the-terms', '', array(
		'image_size' => 'detail',
		) );
}
add_filter( 'the_content', 'mytheme_append_the_term_images' );
add_filter( 'the_excerpt', 'mytheme_append_the_term_images' );



/*
 * Queried Term Image.
 *
 * Return html markup representing the image associated with the
 * currently queried term. In the event that no associated image
 * exists, the filter should return an empty object.
 *
 * In the event that the Taxonomy Images plugin is not installed
 * apply_filters() will return it's second parameter.
 */


/* Default */
$img = apply_filters( 'taxonomy-images-queried-term-image', 'PLEASE INSTALL PLUGIN' );
print '<h2>taxonomy-images-queried-term-image</h2>';
print '<pre>' . htmlentities( $img ) . '</pre>';


/* Inside a yellow box */
$img = apply_filters( 'taxonomy-images-queried-term-image', 'PLEASE INSTALL PLUGIN', array(
	'before' => '<div style="padding:20px;background-color:yellow;">',
	'after'  => '</div>',
	) );
print '<h2>taxonomy-images-queried-term-image - custom wrapper element.</h2>';
print '<pre>' . htmlentities( $img ) . '</pre>';


/* Medium Size */
$img = apply_filters( 'taxonomy-images-queried-term-image', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'medium',
	) );
print '<h2>taxonomy-images-queried-term-image - medium image size</h2>';
print '<pre>' . htmlentities( $img ) . '</pre>';


/* Unrecognized size */
$img = apply_filters( 'taxonomy-images-queried-term-image', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'this-is-probably-not-a-real-image-size',
	) );
print '<h2>taxonomy-images-queried-term-image - unknown image size</h2>';
print '<pre>' . htmlentities( $img ) . '</pre>';


/* Custom attributes. */
$img = apply_filters( 'taxonomy-images-queried-term-image', 'PLEASE INSTALL PLUGIN', array(
	'attr' => array(
		'alt'   => 'Custom alternative text',
		'class' => 'my-class-list bunnies turtles',
		'src'   => 'this-is-where-the-image-lives.png',
		'title' => 'Custom Title',
		),
	) );
print '<h2>taxonomy-images-queried-term-image - custom attributes</h2>';
print '<pre>' . htmlentities( $img ) . '</pre>';



/*
 * Queried Term Image ID.
 *
 * Return the id of the image associated with the currently
 * queried term. In the event that no associated image exists,
 * the filter should return zero.
 *
 * In the event that the Taxonomy Images plugin is not installed
 * apply_filters() will return it's second parameter.
 */
$img = apply_filters( 'taxonomy-images-queried-term-image-id', 'PLEASE INSTALL PLUGIN' );

print '<h2>taxonomy-images-queried-term-image-id</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';




/*
 * Queried Term Image Object.
 *
 * Return an object representing the image associated with the
 * currently queried term. In the event that no associated image
 * exists, the filter should return an empty object.
 *
 * In the event that the Taxonomy Images plugin is not installed
 * apply_filters() will return it's second parameter.
 */
$img = apply_filters( 'taxonomy-images-queried-term-image-object', 'PLEASE INSTALL PLUGIN' );

print '<h2>taxonomy-images-queried-term-image-object</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';




/*
 * Queried Term Image URL.
 *
 * Return a url to the image associated with the current queried
 * term. In the event that no associated image exists, the filter
 * should return an empty string.
 *
 * In the event that the Taxonomy Images plugin is not installed
 * apply_filters() will return it's second parameter.
 */


/* Default */
$img = apply_filters( 'taxonomy-images-queried-term-image-url', 'PLEASE INSTALL PLUGIN' );
print '<h2>taxonomy-images-queried-term-image-url - Default</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';


/* Medium Size */
$img = apply_filters( 'taxonomy-images-queried-term-image-url', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'medium'
	) );
print '<h2>taxonomy-images-queried-term-image-url - Medium</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';


/* Unregistered Size */
$img = apply_filters( 'taxonomy-images-queried-term-image-url', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'this-is-not-real-size-probably-I-hope'
	) );
print '<h2>taxonomy-images-queried-term-image-url - Unregistered</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';




/*
 * Queried Term Image Data.
 *
 * Return an array of data about the image associated with the current
 * queried term. In the event that no associated image exists, the filter
 * should return an empty string.
 *
 * In the event that the Taxonomy Images plugin is not installed
 * apply_filters() will return it's second parameter.
 */


/* Default */
$img = apply_filters( 'taxonomy-images-queried-term-image-data', 'PLEASE INSTALL PLUGIN' );
print '<h2>taxonomy-images-queried-term-image-data - Default</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';


/* Medium Size */
$img = apply_filters( 'taxonomy-images-queried-term-image-data', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'medium'
	) );
print '<h2>taxonomy-images-queried-term-image-data - Medium</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';


/* Unregistered Size */
$img = apply_filters( 'taxonomy-images-queried-term-image-data', 'PLEASE INSTALL PLUGIN', array(
	'image_size' => 'this-is-not-real-size-probably-I-hope'
	) );
print '<h2>taxonomy-images-queried-term-image-data - Unregistered</h2>';
print '<pre>'; var_dump( $img ); print '</pre>';
