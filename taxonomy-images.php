<?php
/*
Plugin Name:          Taxonomy Images
Plugin URI:           http://wordpress.mfields.org/plugins/taxonomy-images/
Description:          The Taxonomy Images plugin enables you to associate images from your Media Library to categories, tags and taxonomies.
Version:              0.7 - ALPHA
Author:               Michael Fields
Author URI:           http://wordpress.mfields.org/
License:              GPLv2

Copyright 2010-2011  Michael Fields  michael@mfields.org

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

define( 'TAXONOMY_IMAGE_PLUGIN_URL',        plugin_dir_url( __FILE__ ) );
define( 'TAXONOMY_IMAGE_PLUGIN_DIR',        trailingslashit( dirname( __FILE__ ) ) );
define( 'TAXONOMY_IMAGE_PLUGIN_VERSION',    '0.7' );
define( 'TAXONOMY_IMAGE_PLUGIN_PERMISSION', 'manage_categories' );

require_once( TAXONOMY_IMAGE_PLUGIN_DIR . 'deprecated.php' );

/*
 * Interface.
 *
 * All functions defined in this plugin should be considered
 * private meaning that they are not to be used in any other
 * WordPress extension including plugins and themes. Direct
 * use of functions defined herein constitutes unsupported use
 * and is strongly discouraged. Custom filters have been added
 * which enable extension authors to interact with this plugin
 * in a responsible manner.
 */
add_filter( 'taxonomy-images-list', 'taxonomy_images_plugin_image_list' );
add_filter( 'taxonomy-images-queried-term-image', 'taxonomy_images_plugin_get_queried_term_image' );
add_shortcode( 'taxonomy-image-list', 'taxonomy_images_plugin_image_list' );


$taxonomy_image_plugin_image = array(
	'name' => 'detail',
	'size' => array( 75, 75, true )
	);


/**
 * Register custom image size with WordPress.
 *
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
 * Modal Button.
 *
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
function taxonomy_image_plugin_modal_button( $fields, $post ) {
	if ( isset( $fields['image-size'] ) && isset( $post->ID ) ) {
		$image_id = (int) $post->ID;

		$o = '<div>';

		$o.= '<span class="button create-association">' . sprintf( __( 'Associate with %1$s', 'taxonomy-images' ), '<span class="term-name">' . esc_html__( 'this term', 'taxonomy-images' ) . '</span>' ) . '</span>';

		$o.= '<span class="remove-association">' . sprintf( __( 'Remove association with %1$s', 'taxonomy-images' ), '<span class="term-name">' . esc_html__( 'this term', 'taxonomy-images' ) . '</span>' ) . '</span>';

		$o.= '<span class="taxonomy-images-close-modal"> ' . esc_html__( 'Close window', 'taxonomy-images' ) . '</span>';

		$o.= '<input class="taxonomy-image-button-image-id" name="' . esc_attr( 'taxonomy-image-button-image-id-' . $image_id ) . '" type="hidden" value="' . esc_attr( $image_id ) . '" />';

		$o.= '<input class="taxonomy-image-button-nonce-create" name="' . esc_attr( 'taxonomy-image-button-nonce-create-' . $image_id ) . '" type="hidden" value="' . wp_create_nonce( 'taxonomy-image-plugin-create-association' ) . '" />';

		$o.= '<input class="taxonomy-image-button-nonce-remove" name="' . esc_attr( 'taxonomy-image-button-nonce-remove-' . $image_id ) . '" type="hidden" value="' . wp_create_nonce( 'taxonomy-image-plugin-remove-association' ) . '" />';

		$o.= '</div>';

		$fields['image-size']['extra_rows']['taxonomy-image-plugin-button']['html'] = $o;
	}
	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'taxonomy_image_plugin_modal_button', 20, 2 );


/**
 * Get Image Source.
 *
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
	if ( isset( $img['url'] ) ) {
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


/**
 * Sanitize Associations.
 *
 * Ensures that all key/value pairs are positive integers.
 * This filter will discard all zero and negative values.
 *
 * @param     array     An array of term_taxonomy_id/attachment_id pairs.
 * @return    array     Sanitized version of parameter.
 *
 * @access    private
 */
function taxonomy_image_plugin_sanitize_associations( $associations ) {
	$o = array();
	foreach ( (array) $associations as $tt_id => $im_id ) {
		$tt_id = absint( $tt_id );
		$im_id = absint( $im_id );
		if ( 0 < $tt_id && 0 < $im_id ) {
			$o[$tt_id] = $im_id;
		}
	}
	return $o;
}


/**
 * Sanitize Settings.
 *
 * A callback for the WordPress Settings API.
 * This function is responsible for ensuring that
 * all values within the 'taxonomy_image_plugin_settings'
 * options are of the appropriate type.
 *
 * @param     array     Unknown.
 * @return    array     Multi-dimensional array of sanitized settings.
 *
 * @access    private
 * @since     2011-05-15
 */
function taxonomy_image_plugin_settings_sanitize( $dirty ) {
	$clean = array();
	if ( isset( $dirty['taxonomies'] ) ) {
		$taxonomies = get_taxonomies();
		foreach ( (array) $dirty['taxonomies'] as $taxonomy ) {
			if ( in_array( $taxonomy, $taxonomies ) ) {
				$clean['taxonomies'][] = $taxonomy;
			}
		}
	}

	$message = __( 'Taxonomies have been updated', 'taxonomy-images' );
	if ( empty( $clean ) ) {
		$message = __( 'All taxonomies have been removed', 'taxonomy-images' );
	}

	add_settings_error( 'taxonomy_image_plugin_settings', 'taxonomies_updated', $message, 'updated' );

	return $clean;
}


/**
 * Register settings with WordPress.
 *
 * This plugin will store to sets of settings in the
 * options table. The first is named 'taxonomy_image_plugin'
 * and stores the associations between terms and images. The
 * keys in this array represent the term_taxonomy_id of the
 * term while the value represents the ID of the image
 * attachment.
 *
 * The second setting is used to store everything else. As of
 * version 0.7 it has one key named 'taxonomies' whichi is a
 * flat array consisting of taxonomy names representing a
 * black-list of registered taxonomies. These taxonomies will
 * NOT be given an image UI.
 *
 * @access    private
 */
function taxonomy_image_plugin_register_settings() {
	register_setting(
		'taxonomy_image_plugin',
		'taxonomy_image_plugin',
		'taxonomy_image_plugin_sanitize_associations'
		);
	register_setting(
		'taxonomy_image_plugin_settings',
		'taxonomy_image_plugin_settings',
		'taxonomy_image_plugin_settings_sanitize'
		);
	add_settings_section(
		'taxonomy_image_plugin_settings',
		__( 'Settings', 'taxonomy-images' ),
		'__return_false',
		'taxonomy_image_plugin_settings'
		);
	add_settings_field(
		'taxonomy-images',
		__( 'Exclude Taxonomies', 'taxonomy-images' ),
		'taxonomy_image_plugin_control_taxonomies',
		'taxonomy_image_plugin_settings',
		'taxonomy_image_plugin_settings'
		);
}
add_action( 'admin_init', 'taxonomy_image_plugin_register_settings' );


/**
 * Admin Menu.
 *
 * Create the admin menu link for the settings page.
 *
 * @access    private
 * @since     2011-05-15
 */
function taxonomy_images_settings_menu() {
	add_options_page(
		__( 'Taxonomy Images', 'taxonomy-images' ),
		__( 'Taxonomy Images', 'taxonomy-images' ),
		'manage_options',
		'taxonomy_image_plugin_settings',
		'taxonomy_image_plugin_settings_page'
		);
}
add_action( 'admin_menu', 'taxonomy_images_settings_menu' );


/**
 * Settings Page Template.
 *
 * This function in conjunction with others usei the WordPress
 * Settings API to create a settings page where users can adjust
 * the behaviour of this plugin. Please see the following functions
 * for more insight on the output generated by this function:
 *
 * taxonomy_image_plugin_control_taxonomies()
 *
 * @access    private
 * @since     2011-05-15
 */
function taxonomy_image_plugin_settings_page() {
	print "\n" . '<div class="wrap">';
	screen_icon();
	print "\n" . '<h2>' . __( 'Taxonomy Images Plugin Settings', 'taxonomy-images' ) . '</h2>';
	print "\n" . '<div id="taxonomy-images">';
	print "\n" . '<form action="options.php" method="post">';

	settings_fields( 'taxonomy_image_plugin_settings' );
	do_settings_sections( 'taxonomy_image_plugin_settings' );

	print "\n" . '<div class="button-holder"><input name="Submit" type="submit" value="' . esc_attr__( 'Save Changes', 'taxonomy-images' ) . '" /></div>';
	print "\n" . '</div></form></div>';
}


function taxonomy_image_plugin_control_taxonomies() {
	$settings = get_option( 'taxonomy_image_plugin_settings' );
	$taxonomies = get_taxonomies( array(), 'objects' );
	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( ! isset( $taxonomy->name ) ) {
			continue;
		}
		if ( ! isset( $taxonomy->label ) ) {
			continue;
		}
		if ( ! isset( $taxonomy->show_ui ) || empty( $taxonomy->show_ui ) ) {
			continue;
		}
		$id = 'taxonomy-images-' . $taxonomy->name;
		$checked = '';
		if ( isset( $settings['taxonomies'] ) && in_array( $taxonomy->name, (array) $settings['taxonomies'] ) ) {
			$checked = ' checked="checked"';
		}
		print "\n" . '<p><label for="' . esc_attr( $id ) . '">';
		print '<input' . $checked . ' id="' . esc_attr( $id ) . '" type="checkbox" name="taxonomy_image_plugin_settings[taxonomies][]" value="' . esc_attr( $taxonomy->name ) . '">';
		print ' ' . esc_html( $taxonomy->label ) . '</label></p>';
	}
}


/**
 * JSON Respose.
 * Terminates script execution.
 *
 * @param     array     Associative array of values to be encoded in JSON.
 *
 * @access    private
 */
function taxonomy_image_plugin_json_response( $args ) {
	$response = wp_parse_args( $args, array(
		'status' => 'bad',
		'why'    => 'Unknown error encountered'
	) );
	header( 'Content-type: application/jsonrequest' );
	print json_encode( $response );
	exit;
}


/**
 * Get Term Info
 *
 * Returns term info by term_taxonomy_id.
 *
 * @param     int       term_taxonomy_id
 * @return    array     Keys: term_id (int) and taxonomy (string).
 *
 * @access    private
 */
function taxonomy_image_plugin_get_term_info( $tt_id ) {
	static $cache = array();
	if ( isset( $cache[$tt_id] ) ) {
		return $cache[$tt_id];
	}
	global $wpdb;
	$data = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %s LIMIT 1", $tt_id ) );
	if ( isset( $data[0]->term_id ) ) {
		$cache[$tt_id]['term_id'] = absint( $data[0]->term_id );
	}
	if ( isset( $data[0]->taxonomy ) ) {
		$cache[$tt_id]['taxonomy'] = sanitize_title_with_dashes( $data[0]->taxonomy );
	}
	if ( isset( $cache[$tt_id] ) ) {
		return $cache[$tt_id];
	}
	return array();
}


/**
 * Check Taxonomy Permissions.
 *
 * Allows a permission check to be performed on a term
 * when all you know is the term_taxonomy_id.
 *
 * @param     int       term_taxonomy_id
 * @return    bool      True if user can edit terms, False if not.
 *
 * @access    private
 */
function taxonomy_image_plugin_check_permissions( $tt_id ) {
	$data = taxonomy_image_plugin_get_term_info( $tt_id );
	if ( ! isset( $data['taxonomy'] ) ) {
		return false;
	}

	$taxonomy = get_taxonomy( $data['taxonomy'] );
	if ( ! isset( $taxonomy->cap->edit_terms ) ) {
		return false;
	}

	return current_user_can( $taxonomy->cap->edit_terms );
}


/**
 * Create an association.
 *
 * Callback for the wp_ajax_{$_GET['action']} hook.
 *
 * @access    private
 */
function taxonomy_image_plugin_create_association() {
	if ( ! isset( $_POST['term_taxonomy_id'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'term_taxonomy_id not sent', 'taxonomy-images' ),
		) );
	}

	$tt_id = absint( $_POST['term_taxonomy_id'] );
	if ( empty( $tt_id ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'term_taxonomy_id is empty', 'taxonomy-images' ),
		) );
	}

	if ( ! taxonomy_image_plugin_check_permissions( $tt_id ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'You do not have the correct capability to manage this term', 'taxonomy-images' ),
		) );
	}

	if ( ! isset( $_POST['wp_nonce'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'No nonce included.', 'taxonomy-images' ),
		) );
	}

	if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'taxonomy-image-plugin-create-association' ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Nonce did not match', 'taxonomy-images' ),
		) );
	}

	if ( ! isset( $_POST['attachment_id'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Image id not sent', 'taxonomy-images' )
		) );
	}

	$image_id = absint( $_POST['attachment_id'] );
	if ( empty( $image_id ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Image id is not a positive integer', 'taxonomy-images' )
		) );
	}

	$assoc = taxonomy_image_plugin_get_associations();
	$assoc[$tt_id] = $image_id;
	if ( update_option( 'taxonomy_image_plugin', taxonomy_image_plugin_sanitize_associations( $assoc ) ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'good',
			'why'    => 'Image successfully associated',
			'attachment_thumb_src' => taxonomy_image_plugin_get_image_src( $image_id )
		) );
	}
	else {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Association could not be created', 'taxonomy-images' )
		) );
	}

	/* Don't know why, but something didn't work. */
	taxonomy_image_plugin_json_response();
}
add_action( 'wp_ajax_taxonomy_image_create_association', 'taxonomy_image_plugin_create_association' );


/**
 * Remove an association.
 *
 * Removes an association from the setting stored in the database.
 * Print json encoded message and terminates script execution.
 *
 * @access    private
 */
function taxonomy_image_plugin_remove_association() {
	if ( ! isset( $_POST['term_taxonomy_id'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'term_taxonomy_id not sent', 'taxonomy-images' ),
		) );
	}

	$tt_id = absint( $_POST['term_taxonomy_id'] );
	if ( empty( $tt_id ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'term_taxonomy_id is empty', 'taxonomy-images' ),
		) );
	}

	if ( ! taxonomy_image_plugin_check_permissions( $tt_id ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'You do not have the correct capability to manage this term', 'taxonomy-images' ),
		) );
	}

	if ( ! isset( $_POST['wp_nonce'] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'No nonce included', 'taxonomy-images' ),
		) );
	}

	if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'taxonomy-image-plugin-remove-association') ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Nonce did not match', 'taxonomy-images' ),
		) );
	}

	$assoc = taxonomy_image_plugin_get_associations();
	if ( ! isset( $assoc[$tt_id] ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'good',
			'why'    => __( 'Nothing to remove', 'taxonomy-images' )
		) );
	}

	unset( $assoc[$tt_id] );

	if ( update_option( 'taxonomy_image_plugin', $assoc ) ) {
		taxonomy_image_plugin_json_response( array(
			'status' => 'good',
			'why'    => __( 'Association successfully removed', 'taxonomy-images' )
		) );
	}
	else {
		taxonomy_image_plugin_json_response( array(
			'status' => 'bad',
			'why'    => __( 'Association could not be removed', 'taxonomy-images' )
		) );
	}

	/* Don't know why, but something didn't work. */
	taxonomy_image_plugin_json_response();
}
add_action( 'wp_ajax_taxonomy_image_plugin_remove_association', 'taxonomy_image_plugin_remove_association' );


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
		$associations = taxonomy_image_plugin_sanitize_associations( get_option( 'taxonomy_image_plugin' ) );
	}
	return $associations;
}
add_action( 'init', 'taxonomy_image_plugin_get_associations' );


/**
 * Dynamically create hooks for each taxonomy.
 *
 * @access    private
 * @since     0.4.3
 */
function taxonomy_image_plugin_add_dynamic_hooks() {
	$settings = get_option( 'taxonomy_image_plugin_settings' );
	$taxonomies = get_taxonomies();
	foreach ( $taxonomies as $taxonomy ) {
		if ( isset( $settings['taxonomies'] ) && in_array( $taxonomy, $settings['taxonomies'] ) ) {
			continue;
		}
		add_filter( 'manage_' . $taxonomy . '_custom_column', 'taxonomy_image_plugin_taxonomy_rows', 15, 3 );
		add_filter( 'manage_edit-' . $taxonomy . '_columns',  'taxonomy_image_plugin_taxonomy_columns' );
		add_action( $taxonomy . '_edit_form_fields',          'taxonomy_image_plugin_edit_tag_form', 10, 2 );
	}
}
add_action( 'admin_init', 'taxonomy_image_plugin_add_dynamic_hooks' );


/**
 * Edit Term Columns.
 *
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
	array_splice( $new_columns, 1 );
	$new_columns['taxonomy_image_plugin'] = __( 'Image', 'taxonomy-images' ); /* Add custom column */
	return array_merge( $new_columns, $original_columns );
}


/**
 * Edit Term Rows.
 *
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
 * Edit Term Control.
 *
 * Create image control for wp-admin/edit-tag-form.php.
 * Hooked into the '{$taxonomy}_edit_form_fields' action.
 *
 * @param     stdClass  Term object.
 * @param     string    Taxonomy slug.
 *
 * @access    private
 * @since     2010-11-08
 */
function taxonomy_image_plugin_edit_tag_form( $term, $taxonomy ) {
	$taxonomy = get_taxonomy( $taxonomy );
	$name = __( 'term', 'taxonomy-images' );
	if ( isset( $taxonomy->labels->singular_name ) ) {
		$name = strtolower( $taxonomy->labels->singular_name );
	}
	?>
	<tr class="form-field hide-if-no-js">
		<th scope="row" valign="top"><label for="description"><?php _e( 'Image', 'taxonomy-images' ) ?></label></th>
		<td>
			<?php print taxonomy_image_plugin_control_image( $term->term_id, $taxonomy->name ); ?>
			<div class="clear"></div>
			<span class="description"><?php printf( __( 'Associate an image from your media library to this %1$s.', 'taxonomy-images' ), $name ); ?></span>
		</td>
	</tr>
	<?php
}

/**
 * @todo      Remove rel tag from link... will need to adjust js to accomodate.
 */
function taxonomy_image_plugin_control_image( $term_id, $taxonomy ) {

	$term = get_term( $term_id, $taxonomy );

	$tt_id = 0;
	if ( isset( $term->term_taxonomy_id ) ) {
		$tt_id = (int) $term->term_taxonomy_id;
	}

	$taxonomy = get_taxonomy( $taxonomy );

	$name = __( 'term', 'taxonomy-images' );
	if ( isset( $taxonomy->labels->singular_name ) ) {
		$name = strtolower( $taxonomy->labels->singular_name );
	}

	$hide = ' hide';
	$attachment_id = 0;
	$associations = taxonomy_image_plugin_get_associations();
	if ( isset( $associations[ $tt_id ] ) ) {
		$attachment_id = (int) $associations[ $tt_id ];
		$hide = '';
	}

	$img = taxonomy_image_plugin_get_image_src( $attachment_id );

	$term = get_term( $term_id, $taxonomy->name );

	$o = "\n" . '<div id="' . esc_attr( 'taxonomy-image-control-' . $tt_id ) . '" class="taxonomy-image-control hide-if-no-js">';
	$o.= "\n" . '<a class="thickbox taxonomy-image-thumbnail" href="' . esc_url( admin_url( 'media-upload.php' ) . '?type=image&tab=library&post_id=0&TB_iframe=true' ) . '" title="' . sprintf( esc_attr__( 'Change the image for this %s.', 'taxonomy-images' ), $name ) . '"><img id="' . esc_attr( 'taxonomy_image_plugin_' . $tt_id ) . '" src="' . esc_url( $img ) . '" alt="" /></a>';
	$o.= "\n" . '<a class="control upload thickbox" href="' . esc_url( admin_url( 'media-upload.php' ) . '?type=image&tab=type&post_id=0&TB_iframe=true' ) . '" title="' . sprintf( esc_attr__( 'Upload a new image for this %s.', 'taxonomy-images' ), $name ) . '">' . esc_html__( 'Upload.', 'taxonomy-images' ) . '</a>';
	$o.= "\n" . '<a class="control remove' . $hide . '" href="#" id="' . esc_attr( 'remove-' . $tt_id ) . '" rel="' . esc_attr( $tt_id ) . '" title="' . sprintf( esc_attr__( 'Remove image from this %s.', 'taxonomy-images' ), $name ) . '">' . esc_html__( 'Delete', 'taxonomy-images' ) . '</a>';
	$o.= "\n" . '<input type="hidden" class="tt_id" name="' . esc_attr( 'tt_id-' . $tt_id ) . '" value="' . esc_attr( $tt_id ) . '" />';

	$o.= "\n" . '<input type="hidden" class="image_id" name="' . esc_attr( 'image_id-' . $tt_id ) . '" value="' . esc_attr( $attachment_id ) . '" />';

	if ( isset( $term->name ) && isset( $term->slug ) ) {
		$o.= "\n" . '<input type="hidden" class="term_name" name="' . esc_attr( 'term_name-' . $term->slug ) . '" value="' . esc_attr( $term->name ) . '" />';
	}

	$o.= "\n" . '</div>';
	return $o;
}


/**
 * Custom javascript for modal media box.
 *
 * This script need to be added to all instance of the media upload box.
 *
 * @access    private
 */
function taxonomy_image_plugin_media_upload_popup_js() {
	wp_enqueue_script( 'taxonomy-images-media-upload-popup', TAXONOMY_IMAGE_PLUGIN_URL . 'media-upload-popup.js', array( 'jquery' ), TAXONOMY_IMAGE_PLUGIN_VERSION );
	wp_localize_script( 'taxonomy-images-media-upload-popup', 'TaxonomyImagesModal', array (
		'termBefore'  => __( '&#8220;', 'taxonomy-images' ),
		'termAfter'   => __( '&#8221;', 'taxonomy-images' ),
		'associating' => __( 'Associating &#8230;', 'taxonomy-images' ),
		'success'     => __( 'Successfully Associated!', 'taxonomy-images' )
		) );
}
add_action( 'admin_print_scripts-media-upload-popup', 'taxonomy_image_plugin_media_upload_popup_js' );


/**
 * Custom javascript for wp-admin/edit-tags.php.
 *
 * @access    private
 */
function taxonomy_image_plugin_edit_tags_js() {
	if ( false == taxonomy_image_plugin_is_screen_active() ) {
		return;
	}
	wp_enqueue_script( 'taxonomy-image-plugin-edit-tags', TAXONOMY_IMAGE_PLUGIN_URL . 'edit-tags.js', array( 'jquery', 'thickbox' ), TAXONOMY_IMAGE_PLUGIN_VERSION );
	wp_localize_script( 'taxonomy-image-plugin-edit-tags', 'taxonomyImagesPlugin', array (
		'nonce'    => wp_create_nonce( 'taxonomy-image-plugin-remove-association' ),
		'img_src'  => TAXONOMY_IMAGE_PLUGIN_URL . 'default.png',
		'tt_id'    => 0,
		'image_id' => 0,
		) );
}
add_action( 'admin_print_scripts-edit-tags.php', 'taxonomy_image_plugin_edit_tags_js' );


/**
 * Custom styles.
 *
 * @since     2011-05-12
 * @access    private
 */
function taxonomy_image_plugin_css_admin() {
	if ( false == taxonomy_image_plugin_is_screen_active() && 'admin_print_styles-media-upload-popup' != current_filter() ) {
		return;
	}
	wp_enqueue_style( 'taxonomy-image-plugin-edit-tags', TAXONOMY_IMAGE_PLUGIN_URL . 'admin.css', array(), TAXONOMY_IMAGE_PLUGIN_VERSION, 'screen' );
}
add_action( 'admin_print_styles-edit-tags.php', 'taxonomy_image_plugin_css_admin' );
add_action( 'admin_print_styles-media-upload-popup', 'taxonomy_image_plugin_css_admin' );
add_action( 'admin_print_styles-settings_page_taxonomy_image_plugin_settings', 'taxonomy_image_plugin_css_admin' );


/**
 * Thickbox styles.
 *
 * @since     2011-05-12
 * @access    private
 */
function taxonomy_image_plugin_css_thickbox() {
	if ( false == taxonomy_image_plugin_is_screen_active() ) {
		return;
	}
	wp_enqueue_style( 'thickbox' );
}
add_action( 'admin_print_styles-edit-tags.php', 'taxonomy_image_plugin_css_thickbox' );


/**
 * Create associations setting in the options table on plugin activation.
 *
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
 * @return    mixed     Plese see 'return' section for description.
 *
 * @access    private
 * @since     2010-05-17
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


/**
 * Is Screen Active?
 *
 * @return    bool
 *
 * @access    private
 * @since     2011-05-16
 */
function taxonomy_image_plugin_is_screen_active() {
	$screen = get_current_screen();
	if ( ! isset( $screen->taxonomy ) ) {
		return false;
	}

	$settings = get_option( 'taxonomy_image_plugin_settings' );

	$taxonomies = array();
	if ( isset( $settings['taxonomies'] ) ) {
		$taxonomies = (array) $settings['taxonomies'];
	}

	if ( ! in_array( $screen->taxonomy, $taxonomies ) ) {
		return true;
	}
	return false;
}