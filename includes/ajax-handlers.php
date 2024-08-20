<?php

function remove_recommended_product() {

	// Nonce check
	if ( ! check_ajax_referer( 'remove_recommended_product_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
		return; // Exit if nonce check fails
	}

	// Capability check
	if ( ! current_user_can( 'delete_posts' ) ) { // Adjust capability as necessary
		wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		return;
	}

	// Validate product ID
	$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
		return;
	}

	// Attempt to delete the product
	global $wpdb;
	$recommended_table_name = $wpdb->prefix . 'automation_engine_recommended_products';
	$result = $wpdb->delete( $recommended_table_name, array( 'product_id' => $product_id ) );

	if ( $result ) {
		wp_send_json_success( array( 'message' => 'Product successfully deleted' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Could not delete the product' ) );
	}
}
add_action( 'wp_ajax_remove_recommended_product', 'remove_recommended_product' );
add_action( 'wp_ajax_nopriv_remove_recommended_product', 'remove_recommended_product' );  // Only add this if needed for users not logged in.
