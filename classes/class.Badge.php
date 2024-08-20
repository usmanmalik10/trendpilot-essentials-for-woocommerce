<?php

namespace TrendpilotEssentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Badge {

	public function registerHooks() {

		add_action( 'wp_enqueue_scripts', [ $this, 'ae_enqueue_badge_styles' ] );
		add_action( 'add_meta_boxes', [ $this, 'ae_add_badge_meta_box' ] );
		add_action( 'save_post', [ $this, 'ae_save_product_badge_meta_box' ] );
		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'ae_display_product_badge' ] );
		add_action( 'woocommerce_before_single_product_summary', [ $this, 'ae_display_product_badge_single_product' ] );

		if ( get_option( 'ae_enable_badges', 1 ) ) {
			add_filter( 'woocommerce_sale_flash', '__return_false' );
		}

	}

	public function ae_add_badge_meta_box() {
		add_meta_box(
			'ae_product_badge',
			'Product Badge',
			[ $this, 'ae_product_badge_meta_box_callback' ],
			'product',
			'side',
			'high'
		);
	}
	//add_action('add_meta_boxes', 'ae_add_badge_meta_box');

	function ae_product_badge_meta_box_callback( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( basename( __FILE__ ), 'ae_product_badge_nonce' );

		// Retrieve current badge and custom text if any.
		$current_badge = get_post_meta( $post->ID, 'ae_product_badge', true );
		$current_custom_text = get_post_meta( $post->ID, 'ae_product_badge_custom_text', true );

		// Options for badges.
		$badges = array(
			'none' => 'None',
			'sale' => 'Sale',
			'popular' => 'Popular',
			'new' => 'New',
			'custom' => 'Custom'
		);

		if ( ! array_key_exists( $current_badge, $badges ) && ! empty( $current_badge ) ) {
			$current_badge = 'custom';
		}

		// Dropdown for selecting badge.
		echo '<select name="ae_product_badge" id="ae_product_badge">';
		foreach ( $badges as $value => $label ) {

			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current_badge, $value, false ) . '>' . esc_html( $label ) . '</option>';

		}
		echo '</select>';

		// Input for custom badge text.
		echo '<p>';
		echo '<label for="ae_product_badge_custom_text">Custom Badge Text:</label>';
		echo '<input type="text" id="ae_product_badge_custom_text" name="ae_product_badge_custom_text" value="' . esc_attr( $current_custom_text ) . '" />';
		echo '</p>';
	}

	function ae_save_product_badge_meta_box( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['ae_product_badge_nonce'] ) || ! wp_verify_nonce( $_POST['ae_product_badge_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions.
		if ( 'product' !== $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Define the badge text as the dropdown value initially
		$badge_text = isset( $_POST['ae_product_badge'] ) ? sanitize_text_field( $_POST['ae_product_badge'] ) : '';

		// If 'custom' is selected, override the badge text with the custom input text
		if ( $badge_text === 'custom' && isset( $_POST['ae_product_badge_custom_text'] ) ) {
			$badge_text = sanitize_text_field( $_POST['ae_product_badge_custom_text'] );

		}

		// Update the badge text meta field in the database.
		update_post_meta( $post_id, 'ae_product_badge', $badge_text );

		// Update the custom text
		update_post_meta( $post_id, 'ae_product_badge_custom_text', sanitize_text_field( $_POST['ae_product_badge_custom_text'] ) );
	}
	//add_action('save_post', 'ae_save_product_badge_meta_box');

	public function ae_display_product_badge( $bypass_option_check = false ) {
		if ( ! $bypass_option_check && ! get_option( 'ae_enable_badges', 1 ) )
			return;  // Exit if badges are disabled and not bypassing the option check

		global $product;
		$badge_text = get_post_meta( $product->get_id(), 'ae_product_badge', true );
		$is_on_sale = $product->is_on_sale();

		if ( $is_on_sale && ( $badge_text === '' || $badge_text === 'none' ) ) {
			$badge_text = 'Sale';
		} elseif ( ! $is_on_sale && strtolower( $badge_text ) === 'sale' ) {
			$badge_text = 'none';
		}

		if ( $badge_text && $badge_text !== 'none' ) {
			echo '<div class="ae-product-badge">' . esc_html( ucfirst( $badge_text ) ) . '</div>';
		}
	}

	//add_action('woocommerce_before_shop_loop_item', 'ae_display_product_badge');

	function ae_display_product_badge_single_product() {
		if ( ! get_option( 'ae_enable_badges', 1 ) )
			return;  // Exit if badges are disabled

		global $product;
		$badge_text = get_post_meta( $product->get_id(), 'ae_product_badge', true );
		$is_on_sale = $product->is_on_sale();

		if ( $is_on_sale && ( $badge_text === '' || $badge_text === 'none' ) ) {
			$badge_text = 'Sale';
		} elseif ( ! $is_on_sale && strtolower( $badge_text ) === 'sale' ) {
			$badge_text = 'none';
		}

		if ( $badge_text && $badge_text !== 'none' ) {
			echo '<div class="ae-product-badge-single">' . esc_html( ucfirst( $badge_text ) ) . '</div>';
		}
	}
	//add_action('woocommerce_before_single_product_summary', 'ae_display_product_badge_single_product', 5);


	// Function to get an option with a default value
	function get_ae_option( $option_name, $default = '' ) {
		$option = get_option( $option_name );
		return ( $option !== false ) ? $option : $default;
	}

	function ae_enqueue_badge_styles() {
		wp_register_style( 'ae-custom-badge-styles', false );
		wp_enqueue_style( 'ae-custom-badge-styles' );

		// Retrieve options with defaults
		$font_size = $this->get_ae_option( 'ae_badge_font_size', '14' );
		$badge_color = $this->get_ae_option( 'ae_badge_color', '#FFA500' );
		$border_radius = $this->get_ae_option( 'ae_badge_border_radius', '100' );
		$font_color = $this->get_ae_option( 'ae_badge_font_color', '#FFFFFF' );  // Retrieve the font color option

		$custom_css = "
        .ae-product-badge, .ae-product-badge-single {
            position: absolute;
            top: 0;
            left: 0;
            background-color: $badge_color;
            color: $font_color;
            font-size: {$font_size}px;
            z-index: 100;
            font-weight: 600;
            border-radius: {$border_radius}px;
            padding-top: 14px;
            padding-bottom: 14px;
            padding-right: 11px;
            padding-left: 11px;
            min-width: 50px;
            min-height: 50px;
            text-align: center;
    
        }";

		wp_add_inline_style( 'ae-custom-badge-styles', $custom_css );
	}


}

//add_action('wp_enqueue_scripts', 'ae_enqueue_badge_styles');
