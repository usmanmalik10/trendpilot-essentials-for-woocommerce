<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotEssentialsLoader {

	public function run() {
		// Register hooks for all feature classes
		$this->load_files();
		$this->register_function_hooks();
		$this->register_feature_hooks();

		// Add admin actions if in admin area
		if ( is_admin() ) {
			$this->register_admin_hooks();
		}
	}

	private function load_files() {
		include TP_ESSENTIALS_PLUGIN_PATH . '/includes/functions.php';
	}

	private function register_feature_hooks() {
		// Array of feature class names
		$features = [ 
			'TrendpilotEssentials\Upsell',
			'TrendpilotEssentials\Badge',
			'TrendpilotEssentials\Recommended',
			'TrendpilotEssentials\TopBar',
			'TrendpilotEssentials\Cron',
			'TrendpilotEssentials\ProductDisplay',
		];

		foreach ( $features as $feature ) {
			if ( class_exists( $feature ) ) {
				$instance = new $feature();
				if ( method_exists( $instance, 'registerHooks' ) ) {
					$instance->registerHooks();
				}
			}
		}
	}

	public function register_function_hooks() {
		add_action( 'template_redirect', 'record_page_view' );
	}

	public function register_admin_hooks() {
		$tpAdmin = new TrendpilotAdmin();
		$tpAdmin->registerHooks();
	}

}
