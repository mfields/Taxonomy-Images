<?php
/*
Plugin Name: Taxonomy Images BETA
Plugin URI: http://wordpress.mfields.org/plugins/taxonomy-images/
Description: The Taxonomy Images plugin enables you to associate images from your Media Library to categories, tags and taxonomies.
Version: 0.5
Author: Michael Fields
Author URI: http://wordpress.mfields.org/
License: GPLv2

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
	
TODO LIST:
	5.	Add support for wp_list_categories() or create new functionality based on wp_list_categories()
*/

if( !function_exists( 'pr' ) ) {
	function pr( $var ) {
		print '<pre>' . print_r( $var, true ) . '</pre>';
	}
}

/* 2.9 Branch support */
if( !function_exists( 'taxonomy_exists' ) ) {
	function taxonomy_exists( $taxonomy ) {
		global $wp_taxonomies;
		return isset( $wp_taxonomies[$taxonomy] );
	}
}
if( !class_exists( 'taxonomy_images_plugin' ) ) {
	/**
	* Category Thumbs
	* @author Michael Fields <michael@mfields.org>
	* @copyright Copyright (c) 2009, Michael Fields.
	* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	* @package Plugins
	* @filesource
	*/
	class taxonomy_images_plugin {
		public $settings = array();
		public $locale = 'taxonomy_image_plugin';
		public $version = '0.5';
		private $permission = 'manage_categories';
		private $attr_slug = 'mf_term_id';
		private $detail_size = array( 75, 75, true );
		private $custom_taxonomies = array();
		private $current_taxonomy = false;
		private $plugin_basename = '';
		public function __construct() {
			/* Set Properties */
			$this->dir = dirname( __FILE__ );
			$this->uri = plugin_dir_url( __FILE__ );
			$this->url = $this->uri;
			$this->settings = get_option( $this->locale );
			$this->plugin_basename = plugin_basename( __FILE__ );
			
			/* Plugin Activation Hooks */
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			
			/* General Hooks. */
			add_action( 'init', array( &$this, 'add_new_image_size' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
			add_action( 'admin_head', array( &$this, 'set_current_taxonomy' ), 10 );
			add_action( 'wp_head', array( &$this, 'set_current_taxonomy' ), 10 );
			
			/* Media Upload Thickbox Hooks. */
			add_filter( 'attachment_fields_to_edit', array( &$this, 'control_add_image_to_taxonomy' ), 20, 2 );
			add_action( 'admin_print_scripts-media-upload-popup', array( &$this, 'media_upload_popup_js' ), 2000 );
			add_action( 'wp_ajax_taxonomy_images_create_association', array( &$this, 'ajax_create_association' ), 10 );
			add_action( 'wp_ajax_taxonomy_images_remove_association', array( &$this, 'ajax_remove_association' ), 10 );
						
			/* Category Admin Hooks. */
			add_action( 'admin_print_scripts-categories.php', array( &$this, 'scripts' ) );
			add_action( 'admin_print_styles-categories.php', array( &$this, 'styles' ) );
			
			/* 3.0 and beyond. Dynamically create hooks. */
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			
			/* 2.9 Support - hook into taxonomy terms administration panel. */
			add_filter( 'manage_categories_custom_column', array( &$this, 'category_rows' ), 15, 3 );
			add_filter( 'manage_categories_columns', array( &$this, 'category_columns' ) );
			add_filter( 'manage_edit-tags_columns', array( &$this, 'category_columns' ) );
			
			/* Styles and Scripts */
			add_action( 'admin_print_scripts-edit-tags.php', array( &$this, 'edit_tags_js' ) );
			add_action( 'admin_print_styles-edit-tags.php', array( &$this, 'edit_tags_css' ) );
			
			/* Custom Actions for front-end. */
			add_action( $this->locale . '_print_image_html', array( &$this, 'print_image_html' ), 1, 3 );
			add_shortcode( $this->locale, array( &$this, 'list_term_images_shortcode' ) );
		}
		public function activate() {
			add_option( $this->locale, array() );
		}
		public function media_upload_popup_js() {
			if( isset( $_GET[ $this->attr_slug ] ) ) {
				wp_enqueue_script( 'taxonomy-images-media-upload-popup', $this->uri . 'media-upload-popup.js', array( 'jquery' ), $this->version );
				$term_id = (int) $_GET[ $this->attr_slug ];
				wp_localize_script( 'taxonomy-images-media-upload-popup', 'taxonomyImagesPlugin', array (
					'attr' => $this->attr( $term_id ),
					'locale' => $this->locale,
					'term_id' => $term_id,
					'attr_slug' => $this->attr_slug,
					'nonce_create' => wp_create_nonce( 'taxonomy-images-plugin-create-association' ),
					) );
			}
		}
		public function edit_tags_js() {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'taxonomy-images-edit-tags', $this->uri . 'edit-tags.js', array( 'jquery' ), $this->version );
			wp_localize_script( 'taxonomy-images-edit-tags', 'taxonomyImagesPlugin', array (
				'nonce_remove' => wp_create_nonce( 'taxonomy-images-plugin-remove-association' ),
				'img_src' => $this->url . 'default-image.png',
				) );
		}
		public function edit_tags_css() {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'taxonomy-images-edit-tags', $this->uri . 'admin.css', array(), $this->version, 'screen' );
		}
		public function register_settings() {
			register_setting( $this-locale, $this-locale, array( $this, 'sanitize_term_image_array' ) );
		}
		/**
		 * Dynamically create hooks for the columns and rows of edit-tags.php
		 * @since 0.4.3
		 * @uses $wp_taxonomies
		 * @return void
		 */
		public function admin_init() {
			global $wp_taxonomies;
			foreach( $wp_taxonomies as $taxonomy => $taxonomies ) {
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( &$this, 'category_rows' ), 10, 3 );
				add_filter( 'manage_edit-' . $taxonomy . '_columns', array( &$this, 'category_columns' ), 10, 3 );
			}
		}
		public function get_fullsize_image_dimensions( $term_tax_id ) {
			$post_id = false;
			$meta = false;
			if( array_key_exists( $term_tax_id, $this->settings ) ) {
				$post_id = $this->settings[$term_tax_id];
			}
			if ( $post_id ) {
				$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
			}
			return $meta;
		}
		public function list_term_images_shortcode( $atts = array() ) {
			$o = '';
			$defaults = array(
				// 'id' => false,
				'taxonomy' => 'category',
				'size' => 'detail',
				'template' => 'list'
				);
				
			extract( shortcode_atts( $defaults, $atts ) );
			
			/* No taxonomy defined return an html comment. */
			if( !taxonomy_exists( $taxonomy ) ) {
				$tax = strip_tags( trim( $taxonomy ) );
				return '<!--' . $this->locale . ' error: Taxonomy "' . $taxonomy . '" is not defined.-->';
			}
			
			$terms = get_terms( $taxonomy );
			
			if( !is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$open = '';
					$close = '';
					$img_tag = '';
					$url = get_term_link( $term, $term->taxonomy );
					$img = $this->get_image_html( $size, $term->term_taxonomy_id, true, 'left' );
					$title = apply_filters( 'the_title', $term->name );
					$title_attr = esc_attr( $term->name . ' (' . $term->count . ')' );
					$description = apply_filters( 'the_content', $term->description );
					if( $template === 'grid' ) {
						$o.= "\n\t" . '<div class="' . $this->locale . '-' . $template . '">';
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
		public function set_current_taxonomy() {
			if( is_admin() ) {
				global $hook_suffix;
				if( $hook_suffix === 'categories.php' )
					$this->current_taxonomy = 'category';
				if( $hook_suffix === 'edit-tags.php' && isset( $_GET['taxonomy'] ) )
					$this->current_taxonomy = ( get_taxonomy( $_GET['taxonomy'] ) ) ? $_GET['taxonomy'] : false;
			}
			else {
				global $wp_query;
				$q = $wp_query->get_queried_object();
				$this->current_taxonomy = ( get_taxonomy( $q->taxonomy ) ) ? $q->taxonomy : false;
			}
		}
		public function add_new_image_size() {
			add_image_size( 'detail', $this->detail_size[0], $this->detail_size[1], $this->detail_size[2] );
		}
		public function create_nonce_action( $action ) {
			return $this->locale . '-' . $action;
		}
		public function installation_success( $c ) {
			return $c . ' here I am';
		}
		/*
		* Ensures that all key/value pairs in an array are integers.
		* @param array On dimensional array of term_taxonomy_id/attachment_id pairs.
		* @return array 
		*/
		public function sanitize_term_image_array( $settings ) {
			$o = array();
			if( is_array( $settings ) ) {
				foreach( $settings as $key => $value ) {
					$o[ (int) $key ] = (int) $value;
				}
			}
			return $o;
		}
		public function control_add_image_to_taxonomy( $fields, $post ) {
			if(
				/* Newly uploaded image in media popup. */
				( isset( $_POST['fetch'] ) && 1 == $_POST['fetch'] ) ||
				
				/* Media Library tab of media popup.  */
				( isset( $_GET[$this->attr_slug] ) ) 
			) {
				$id = (int) $post->ID;
				$text = __( 'Add Thumbnail to Taxonomy', $this->locale );
				$button = '<a rel="' . $id . '" class="button-primary ' . $this->locale . '" href="#" onclick="return false;">' . $text . '</a>';
				$fields['image-size']['extra_rows']['taxonomy-image-plugin-button']['html'] = $button;
			}
			return $fields;
		}
		public function category_rows( $c, $column_name, $term_id ) {
			if( $column_name === 'custom' ) {
				$term_id = $this->term_tax_id( (int) $term_id );
				$href_library = $this->uri_library( $term_id );
				$href_upload = $this->uri_upload( $term_id );
				$id = $this->locale . '_' . $term_id;
				$attachment_id = ( isset( $this->settings[ $term_id ] ) ) ? (int) $this->settings[ $term_id ] : false;
				$img = ( $attachment_id ) ? $this->get_thumb( $attachment_id ) : $this->url . 'default-image.png';
				$text = array(
					esc_attr__( 'Please enable javascript to activate the taxonomy images plugin.', $this->locale ),
					esc_attr__( 'Upload.', $this->locale ),
					esc_attr__( 'Upload a new image for this taxonomy.', $this->locale ),
					esc_attr__( 'Media Library.', $this->locale ),
					esc_attr__( 'Choose an image from you Media Library.', $this->locale ),
					esc_attr__( 'Delete', $this->locale ),
					esc_attr__( 'Remove this association.', $this->locale ),
					);
				$class = array(
					'remove' => '',
					);
				if( !$attachment_id ) {
					$class['remove'] = ' hide';
				}
				return <<<EOF
{$c}
<img class="hide-if-js" src="{$this->url}no-javascript.png" alt="{$text[0]}" />
<div id="taxonomy-image-control-{$term_id}" class="taxonomy-image-control hide-if-no-js">
	<a class="thickbox taxonomy-image-thumbnail" onclick="return false;" href="{$href_library}"><img id="{$id}" src="{$img}" alt="" /></a>
	<a class="upload control thickbox" onclick="return false;" href="{$href_upload}" title="{$text[2]}">{$text[1]}</a>
	<span id="remove-{$term_id}" rel="{$term_id}" class="delete control{$class['remove']}" title="{$text[1]}">{$text[0]}</span>
</div>
EOF;
			}
		}
		public function category_columns( $original_columns ) {
			$new_columns = $original_columns;
			array_splice( $new_columns, 1 ); /* isolate the checkbox column */
			$new_columns['custom'] = __( 'Image', $this->locale ); /* Add custom column */
			return array_merge( $new_columns, $original_columns ); 
		}
		public function term_tax_id( $term ) {
			if( empty( $this->current_taxonomy ) ) {
				return false;
			}
			$data = get_term( $term, $this->current_taxonomy );
			if( isset( $data->term_taxonomy_id ) && !empty( $data->term_taxonomy_id ) ) {
				return $data->term_taxonomy_id;
			}
			else {
				return false;
			}
		}
		public function print_image_html( $size = 'medium', $term_tax_id = false, $title = true, $align = 'none' ) {
			print $this->get_image_html( $size, $term_tax_id, $title, $align );
		}
		/*
		* @uses $wp_query
		*/
		public function get_image_html( $size = 'medium', $term_tax_id = false, $title = true, $align = 'none' ) {
			$o = '';
			if( !$term_tax_id ) {
				global $wp_query;
				$mfields_queried_object = $wp_query->get_queried_object();
				$term_tax_id = $mfields_queried_object->term_taxonomy_id;
			}
			
			$term_tax_id = (int) $term_tax_id;
			
			if( isset( $this->settings[ $term_tax_id ] ) ) {
				$attachment_id = (int) $this->settings[ $term_tax_id ];
				$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
				$attachment = get_post( $attachment_id ); /* Just in case an attachment was deleted, but there is still a record for it in this plugins settings. */
				if( $attachment !== NULL ) {
					$o = get_image_tag( $attachment_id, $alt, '', $align, $size );
					
				}
			}
			return $o;
		}
		/*
		* @param $id (int) Attachment ID
		*/
		public function get_thumb( $id ) {
			global $wp_version;
			
			/* Get the originally uploaded size path. */
			list( $img_url, $img_path ) = get_attachment_icon_src( $id, true );
			
			/* Attepmt to get custom intermediate size. */
			$img = image_get_intermediate_size( $id, 'detail' );
			
			/* If custom intermediate size cannot be found, attempt to create it. */
			if( !$img ) {
				
				/* Need to check to see if fullsize path can be found - sometimes this disappears during import/export. */
				if( !is_file( $img_path ) ) {
					$wp_upload_dir = wp_upload_dir();
					$img_path = $wp_upload_dir['path'] . get_post_meta( $id, '_wp_attached_file', true );
				}
				
				if( is_file( $img_path ) ) {
					$new = image_resize( $img_path, $this->detail_size[0], $this->detail_size[1], $this->detail_size[2] );
					
					if( !is_wp_error( $new ) ) {
						$meta = wp_generate_attachment_metadata( $id, $img_path );
						wp_update_attachment_metadata( $id, $meta );
						$img = image_get_intermediate_size( $id, 'detail' );
					}
				}
			}
			
			/* Custom intermediate size cannot be created, try for thumbnail. */
			if( !$img ) {
				$img = image_get_intermediate_size( $id, 'thumbnail' );
			}
			
			/* Thumbnail cannot be found, try fullsize. */
			if( !$img ) {
				$img['url'] = wp_get_attachment_url( $id );
			}
			
			/* Administration */
			if( isset( $img['url'] ) && !empty( $img['url'] ) ) {
				return $img['url'];
			}
			else if( is_admin() ) {
				return $this->url . 'deleted-image.png';
			}
			return false;
		}
		public function uri_library( $term_tax_id = 0 ) {
			return admin_url( 'media-upload.php' ) . '?type=image&amp;tab=library&amp;' . $this->attr( $term_tax_id ). '&amp;TB_iframe=true';
		}
		public function uri_upload( $term_tax_id = 0 ) {
			return admin_url( 'media-upload.php' ) . '?type=image&amp;tab=type&amp;' . $this->attr( $term_tax_id ). '&amp;TB_iframe=true';
		}
		public function attr( $id = 0 ) { /* $id = term_id */
			return $this->attr_slug . '=' . (int) $id;
		}
		public function ajax_create_association() {
			/* Vars */
			global $wpdb;
			$nonce = false;
			$message = 'bad'; /* No need to localize. */
			$term_id = false;
			$term_exists = false;
			$attachment_id = false;
			$attachement_exists = false;
			$attachment_thumb_src = false;
			
			/* Check permissions */
			if( !current_user_can( $this->permission ) ) {
				wp_die( __( 'Sorry, you do not have the propper permissions to access this resource.', $this->locale ) );
			}

			/* Nonce does not match */
			if( !isset( $_POST['wp_nonce'] ) ) {
				wp_die( __( 'Access Denied to this resource 1.', $this->locale ) );
			}

			if( !wp_verify_nonce( $_POST['wp_nonce'], 'taxonomy-images-plugin-create-association' ) ) {
				wp_die( __( 'Access Denied to this resource 2.', $this->locale ) );
			}

			/* Check value of $_POST['term_id'] */
			if( isset( $_POST['term_id'] ) && !empty( $_POST['term_id'] ) && ( $_POST['term_id'] !== 'undefined' ) ) {
				$term_id = (int) $_POST['term_id'];
			}

			/* Query for $term_id */
			if( $term_id ) {
				$term_exists = $wpdb->get_var( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE `term_taxonomy_id` = {$term_id}" );
			}

			/* Term does not exist - do not proceed. */	
			if( !$term_exists ) {
				wp_die( __( 'The term does not exist.', $this->locale ) );
			}
				
			/* Query for $attachment_id */
			if( isset( $_POST['attachment_id'] ) ) {
				$attachment_id = (int) $_POST['attachment_id'];
				if( is_object( get_post( $attachment_id ) ) ) {
					$attachement_exists = true;
				}
			}

			/* Attachment does not exist - do not proceed */
			if( !$attachement_exists ) {
				wp_die( __( 'The attachment does not exist.', $this->locale ) );
			}

			$setting = get_option( $this->locale );
			$setting[$term_id] = $attachment_id;

			if( update_option( $this->locale, $setting ) ) {
				$message = 'good'; /* No need to localize. */
				$attachment_thumb_src = $this->get_thumb( $attachment_id );
			}

			/* Output */
			mfields_json_response( array(
				'message' => $message,
				'term_id' => $term_id,
				'attachment_id' => $attachment_id,
				'attachment_thumb_src' => $attachment_thumb_src,
				) );

		}
		public function ajax_remove_association() {
			/* Vars */
			global $wpdb;
			
			/* Check permissions */
			if( !current_user_can( $this->permission ) ) {
				mfields_json_response( array(
					'status' => 'bad',
					'why' => __( 'Sorry, you do not have the propper permissions to access this resource.', $this->locale ),
					) );
			}

			/* Nonce does not match */
			if( !isset( $_POST['wp_nonce'] ) ) {
				mfields_json_response( array(
				'status' => 'bad',
				'why' => __( 'Access Denied to this resource.1', $this->locale ),
				) );
			}

			if( !wp_verify_nonce( $_POST['wp_nonce'], 'taxonomy-images-plugin-remove-association' ) ) {
				mfields_json_response( array(
				'status' => 'bad',
				'why' => __( 'Access Denied to this resource.2', $this->locale ),
				) );
			}

			/* Check value of $_POST['term_id'] */
			if( isset( $_POST['term_id'] ) && !empty( $_POST['term_id'] ) && ( $_POST['term_id'] !== 'undefined' ) ) {
				$term_id = (int) $_POST['term_id'];
			}
			
			$associations = get_option( $this->locale );
			
			if( array_key_exists( $term_id, $associations ) ) {
				unset( $associations[$term_id] );
			}
			
			update_option( $this->locale, $associations );
			
			/* Output */
			mfields_json_response( array(
				'message' => 'good',
				) );

		}
	}
	$taxonomy_images_plugin = new taxonomy_images_plugin();
}

if( !function_exists( 'mfields_json_response' ) ) {
	/**
	 * JSON Respose.
	 * Terminate script execution.
	 * @param array Values to be encoded in JSON.
	 * @return void
	 */
	function mfields_json_response( $response ) {
		header( 'Content-type: application/jsonrequest' );
		print json_encode( $response );
		exit;
	}
}













?>