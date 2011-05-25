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
 * @package      Taxonomy Images
 * @author       Michael Fields <michael@mfields.org>
 * @copyright    Copyright (c) 2011, Michael Fields
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        0.7
 */


add_filter( 'taxonomy-images-get-terms', 'taxonomy_images_plugin_get_terms', 10, 2 );
add_filter( 'taxonomy-images-get-the-terms', 'taxonomy_images_plugin_get_the_terms', 10, 2 );
add_filter( 'taxonomy-images-list-the-terms', 'taxonomy_images_plugin_list_the_terms', 10, 2 );
add_filter( 'taxonomy-images-queried-term-image', 'taxonomy_images_plugin_get_queried_term_image' );


/**
 * Get Terms.
 *
 * This function adds a custom property (image_id) to each
 * object returned by WordPress core function get_terms().
 * This property will be set for all term objects. In cases
 * where a term has an associated image, "image_id" will
 * contain the value of the image object's ID property. If
 * no image has been associated, this property will contain
 * integer with the value of zero.
 *
 * Recognized Arguments:
 *
 * cache_images (bool) A non-empty value will trigger
 * this function to query for and cache all associated
 * images. An empty value disables caching. Defaults to
 * boolean true.
 *
 * having_images (bool) A non-empty value will trigger
 * this function to only return terms that have associated
 * images. If an empty value is passed all terms of the 
 * taxonomy will be returned.
 *
 * taxonomy (string) Name of a registered taxonomy to
 * return terms from. Defaults to "category".
 *
 * term_args (array) Arguments to pass as the second
 * parameter of get_terms(). Defaults to an empty array.
 *
 * @param     mixed     Default value for apply_filters() to return. Unused.
 * @param     array     Named arguments. Please see above for explantion.
 * @return    array     List of term objects.
 *
 * @access    private   Use the 'taxonomy-images-get-terms' filter.
 * @since     0.7
 */
function taxonomy_images_plugin_get_terms( $default, $args = array() ) {
	$filter = 'taxonomy-images-get-terms';
	$function = __FUNCTION__ . '()';

	if ( $filter !== current_filter() ) {
		return taxonomy_image_plugin_notice_api( '<p><code>' . $function . '</code> has been called directly. Please use the "' . $filter . '" filter instead.</p>' );
	}

	$args = wp_parse_args( $args, array(
		'cache_images'  => true,
		'having_images' => true,
		'taxonomy'      => 'Xcategory',
		'term_args'     => array(),
		) );

	/*
	 * Is taxonomy registered?
	 * If not, users will be presented a notice and
	 * we will return early.
	 */
	$is_registered_taxonomy = taxonomy_image_plugin_is_registered_taxonomy( $args['taxonomy'], $filter );
	if ( 'yes' !== $is_registered_taxonomy ) {
		return $is_registered_taxonomy;
	}

	$assoc = taxonomy_image_plugin_get_associations();
	if ( empty( $assoc ) ) {
		return array();
	}

	$terms = get_terms( $args['taxonomy'], $args['term_args'] );
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$image_ids = array();
	$terms_with_images = array();
	foreach ( (array) $terms as $key => $term ) {
		$terms[$key]->image_id = 0;
		if ( array_key_exists( $term->term_taxonomy_id, $assoc ) ) {
			$terms[$key]->image_id = $assoc[$term->term_taxonomy_id];
			$image_ids[] = $assoc[$term->term_taxonomy_id];
			if ( ! empty( $args['having_images'] ) ) {
				$terms_with_images[] = $terms[$key];
			}
		}
	}
	$image_ids = array_unique( $image_ids );

	if ( ! empty( $args['cache_images'] ) ) {
		$images = array();
		if ( ! empty( $image_ids ) ) {
			$images = get_children( array( 'include' => implode( ',', $image_ids ) ) );
		}
	}

	if ( ! empty( $terms_with_images ) ) {
		return $terms_with_images;
	}
	return $terms;
}


/**
 * Get the Terms.
 *
 * This function adds a custom property (image_id) to each
 * object returned by WordPress core function get_the_terms().
 * This property will be set for all term objects. In cases
 * where a term has an associated image, "image_id" will
 * contain the value of the image object's ID property. If
 * no image has been associated, this property will contain
 * integer with the value of zero.
 *
 * Recognized Arguments:
 *
 * having_images (bool) A non-empty value will trigger
 * this function to only return terms that have associated
 * images. If an empty value is passed all terms of the
 * taxonomy will be returned. Optional.
 *
 * post_id (int) The post to retrieve terms from. Defaults
 * to the ID property of the global $post object. Optional.
 *
 * taxonomy (string) Name of a registered taxonomy to
 * return terms from. Defaults to "category". Optional.
 *
 * @param     mixed     Default value for apply_filters() to return. Unused.
 * @param     array     Named arguments. Please see above for explantion.
 * @return    array     List of term objects. Empty array if none were found.
 *
 * @access    private   Use the 'taxonomy-images-get-the-terms' filter.
 * @since     0.7
 */
function taxonomy_images_plugin_get_the_terms( $default, $args ) {
	$filter   = 'taxonomy-images-get-the-terms';
	$function = __FUNCTION__ . '()';

	if ( $filter !== current_filter() ) {
		return taxonomy_image_plugin_notice_api( '<p><code>' . $function . '</code> has been called directly. Please use the <strong>' . $filter . '</strong> filter instead.</p>' );
	}

	$args = wp_parse_args( $args, array(
		'having_images' => true,
		'post_id'       => 0,
		'taxonomy'      => 'category',
		) );

	/*
	 * Is taxonomy registered?
	 * If not, users will be presented a notice and
	 * we will return early.
	 */
	$is_registered_taxonomy = taxonomy_image_plugin_is_registered_taxonomy( $args['taxonomy'], $filter );
	if ( 'yes' !== $is_registered_taxonomy ) {
		return $is_registered_taxonomy;
	}

	$assoc = taxonomy_image_plugin_get_associations();

	if ( empty( $args['post_id'] ) ) {
		$args['post_id'] = get_the_ID();
	}

	$terms = get_the_terms( $args['post_id'], $args['taxonomy'] );

	if ( empty( $terms ) ) {
		return array();
	}

	$terms_with_images = array();
	foreach ( (array) $terms as $key => $term ) {
		$terms[$key]->image_id = 0;
		if ( array_key_exists( $term->term_taxonomy_id, $assoc ) ) {
			$terms[$key]->image_id = $assoc[$term->term_taxonomy_id];
			if ( ! empty( $args['having_images'] ) ) {
				$terms_with_images[] = $terms[$key];
			}
		}
	}
	if ( ! empty( $terms_with_images ) ) {
		return $terms_with_images;
	}
	return $terms;
}


/**
 * List the Terms.
 *
 * Lists all terms associated with a given post that
 * have associated images. Terms without images will
 * not be included.
 *
 * Recognized Arguments:
 *
 * after (string) Text to append to the output. Optional.
 * Defaults to an empty string.
 *
 * before (string) Text to preppend to the output. Optional.
 * Defaults to an empty string.
 *
 * image_size (string) Any registered image size. Values will
 * vary from installation to installation. Image sizes defined
 * in core include: "thumbnail", "medium" and "large". "Fullsize"
 * may also be used to get the un modified image that was uploaded.
 * Optional. Defaults to "thumbnail".
 *
 * post_id (int) The post to retrieve terms from. Defaults
 * to the ID property of the global $post object. Optional.
 *
 * taxonomy (string) Name of a registered taxonomy to
 * return terms from. Defaults to "category". Optional.
 *
 * @param     mixed     Default value for apply_filters() to return. Unused.
 * @param     array     Named arguments. Please see above for explantion.
 * @return    string    HTML markup.
 *
 * @access    private   Use the 'taxonomy-images-list-the-terms' filter.
 * @since     0.7
 */
function taxonomy_images_plugin_list_the_terms( $default, $args ) {
	$filter   = 'taxonomy-images-list-the-terms';
	$function = __FUNCTION__ . '()';

	if ( $filter !== current_filter() ) {
		return taxonomy_image_plugin_notice_api( '<p><strong>' . $function . '</strong> has been called directly. Please use the <strong>' . $filter . '</strong> filter instead.</p>' );
	}

	$args = wp_parse_args( $args, array(
		'after'      => '',
		'before'     => '',
		'image_size' => 'thumbnail',
		'post_id'    => 0,
		'taxonomy'   => 'category',
		) );

	$args['having_images'] = true;

	/*
	 * Is taxonomy registered?
	 * If not, users will be presented a notice and
	 * we will return early.
	 */
	$is_registered_taxonomy = taxonomy_image_plugin_is_registered_taxonomy( $args['taxonomy'], $filter );
	if ( 'yes' !== $is_registered_taxonomy ) {
		return $is_registered_taxonomy;
	}

	$terms = apply_filters( 'taxonomy-images-get-the-terms', '', $args );

	if ( empty( $terms ) ) {
		return '';
	}

	$output = '';
	foreach( $terms as $term ) {
		if ( ! isset( $term->image_id ) ) {
			continue;
		}
		$output .= wp_get_attachment_image( $term->image_id, $args['image_size'] );
	}

	return $args['before'] . $output . $args['after'];
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
 * @param     mixed     Default value for apply_filters() to return. Unused.
 * @param     array     Named array of arguments.
 * @return    mixed     Plese see 'return' section above for description.
 *
 * @access    private   Use the 'taxonomy-images-queried-term-image' filter.
 * @since     0.7
 */
function taxonomy_images_plugin_get_queried_term_image( $args ) {
	$filter   = 'taxonomy-images-get-queried-term-image';
	$function = __FUNCTION__ . '()';

	if ( $filter !== current_filter() ) {
		return taxonomy_image_plugin_notice_api( '<p><strong>' . $function . '</strong> has been called directly. Please use the <strong>' . $filter . '</strong> filter instead.</p>' );
	}

	if ( ! is_category() && ! is_tag() && ! is_taxonomy() ) {
		return taxonomy_image_plugin_notice_api( '<p>The <strong>' . $filter . '</strong> has been used in an unsupported template file. This function can only be used in taxonomy archives. Please move to category.php, tag.php, taxonomy.php or a more specific taxonomy template. Please read up on <a href="http://codex.wordpress.org/Template_Hierarchy">Template Hierarchy</a> in the Codex for specific templates.</p>' );
	}

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