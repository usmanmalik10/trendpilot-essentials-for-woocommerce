<?php

define( 'AE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_settings_section( 'ae_badge_settings', null, '__return_null', 'ae_plugin' );

?>


<!-- HEX color picker script -->

<script>
	jQuery(document).ready(function ($) {
		$(".hex-color-picker").spectrum({
			preferredFormat: "hex",
			showInput: true,
			showInitial: true,
			allowEmpty: true,
			showPalette: true,
			palette: [
				["#000", "#444", "#666", "#999", "#ccc", "#eee", "#f3f3f3", "#fff"],
				["#f00", "#f90", "#ff0", "#0f0", "#0ff", "#00f", "#90f", "#f0f"]
			]
		});
	});
</script>

<!-- Upsell Settings HTML -->
<div class="plugin-set-sect" id="upsell-settings">
	<h1>
		Upsell Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="https://trendpilot-bucket.s3.eu-west-1.amazonaws.com/Trendpilot+Wordpress+Plugin+Files+(LIVE)/Feature+Guide+Images/Upsell+Popup.png"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<p class="description">Popup shows upon Add to Cart on product page.</p>
	<form method="post" action="options.php#upsell-settings">
		<?php settings_fields( 'upsell_settings' );
		do_settings_sections( 'upsell_settings' ); ?>
		<table class="form-table">
			<!-- New setting for enabling Upsell Page -->
			<tr valign="top">
				<th scope="row">Enable Upsell Popup</th>
				<td>
					<input type="checkbox" name="enable_upsell_page" <?php echo get_option( 'enable_upsell_page' ) ? 'checked' : ''; ?> />
					<p class="description">Enable or disable the Upsell Popup feature.</p>
				</td>
			</tr>
			<!-- New setting for defining Upsell Product ID -->
			<tr valign="top">
				<th scope="row">Upsell Product</th>
				<td>
					<select name="upsell_product_id">
						<?php
						$current_upsell_id = get_option( 'upsell_product_id', '' );
						$args = array(
							'limit' => -1, // Get all products
							'status' => 'publish', // Only get published products
						);
						$products = wc_get_products( $args );

						foreach ( $products as $product ) {
							$selected = ( $product->get_id() == $current_upsell_id ) ? ' selected' : '';
							echo '<option value="' . esc_attr( $product->get_id() ) . '"' . esc_attr( $selected ) . '>' . esc_html( $product->get_name() ) . '</option>';
						}
						?>
					</select>
					<p class="description">Select a Product for the Upsell Page.</p>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
</div>

</form>

<div class="plugin-set-sect" id="recommended-settings">
	<h1>
		'Recommended' Section Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="https://trendpilot-bucket.s3.eu-west-1.amazonaws.com/Trendpilot+Wordpress+Plugin+Files+(LIVE)/Feature+Guide+Images/Recommended+Products.png"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<p class="description">We've added a new 'order by' option to the Woocommerce shop page. Choose products to
		highlight here.</p>
	<!-- Separate form for adding a product to the recommended list '-->
	<div class="wrap">
		<form method="post" action="#recommended-settings">
			<!-- Nonce field for Recommended Products -->
			<?php wp_nonce_field( 'add_to_recommended_nonce_action', 'add_to_recommended_nonce' ); ?>
			<!-- Checkbox to Enable/Disable Recommended Section -->
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Enable Recommended Section</th>
					<td>
						<input type="hidden" name="tp_enable_disable_recommended" value="0">
						<input type="checkbox" name="tp_enable_disable_recommended" id="tp_enable_disable_recommended"
							value="1" <?php echo checked( 1, get_option( 'tp_enable_disable_recommended', 0 ) ); ?> />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Manually Add Product To Recommended</th>
					<td>
						<select name="add_to_recommended_product_id">
							<option value="">Select a product:</option>
							<?php
							$args = array(
								'limit' => -1, // Get all products
								'status' => 'publish', // Only get published products
							);
							$products = wc_get_products( $args );
							foreach ( $products as $product ) {
								echo '<option value="' . esc_attr( $product->get_id() ) . '">' . esc_html( $product->get_name() ) . '</option>';
							}
							?>
						</select>
						<p class="description">Select a Product to add it to the recommended list.</p>
					</td>
				</tr>
				<!-- Settings for Total Recommended Products -->
				<tr valign="top">
					<th scope="row">Total Recommended Products</th>
					<td>
						<input type="number" name="total_recommended_products"
							value="<?php echo esc_attr( get_option( 'total_recommended_products', 5 ) ); ?>" />
						<p class="description">Set the max number of recommended products allowed at any time. Default
							is 5. New entries will overwrite the oldest-added</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Update' ); ?>
		</form>

	</div>


	<!-- New table for displaying the names of recommended products -->
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Current Recommended Products</th>
			<td>
				<ul>
					<?php
					global $wpdb;
					$recommended_table_name = $wpdb->prefix . 'automation_engine_recommended_products';
					$recommended_products = $wpdb->get_col( "SELECT product_id FROM $recommended_table_name" );

					foreach ( $recommended_products as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( $product ) {
							echo '<li>' . esc_html( $product->get_name() ) . ' <button class="remove-product" data-product-id="' . (int) $product_id . '">X</button></li>';

						}
					}
					?>
				</ul>
			</td>
		</tr>
	</table>
</div>

<script>
	// Remove recommended products script
	document.addEventListener('DOMContentLoaded', function () {
		var removeButtons = document.querySelectorAll('.remove-product');

		removeButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				var productId = this.getAttribute('data-product-id');
				var nonce = '<?php echo esc_js( wp_create_nonce( 'remove_recommended_product_nonce' ) ); ?>';
				var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

				// Log the important variables
				console.log('ajaxUrl: ', ajaxUrl);
				console.log('Product ID: ' + productId);
				console.log('Nonce: ' + nonce);

				// Using jQuery for AJAX request
				jQuery.ajax({
					url: ajaxUrl,
					type: 'POST',
					data: {
						action: 'remove_recommended_product',
						product_id: productId,
						nonce: nonce
					},
					success: function (response) {
						console.log('Response received: ', response);
						if (response.success) {
							button.parentElement.remove();
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function (xhr, status, error) {
						console.log('HTTP error status:', status, 'Error:', error);
						console.log('Server response:', xhr.responseText);
						alert('Failed to execute the request.');
					}
				});
			});
		});
	});
</script>

<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function () {
		var tooltipIcons = document.querySelectorAll('.tooltip-icon');

		tooltipIcons.forEach(function (icon) {
			icon.addEventListener('mouseover', function () {
				this.querySelector('.tp-tooltip-image').style.display = 'block';
			});

			icon.addEventListener('mouseout', function () {
				this.querySelector('.tp-tooltip-image').style.display = 'none';
			});
		});
	});
</script>

<div class="plugin-set-sect" id="topbar-settings">
	<h1>
		Top Bar Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="https://trendpilot-bucket.s3.eu-west-1.amazonaws.com/Trendpilot+Wordpress+Plugin+Files+(LIVE)/Feature+Guide+Images/Top+Bar.png"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<form method="post" action="options.php#topbar-settings">
		<?php
		settings_fields( 'ae_topbar_settings' );
		do_settings_sections( 'ae_topbar_settings' );
		$topBarText = get_option( 'ae_top_bar_message', 'Default message' );
		$topBarColor = get_option( 'ae_top_bar_background_color', '#012C6D' );
		$topBarTextColor = get_option( 'ae_top_bar_text_color', '#FFFFFF' );
		$topBarActive = get_option( 'ae_top_bar_active', 0 );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Activate Top Bar</th>
				<td>
					<input type="checkbox" name="ae_top_bar_active" value="1" <?php checked( 1, $topBarActive, true ); ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Text</th>
				<td><input type="text" name="ae_top_bar_message" value="<?php echo esc_attr( $topBarText ); ?>"
						style="width: 500px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Background Color</th>
				<td><input type="text" id="ae_top_bar_background_color" name="ae_top_bar_background_color"
						class="hex-color-picker" value="<?php echo esc_attr( $topBarColor ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Text Color</th>
				<td><input type="color" name="ae_top_bar_text_color" class="hex-color-picker"
						value="<?php echo esc_attr( $topBarTextColor ); ?>" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

<?php

?>

<div class="plugin-set-sect" id="badge-settings">
	<h1>
		Product Badge Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="https://trendpilot-bucket.s3.eu-west-1.amazonaws.com/Trendpilot+Wordpress+Plugin+Files+(LIVE)/Feature+Guide+Images/Product+badges.png"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<p class="description">Set badges on Edit product pages</p>
	<form method="post" action="options.php#badge-settings">
		<?php
		settings_fields( 'ae_badge_settings' );
		do_settings_sections( 'ae_badge_settings' );
		$badgeFontSize = get_option( 'ae_badge_font_size', '14px' );
		$badgeColor = get_option( 'ae_badge_color', '#FFA500' );
		$badgeBorderRadius = get_option( 'ae_badge_border_radius', '100px' );
		?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">Enable Product Badges</th>
				<td><input type="checkbox" name="ae_enable_badges" value="1" <?php checked( 1, get_option( 'ae_enable_badges', 1 ) ); ?> /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Badge Font Size</th>
				<td><input type="text" name="ae_badge_font_size" value="<?php echo esc_attr( $badgeFontSize ); ?>"
						style="width: 100px;" /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Badge Color</th>
				<td><input type="text" id="ae_badge_color" name="ae_badge_color" class="hex-color-picker"
						value="<?php echo esc_attr( $badgeColor ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Badge Border Radius</th>
				<td><input type="text" name="ae_badge_border_radius"
						value="<?php echo esc_attr( $badgeBorderRadius ); ?>" style="width: 100px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Badge Font Color</th>
				<td>
					<input type="text" id="ae_badge_font_color" name="ae_badge_font_color" class="hex-color-picker"
						value="<?php echo esc_attr( get_option( 'ae_badge_font_color', '#FFFFFF' ) ); ?>" />
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

<div class="plugin-set-sect" id="data-settings">
	<h1>Data Tools</h1>

	<form method="post" action="options.php#data-settings">
		<?php settings_fields( 'flush_settings' );
		do_settings_sections( 'flush_settings' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Flush Page View Data Period (Days)</th>
				<td>
					<input type="number" name="page_view_flush_period"
						value="<?php echo esc_attr( get_option( 'page_view_flush_period', 30 ) ); ?>" />
					<p class="description">Every day we will remove product & category views older than this number of
						days. Default is 30 days. </p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Flush Click Data Period (Days)</th>
				<td>
					<input type="number" name="click_data_flush_period"
						value="<?php echo esc_attr( get_option( 'click_data_flush_period', 30 ) ); ?>" />
					<p class="description">Every day we will remove upsell click data older than this number of days.
						Default is 30 days. </p>
				</td>
			</tr>
		</table>

		<!-- Flush buttons -->
		<div style="margin-bottom: 15px">
			<?php wp_nonce_field( 'flush_page_views_action', 'flush_page_views_nonce' ); ?>
			<input style="min-width:210px" type="submit" name="flush_page_views" value="Flush Page Views table">
			<span style="margin-left: 10px;">Click this button to manually remove all entries in the Page/Category Views
				table.
				Warning: This will reset all page & category view data</span>
		</div>
		<div style="margin-bottom: 15px">
			<?php wp_nonce_field( 'flush_click_data_action', 'flush_click_data_nonce' ); ?>
			<input style="min-width:210px" type="submit" name="flush_click_data" value="Flush Click Data table">
			<span style="margin-left: 10px;">Click this button to manually remove all entries in the click data table.
				Warning: This will reset all click data and live automations may be affected.</span>
		</div>
		<?php submit_button(); ?>
</div>
</form>

<div class="plugin-set-sect">
	<h1>Important Information</h1>
	<p class="description">When changing theme, please deactivate and reactive this plugin to ensure functionality</p>
</div>

<?php

