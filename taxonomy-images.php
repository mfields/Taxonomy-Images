<?php
/*
Plugin Name:          Taxonomy Images
Plugin URI:           http://wordpress.mfields.org/plugins/taxonomy-images/
Description:          The Taxonomy Images plugin enables you to associate images from your Media Library to categories, tags and taxonomies.
Version:              0.6 - ALPHA
Author:               Michael Fields
Author URI:           http://wordpress.mfields.org/
License:              GPLv2

Copyright 2010  Michael Fields  michael@mfields.org

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'TAXONOMY_IMAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TAXONOMY_IMAGE_PLUGIN_SLUG', 'taxonomy_images_plugin' );
define( 'TAXONOMY_IMAGE_PLUGIN_VERSION', '0.6' );
define( 'TAXONOMY_IMAGE_PLUGIN_PERMISSION', 'manage_categories' );

$taxonomy_image_plugin_image = array(
	'name' => 'detail',
	'size' => array( 75, 75, true )
	);

/**
 * Register custom image size with WordPress.
 *
 * @return    void
 *
 * @global    array     $taxonomy_image_plugin_image.
 * @access    private
 * @since     2010-10-28
 */
function taxonomy_image_plugin_add_image_size() {
	global $taxonomy_image_plugin_image;
	add_image_size(
		$taxonomy_image_plugin_image['name'],
		$taxonomy_image_plugin_image['size'][0],
		$taxonomy_image_plugin_image['size'][1],
		$taxonomy_image_plugin_image['size'][2]
		);
}
add_action( 'init', 'taxonomy_image_plugin_add_image_size' );


/**
 * Create a button in the modal media window to associate the current image to the term.
 *
 * @param     array     Multidimensional array representing the images form.
 * @param     stdClass  WordPress post object.
 * @return    array     The image's form array with added button if modal window was accessed by this script.
 *
 * @access    private
 * @since     2010-10-28
 * @alter     2011-03-03
 */
function taxonomy_image_plugin_add_image_to_taxonomy_button( $fields, $post ) {
	if ( isset( $fields['image-size'] ) && isset( $post->ID ) ) {
		$fields['image-size']['extra_rows']['taxonomy-image-plugin-button']['html'] = '<a rel="' . esc_attr( $post->ID ) . '" class="button-primary taxonomy-image-button" href="#" onclick="return false;">' . esc_html__( 'Add Thumbnail to Taxonomy', 'taxonomy_image_plugin' ) . '</a>';
	}
	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'taxonomy_image_plugin_add_image_to_taxonomy_button', 20, 2 );


/**
 * Return a raw uri to a custom image size.
 *
 * If size doesn't exist, attempt to create a resized version.
 * The output of this function should be escaped before printing to the browser.
 *
 * @param     int       The database id of an image attachment.
 * @return    string    URI of custom image on success; emtpy string otherwise.
 *
 * @global    array     $taxonomy_image_plugin_image.
 * @access    private.
 * @since     2010-10-28
 */
function taxonomy_image_plugin_get_image_src( $id ) {
	global $taxonomy_image_plugin_image;

	/* Return url to custom intermediate size if it exists. */
	$img = image_get_intermediate_size( $id, $taxonomy_image_plugin_image['name'] );
	if( isset( $img['url'] ) ) {
		return $img['url'];
	}

	/* Detail image does not exist, attempt to create it. */
	$wp_upload_dir = wp_upload_dir();
	if ( isset( $wp_upload_dir['basedir'] ) ) {

		/* Create path to original uploaded image. */
		$path = trailingslashit( $wp_upload_dir['basedir'] ) . get_post_meta( $id, '_wp_attached_file', true );
		if ( is_file( $path ) ) {

			/* Attempt to create a new downsized version of the original image. */
			$new = image_resize( $path,
				$taxonomy_image_plugin_image['size'][0],
				$taxonomy_image_plugin_image['size'][1],
				$taxonomy_image_plugin_image['size'][2]
				);

			/* Image creation successful. Generate and cache image metadata. Return url. */
			if ( ! is_wp_error( $new ) ) {
				$meta = wp_generate_attachment_metadata( $id, $path );
				wp_update_attachment_metadata( $id, $meta );
				$img = image_get_intermediate_size( $id, $taxonomy_image_plugin_image['name'] );
				if ( isset( $img['url'] ) ) {
					return $img['url'];
				}
			}
		}
	}

	/* Custom intermediate size cannot be created, try for thumbnail. */
	$img = image_get_intermediate_size( $id, 'thumbnail' );
	if ( isset( $img['url'] ) ) {
		return $img['url'];
	}

	/* Thumbnail cannot be found, try fullsize. */
	$url = wp_get_attachment_url( $id );
	if ( ! empty( $url ) ) {
		return $url;
	}

	/*
	 * No image can be found.
	 * This is most likely caused by a user deleting an attachment before deleting it's association with a taxonomy.
	 * If we are in the administration panels:
	 * - Delete the association.
	 * - Return uri to default.png.
	 */
	if ( is_admin() ) {
		$associations = taxonomy_image_plugin_sanitize_associations( get_option( 'taxonomy_image_plugin' ) );
		foreach ( $associations as $term => $img ) {
			if ( $img === $id ) {
				unset( $associations[$term] );
			}
		}
		update_option( 'taxonomy_image_plugin', $associations );
		return TAXONOMY_IMAGE_PLUGIN_URL . 'default.png';
	}

	/*
	 * No image can be found.
	 * Return path to blank-image.png.
	 */
	return TAXONOMY_IMAGE_PLUGIN_URL . 'blank.png';
}


/*
 * Remove the uri tab from the media upload box.
 *
 * This plugin only supports associating images from the media library.
 * Leaving this tab will only confuse users.
 *
 * @param     array     An associative array representing the navigation in the modal media box.
 * @return    array     Altered navigation list if modal media box is accessed via this script.
 *
 * @access    private
 */
function taxonomy_image_plugin_media_upload_remove_url_tab( $tabs ) {
	if ( isset( $_GET[TAXONOMY_IMAGE_PLUGIN_SLUG] ) ) {
		unset( $tabs['type_url'] );
	}
	return $tabs;
}
add_filter( 'media_upload_tabs', 'taxonomy_image_plugin_media_upload_remove_url_tab' );


/*
 * Ensures that all key/value pairs are positive integers.
 * This filter will discard all zero and negative values.
 *
 * @param     array     An array of term_taxonomy_id/attachment_id pairs.
 * @return    array     Sanitized version of parameter.
 *
 * @access    public
 */
function taxonomy_image_plugin_sanitize_associations( $associations ) {
	$o = array();
	foreach ( (array) $associations as $term_taxonomy_id => $image_id ) {
		$term_taxonomy_id = (int) $term_taxonomy_id;
		$image_id = (int) $image_id;
		/* Object IDs cannot be zero. */
		if ( 0 < $term_taxonomy_id && 0 < $image_id ) {
			$o[ $term_taxonomy_id ] = $image_id;
		}
	}
	return $o;
}


/**
 * JSON Respose.
 * Terminate script execution.
 *
 * @param     array     Associative array of values to be encoded in JSON.
 * @return    void
 *
 * @access    private
 */
function taxonomy_image_plugin_json_response( $response ) {
	header( 'Content-type: application/jsonrequest' );
	print json_encode( $response );
	exit;
}


/**
 * Register settings with WordPress.
 *
 * @return    void
 *
 * @access    private
 */
function taxonomy_image_plugin_register_setting() {
	register_setting( 'taxonomy_image_plugin', 'taxonomy_image_plugin', 'taxonomy_image_plugin_sanitize_associations' );
}
add_action( 'admin_init', 'taxonomy_image_plugin_register_setting' );


function taxonomy_image_plugin_ajax_gateway( $nonce_slug ) {
	
	/* Check permissions */
	if ( ! current_user_can( TAXONOMY_IMAGE_PLUGIN_PERMISSION ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why' => __( 'Access Denied: You do not have to appropriate capability for this action.', 'taxonomy_image_plugin' ),
		) );
	}

	/* Nonce does not exist in $_POST. */
	if ( ! isset( $_POST['wp_nonce'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why' => __( 'Access Denied: No nonce passed.', 'taxonomy_image_plugin' ),
		) );
	}

	/* Nonce does not match */
	if ( ! wp_verify_nonce( $_POST['wp_nonce'], $nonce_slug ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why' => __( 'Access Denied: Nonce did not match. ' . $_POST['wp_nonce'] . ' - ' . wp_create_nonce( $nonce_slug ), 'taxonomy_image_plugin' ),
		) );
	}

	/* Check value of $_POST['term_id'] */
	if ( ! isset( $_POST['term_taxonomy_id'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why' => __( 'term_taxonomy_id not sent.', 'taxonomy_image_plugin' ),
		) );
	}
	
	return (int) $_POST['term_taxonomy_id'];
}


/**
 * Remove an association from the 'taxonomy_image_plugin' setting.
 *
 * Callback for the wp_ajax_{$_GET['action']} hook.
 *
 * @return    void
 *
 * @access    private
 */
function taxonomy_image_plugin_create_association() {
	$term_taxonomy_id = taxonomy_image_plugin_ajax_gateway( 'taxonomy-image-plugin-create-association' );

	/* Query for $attachment_id */
	$attachment_id = 0;
	if ( isset( $_POST['attachment_id'] ) ) {
		$attachment_id = (int) $_POST['attachment_id'];
	}
	
	/* Update the database. */
	$associations = taxonomy_image_plugin_sanitize_associations( get_option( 'taxonomy_image_plugin' ) );
	$associations[$term_taxonomy_id] = $attachment_id;
	update_option( 'taxonomy_image_plugin', $associations );
	
	/* Send response which terminates the script. */
	taxonomy_image_plugin_json_response( array( 
		'status' => 'good',
		'attachment_thumb_src' => taxonomy_image_plugin_get_image_src( $attachment_id )
	) );
}
add_action( 'wp_ajax_taxonomy_image_create_association', 'taxonomy_image_plugin_create_association' );


/**
 * Remove an association from the 'taxonomy_image_plugin' setting.
 *
 * Callback for the wp_ajax_{$_GET['action']} hook.
 *
 * @return    void
 *
 * @access    private
 */
function taxonomy_image_plugin_remove_association() {
	$term_taxonomy_id = taxonomy_image_plugin_ajax_gateway( 'taxonomy-image-plugin-remove-association' );
	$associations = taxonomy_image_plugin_sanitize_associations( get_option( 'taxonomy_image_plugin' ) );
	if ( isset( $associations[$term_taxonomy_id] ) ) {
		unset( $associations[$term_taxonomy_id] );
	}
	update_option( 'taxonomy_image_plugin', taxonomy_image_plugin_sanitize_associations( $associations ) );
	taxonomy_image_plugin_json_response( array( 'status' => 'good' ) );
}
add_action( 'wp_ajax_taxonomy_image_plugin_remove_association', 'taxonomy_image_plugin_remove_association' );


/**
 * Get the term_taxonomy_id of a given term of a specific taxonomy.
 *
 * @param     int       Term id
 * @param     string    Taxonomy slug
 * @return    int       term_taxonomy id on success; zero otherwise.
 *
 * @access public
 */
function taxonomy_image_plugin_term_taxonomy_id( $term_id, $taxonomy ) {
	$data = get_term( $term_id, $taxonomy );
	if ( isset( $data->term_taxonomy_id ) ) {
		return (int) $data->term_taxonomy_id;
	}
	return 0;
}


/**
 * Get a list of user-defined associations.
 * Associations are stored in the WordPress options table.
 *
 * @param     bool      Should WordPress query the database for the results
 * @return    array     List of associations. Key => taxonomy_term_id; Value => image_id
 *
 * @access    private
 */
function taxonomy_image_plugin_get_associations( $refresh = false ) {
	static $associations = array();
	if ( empty( $associations ) || $refresh ) {
		$associations = get_option( 'taxonomy_image_plugin' );
	}
	return taxonomy_image_plugin_sanitize_associations( $associations );
}
add_action( 'init', 'taxonomy_image_plugin_get_associations' );


/**
 * Dynamically create hooks for each taxonomy.
 *
 * @return    void
 *
 * @access    private
 * @since     0.4.3
 */
function taxonomy_image_plugin_add_dynamic_hooks() {
	global $wp_taxonomies;
	foreach ( $wp_taxonomies as $taxonomy => $taxonomies ) {
		add_filter( 'manage_' . $taxonomy . '_custom_column',	'taxonomy_image_plugin_taxonomy_rows', 15, 3 );
		add_filter( 'manage_edit-' . $taxonomy . '_columns',	'taxonomy_image_plugin_taxonomy_columns' );
		add_action(	$taxonomy . '_edit_form_fields',			'taxonomy_image_plugin_edit_tag_form', 10, 2 );
	}
}
add_action( 'admin_init', 'taxonomy_image_plugin_add_dynamic_hooks' );


/**
 * Insert a new column on wp-admin/edit-tags.php.
 *
 * @see taxonomy_image_plugin_add_dynamic_hooks()
 *
 * @param     array     A list of columns.
 * @return    array     List of columns with "Images" inserted after the checkbox.
 *
 * @access    private
 * @since     0.4.3
 */
function taxonomy_image_plugin_taxonomy_columns( $original_columns ) {
	$new_columns = $original_columns;
	array_splice( $new_columns, 1 ); /* isolate the checkbox column */
	$new_columns['taxonomy_image_plugin'] = __( 'Image', 'taxonomy_image_plugin' ); /* Add custom column */
	return array_merge( $new_columns, $original_columns );
}


/**
 * Create image control for each term row of wp-admin/edit-tags.php.
 *
 * @see taxonomy_image_plugin_add_dynamic_hooks()
 *
 * @param     string    Row.
 * @param     string    Name of the current column.
 * @param     int       Term ID.
 * @return    string    @see taxonomy_image_plugin_control_image()
 *
 * @access private
 * @since 2010-11-08
 */
function taxonomy_image_plugin_taxonomy_rows( $row, $column_name, $term_id ) {
	if ( 'taxonomy_image_plugin' === $column_name ) {
		global $taxonomy;
		return $row . taxonomy_image_plugin_control_image( $term_id, $taxonomy );
	}
	return $row;
}


/**
 * Create image control for wp-admin/edit-tag-form.php.
 *
 * @param     stdClass  Term object.
 * @param     string    Taxonomy slug.
 * @return    void
 *
 * @access    private
 * @since     2010-11-08
 */
function taxonomy_image_plugin_edit_tag_form( $term, $taxonomy ) {
	$taxonomy = get_taxonomy( $taxonomy );
	$name = __( 'term', 'taxonomy_images_plugin' );
	if( isset( $taxonomy->labels->singular_name ) ) {
		$name = strtolower( $taxonomy->labels->singular_name );
	}
	?>
	<tr class="form-field hide-if-no-js">
		<th scope="row" valign="top"><label for="description"><?php _e( 'Image', 'taxonomy_image_plugin' ) ?></label></th>
		<td>
			<?php print taxonomy_image_plugin_control_image( $term->term_id, $taxonomy->name ); ?>
			<div class="clear"></div>
			<span class="description"><?php printf( __( 'Associate an image from your media library to this %1$s.', 'taxonomy_image_plugin' ), $name ); ?></span>
		</td>
	</tr>
	<?php
}


function taxonomy_image_plugin_control_image( $term_id, $taxonomy ) {
	$taxonomy = get_taxonomy( $taxonomy );
	$name = __( 'term', 'taxonomy_images_plugin' );
	if( isset( $taxonomy->labels->singular_name ) ) {
		$name = strtolower( $taxonomy->labels->singular_name );
	}
	$term_tax_id = taxonomy_image_plugin_term_taxonomy_id( (int) $term_id, $taxonomy->name );
	$href_library = admin_url( 'media-upload.php' ) . '?type=image&amp;tab=library&amp;' . TAXONOMY_IMAGE_PLUGIN_SLUG . '=' . $term_tax_id. '&amp;post_id=0&amp;TB_iframe=true';
	$href_upload = admin_url( 'media-upload.php' ) . '?type=image&amp;tab=type&amp;' . TAXONOMY_IMAGE_PLUGIN_SLUG . '=' . $term_tax_id. '&amp;post_id=0&amp;TB_iframe=true';;
	$id = 'taxonomy_image_plugin' . '_' . $term_tax_id;
	$class = array(
		'image' => 'thickbox taxonomy-image-thumbnail',
		'upload' => 'upload control thickbox',
		'remove' => 'remove control hide',
		);
	$img = TAXONOMY_IMAGE_PLUGIN_URL . 'default.png';
	$associations = taxonomy_image_plugin_get_associations();
	if ( isset( $associations[ $term_tax_id ] ) ) {
		$attachment_id = (int) $associations[ $term_tax_id ];
		$img = taxonomy_image_plugin_get_image_src( $attachment_id );
		$class['remove'] = str_replace( ' hide', '', $class['remove'] );
	}
	$text = array(
		esc_attr__( 'Please enable javascript to activate the taxonomy images plugin.', 'taxonomy_image_plugin' ),
		esc_attr__( 'Upload.', 'taxonomy_image_plugin' ),
		sprintf( esc_attr__( 'Upload a new image for this %s.', 'taxonomy_image_plugin' ), $name ),
		esc_attr__( 'Media Library.', 'taxonomy_image_plugin' ),
		sprintf( esc_attr__( 'Change the image for this %s.', 'taxonomy_image_plugin' ), $name ),
		esc_attr__( 'Delete', 'taxonomy_image_plugin' ),
		sprintf( esc_attr__( 'Remove image from this %s.', 'taxonomy_image_plugin' ), $name ),
		);
	return <<<EOF
<div id="taxonomy-image-control-{$term_tax_id}" class="taxonomy-image-control hide-if-no-js">
	<a class="{$class['image']}" href="{$href_library}" title="{$text[4]}"><img id="{$id}" src="{$img}" alt="" /></a>
	<a class="{$class['upload']}" href="{$href_upload}" title="{$text[2]}">{$text[1]}</a>
	<a class="{$class['remove']}" href="#" id="remove-{$term_tax_id}" rel="{$term_tax_id}" title="{$text[6]}">{$text[5]}</a>
</div>
EOF;
}


/**
 * Custom javascript for modal media box.
 * These scripts should only be included where a box has been opened via this script.
 *
 * @return    void
 * @access    private
 */
function taxonomy_image_plugin_media_upload_popup_js() {
	wp_enqueue_script( 'taxonomy-images-media-upload-popup', TAXONOMY_IMAGE_PLUGIN_URL . 'media-upload-popup.js', array( 'jquery' ), TAXONOMY_IMAGE_PLUGIN_VERSION );
	if ( isset( $_GET[ TAXONOMY_IMAGE_PLUGIN_SLUG ] ) ) {
		$term_id = (int) $_GET[ TAXONOMY_IMAGE_PLUGIN_SLUG ];
		wp_localize_script( 'taxonomy-images-media-upload-popup', 'taxonomyImagesPlugin', array (
			'attr' => TAXONOMY_IMAGE_PLUGIN_SLUG . '=' . $term_id, // RED FLAG!!!!!!!!!!!!
			'nonce' => wp_create_nonce( 'taxonomy-image-plugin-create-association' ),
			'locale' => 'taxonomy_image_plugin',
			'attr_slug' => TAXONOMY_IMAGE_PLUGIN_SLUG,
			'term_taxonomy_id' => $term_id
			) );
	}
}
add_action( 'admin_print_scripts-media-upload-popup', 'taxonomy_image_plugin_media_upload_popup_js', 2000 );


/**
 * Custom javascript for wp-admin/edit-tags.php.
 *
 * @return    void
 * @access    private
 */
function taxonomy_image_plugin_edit_tags_js() {
	wp_enqueue_script( 'taxonomy-image-plugin-edit-tags', TAXONOMY_IMAGE_PLUGIN_URL . 'edit-tags.js', array( 'jquery', 'thickbox' ), TAXONOMY_IMAGE_PLUGIN_VERSION );
	wp_localize_script( 'taxonomy-image-plugin-edit-tags', 'taxonomyImagesPlugin', array (
		'nonce' => wp_create_nonce( 'taxonomy-image-plugin-remove-association' ),
		'img_src' => TAXONOMY_IMAGE_PLUGIN_URL . 'default.png'
		) );
}
add_action( 'admin_print_scripts-edit-tags.php', 'taxonomy_image_plugin_edit_tags_js' );


/**
 * Custom styles for wp-admin/edit-tags.php
 *
 * @return    void
 * @access    private
 */
function taxonomy_image_plugin_edit_tags_css() {
	wp_enqueue_style( 'taxonomy-image-plugin-edit-tags', TAXONOMY_IMAGE_PLUGIN_URL . 'admin.css', array( 'thickbox' ), TAXONOMY_IMAGE_PLUGIN_VERSION, 'screen' );
}
add_action( 'admin_print_styles-edit-tags.php', 'taxonomy_image_plugin_edit_tags_css' );


/**
 * Custom styles for the media upload modal.
 *
 * @return    void
 * @access    private
 */
function taxonomy_image_plugin_media_upload_css() {
	wp_enqueue_style( 'taxonomy-image-plugin-edit-tags', TAXONOMY_IMAGE_PLUGIN_URL . 'admin.css', array(), TAXONOMY_IMAGE_PLUGIN_VERSION, 'screen' );
}
add_action( 'admin_print_styles-media-upload-popup', 'taxonomy_image_plugin_media_upload_css' );


/**
 * Create associations setting in the options table on plugin activation.
 *
 * @return    void
 * @access    private
 */
function taxonomy_image_plugin_activate() {
	$associations = get_option( 'taxonomy_image_plugin' );
	if ( false === $associations ) {
		add_option( 'taxonomy_image_plugin', array() );
	}
}
register_activation_hook( __FILE__, 'taxonomy_image_plugin_activate' );


/**
 * Shortcode wrapper for taxonomy_images_plugin_image_list();
 *
 * @param     array     These can be defined in the shortcode as attributes.
 * taxonomy   string    The taxonomy to query for.
 * context    string    Can be either 'global' or 'post'. See taxonomy_images_plugin_image_list() for full description.
 * size       string    The name of an image size registered for your installation. Defaults to 'detail'.
 * include    string    A comma or space-delimited string of term ids to include.
 * exclude    string    A comma or space-delimited string of term ids to exclude. If 'include' is non-empty, 'exclude' is ignored.
 *
 * @return    string    see taxonomy_images_plugin_image_list()
 * @access    private
 */
function taxonomy_images_plugin_shortcode_image_list( $atts = array() ) {
	global $taxonomy_image_plugin_image;
	$o = '';
	$defaults = array(
		'taxonomy' => 'category',
		'context'  => 'global',
		'size'     => $taxonomy_image_plugin_image['name'],
		'include'  => null,
		'exclude'  => null
		);
	$atts = shortcode_atts( $defaults, $atts );
	$args = array_slice( $atts, -2, 2 );	
	extract( $atts );
	return taxonomy_images_plugin_image_list( $taxonomy, $context, $size, false, $args );
}
add_shortcode( 'taxonomy_image_list', 'taxonomy_images_plugin_shortcode_image_list' );


/**
 * Generate a list of all terms of a given taxonomy + their associated images.
 *
 * Only include terms whose post_count > 0 and have an associated image.
 * Although this function's access is private, it may be used in any template
 * file via the 'taxonomy_image_plugin_image_list' action.
 *
 * @param     string    Taxonomy slug.
 * @param     string    Context can be either 'global' or 'post'.
 *                      'global' - get_terms() returns all taxonomy terms.
 *                      'post'   - get_the_terms() returns all terms associated with the global post object.
 * @param     string    Image size. Can be any value registered with WordPress. Defaults to 'detail'.
 * @param     bool      Should the value be printed? true = yes, false = no.
 * @param     array     Arguments to pass as the second parameter of get_terms().
 * @return    string    Unordered list. Will return void if print is true.
 *
 * @access    private
 * @since     2010-12-04
 */
function taxonomy_images_plugin_image_list( $taxonomy = 'category', $context = 'global', $image_size = 'detail', $print = true, $args = array() ) {
	$o = '';
	$terms = array();
	
	/* No taxonomy defined return an html comment. */
	if ( ! taxonomy_exists( $taxonomy ) ) {
		$tax = strip_tags( trim( $taxonomy ) );
		return '<!-- taxonomy_image_plugin error: "' . $taxonomy . '" does not exist on your WordPress installation. -->';
	}

	/* Get all image/term associations. */
	$associations = taxonomy_image_plugin_get_associations();

	/* Get all terms in the given taxonomy. */
	if ( 'global' === $context ) {
		$terms = get_terms( $taxonomy, $args );
	}
	else if ( 'post' === $context ) {
		$terms = get_the_terms( 0, $taxonomy );
	}

	/* Loop over terms. */
	if ( ! is_wp_error( $terms ) ) {
		foreach ( (array) $terms as $term ) {
			
			/* Minor extensions to the term object. */
			$term->img = '';
			$term->url = get_term_link( $term, $term->taxonomy );
			
			/* Get the image for the associated. */
			if ( array_key_exists( $term->term_taxonomy_id, $associations ) ) {
				$term->img = wp_get_attachment_image( $associations[$term->term_taxonomy_id], $image_size, false );
			}
			
			/* Only display terms that have associated images. */
			if ( ! empty( $term->img ) ) {
				$element = apply_filters( 'taxonomy_images_plugin_list_item_element', 'li', $term );
				$element = apply_filters( "taxonomy_images_plugin_list_item_element_{$taxonomy}", $element, $term );
				$atts = array( 'title' => $term->name . ' (' . $term->count . ')', 'href'  => $term->url );
				$atts = apply_filters( 'taxonomy_images_plugin_list_item_link_atts', $atts, $term );
				$atts = apply_filters( "taxonomy_images_plugin_list_item_link_atts_{$taxonomy}", $atts, $term );
				$attributes = '';
				foreach ( (array) $atts as $name => $value ) {
					$attributes.= ' ' . $name . '="' . esc_attr( $value ) . '"';
				}
				$o.= "<{$element}><a{$attributes}>{$term->img}</a></{$element}>\n";
			}
			
		}
		if ( ! empty( $o ) ) {
			$element = apply_filters( 'taxonomy_images_plugin_list_element', 'ul' );
			$element = apply_filters( "taxonomy_images_plugin_list_element_{$taxonomy}", $element );
			$o = "<{$element}>\n{$o}</{$element}>\n";
		}
	}
	if ( $print ) {
		print $o;
	}
	else {
		return $o;
	}
}
add_action( 'taxonomy_images_plugin_image_list', 'taxonomy_images_plugin_image_list', 10, 4 );


/**
 * Deprecated Shortcode.
 *
 * @return    void
 * @access    private
 */
function taxonomy_images_plugin_shortcode_deprecated( $atts = array() ) { // DEPRECATED
	$o = '';
	$defaults = array(
		'taxonomy' => 'category',
		'size' => 'detail',
		'template' => 'list'
		);

	extract( shortcode_atts( $defaults, $atts ) );

	/* No taxonomy defined return an html comment. */
	if ( ! taxonomy_exists( $taxonomy ) ) {
		$tax = strip_tags( trim( $taxonomy ) );
		return '<!-- taxonomy_image_plugin error: Taxonomy "' . $taxonomy . '" is not defined.-->';
	}

	$terms = get_terms( $taxonomy );
	$associations = taxonomy_image_plugin_get_associations( $refresh = false );
	
	if ( ! is_wp_error( $terms ) ) {
		foreach( (array) $terms as $term ) {
			$url         = get_term_link( $term, $term->taxonomy );
			$title       = apply_filters( 'the_title', $term->name );
			$title_attr  = esc_attr( $term->name . ' (' . $term->count . ')' );
			$description = apply_filters( 'the_content', $term->description );
			
			$img = '';
			if ( array_key_exists( $term->term_taxonomy_id, $associations ) ) {
				$img = wp_get_attachment_image( $associations[$term->term_taxonomy_id], 'detail', false );
			}
			
			if( $template === 'grid' ) {
				$o.= "\n\t" . '<div class="taxonomy_image_plugin-' . $template . '">';
				$o.= "\n\t\t" . '<a style="float:left;" title="' . $title_attr . '" href="' . $url . '">' . $img . '</a>';
				$o.= "\n\t" . '</div>';
			}
			else {
				$o.= "\n\t\t" . '<a title="' . $title_attr . '" href="' . $url . '">' . $img . '</a>';;
				$o.= "\n\t\t" . '<h2 style="clear:none;margin-top:0;padding-top:0;line-height:1em;"><a href="' . $url . '">' . $title . '</a></h2>';
				$o.= $description;
				$o.= "\n\t" . '<div style="clear:both;height:1.5em"></div>';
				$o.= "\n";
			}
		}
	}
	return $o;
}
add_shortcode( 'taxonomy_image_plugin', 'taxonomy_images_plugin_shortcode_deprecated' );


/**
 * This class has been left for backward compatibility with versions
 * of this plugin 0.5 and under. Please do not use any methods or
 * properties directly in your theme.
 *
 * @access     private        This class is deprecated. Do not use!!!
 */
class taxonomy_images_plugin {
	public $settings = array();
	public function __construct() {
		$this->settings = taxonomy_image_plugin_get_associations();
		add_action( 'taxonomy_image_plugin_print_image_html', array( &$this, 'print_image_html' ), 1, 3 );
	}
	public function get_thumb( $id ) {
		return taxonomy_image_plugin_get_image_src( $id );
	}
	public function print_image_html( $size = 'medium', $term_tax_id = false, $title = true, $align = 'none' ) {
		print $this->get_image_html( $size, $term_tax_id, $title, $align );
	}
	public function get_image_html( $size = 'medium', $term_tax_id = false, $title = true, $align = 'none' ) {
		$o = '';
		if ( false === $term_tax_id ) {
			global $wp_query;
			$obj = $wp_query->get_queried_object();
			if ( isset( $obj->term_taxonomy_id ) ) {
				$term_tax_id = $obj->term_taxonomy_id;
			}
			else {
				return false;
			}
		}
		$term_tax_id = (int) $term_tax_id;
		if ( isset( $this->settings[ $term_tax_id ] ) ) {
			$attachment_id = (int) $this->settings[ $term_tax_id ];
			$alt           = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$attachment    = get_post( $attachment_id );
			/* Just in case an attachment was deleted, but there is still a record for it in this plugins settings. */
			if ( $attachment !== NULL ) {
				$o = get_image_tag( $attachment_id, $alt, '', $align, $size );
			}
		}
		return $o;
	}
}
$taxonomy_images_plugin = new taxonomy_images_plugin();
