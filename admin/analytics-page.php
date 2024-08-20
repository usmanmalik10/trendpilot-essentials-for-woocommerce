<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
	// Check if the nonce field exists and then validate it
	if ( isset( $_GET['selected_product_nonce'] ) && ! wp_verify_nonce( $_GET['selected_product_nonce'], 'selected_product_action' ) ) {
		die( 'Security check failed for selected product' );
	}
	// Check for and validate the nonce for the selected category form
	if ( isset( $_GET['selected_category_nonce'] ) && ! wp_verify_nonce( $_GET['selected_category_nonce'], 'selected_category_action' ) ) {
		die( 'Security check failed for selected category' );
	}
	// Similar nonce validation for other forms can be added here
	if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'change-filter-option-nonce' ) ) {
		die( 'Security check failed for update settings' );
	}

}

include plugin_dir_path( __FILE__ ) . '../includes/analytics-page-functions.php';

wp_enqueue_style( 'my-plugin-custom-styles', plugin_dir_url( __FILE__ ) . '../admin/css/tp-bootstrap-custom.css', array(), '1.0.0' );

?>

<div class="tp-header-container">
	<div class="row align-items-center py-2">
		<div class="col-lg-8 col-md-7 d-flex align-items-center">
			<h2>Analytics</h2>
			<!-- Combined Form for Both Checkboxes -->
			<form action="" method="GET" class="d-flex align-items-center ml-3 tp-analytics-filter-form"
				id="filterForm">
				<!-- Generate a nonce and include it as a hidden field -->
				<?php
				$nonce = wp_create_nonce( 'change-filter-option-nonce' );
				echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
				// List of checkbox parameters to be preserved
				$checkboxes = [ 'show_recommended', 'show_upsell' ];
				?>
				<input type="hidden" name="page" value="trendpilot_analytics">
				<?php if ( get_option( 'tp_enable_disable_recommended' ) === '0' ) { ?>
					<div>
						<input type="checkbox" id="show_recommended" name="show_recommended" value="1" <?php echo isset( $_GET['show_recommended'] ) && $_GET['show_recommended'] === '1' ? 'checked' : ''; ?>
							onchange="this.form.submit()">
						<label for="show_recommended">Show Recommended Section Data</label>
					</div>
				<?php } ?>
				<?php if ( get_option( 'enable_upsell_page' ) !== 'on' ) { ?>
					<div class="ml-3">
						<input type="checkbox" id="show_upsell" name="show_upsell" value="1" <?php echo isset( $_GET['show_upsell'] ) && $_GET['show_upsell'] === '1' ? 'checked' : ''; ?>
							onchange="this.form.submit()">
						<label for="show_upsell">Show Upsell Data</label>
					</div>
				<?php } ?>
			</form>
		</div>
		<div class="col-lg-4 col-md-5">
			<form action="options.php" method="post" class="d-flex align-items-center justify-content-lg-end">
				<?php settings_fields( 'trendpilot_options_group' ); ?>
				<label for="tp_analytics_period" class="mr-2 mb-0">Select period (days):</label>
				<div class="d-flex align-items-center">
					<input type="number" class="form-control mr-2 tp-period-card-label" id="tp_analytics_period"
						name="tp_analytics_period" min="0"
						value="<?php echo esc_attr( get_option( 'tp_analytics_period' ) ); ?>" placeholder="Enter days"
						required style="width: auto;">
					<button type="submit" class="btn btn-primary tp-period-card-label">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>


<div class="col-lg-8 col-md-7 tp-section-title">
	Views
</div>

<!-- VIEWS ROW Start  -->
<div class="row row-sm upsell-row">

	<!-- CUSTOM CARD: Product Views - START-->
	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Product Views</label></p>
					<p class="text-muted card-sub-title">Product Views over the last
						<?php echo esc_attr( get_option( 'tp_analytics_period' ) ) ?> days
					</p>
				</div>
				<form method="GET">
					<select name="selected_product" onchange="this.form.submit()">
						<option value="">All Products</option>
						<?php getProductViewOptions(); ?>
					</select>
					<?php

					/// We are cycling through the GET parameters here to preserve the existing parameters on the next page load.
					// Correcting double escaping and simplifying
					foreach ( $_GET as $key => $value ) {
						if ( $key != 'selected_product' ) {
							echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '" value="' . esc_attr( sanitize_text_field( $value ) ) . '">';
						}
					}

					// Include nonce for the form
					wp_nonce_field( 'selected_product_action', 'selected_product_nonce' );
					?>
				</form>


				<?php
				$productViewsData = calculateProductViews();

				// output of calculateProductViews uses json_encode()
				$labels_json = $productViewsData[0];
				$datasets_json = $productViewsData[1];
				$total_product_views = $productViewsData[2];
				?>

				<div class="chartjs-wrapper" style="width:100%; overflow-x: auto; height: 100%; min-height:400px">
					<canvas id="productViewChart" class="chartjs-render-monitor"></canvas>
				</div>
				<div>
					<p style="margin-top: 10px;" class="mb-0 text-muted">
						Total product views over the last <?php echo esc_attr( get_option( 'tp_analytics_period' ) ); ?>
						days:
						<b class="text-primary"><?php echo esc_html( $total_product_views ); ?></b>
					</p>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			var ctx = document.getElementById('productViewChart').getContext('2d');
			var labels = <?php echo wp_json_encode( $labels_json ); ?>;
			var datasets = <?php echo wp_json_encode( $datasets_json ); ?>;

			// Define colors for the datasets
			var colors = [
				"rgba(63, 81, 181, 0.8)", // Indigo
				"rgba(103, 58, 183, 0.8)", // Deep Purple
				"rgba(33, 150, 243, 0.8)", // Blue
				"rgba(156, 39, 176, 0.8)", // Purple
				"rgba(3, 169, 244, 0.8)"  // Light Blue
			];

			// Apply colors and line tension to datasets
			datasets.forEach((dataset, index) => {
				dataset.borderColor = colors[index % colors.length];
				dataset.backgroundColor = colors[index % colors.length];
				dataset.fill = false;
				dataset.tension = 0.4; // Set line tension for curved lines
			});

			var productViewChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: datasets
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false // Change to true if legend is needed
						},
						tooltip: {
							mode: 'nearest',
							intersect: true,
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text: 'Views'
							}
						},
						x: {
							title: {
								display: true,
								text: 'Date'
							}
						}
					}
				}
			});
		});
	</script>
	<!-- CUSTOM CARD: Product Views - END-->

	<!-- CUSTOM CARD: Category Views - START-->
	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Category Views</label></p>
					<p class="text-muted card-sub-title">Category Views over the last
						<?php echo esc_attr( get_option( 'tp_analytics_period' ) ) ?> days
					</p>
				</div>
				<form method="GET">
					<select name="selected_category" onchange="this.form.submit()">
						<option value="">All Categories</option>
						<?php getCategoryViewOptions(); ?>
					</select>
					<?php
					// Preserve other GET parameters
					foreach ( $_GET as $key => $value ) {
						if ( $key != 'selected_category' ) {
							echo '<input type="hidden" name="' . esc_attr( sanitize_text_field( $key ) ) . '" value="' . esc_attr( sanitize_text_field( $value ) ) . '">';
						}
					}


					// Include nonce for the form
					wp_nonce_field( 'selected_category_action', 'selected_category_nonce' );
					?>
				</form>

				<?php
				$catViewsData = calculateCategoryViews();

				// output of calculateCategoryViews uses json_encode()
				$labels_json = $catViewsData[0];
				$datasets_json = $catViewsData[1];
				$total_category_views = $catViewsData[2];
				?>

				<div class="chartjs-wrapper" style="width:100%; overflow-x: auto; height: 100%; min-height:400px">
					<canvas id="categoryViewChart" class="chartjs-render-monitor"></canvas>
				</div>
				<div>
					<p style="margin-top: 10px;" class="mb-0 text-muted">Total category views over the last
						<?php echo esc_attr( get_option( 'tp_analytics_period' ) ); ?> days: <b
							class="text-primary"><?php echo esc_attr( $total_category_views ); ?></b>
					</p>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			var ctx = document.getElementById('categoryViewChart').getContext('2d');

			// Define colors for the datasets
			var colors = [
				"rgba(63, 81, 181, 0.8)", // Indigo
				"rgba(103, 58, 183, 0.8)", // Deep Purple
				"rgba(33, 150, 243, 0.8)", // Blue
				"rgba(156, 39, 176, 0.8)", // Purple
				"rgba(3, 169, 244, 0.8)"  // Light Blue
			];

			var labels = <?php echo wp_json_encode( $labels_json ); ?>;
			var datasets = <?php echo wp_json_encode( $datasets_json ); ?>;

			// Apply colors and line tension to datasets
			datasets.forEach((dataset, index) => {
				dataset.borderColor = colors[index % colors.length];
				dataset.backgroundColor = 'rgba(0,0,0,0)'; // No fill for line charts
				dataset.fill = false;
				dataset.tension = 0.4; // Set line tension for curved lines
			});

			var categoryViewChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: datasets
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false // Set to true if legend is needed
						},
						tooltip: {
							mode: 'nearest',
							intersect: true,
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text: 'Views'
							}
						},
						x: {
							title: {
								display: true,
								text: 'Date'
							}
						}
					}
				}
			});
		});
	</script>

	<!-- CUSTOM CARD: Category Views - END-->


	<!-- VIEWS ROW End  -->
</div>

<?php if ( (int) get_option( 'tp_enable_disable_recommended', 0 ) == 1 || isset( $_GET['show_recommended'] ) && (int) $_GET['show_recommended'] == 1 ) { ?>
	<div class="col-lg-8 col-md-7 tp-section-title">
		'Recommended' Section
	</div>


	<!-- RECOMMENDED ROW Start  -->
	<div class="row row-sm recommended-row">

		<!-- CUSTOM CARD: Recommended Chart START  -->

		<?php
		$recommendedData = getRecommendedData();

		/// function getRecommendedData() uses json_encode for $recommendedData[0] and $recommendedData[1]. $recommendedData[2] is escaped on output.
		$labels_json = $recommendedData[0];
		$datasets_json = $recommendedData[1];
		$datasets = $recommendedData[2];
		?>

		<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
			<div class="card custom-card overflow-hidden">
				<div class="card-body">
					<div>
						<p><label class="card-label-header font-weight-bold mb-2">Recomended clicks over time</label></p>
						<p class="text-muted card-sub-title">Over last
							<?php echo esc_attr( get_option( 'tp_analytics_period' ) ) ?> days
						</p>
					</div>
					<!-- Place this dropdown above or below your chart container -->
					<select id="legendDropdown" onchange="updateDatasetVisibility()">
						<option value="all">Show All</option>
						<!-- Dynamically generate options based on datasets -->
						<?php foreach ( $datasets as $index => $prodName ) : ?>
							<option value="<?php echo esc_attr( $index ); ?>"><?php echo esc_attr( ( $prodName['label'] ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<div class="chartjs-wrapper" style="width: 100%; height: 100%;min-height: 400px;">
						<canvas id="recommendedChart" class="chartjs-render-monitor"></canvas>
					</div>
				</div>
			</div>
		</div>
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				var ctx = document.getElementById('recommendedChart').getContext('2d');
				var labels = <?php echo wp_json_encode( $labels_json ); ?>;
				var datasets = <?php echo wp_json_encode( $datasets_json ); ?>;
				var recommendedChart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: datasets
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								display: false // Disable the default legend
							}
						},
						scales: {
							x: {
								stacked: true
							},
							y: {
								stacked: true,
								beginAtZero: true
							}
						}
					}
				});

				window.updateDatasetVisibility = function () {
					var select = document.getElementById('legendDropdown');
					var selectedValue = select.value;

					recommendedChart.data.datasets.forEach(function (dataset, index) {
						if (selectedValue === "all") {
							dataset.hidden = false; // Show all datasets
						} else {
							dataset.hidden = index !== parseInt(selectedValue); // Hide all datasets that do not match the selection
						}
					});
					recommendedChart.update(); // Update the chart to reflect changes
				};
			});
		</script>
		<!-- CUSTOM CARD: Recommended Chart END  -->

		<!-- CUSTOM CARD: Top Recommended Start  -->
		<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
			<div class="card custom-card recommended-card">
				<div class="card-header border-bottom-0 pb-1">
					<label class="card-label-header font-weight-bold mb-2">Top Recommended Products</label>
					<p class="text-muted mb-2">Most clicked recommended products in the last
						<?php echo esc_attr( get_option( 'tp_analytics_period' ) ); ?> days
					</p>
				</div>
				<div class="card-body pt-0">
					<ul class="list-unstyled">
						<?php
						$recommended_products = get_recommended_products();
						foreach ( $recommended_products as $prod ) {
							$product = wc_get_product( $prod->product_id );
							$image_url = wp_get_attachment_url( $product->get_image_id() );
							?>
							<li class="mb-4">
								<div class="row">
									<div class="col-md-1">
										<img src="<?php echo esc_url( $image_url ); ?>"
											alt="<?php echo esc_attr( $product->get_name() ); ?>"
											style="max-width: 100%; height: auto;" class="img-fluid rounded">
									</div>
									<div class="col-md-9">
										<div class="card-item-title mt-3">
											<label
												class="font-weight-bold mb-2"><?php echo esc_html( $product->get_name() ); ?></label>
											<p class="mb-0 text-muted">Clicks: <b
													class="text-primary"><?php echo esc_html( $prod->total_clicks ); ?></b></p>
										</div>
									</div>
								</div>
							</li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
		<!-- CUSTOM CARD: Top Recommended End  -->

		<!-- RECOMMENDED ROW End  -->

	</div>
<?php } ?>

<?php if ( get_option( 'enable_upsell_page' ) === 'on' || isset( $_GET['show_upsell'] ) && (int) $_GET['show_upsell'] == 1 ) { ?>
	<div class="col-lg-8 col-md-7 tp-section-title">
		Upsells
	</div>

	<!-- UPSELL ROW Start  -->
	<div class="row row-sm upsell-row">

		<!-- CUSTOM CARD: Current Upsell Product Clicks  -->

		<?php
		$upsellData = getUpsellData();
		$image_url = $upsellData[0];
		$product = $upsellData[1];
		$upsell_clicks = $upsellData[2];
		?>

		<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
			<div class="card custom-card upsell-card">
				<div class="card-header border-bottom-0 pb-1">
					<label class="card-label-header font-weight-bold mb-2">Upsell 'Add To Cart's</label>
					<p class="text-muted mb-2">Total 'Add To Cart's of the current upsell product.</p>
				</div>
				<div class="card-body pt-0">
					<ul class="list-unstyled">
						<li class="mb-4">
							<div class="row">
								<div class="col-md-4">
									<img src="<?php echo esc_url( $image_url ); ?>" alt="image"
										class="img-fluid rounded custom-card-image-col">
								</div>
								<div class="col-md-6">
									<div class="card-item-title mt-3">
										<label class="font-weight-bold mb-2">Current Product: </label>
										<p class="mb-0 text-muted"><b
												class="text-primary"><?php echo esc_html( $product->get_name() ); ?></b></p>
									</div>
									<div class="card-item-title mt-3">
										<label class="font-weight-bold mb-2">Add to Carts:</label>
										<p class="mb-0 text-muted"><b
												class="text-primary"><?php echo esc_html( $upsell_clicks ); ?></b></p>
									</div>
									<div class="card-item-title mt-3">
										<label class="font-weight-bold mb-2">Since (date upsell product changed)</label>
										<p class="mb-0 text-muted"><b
												class="text-primary"><?php echo esc_html( get_option( 'upsell_date_last_change' ) ); ?></b>
										</p>
									</div>
								</div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>


		<!-- CUSTOM CARD: Upsell 'Add To Cart's Chart  START -->
		<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
			<div class="card custom-card overflow-hidden">
				<div class="card-body">
					<div>
						<p><label class="card-label-header font-weight-bold mb-2"> Upsell 'Add To Cart's over time</label>
						</p>
						<p class="text-muted card-sub-title">Current upsell product's 'Add To Cart's over last
							<?php echo esc_attr( get_option( 'tp_analytics_period' ) ) ?> days
						</p>
						<p class="text-muted card-sub-title">Note: Only shows data since the upsell product was last changed
						</p>
					</div>
					<div class="chartjs-wrapper" style="width: 100%; height: 100%; min-height:400px">
						<canvas id="upsellChart" class="chartjs-render-monitor"></canvas>
					</div>
				</div>
			</div>
		</div>
		<!-- '-->



		<?php
		$upsellClicks = getUpsellClicks();

		// function getUpsellClicks() uses json_encode for its return values 
		$labels_json = $upsellClicks[0];
		$data_json = $upsellClicks[1];
		$backgroundColor = "#6a77c4"; // A shade of purple
		$borderColor = "#6a77c4";
		?>

		<script>
			document.addEventListener("DOMContentLoaded", function () {
				var ctx = document.getElementById('upsellChart').getContext('2d');
				var labels = <?php echo wp_json_encode( $labels_json ); ?>;
				var data = <?php echo wp_json_encode( $data_json ); ?>;
				var upsellChart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							label: '# of Upsell Clicks',
							data: data,
							backgroundColor: '<?php echo esc_js( $backgroundColor ); ?>',
							borderColor: '<?php echo esc_js( $borderColor ); ?>',
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							y: {
								beginAtZero: true,
							},
							x: {
								beginAtZero: false  // Ensure this is set correctly for your data
							}
						}
					}
				});
			});
		</script>


		<!-- CUSTOM CARD: Upsell 'Add To Cart's Chart  START -->

		<!-- UPSELL ROW End  -->
	</div>
<?php } ?>

<?php
