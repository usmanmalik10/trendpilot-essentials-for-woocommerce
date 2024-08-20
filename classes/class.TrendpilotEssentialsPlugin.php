<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotEssentialsPlugin {
	// Loader instance
	protected static $loader;

	// Initialization method
	public static function init() {
		self::get_loader()->run();
	}

	// Get the loader instance
	protected static function get_loader() {
		if ( null === self::$loader ) {
			include_once plugin_dir_path( __FILE__ ) . '/class.TrendpilotEssentialsLoader.php';
			self::$loader = new TrendpilotEssentialsLoader();
		}
		return self::$loader;
	}

	// Activation method
	public static function activate() {
		global $wpdb;

		// Database table names
		$workflows_table_name = $wpdb->prefix . 'automation_engine_workflows';
		$states_table_name = $wpdb->prefix . 'automation_engine_states';
		$page_views_table_name = $wpdb->prefix . 'automation_engine_page_views';
		$click_data_table_name = $wpdb->prefix . 'automation_engine_click_data';
		$recommended_loop_clicks_table_name = $wpdb->prefix . 'automation_engine_recommended_loop_clicks';
		$recommended_products_table_name = $wpdb->prefix . 'automation_engine_recommended_products';
		$logger_table_name = $wpdb->prefix . 'automation_engine_workflow_logger';
		$ab_tests_table_name = $wpdb->prefix . 'automation_engine_ab_tests';

		$charset_collate = $wpdb->get_charset_collate();

		// SQL statements for existing tables
		$sql_workflows = "CREATE TABLE $workflows_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                unique_id varchar(255) DEFAULT NULL,
                name varchar(255) NOT NULL,
                steps longtext,
                is_repeat tinyint(1) NOT NULL DEFAULT 0,
                status varchar(50) DEFAULT 'active' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                /* workflow_json longtext DEFAULT NULL, */
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_states = "CREATE TABLE $states_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                unique_id varchar(255) DEFAULT NULL,
                user_id mediumint(9) DEFAULT NULL,
                current_step mediumint(9),
                parameters longtext,
                steps longtext,
                status varchar(50),
                is_child tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_page_views = "CREATE TABLE $page_views_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id mediumint(9) DEFAULT NULL,
                category_id mediumint(9) DEFAULT NULL,
                views mediumint(9) DEFAULT 0,
                viewed_date date DEFAULT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_click_data = "CREATE TABLE $click_data_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                upsell_clicks int DEFAULT 0 NOT NULL,
                homepage_banner int DEFAULT 0 NOT NULL,
                clicked_date date NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_recommended_loop_clicks = "CREATE TABLE $recommended_loop_clicks_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id mediumint(9) NOT NULL,
                clicks mediumint(9) NOT NULL,
                clicked_date date NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_recommended_products = "CREATE TABLE $recommended_products_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id mediumint(9) NOT NULL,
                date_added DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_logger = "CREATE TABLE $logger_table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                workflow_id varchar(255) NOT NULL,
                user_id mediumint(9),
                step text NOT NULL,
                description text NOT NULL,
                step_name text NOT NULL,
                timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		$sql_ab_tests = "CREATE TABLE " . $wpdb->prefix . "automation_engine_ab_tests (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                selector varchar(255) NOT NULL,
                type varchar(100) NOT NULL,
                goal_type varchar(100) NOT NULL,
                variation_a text NOT NULL,
                variation_b text NOT NULL,
                variation_a_count mediumint(9) NOT NULL DEFAULT 0,
                variation_b_count mediumint(9) NOT NULL DEFAULT 0,
                status varchar(50) NOT NULL,
                product_id mediumint(9),
                start_date date NOT NULL,
                end_date date NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

		// Include WordPress upgrade library
		require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Run dbDelta for the initial table creation
		dbDelta( $sql_workflows );
		dbDelta( $sql_states );
		dbDelta( $sql_page_views );
		dbDelta( $sql_click_data );
		dbDelta( $sql_recommended_loop_clicks );
		dbDelta( $sql_recommended_products );
		dbDelta( $sql_logger );
		dbDelta( $sql_ab_tests );

		// Check if the 'workflow_json' column exists, if not, add it
		// $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $workflows_table_name LIKE 'workflow_json'" );
		// if ( empty( $column_exists ) ) {
		// 	$wpdb->query( "ALTER TABLE $workflows_table_name ADD workflow_json LONGTEXT DEFAULT NULL" );
		// }

		// Flush the rewrite rules
		flush_rewrite_rules();

		update_option( 'woocommerce_default_catalog_orderby', 'recommended' );
	}

	// Deactivation method
	public static function deactivate() {
		// Remove custom rewrite rules
		flush_rewrite_rules();
	}

}