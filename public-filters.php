<?php
/**
 * Interface.
 *
 * All functions defined in this plugin should be considered
 * private meaning that they are not to be used in any other
 * WordPress extension including plugins and themes. Direct
 * use of functions defined herein constitutes unsupported use
 * and is strongly discouraged. This file contains custom filters
 * have been added which enable extension authors to interact with
 * this plugin in a responsible manner.
 *
 * @since     0.7
 */


add_filter( 'taxonomy-images-list', 'taxonomy_images_plugin_image_list' );
add_filter( 'taxonomy-images-queried-term-image', 'taxonomy_images_plugin_get_queried_term_image' );


/**
 * List Taxonomy Images.
 *
 * Generate a list of all terms of a given taxonomy with associated images.
 *
 * This is basically a wrapper for get_terms() and get_the_terms(). The distiction
 * can be made by via the 'context' argument. If this argument is not set
 * (or has an empty value) this function will use get_the_terms(). However,
 * if the 'context' argument is set to 'all', this function will use get_terms().
 *
 * When get_the_terms() is triggered, terms of the given taxonomy, associated
 * with the current global post object will be targeted. In cases where terms
 * associated with a specific post need to be targeted, users should set the
 * 'post_id' argument with the ID of the desired post.
 *
 * When get_terms() is triggered, all terms of the given taxonomy that have
 * images will be targeted. Triggering get_terms() will allow this function
 * to recognize additional arguments which can be used to modify the output.
 * The follow additional parameters are recognized:
 *
 * exclude - A comma- or space-delimited string of term ids to exclude from
 * the results. This argument will be ignored if 'include' is not empty. An
 * array may NOT be used as the value.
 *
 * include - A comma- or space-delimited string of term ids to include in the
 * results. An array may NOT be used as the value.
 *
 * This function should never be called directly in any file however it may
 * be access in any template file via the 'taxonomy-images-list' filter.
 *
 * @param     array     Arguments to pass as the second parameter of get_terms().
 * @return    string    HTML markup displaying taxonomy images. HTML comment upon error.
 *
 * @access    private
 * @since     2010-12-04
 */
function taxonomy_images_plugin_image_list( $args ) {
	$args = wp_parse_args( $args, array(
		'context'   => '',
		'size'      => 'detail',
		'taxonomy'  => 'category',
		'post_id'   => 0,
		'item'      => '<li><a href="%1$s">%2$s</a></li>',
		'container' => '<ul>%1$s</ul>',

		'include'  => null,
		'exclude'  => null,

		) );

	$o = '';
	$terms = array();

	/* No taxonomy defined return an html comment. */
	if ( ! taxonomy_exists( $args['taxonomy'] ) ) {
		$tax = strip_tags( trim( $args['taxonomy'] ) );
		return '<!-- taxonomy_image_plugin: ' . sprintf( __( '"%1$s" does not exist on your WordPress installation.', 'taxonomy-images' ), esc_html( $args['taxonomy'] ) ) . ' -->';
	}

	/* Get all image/term associations. */
	$associations = taxonomy_image_plugin_get_associations();

	/* Get all terms in the given taxonomy. */
	if ( 'all' === $args['context'] ) {
		$tax_args = array();
		if ( isset( $args['exclude'] ) ) {
			$tax_args['exclude'] = $args['exclude'];
		}
		if ( isset( $args['include'] ) ) {
			$tax_args['include'] = $args['include'];
		}
		$terms = get_terms( $args['taxonomy'], $tax_args );
	}
	else {
		$terms = get_the_terms( $args['post_id'], $args['taxonomy'] );
	}

	if ( is_wp_error( $terms ) ) {
		return '<!-- taxonomy_image_plugin: ' . __( 'Error retrieving terms.', 'taxonomy-images' ) . ' -->';
	}

	foreach ( (array) $terms as $term ) {
		$image = '';
		if ( array_key_exists( $term->term_taxonomy_id, $associations ) ) {
			$image = wp_get_attachment_image( $associations[$term->term_taxonomy_id], $args['size'] );
		}
		if ( empty( $image ) ) {
			continue;
		}
		$o.= sprintf( $args['item'], esc_url( get_term_link( $term, $term->taxonomy ) ), $image );
	}

	if ( ! empty( $o ) ) {
		return sprintf( $args['container'], $o );
	}

	return '<!-- taxonomy_image_plugin: ' . __( 'There are no terms with images.', 'taxonomy-images' ) . ' -->';
}


/**
 * Queried Term Image.
 *
 * Designed to be used in archive templates including
 * (but not limited to) archive.php, category.php, tag.php,
 * taxonomy.php as well as derivatives of these templates.
 *
 * Return Value
 * This function will return a representation of the image
 * associated with the term currently queried. If no image
 * has been associated, an empty value will be returned.
 * Users may choose the type of value to be returned by
 * adjusting the value of the "return" argument. Recognized
 * values are listed below:
 *
 * id - An integer representing the image attachment's ID.
 * In the event that an image has been associated zero will
 * be returned.
 *
 * object - All data stored in the WordPress posts table for
 * the image associated with the term in object form. In the
 * event that no image is found an empty object will be returned.
 *
 * url - A full url to the image file, empty string if no image
 * is associated.
 *
 * image-data - A array of data representing the image with named
 * keys. This is unfiltered output of WordPress core function
 * image_get_intermediate_size(). On success the array will contain
 * the following keys: "file", "width", "height", "path" and "url".
 * In the even that no image has been associated with the queried
 * term an empty array will be returned.
 *
 * html - (default) HTML markup to display the associated image.
 *
 * Image Size
 * Intermediate image urls may be requested by setting the "size"
 * argument. If no image size is specified, "thumbnail" will be
 * used as a default value. In the event that an unregistered size
 * is specified, this function will return an empty value.
 *
 * This function should never be called directly in any file
 * however it may be access in any template file via the
 * 'taxonomy-images-queried-term-image' filter.
 *
 * @param     array     Named array of arguments.
 * @return    mixed     Plese see 'return' section above for description.
 *
 * @access    private
 * @since     0.7
 */
function taxonomy_images_plugin_get_queried_term_image( $args ) {
	$args = wp_parse_args( $args, array(
		'return' => 'html',
		'size'   => 'thumbnail',
		'before' => '',
		'after'  => '',
		) );

	global $wp_query;
	$obj = get_queried_object();

	$tt_id = 0;
	if ( isset( $obj->term_taxonomy_id ) ) {
		$tt_id = absint( $obj->term_taxonomy_id );
	}

	$ID = 0;
	$associations = taxonomy_image_plugin_get_associations();
	if ( array_key_exists( $tt_id, $associations ) ) {
		$ID = $associations[$tt_id];
	}

	if ( 'id' == $args['return'] ) {
		return $ID;
	}
	else if ( 'object' == $args['return'] ) {
		$image = get_post( $ID );
		if ( isset( $image->post_mime_type ) && false !== strpos( $image->post_mime_type, 'image' ) ) {
			return $image;
		}
		else {
			return new stdClass;
		}
	}
	else if ( 'url' == $args['return'] ) {
		$image = image_get_intermediate_size( $ID, $args['size'] );
		if ( isset( $image['url'] ) ) {
			return $image['url'];
		}
		else {
			return '';
		}
	}
	else if ( 'image-data' == $args['return'] ) {
		$image = image_get_intermediate_size( $ID, $html_args );
		if ( is_array( $image ) ) {
			return $image;
		}
		else {
			return array();
		}
	}
	else if ( 'html' == $args['return'] ) {
		return $args['before'] . wp_get_attachment_image( $ID, $args['size'] ) . $args['after'];
	}
	return false;
}