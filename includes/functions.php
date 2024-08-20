<?php


function manual_flush_page_views() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'automation_engine_page_views';
	$wpdb->query( $wpdb->prepare( "DELETE FROM `{$page_views_table_name}` WHERE 1=1" ) );
	echo '<div class="updated"><p>Successfully flushed page views.</p></div>';
}

function manual_flush_click_data() {
	global $wpdb;
	$click_data_table_name = $wpdb->prefix . 'automation_engine_click_data';
	$wpdb->query( $wpdb->prepare( "DELETE FROM `{$click_data_table_name}` WHERE 1=1" ) );
	echo '<div class="updated"><p>Successfully flushed click data.</p></div>';
}

//$timezone = get_option( 'timezone_string' ) ?: 'UTC';

function record_page_view() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'automation_engine_page_views';

	// Check if the current request is for an upsell popup
	if ( isset( $_GET['show_atc_modal'] ) && $_GET['show_atc_modal'] == '1' ) {
		// Verify the nonce before proceeding
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'show_atc_modal_nonce' ) ) {
			wp_die( 'Nonce verification failed' );
		}
		return; // Skip recording page view if it's an upsell popup
	}

	$is_product_page = is_product();
	$is_category_page = is_product_category();
	$item_id = 0;

	if ( $is_product_page ) {
		$item_id = get_the_ID();
	} elseif ( $is_category_page ) {
		$term = get_queried_object();
		$item_id = $term->term_id;
	} else {
		return;
	}

	$today = current_time( 'Y-m-d' );

	$existing_entry = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $page_views_table_name 
         WHERE ((product_id = %d AND product_id IS NOT NULL) OR 
                (category_id = %d AND category_id IS NOT NULL)) AND 
               viewed_date = %s",
		$is_product_page ? $item_id : 0,
		$is_category_page ? $item_id : 0,
		$today
	) );

	if ( $existing_entry ) {
		$wpdb->query( $wpdb->prepare(
			"UPDATE $page_views_table_name SET views = views + 1 
             WHERE id = %d",
			$existing_entry
		) );
	} else {
		$wpdb->insert(
			$page_views_table_name,
			array(
				'product_id' => $is_product_page ? $item_id : NULL,
				'category_id' => $is_category_page ? $item_id : NULL,
				'views' => 1,
				'viewed_date' => $today
			),
			array( '%d', '%d', '%d', '%s' )
		);
	}
}



//add_action('template_redirect', 'record_page_view');

/**
 * Function to flush old page views.
 */
function flush_old_page_views() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'automation_engine_page_views';
	$days_to_keep = get_option( 'page_view_flush_period', 30 );

	// Calculate the date before which data should be deleted using gmdate for UTC consistency
	$delete_before_date = gmdate( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

	// Prepare and execute the delete query
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $page_views_table_name WHERE viewed_date <= %s", $delete_before_date ) );
}

// Flush function for old click data
function flush_old_click_data() {
	global $wpdb;
	$click_data_table_name = $wpdb->prefix . 'automation_engine_click_data';
	$days_to_keep = get_option( 'click_data_flush_period', 30 );

	// Calculate the date before which data should be deleted using gmdate for UTC consistency
	$delete_before_date = gmdate( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

	// Prepare and execute the delete query
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $click_data_table_name WHERE clicked_date <= %s", $delete_before_date ) );
}

/// when upsell_product_id changes, this function changes 'upsell_date_last_changed' to todays date
// it also clears the upsell click data table if the value of upsell_product_id has changed
function handle_upsell_product_id_change( $option, $old_value, $value ) {
	global $wpdb;

	// This function should only act on 'upsell_product_id' changes
	if ( $option !== 'upsell_product_id' ) {
		return;
	}

	// Check if the value has changed
	if ( $old_value === $value ) {
		return; // Exit the function if the product ID has not changed
	}

	// Update the datetime for upsell_date_last_change
	$current_date = current_time( 'Y-m-d' ); // This will get the current date without time
	$existing_date = get_option( 'upsell_date_last_change' );

	if ( $current_date !== $existing_date ) {
		update_option( 'upsell_date_last_change', $current_date );
	}

	// Clear data from the upsell clicks table
	$table_name = $wpdb->prefix . 'automation_engine_click_data';
	$cleared = $wpdb->query( "DELETE FROM {$table_name}" );
}
add_action( 'updated_option', 'handle_upsell_product_id_change', 10, 3 );