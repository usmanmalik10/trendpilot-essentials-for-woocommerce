<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function getProductViewOptions() {
	// Check if 'selected_product' is set, then perform nonce verification
	if ( isset( $_GET['selected_product'] ) ) {
		if ( ! isset( $_GET['selected_product_nonce'] ) || ! wp_verify_nonce( $_GET['selected_product_nonce'], 'selected_product_action' ) ) {
			wp_die( 'Security check failed' );
		}
	}

	global $wpdb;
	$product_ids = $wpdb->get_results(
		"SELECT p.ID, p.post_title FROM {$wpdb->prefix}posts p
        INNER JOIN {$wpdb->prefix}automation_engine_page_views v ON p.ID = v.product_id
        WHERE p.post_type = 'product' AND p.post_status = 'publish' AND v.product_id IS NOT NULL
        GROUP BY p.ID
        ORDER BY p.post_title ASC"
	);
	$selected_product = isset( $_GET['selected_product'] ) ? (int) $_GET['selected_product'] : '';

	foreach ( $product_ids as $product ) {
		$selected = ( $selected_product == $product->ID ) ? ' selected' : '';
		echo "<option value='" . esc_attr( $product->ID ) . "'" . esc_attr( $selected ) . ">" . esc_html( $product->post_title ) . "</option>";
	}
}


function getCategoryViewOptions() {
	if ( isset( $_GET['selected_category'] ) ) {
		if ( ! isset( $_GET['selected_category_nonce'] ) || ! wp_verify_nonce( $_GET['selected_category_nonce'], 'selected_category_action' ) ) {
			wp_die( 'Security check failed' );
		}
	}

	global $wpdb;
	$categories = $wpdb->get_results(
		"SELECT t.term_id, t.name FROM {$wpdb->prefix}terms t
        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->prefix}automation_engine_page_views v ON t.term_id = v.category_id
        WHERE tt.taxonomy = 'product_cat' AND v.category_id IS NOT NULL
        GROUP BY t.term_id
        ORDER BY t.name ASC"
	);

	$selected_category = isset( $_GET['selected_category'] ) ? (int) $_GET['selected_category'] : '';

	foreach ( $categories as $category ) {
		$selected = $selected_category == $category->term_id ? ' selected' : '';
		echo '<option value="' . esc_attr( $category->term_id ) . '"' . esc_attr( $selected ) . '>' . esc_html( $category->name ) . '</option>';
	}
}


function calculateProductViews() {

	if ( isset( $_GET['selected_product'] ) ) {
		if ( ! isset( $_GET['selected_product_nonce'] ) || ! wp_verify_nonce( $_GET['selected_product_nonce'], 'selected_product_action' ) ) {
			wp_die( 'Security check failed' );
		}
	}

	global $wpdb;
	$days_to_look_back = get_option( 'tp_analytics_period', 7 );
	$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days_to_look_back} days" ) );
	// First, retrieve the product to check if it exists and is valid
	$selected_product = isset( $_GET['selected_product'] ) ? (int) $_GET['selected_product'] : 0;

	$sql = "SELECT p.post_title, v.viewed_date, SUM(v.views) AS total_views
                    FROM {$wpdb->prefix}posts p
                    INNER JOIN {$wpdb->prefix}automation_engine_page_views v ON p.ID = v.product_id
                    WHERE p.post_type = 'product' AND p.post_status = 'publish' AND v.viewed_date > %s";

	$params = [ $date_threshold ];

	if ( $selected_product && wc_get_product( $selected_product ) ) {
		$sql .= " AND product_id = %d";
		$params[] = $selected_product;
	}

	$sql .= " GROUP BY v.product_id, v.viewed_date
                      ORDER BY p.post_title ASC, v.viewed_date ASC";

	$view_data = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

	$total_product_views = 0; // Initialize total views counter
	$products = [];
	foreach ( $view_data as $item ) {
		$products[ $item->post_title ][] = [ 'date' => $item->viewed_date, 'views' => $item->total_views ];
		$total_product_views += $item->total_views; // Summing total views
	}

	$labels = array_unique( array_map( function ($item) {
		return $item->viewed_date;
	}, $view_data ) );
	sort( $labels );
	$datasets = [];
	foreach ( $products as $product_name => $views ) {
		$data = array_fill( 0, count( $labels ), 0 );
		foreach ( $views as $view ) {
			$index = array_search( $view['date'], $labels );
			if ( $index !== false ) {
				$data[ $index ] = $view['views'];
			}
		}
		$datasets[] = [ 
			'label' => $product_name,
			'data' => $data,
			'borderColor' => sprintf( '#%06X', wp_rand( 0, 0xFFFFFF ) ),
			'fill' => false
		];

	}

	$labels_json = $labels;
	$datasets_json = $datasets;

	return [ $labels_json, $datasets_json, (int) $total_product_views ];

}

function calculateCategoryViews() {

	if ( isset( $_GET['selected_category'] ) ) {
		if ( ! isset( $_GET['selected_category_nonce'] ) || ! wp_verify_nonce( $_GET['selected_category_nonce'], 'selected_category_action' ) ) {
			wp_die( 'Security check failed' );
		}
	}

	global $wpdb;
	$days_to_look_back = (int) get_option( 'tp_analytics_period', 7 );
	$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days_to_look_back} days" ) );

	// Retrieve and check the selected category
	$selected_category = isset( $_GET['selected_category'] ) ? (int) $_GET['selected_category'] : 0;

	// Start building the SQL query dynamically
	$sql = "SELECT t.name, v.viewed_date, SUM(v.views) AS total_views
                        FROM {$wpdb->prefix}terms t
                        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                        INNER JOIN {$wpdb->prefix}automation_engine_page_views v ON t.term_id = v.category_id
                        WHERE tt.taxonomy = 'product_cat' AND v.viewed_date > %s";

	$params = [ $date_threshold ]; // Parameters for prepared statement

	if ( $selected_category && term_exists( $selected_category, 'product_cat' ) ) {
		$sql .= " AND category_id = %d"; // Append the condition to the SQL string
		$params[] = $selected_category; // Add the category ID to the parameters list
	}

	$sql .= " GROUP BY v.category_id, v.viewed_date
                          ORDER BY t.name ASC, v.viewed_date ASC";

	// Prepare and execute the query
	$view_data = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

	$total_category_views = 0;
	$categories_data = [];
	foreach ( $view_data as $item ) {
		$categories_data[ $item->name ][] = [ 'date' => $item->viewed_date, 'views' => $item->total_views ];
		$total_category_views += $item->total_views;
	}

	$labels = array_unique( array_map( function ($item) {
		return $item->viewed_date;
	}, $view_data ) );
	sort( $labels );
	$datasets = [];
	foreach ( $categories_data as $category_name => $views ) {
		$data = array_fill( 0, count( $labels ), 0 );
		foreach ( $views as $view ) {
			$index = array_search( $view['date'], $labels );
			if ( $index !== false ) {
				$data[ $index ] = $view['views'];
			}
		}
		$datasets[] = [ 
			'label' => sanitize_text_field( $category_name ),
			'data' => $data,
			'borderColor' => sprintf( '#%06X', wp_rand( 0, 0xFFFFFF ) ),
			'fill' => false
		];
	}

	$labels_json = $labels;
	$datasets_json = $datasets;

	return [ $labels_json, $datasets_json, (int) $total_category_views ];
}

function getRecommendedData() {
	global $wpdb;

	// Define a nicer color palette
	$colors = [ 
		"rgba(63, 81, 181, 0.8)", // Indigo
		"rgba(156, 39, 176, 0.8)", // Purple
		"rgba(33, 150, 243, 0.8)", // Blue
		"rgba(103, 58, 183, 0.8)", // Deep Purple
		"rgba(3, 169, 244, 0.8)"  // Light Blue
	];

	// Get the number of days to look back, defaulting to 7 if not set
	$days_to_look_back = (int) get_option( 'tp_analytics_period', 7 );

	// Calculate the date from 'tp_analytics_period' days ago
	$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days_to_look_back} days" ) );

	// Prepare the query to fetch clicks data since after the threshold day
	$click_data = $wpdb->get_results( $wpdb->prepare(
		"SELECT product_id, clicked_date, SUM(clicks) as clicks 
		 FROM {$wpdb->prefix}automation_engine_recommended_loop_clicks 
		 WHERE clicked_date > %s 
		 GROUP BY product_id, clicked_date 
		 ORDER BY clicked_date, product_id",
		$date_threshold
	) );

	$product_ids = array_unique( array_map( function ($item) {
		return $item->product_id;
	}, $click_data ) );

	if ( count( $product_ids ) > 0 ) {
		// Prepare placeholders for the product IDs
		$placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );


		// Execute the query
		$product_names_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title 
				FROM {$wpdb->posts} 
				WHERE ID IN ($placeholders) 
				AND post_type = 'product' 
				ORDER BY post_title ASC", ...$product_ids )
		);
	} else {
		$product_names_results = [];
	}

	// Combine the results into a single array with IDs as keys and post titles as values
	$product_names = array_combine(
		array_map( function ($item) {
			return (int) $item->ID;
		}, $product_names_results ),
		array_map( function ($item) {
			return sanitize_text_field( $item->post_title );
		}, $product_names_results )
	);

	// Prepare data for the chart
	$labels = [];
	$datasets = [];

	foreach ( $click_data as $entry ) {
		$productId = $entry->product_id;
		$clickDate = $entry->clicked_date;
		$clicks = $entry->clicks;
		$productName = $product_names[ $productId ] ?? "Product ID: $productId"; // Fallback to product ID if name not found

		// Ensure there is an array to store product data
		if ( ! array_key_exists( $productName, $datasets ) ) {
			$colorIndex = count( $datasets ) % count( $colors ); // Cycle through colors
			$datasets[ $productName ] = [ 
				'label' => $productName,
				'data' => [],
				'backgroundColor' => $colors[ $colorIndex ],
				'borderColor' => $colors[ $colorIndex ],
				'borderWidth' => 0 // no borders
			];
		}

		// Add the data point for the current date
		$datasets[ $productName ]['data'][] = [ 'x' => $clickDate, 'y' => $clicks ];
		// Ensure each date is only added once
		if ( ! in_array( $clickDate, $labels ) ) {
			$labels[] = $clickDate;
		}
	}

	// Sort labels to ensure the dates are in order
	sort( $labels );

	// Prepare data arrays for each product
	foreach ( $datasets as &$dataset ) {
		$datasetData = array_fill( 0, count( $labels ), 0 ); // fill with zeroes
		foreach ( $dataset['data'] as $dataPoint ) {
			$index = array_search( $dataPoint['x'], $labels );
			$datasetData[ $index ] = $dataPoint['y'];
		}
		$dataset['data'] = $datasetData; // replace with filled data
	}

	$datasets = array_values( $datasets ); // reset keys

	$labels_json = $labels;
	$datasets_json = $datasets;

	//Sort datasets by product name just before rendering the dropdown
	uasort( $datasets, function ($a, $b) {
		return strcmp( $a['label'], $b['label'] );
	} );

	return [ $labels_json, $datasets_json, $datasets ];

}


function get_recommended_products() {
	global $wpdb;

	// Fetch the number of days to look back from the 'tp_analytics_period' option
	$days_to_look_back = (int) get_option( 'tp_analytics_period', 7 );

	// Calculate the date from 'tp_analytics_period' days ago
	$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days_to_look_back} days +1 day" ) );

	$recommended_products = $wpdb->get_results( $wpdb->prepare(
		"SELECT product_id, SUM(clicks) AS total_clicks FROM {$wpdb->prefix}automation_engine_recommended_loop_clicks
        WHERE clicked_date >= %s
        GROUP BY product_id
        ORDER BY total_clicks DESC", // No limit, get all products
		$date_threshold
	) );

	return $recommended_products;
}

function getUpsellData() {


	global $wpdb;
	$prefix = $wpdb->prefix;

	// Fetch the current upsell product ID from WordPress options
	$upsell_product_id = get_option( 'upsell_product_id' );
	if ( ! $upsell_product_id ) {
		echo "<p>Error: No upsell product ID configured.</p>";
		return; // Exit if no upsell product ID is set
	}

	// Fetch the product details using WooCommerce function
	$product = wc_get_product( $upsell_product_id );
	if ( ! $product ) {
		echo "<p>Error: Product not found.</p>";
		return; // Exit if product not found
	}

	// Fetch the last change date for upsell clicks
	$last_change_date = get_option( 'upsell_date_last_change' );
	if ( ! $last_change_date ) {
		echo "<p>Error: Last change date for upsell not configured.</p>";
		return; // Exit if last change date not set
	}

	// Fetching the number of upsell clicks since the last date change
	$upsell_clicks = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(upsell_clicks) FROM {$prefix}automation_engine_click_data WHERE clicked_date >= %s AND upsell_clicks IS NOT NULL", $last_change_date ) );

	$image_url = wp_get_attachment_url( $product->get_image_id() );

	return [ $image_url, $product, $upsell_clicks ];

}

function getUpsellClicks() {
	global $wpdb;

	// Get the number of days to look back, defaulting to 7 if not set
	$days_to_look_back = (int) get_option( 'tp_analytics_period', 7 );

	// Calculate the date from 'tp_analytics_period' days ago
	$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days_to_look_back} days" ) );

	// Prepare the query to fetch upsell clicks data since after the threshold day, ordered by date ascending
	$click_data = $wpdb->get_results( $wpdb->prepare(
		"SELECT clicked_date, upsell_clicks FROM {$wpdb->prefix}automation_engine_click_data WHERE clicked_date > %s ORDER BY clicked_date ASC",
		$date_threshold
	) );

	// Prepare data for the chart
	$labels = [];
	$data = [];
	foreach ( $click_data as $day ) {
		$labels[] = $day->clicked_date; // Collect all dates
		$data[] = $day->upsell_clicks;  // Collect all clicks
	}

	// Convert data to JSON format to pass to JavaScript
	$labels_json = $labels;
	$data_json = $data;

	return [ $labels_json, $data_json ];
}
