<?php

/* Vars */
global $wpdb;
$nonce = false;
$term_id = false;
$term_exists = false;
$attachment_id = false;
$attachement_exists = false;
$attachment_thumb_src = false;

/* Check permissions */
if( !current_user_can( $this->permission ) )
	wp_die( 'Sorry, you do not have the propper permissions to access this resource.' );


/* Nonce does not match */
$nonce = ( isset( $_POST['wp_nonce'] ) ) ? $_POST['wp_nonce'] : false;
if( !wp_verify_nonce( $nonce, $this->create_nonce_action( $this->ajax_action ) ) )
	wp_die( 'Access Denied to this resource.' );


/* Check value of $_POST['term_id'] */
$term_id = ( isset( $_POST['term_id'] ) && !empty( $_POST['term_id'] ) && ( $_POST['term_id'] !== 'undefined' ) )
	? (int) $_POST['term_id']
	: false;

	
/* Query for $term_id */
if( $term_id )
	$term_exists = $wpdb->get_var( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE `term_taxonomy_id` = {$term_id}" );

	
/* Term does not exist - do not proceed. */	
if( !$term_exists )
	wp_die( 'The term does not exist.' );
	
	
/* Check value of $_POST['attachment_id'] */
$attachment_id = ( isset( $_POST['attachment_id'] ) && !empty( $_POST['attachment_id'] ) && ( $_POST['attachment_id'] !== 'undefined' ) )
	? (int) $_POST['attachment_id']
	: false;	

/* Query for $attachment_id */
$attachement_exists = ( is_object( get_post( $attachment_id ) ) ) ? true : false;

/* Attachment does not exist - do not proceed */
if( !$attachement_exists )
	wp_die( 'The attachment does not exist.' );

$setting = get_option( $this->locale );
$setting[$term_id] = $attachment_id;

if( update_option( $this->locale, $setting ) ) {
	$message = 'good';
	$attachment_thumb_src = $this->get_thumb( $attachment_id );
}
else
	$message = 'bad';
	
/* Output */
header( 'Content-type: application/jsonrequest' );
$json = json_encode( array(
	'message' => $message,
	'term_id' => $term_id,
	'attachment_id' => $attachment_id,
	'attachment_thumb_src' => $attachment_thumb_src
	) );
print $json;
exit();
?>