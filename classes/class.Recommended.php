<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Recommended {

	private $tp_enable_disable_recommended;

	function __construct() {
		$this->tp_enable_disable_recommended = get_option( 'tp_enable_disable_recommended' );
	}

	public function registerHooks() {

		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'modify_recommended_product_urls' ] );
		add_action( 'template_redirect', [ $this, 'record_recommended_clicks' ] );


		if ( $this->tp_enable_disable_recommended ) {
			add_filter( 'woocommerce_default_catalog_orderby_options', [ $this, 'addRecommendedToAdmin' ] );
			add_filter( 'woocommerce_catalog_orderby', [ $this, 'addRecommendedToAdmin' ], 10, 1 );
			add_filter( 'woocommerce_catalog_orderby', [ $this, 'addRecommendedOrderOption' ], 10, 1 );
			add_action( 'pre_get_posts', [ $this, 'sort_products_by_recommended' ] );
		}

	}

	public function addRecommendedToAdmin( $sortby ) {
		$sortby['recommended'] = 'Recommended';
		return $sortby;
	}

	public function addRecommendedOrderOption( $sortby ) {
		$sortby = [ 'recommended' => 'Recommended' ] + $sortby;
		return $sortby;
	}

	public function sort_products_by_recommended( $query ) {
		global $wpdb;

		if ( ! is_admin() && $query->is_main_query() ) {

			/// nonce verification not neccesary, because using built-in Woocommerce Get parameter 'orderby', which doesnt appear to use nonce verification.
			$current_orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : get_option( 'woocommerce_default_catalog_orderby' );

			if ( $current_orderby === 'recommended' ) {
				$table_name = sanitize_text_field( $wpdb->prefix . 'automation_engine_recommended_products' );

				add_filter( 'posts_fields', function ($fields) use ($wpdb, $table_name) {
					$fields .= ", IF($wpdb->posts.ID IN (SELECT product_id FROM $table_name),1,0) as is_recommended";
					return $fields;
				} );

				add_filter( 'posts_join', function ($join) use ($wpdb, $table_name) {
					$join .= " LEFT JOIN $table_name ON $wpdb->posts.ID = $table_name.product_id";
					return $join;
				} );

				add_filter( 'posts_orderby', function ($orderby) use ($wpdb, $table_name) {
					$default_orderby = "$wpdb->posts.menu_order ASC, $wpdb->posts.post_date DESC"; // WooCommerce default
					$orderby = "is_recommended DESC, $table_name.date_added DESC, " . $default_orderby;
					return $orderby;
				} );
			}

		}

	}

	public function add_to_recommended( $product_id ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'automation_engine_recommended_products';
		$product_id = absint( $product_id );
		// Check if the product is already in the recommended list
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE product_id = %d", $product_id ) ); // db call ok

		// If the product already exists, return true
		if ( $exists > 0 ) {
			return true;
		}

		// Get the value of 'total_recommended_products' option
		$max_recommended_products = get_option( 'total_recommended_products', 5 ); // default is 5

		// Get the current count of recommended products
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		// Remove the oldest recommended product if adding a new one exceeds the max limit
		if ( $total_count >= $max_recommended_products ) {
			$wpdb->query( "DELETE FROM $table_name ORDER BY date_added ASC LIMIT 1" );
		}

		// Add the new product to recommended
		$result = $wpdb->insert(
			$table_name,
			array( 'product_id' => $product_id, 'date_added' => current_time( 'mysql' ) ),
			array( '%d', '%s' )
		);

		return $result !== false;
	}

	function remove_from_recommended( $product_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'automation_engine_recommended_products';

		// Remove the product from the recommended list
		$result = $wpdb->delete(
			$table_name,
			array( 'product_id' => (int) $product_id ),
			array( '%d' )
		);

		// Check if the product was successfully removed
		return $result !== false;
	}

	// Step 1: Add query parameter to URLs of recommended products in the shop loop
	//add_action('woocommerce_before_shop_loop_item', 'modify_recommended_product_urls');

	function modify_recommended_product_urls() {
		global $product, $wpdb;

		// Get the ID of the current product in the loop
		$product_id = $product->get_id();

		// Check if the product is recommended by querying your custom table
		$table_name = $wpdb->prefix . 'automation_engine_recommended_products';
		$is_recommended = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE product_id = %d", $product_id ) );

		if ( $is_recommended ) {
			add_filter( 'woocommerce_loop_product_link', [ $this, 'add_rec_click_query_arg' ], 10, 1 );
			remove_filter( 'woocommerce_loop_product_link', [ $this, 'remove_rec_click_query_arg' ], 10 );
		} else {
			add_filter( 'woocommerce_loop_product_link', [ $this, 'remove_rec_click_query_arg' ], 10, 1 );
			remove_filter( 'woocommerce_loop_product_link', [ $this, 'add_rec_click_query_arg' ], 10 );
		}
	}

	function add_rec_click_query_arg( $link ) {
		$nonce = wp_create_nonce( 'rec_click_nonce' );
		$link = add_query_arg( 'rec_click', '1', $link );
		$link = add_query_arg( '_wpnonce', $nonce, $link );
		return $link;
	}


	function remove_rec_click_query_arg( $link ) {
		return $link;
	}

	// Step 2: Hook into template_redirect to capture and record clicks
	//add_action('template_redirect', 'record_recommended_clicks');

	function record_recommended_clicks() {
		global $wpdb;
		if ( is_single() && 'product' == get_post_type() ) {
			if ( isset( $_GET['rec_click'] ) && $_GET['rec_click'] == 1 ) {
				// Verify nonce
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'rec_click_nonce' ) ) {
					wp_die( 'Security check failed' );
				}

				$product_id = get_the_ID();
				$clicked_date = current_time( 'Y-m-d' );

				// Step 3: Check and update the table
				$table_name = $wpdb->prefix . 'automation_engine_recommended_loop_clicks';
				$existing_entry = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $table_name WHERE product_id = %d AND clicked_date = %s",
					intval( $product_id ),
					$clicked_date
				) );

				if ( $existing_entry ) {
					$wpdb->update(
						$table_name,
						array( 'clicks' => $existing_entry->clicks + 1 ),
						array( 'id' => $existing_entry->id ),
						array( '%d' ),
						array( '%d' )
					);
				} else {
					$wpdb->insert(
						$table_name,
						array( 'product_id' => $product_id, 'clicks' => 1, 'clicked_date' => $clicked_date ),
						array( '%d', '%d', '%s' )
					);
				}

				// Remove the 'rec_click' query parameter and redirect with JavaScript (quicker method than wp_safe_redirect)
				echo '<script type="text/javascript">
					 var url = new URL(window.location.href).origin + new URL(window.location.href).pathname;
					 window.history.replaceState({}, document.title, url);
				</script>';
			}
		}
	}



}
