<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotAdmin {

	public function __construct() {

		// Directly call register settings method
		$this->register_settings();
	}

	public function registerHooks() {
		// Use array($this, 'methodName') to reference the method within the class
		add_action( 'admin_init', [ $this, 'handle_post_requests' ] );
		add_action( 'admin_init', [ $this, 'ensure_tp_analytics_period_is_set' ] );
		add_action( 'admin_menu', [ $this, 'add_essentials_menu' ] );
		add_action( 'admin_menu', [ $this, 'add_essentials_submenus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );

		add_filter( 'plugin_action_links_' . TRENDPILOT_PLUGIN_BASENAME, [ $this, 'trendpilot_add_action_links' ] );
	}

	public function trendpilot_add_action_links( $links ) {

		$settings_link = '<a href="admin.php?page=trendpilot_essentials">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	// Register settings method
	public function register_settings() {
		register_setting(
			'trendpilot_options_group',
			'tp_analytics_period',
			array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
				'default' => 7,
				'capability' => 'manage_options'
			)
		);

		register_setting( 'flush_settings', 'page_view_flush_period', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'upsell_settings', 'enable_upsell_page', [ 
			'sanitize_callback' => 'sanitize_text_field',
			'capability' => 'manage_options'
		] );
		register_setting( 'upsell_settings', 'upsell_product_id', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'flush_settings', 'click_data_flush_period', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'recommended_settings', 'total_recommended_products', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );

		register_setting( 'ae_topbar_settings', 'ae_top_bar_message', [ 
			'sanitize_callback' => 'sanitize_text_field',
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_topbar_settings', 'ae_top_bar_background_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_topbar_settings', 'ae_top_bar_text_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_topbar_settings', 'ae_top_bar_active', [ 
			'sanitize_callback' => array( $this, 'my_plugin_sanitize_checkbox' ),
			'capability' => 'manage_options'
		] );

		register_setting( 'ae_badge_settings', 'ae_badge_font_size', [ 
			'sanitize_callback' => 'absint',
			'default' => 12,
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_badge_settings', 'ae_badge_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#FFFFFF',
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_badge_settings', 'ae_badge_border_radius', [ 
			'sanitize_callback' => 'absint',
			'default' => 0,
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_badge_settings', 'ae_badge_font_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#000000',
			'capability' => 'manage_options'
		] );
		register_setting( 'ae_badge_settings', 'ae_enable_badges', [ 
			'sanitize_callback' => array( $this, 'my_plugin_sanitize_checkbox' ),
			'default' => 1,
			'capability' => 'manage_options'
		] );

		// Ensure tp_analytics_period is set
		$this->ensure_tp_analytics_period_is_set();
	}

	public function handle_post_requests() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Handle flush page views
			if ( isset( $_POST['flush_page_views'] ) && check_admin_referer( 'flush_page_views_action', 'flush_page_views_nonce' ) ) {
				manual_flush_page_views();
			}

			// Handle flush click data
			if ( isset( $_POST['flush_click_data'] ) && check_admin_referer( 'flush_click_data_action', 'flush_click_data_nonce' ) ) {
				manual_flush_click_data();
			}

			// Handle add to recommended product
			if ( ! empty( $_POST['add_to_recommended_product_id'] ) && check_admin_referer( 'add_to_recommended_nonce_action', 'add_to_recommended_nonce' ) ) {
				$product_id_to_add = sanitize_text_field( $_POST['add_to_recommended_product_id'] );
				$recommended = new Recommended();
				$recommended->add_to_recommended( $product_id_to_add );
				echo '<div class="updated"><p>Product added to the recommended list.</p></div>';
			}

			// Check and handle the enable/disable recommended section
			if ( isset( $_POST['tp_enable_disable_recommended'] ) ) {
				update_option( 'tp_enable_disable_recommended', $_POST['tp_enable_disable_recommended'] ? 1 : 0 );
			}

			// Handle the total recommended products setting
			if ( isset( $_POST['total_recommended_products'] ) ) {
				$total_recommended = intval( $_POST['total_recommended_products'] );
				update_option( 'total_recommended_products', $total_recommended );
			}
		}
	}

	// Add main menu
	public function add_essentials_menu() {
		add_menu_page(
			'Trendpilot Essentials',                 // Page title
			'Trendpilot Essentials for WooCommerce',                 // Menu title
			'manage_options',                        // Capability
			'trendpilot_essentials',                 // Menu slug
			array( $this, 'trendpilot_essentials_callback' ),  // Callback function
			null,                                    // Icon URL
			3                                        // Position
		);
	}

	// Add sub-menus
	public function add_essentials_submenus() {
		// Analytics submenu
		add_submenu_page(
			'trendpilot_essentials',                  // Parent slug
			'Analytics',                              // Page title
			'Analytics',                              // Menu title
			'manage_options',                         // Capability
			'trendpilot_analytics',                   // Menu slug
			array( $this, 'trendpilot_analytics_callback' )   // Callback function
		);

		// Settings submenu
		add_submenu_page(
			'trendpilot_essentials',                  // Parent slug
			'Settings',                               // Page title
			'Settings',                               // Menu title
			'manage_options',                         // Capability
			'trendpilot_settings',                    // Menu slug
			array( $this, 'my_plugin_display_settings_page' )  // Callback function
		);

		// Product Displays submenu
		add_submenu_page(
			'trendpilot_essentials',                  // Parent slug
			'Product Displays',                       // Page title
			'Product Displays',                       // Menu title
			'manage_options',                         // Capability
			'edit.php?post_type=product_displays'     // Menu slug linking to custom post type list table
		);
	}


	// Main menu callback function
	public function trendpilot_essentials_callback() {
		include plugin_dir_path( __FILE__ ) . '../admin/plugin-home-page.php';
	}

	// Callback function for the 'Analytics' submenu
	public function trendpilot_analytics_callback() {
		include plugin_dir_path( __FILE__ ) . '../admin/analytics-page.php';
	}

	// Enqueue admin styles
	public function enqueue_admin_styles( $hook ) {

		if ( $hook == 'trendpilot-essentials-for-woocommerce_page_trendpilot_analytics' ) {
			//Enqueue Bootstrap CSS
			wp_enqueue_style(
				'bootstrap-css',
				'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
				array(),
				'5.1.3'
			);

			// Enqueue Chart.js
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js',
				array(),
				null,
				true
			);
		}

		error_log( '$hook: ' . $hook );

		// Array of slugs for the pages where the stylesheet should be enqueued
		$settings_page_slugs = [ 
			'toplevel_page_trendpilot_essentials',
			'trendpilot-essentials-for-woocommerce_page_trendpilot_settings'
		];

		// Check if we're on one of the correct pages
		if ( ! in_array( $hook, $settings_page_slugs ) ) {
			return;
		}

		// The URL to the plugin's stylesheet
		$css_url = plugin_dir_url( __FILE__ ) . '../admin/css/plugin-settings-styles.css';

		// Enqueue the plugin's stylesheet
		wp_enqueue_style( 'my-plugin-settings-styles', $css_url );

		// Enqueue the Spectrum CSS
		wp_enqueue_style(
			'spectrum-css',
			'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css',
			array(),
			'1.8.0'
		);

		// Enqueue the Spectrum JS
		wp_enqueue_script(
			'spectrum-js',
			'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js',
			array( 'jquery' ),
			'1.8.0',
			true
		);


	}

	// Callback function for the 'Settings' submenu
	public function my_plugin_display_settings_page() {
		include plugin_dir_path( __FILE__ ) . '../admin/plugin_settings.php';
	}

	public function my_plugin_sanitize_checkbox( $input ) {
		// If checkbox is checked, return 1 (true), otherwise return 0 (false)
		return ( $input ? 1 : 0 );
	}

	public function ensure_tp_analytics_period_is_set() {
		if ( false === get_option( 'tp_analytics_period' ) ) {
			update_option( 'tp_analytics_period', 7 );
		}
	}
}