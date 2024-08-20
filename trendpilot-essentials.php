<?php

/*
Plugin Name: Trendpilot Essentials For Woocommerce
Description: Upsells, Essential View & Click Analytics, Top Bar, Product Badges and more!
Author: Trendpilot
Version: 1.0
Plugin URI: https://trendpilot.io/automation-engine
Author URI: https://trendpilot.io
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/


if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

define('TRENDPILOT_ESSENTIALS_VERSION', '1.0.0');
define('TP_ESSENTIALS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRENDPILOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

function trendpilot_essentials_check_woocommerce()
{
	if (! class_exists('WooCommerce')) {
		error_log('WC is inactive');
		add_action('admin_notices', 'trendpilot_essentials_woocommerce_missing_notice');
	}
}

function trendpilot_essentials_woocommerce_missing_notice()
{
?>
<div class="notice notice-error">
  <p>
    <?php echo esc_html__('Trendpilot Essentials requires WooCommerce to be installed and active.', 'trendpilot-essentials'); ?>
  </p>
</div>
<?php
}

add_action('plugins_loaded', 'trendpilot_essentials_check_woocommerce');

function trendpilot_essentials_autoloader($class)
{
	// Check if the class belongs to the TrendpilotPro namespace.
	if (strpos($class, 'TrendpilotEssentials\\') === 0) {
		$class = str_replace('TrendpilotEssentials\\', '', $class);
		$file = TP_ESSENTIALS_PLUGIN_PATH . 'classes/class.' . $class . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
}

include_once TP_ESSENTIALS_PLUGIN_PATH . 'includes/ajax-handlers.php';
spl_autoload_register('trendpilot_essentials_autoloader');
register_activation_hook(__FILE__, array('TrendpilotEssentials\TrendpilotEssentialsPlugin', 'activate'));
register_deactivation_hook(__FILE__, array('TrendpilotEssentials\TrendpilotEssentialsPlugin', 'deactivate'));
TrendpilotEssentials\TrendpilotEssentialsPlugin::init();