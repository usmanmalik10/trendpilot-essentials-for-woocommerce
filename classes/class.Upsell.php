<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Upsell {
	private $timezone;

	// Constructor to set the timezone property
	public function __construct() {
		$this->timezone = get_option( 'timezone_string' ) ?: 'UTC';
	}

	public function registerHooks() {
		add_action( 'template_redirect', [ $this, 'record_upsell_click' ] );
		add_action( 'wp_footer', [ $this, 'add_upsell_product_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_upsell_modal_styles' ] );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'add_empty_upsell_modal_to_product_page' ] );
	}

	public function add_upsell_product_script() {
		if ( is_product() ) {
			$isUpsellEnabled = get_option( 'enable_upsell_page', false );
			$upsell_product_id = get_option( 'upsell_product_id', '' );

			global $product;
			$current_product_id = $product->get_id();

			if ( ! empty( $upsell_product_id ) && $isUpsellEnabled && $current_product_id != $upsell_product_id ) {
				$upsellProduct = wc_get_product( $upsell_product_id );
				if ( $upsellProduct ) {
					$regular_price = wc_price( $upsellProduct->get_regular_price() );
					$sale_price = wc_price( $upsellProduct->get_sale_price() );

					$formatted_price = $upsellProduct->is_on_sale() ? "<del style='color:#989898'>{$regular_price}</del> <ins>{$sale_price}</ins>" : $regular_price;
					$nonce = wp_create_nonce( 'record_upsell_click' );  // Use 'record_upsell_click' here
					?>
					<script type="text/javascript">
						var upsellProduct = {
							name: <?php echo wp_json_encode( sanitize_text_field( $upsellProduct->get_name() ) ); ?>,
							price: <?php echo wp_json_encode( $formatted_price ); ?>,
							imageUrl: <?php echo wp_json_encode( wp_get_attachment_url( sanitize_text_field( (int) $upsellProduct->get_image_id() ) ) ); ?>,
							cartUrl: <?php echo wp_json_encode( wc_get_checkout_url() . '?add-to-cart=' . (int) $upsell_product_id . '&up_pg=1&_wpnonce=' . $nonce ); ?>,  // Add nonce here
							checkoutUrl: <?php echo wp_json_encode( sanitize_text_field( wc_get_checkout_url() ) ); ?>
						};
						var upsellNonce = <?php echo wp_json_encode( $nonce ); ?>;
					</script>
					<?php
					wp_enqueue_script( 'upsell-modal', plugin_dir_url( __FILE__ ) . '../public/js/upsell-modal.js', array( 'jquery' ), null, true );

				}
			}
		}
	}


	public function record_upsell_click() {

		global $wpdb;
		$click_data_table_name = $wpdb->prefix . 'automation_engine_click_data';

		if ( isset( $_GET['up_pg'] ) && $_GET['up_pg'] == '1' ) {


			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'record_upsell_click' ) ) {
				wp_die( 'Security check failed' );
			}

			// Get the current date in the site's timezone
			$today = current_time( 'Y-m-d' );

			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $click_data_table_name WHERE clicked_date = %s",
					$today
				)
			);

			if ( $existing_entry ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $click_data_table_name SET upsell_clicks = upsell_clicks + 1 WHERE id = %d",
						$existing_entry
					)
				);
			} else {
				$wpdb->insert(
					$click_data_table_name,
					array(
						'upsell_clicks' => 1,
						'clicked_date' => $today
					),
					array( '%d', '%s' )
				);
			}
		}
	}


	public function enqueue_upsell_modal_styles() {
		wp_enqueue_style( 'upsell-modal-styles', plugin_dir_url( __FILE__ ) . '../public/css/upsell-modal.css' );
	}

	public function add_empty_upsell_modal_to_product_page() {
		?>
		<div id="upsellModal" class="modal" style="display:none;">
			<div class="modal-content">
				<!-- Content will be loaded here by JavaScript -->
			</div>
			<button class="modal-close">Close</button>
		</div>
		<?php
	}

}



//add_action('template_redirect', 'record_upsell_click');
