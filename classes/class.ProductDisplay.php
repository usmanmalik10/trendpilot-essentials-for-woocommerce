<?php

namespace TrendpilotEssentials;

class ProductDisplay {

	public function registerHooks() {
		add_action( 'init', array( $this, 'registerCustomPostType' ) );
		add_filter( 'manage_edit-product_displays_columns', array( $this, 'setCustomProductDisplayColumns' ) );
		add_action( 'manage_product_displays_posts_custom_column', array( $this, 'customProductDisplayColumn' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
		add_action( 'save_post', array( $this, 'saveMetaBoxes' ) );
		add_action( 'do_meta_boxes', array( $this, 'removeDefaultEditor' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
		add_shortcode( 'tp_product_display', array( $this, 'renderProductDisplayShortcode' ) );

		add_action( 'admin_head', array( $this, 'hidePublishBoxElements' ) );
	}

	public function hidePublishBoxElements() {
		?>
		<style type="text/css">
			#visibility,
			#post-visibility-select,
			#preview-action {
				display: none;
			}

			.misc-pub-post-status {
				display: none;
			}
		</style>
		<?php
	}

	public function registerCustomPostType() {
		$args = array(
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => false, // Do not show in admin menu directly
			'supports' => array( 'title' ), // Removed 'editor' to remove post_content
			'label' => 'Product Displays',
			'has_archive' => true,
		);
		register_post_type( 'product_displays', $args );
	}

	public function setCustomProductDisplayColumns( $columns ) {
		$new_columns = array(
			'cb' => $columns['cb'], // Checkbox for bulk actions
			'title' => $columns['title'], // Title of the post
			'product_id' => 'Product', // Custom column for Product
			'display_type' => 'Display Type', // Custom column for Display Type
			'display_id' => 'Display ID', // Custom column for Display ID
			'shortcode' => 'Shortcode', // Custom column for Shortcode
			'date' => $columns['date'] // Date column
		);
		return $new_columns;
	}

	public function customProductDisplayColumn( $column, $post_id ) {
		switch ( $column ) {
			case 'display_id':
				echo esc_html( $post_id );
				break;
			case 'shortcode':
				$shortcode = '[tp_product_display id="' . $post_id . '"]';
				echo '<input type="text" value="' . esc_attr( $shortcode ) . '" readonly="readonly" onclick="copyShortcode(this)" />';
				echo '<span class="shortcode-copied-message" style="display:none; color: green; margin-left: 10px;">Shortcode copied!</span>';
				break;
			case 'product_id':
				$product_id = get_post_meta( $post_id, 'product_id', true );
				$product = wc_get_product( $product_id );
				echo esc_html( $product ? $product->get_title() : 'No Product' );
				break;
			case 'display_type':
				$display_type = get_post_meta( $post_id, 'display_type', true );
				$display_type_label = $this->getDisplayTypeLabel( $display_type );
				echo esc_html( $display_type_label );
				break;
		}
	}

	public function addMetaBoxes() {

		// Adding Product meta box first
		add_meta_box(
			'product_display_product_id_meta_box',  // ID of the meta box
			'Product',                             // Title of the meta box
			array( $this, 'displayProductIDMetaBox' ), // Callback function to display the meta box content
			'product_displays',                     // Post type
			'normal',                               // Context (normal, side, etc.)
			'high'                                  // Priority
		);
		// Adding Display Type meta box second
		add_meta_box(
			'product_display_display_type_meta_box', // ID of the meta box
			'Display Type',                          // Title of the meta box
			array( $this, 'displayDisplayTypeMetaBox' ), // Callback function to display the meta box content
			'product_displays',                      // Post type
			'normal',                                // Context (normal, side, etc.)
			'high'                                   // Priority
		);
		// Adding HTML meta box last
		add_meta_box(
			'product_display_html_meta_box',        // ID of the meta box
			'HTML',                                 // Title of the meta box
			array( $this, 'displayHTMLMetaBox' ),     // Callback function to display the meta box content
			'product_displays',                     // Post type
			'normal',                               // Context (normal, side, etc.)
			'high'                                  // Priority
		);
		// Adding Shortcode meta box
		add_meta_box(
			'product_display_shortcode_meta_box',   // ID of the meta box
			'Shortcode',                            // Title of the meta box
			array( $this, 'displayShortcodeMetaBox' ), // Callback function to display the meta box content
			'product_displays',                     // Post type
			'side',                                 // Context (side, normal, etc.)
			'high'                                  // Priority
		);

		// Adding Design Attributes meta box
		error_log( 'Adding Design Attributes Meta Box' ); // Debug statement
		add_meta_box(
			'product_display_design_attributes_meta_box',
			'Design Attributes',
			array( $this, 'displayDesignAttributesMetaBox' ),
			'product_displays',
			'normal',
			'high'
		);

		// Adding Shortcode Preview meta box
		add_meta_box(
			'product_display_shortcode_preview_meta_box', // ID of the meta box
			'Product Display Preview', // Title of the meta box
			array( $this, 'displayShortcodePreviewMetaBox' ), // Callback function to display the meta box content
			'product_displays', // Post type
			'normal', // Context (normal, side, etc.)
			'high' // Priority
		);

	}

	// public function saveMetaBoxes( $post_id ) {
	// 	if ( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['meta_box_nonce'], 'product_display_meta_box_nonce' ) ) {
	// 		return;
	// 	}

	// 	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	// 		return;
	// 	}

	// 	if ( ! current_user_can( 'edit_post', $post_id ) ) {
	// 		return;
	// 	}

	// 	if ( isset( $_POST['product_id'] ) ) {
	// 		update_post_meta( $post_id, 'product_id', sanitize_text_field( $_POST['product_id'] ) );
	// 	}

	// 	if ( isset( $_POST['display_type'] ) ) {
	// 		update_post_meta( $post_id, 'display_type', sanitize_text_field( $_POST['display_type'] ) );
	// 	}

	// 	if ( isset( $_POST['html'] ) ) {
	// 		update_post_meta( $post_id, 'html', wp_kses_post( $_POST['html'] ) );
	// 	}
	// }

	//temporary method to allow style tags. replace with above method when done.
	public function saveMetaBoxes( $post_id ) {
		// Verify the nonce for the product ID and display type meta boxes.
		if ( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['meta_box_nonce'], 'product_display_meta_box_nonce' ) ) {
			return;
		}

		// Verify the nonce for the design attributes meta box.
		if ( ! isset( $_POST['design_attributes_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['design_attributes_meta_box_nonce'], 'product_display_design_attributes_meta_box_nonce' ) ) {
			return;
		}

		// Check for autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save the product ID.
		if ( isset( $_POST['product_id'] ) ) {
			update_post_meta( $post_id, 'product_id', sanitize_text_field( $_POST['product_id'] ) );
		}

		// Sanitize and save the display type.
		if ( isset( $_POST['display_type'] ) ) {
			update_post_meta( $post_id, 'display_type', sanitize_text_field( $_POST['display_type'] ) );
		}

		// Sanitize and save the HTML content.
		if ( isset( $_POST['html'] ) ) {
			$allowed_tags = array(
				'div' => array( 'class' => array(), 'style' => array() ),
				'img' => array( 'class' => array(), 'src' => array(), 'alt' => array(), 'style' => array() ),
				'h1' => array( 'class' => array(), 'style' => array() ),
				'p' => array( 'class' => array(), 'style' => array() ),
				'a' => array( 'class' => array(), 'href' => array(), 'style' => array() ),
				'button' => array( 'class' => array(), 'style' => array() ),
				'style' => array(),
			);
			update_post_meta( $post_id, 'html', wp_kses( $_POST['html'], $allowed_tags ) );
		}

		// Sanitize and save other meta fields.
		if ( isset( $_POST['design_heading_1'] ) ) {
			update_post_meta( $post_id, 'design_heading_1', sanitize_text_field( $_POST['design_heading_1'] ) );
		}

		if ( isset( $_POST['background_image_1'] ) ) {
			update_post_meta( $post_id, 'background_image_1', intval( $_POST['background_image_1'] ) );
		}

		if ( isset( $_POST['theme_color'] ) ) {
			update_post_meta( $post_id, 'theme_color', sanitize_hex_color( $_POST['theme_color'] ) );
		}

		if ( isset( $_POST['cta_color'] ) ) {
			update_post_meta( $post_id, 'cta_color', sanitize_hex_color( $_POST['cta_color'] ) );
		}

		if ( isset( $_POST['text_color'] ) ) {
			update_post_meta( $post_id, 'text_color', sanitize_hex_color( $_POST['text_color'] ) );
		}

		if ( isset( $_POST['cta_text_color'] ) ) {
			update_post_meta( $post_id, 'cta_text_color', sanitize_hex_color( $_POST['cta_text_color'] ) );
		}

		if ( isset( $_POST['cta_text'] ) ) {
			update_post_meta( $post_id, 'cta_text', sanitize_text_field( $_POST['cta_text'] ) );
		}

		if ( isset( $_POST['show_badge'] ) ) {
			update_post_meta( $post_id, 'show_badge', sanitize_text_field( $_POST['show_badge'] ) );
		} else {
			update_post_meta( $post_id, 'show_badge', '0' );
		}
	}


	public function displayDesignAttributesMetaBox( $post ) {
		// error_log( 'displayDesignAttributesMetaBox triggered' );

		// $display_type = get_post_meta( $post->ID, 'display_type', true );
		// error_log( 'display_type: ' . $display_type );

		$design_heading = get_post_meta( $post->ID, 'design_heading_1', true );
		$background_image = get_post_meta( $post->ID, 'background_image_1', true );
		$theme_color = get_post_meta( $post->ID, 'theme_color', true );
		$cta_color = get_post_meta( $post->ID, 'cta_color', true );
		$text_color = get_post_meta( $post->ID, 'text_color', true );
		$cta_text_color = get_post_meta( $post->ID, 'cta_text_color', true );
		$cta_text = get_post_meta( $post->ID, 'cta_text', true );
		$show_badge = get_post_meta( $post->ID, 'show_badge', true );

		wp_nonce_field( 'product_display_design_attributes_meta_box_nonce', 'design_attributes_meta_box_nonce' );

		?>
		<p>
			<label for="design_heading_1">Design Heading:</label>
			<input type="text" name="design_heading_1" id="design_heading_1"
				value="<?php echo esc_attr( $design_heading ); ?>" />
		</p>
		<p>
			<label for="cta_text">CTA Text:</label>
			<input type="text" name="cta_text" id="cta_text" value="<?php echo esc_attr( $cta_text ); ?>" />
		</p>
		<p>
			<label for="background_image_1">Background Image:</label>
			<input type="hidden" name="background_image_1" id="background_image_1"
				value="<?php echo esc_attr( $background_image ); ?>" />
			<button type="button" class="button" id="upload_background_image_button">Upload Image</button>
			<span id="background_image_url" style="font-weight: bold; margin-left: 10px;">
				<?php if ( $background_image ) : ?>
					<?php echo esc_url( wp_get_attachment_url( $background_image ) ); ?>
				<?php endif; ?>
			</span>
		</p>
		<p>
			<label for="theme_color">Theme Color:</label>
			<input type="text" name="theme_color" id="theme_color" value="<?php echo esc_attr( $theme_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="cta_color">CTA Button Color:</label>
			<input type="text" name="cta_color" id="cta_color" value="<?php echo esc_attr( $cta_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="text_color">Text Color:</label>
			<input type="text" name="text_color" id="text_color" value="<?php echo esc_attr( $text_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="cta_text_color">CTA Text Color:</label>
			<input type="text" name="cta_text_color" id="cta_text_color" value="<?php echo esc_attr( $cta_text_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="show_badge">Show Product Badge:</label>
			<input type="checkbox" name="show_badge" id="show_badge" value="1" <?php checked( $show_badge, '1' ); ?> />
		</p>
		<script>
			jQuery(document).ready(function ($) {
				var designAttributesBox = $('#product_display_design_attributes_meta_box');
				designAttributesBox.show(); // Always show the Design Attributes box

				var frame;
				$('#upload_background_image_button').on('click', function (event) {
					event.preventDefault();
					if (frame) {
						frame.open();
						return;
					}
					frame = wp.media({
						title: 'Select or Upload Background Image',
						button: {
							text: 'Use this image'
						},
						multiple: false
					});
					frame.on('select', function () {
						var attachment = frame.state().get('selection').first().toJSON();
						$('#background_image_1').val(attachment.id);
						$('#background_image_url').html(attachment.url);
					});
					frame.open();
				});

				// Initialize Spectrum color pickers with default values
				$('.color-picker').spectrum({
					showInput: true,
					preferredFormat: "hex",
					allowEmpty: true,
					change: function (color) {
						$(this).val(color ? color.toHexString() : '');
					}
				});
			});
		</script>

		<?php
	}

	public function displayShortcodeMetaBox( $post ) {
		$shortcode = '[tp_product_display id="' . (int) $post->ID . '"]';
		?>
		<p>
			<input type="text" value="<?php echo esc_attr( $shortcode ); ?>" readonly="readonly"
				onclick="copyShortcode(this)" />
			<span class="shortcode-copied-message" style="display:none; color: green; margin-left: 10px;">Shortcode
				copied!</span>
		</p>
		<script>
			function copyShortcode(input) {
				input.select();
				document.execCommand('copy');
				var message = input.nextElementSibling;
				message.style.display = 'inline';
				setTimeout(function () {
					message.style.display = 'none';
				}, 2000);
			}
		</script>
		<?php
	}

	public function displayShortcodePreviewMetaBox( $post ) {
		$shortcode = '[tp_product_display id="' . (int) $post->ID . '"]';
		?>
		<div>
			<iframe id="shortcode_preview_iframe" style="width:100%; border:none;" onload="resizeIframe(this);"></iframe>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var iframe = document.getElementById('shortcode_preview_iframe');
				var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
				iframeDoc.open();
				iframeDoc.write('<html><head><style>body { font-family: "Arial", "Open Sans", sans-serif; }</style></head><body>' + <?php echo json_encode( do_shortcode( $shortcode ) ); ?> + '</body></html>');
				iframeDoc.close();
			});

			function resizeIframe(iframe) {
				iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
			}
		</script>
		<?php
	}


	public function displayProductIDMetaBox( $post ) {
		$product_id = get_post_meta( $post->ID, 'product_id', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="product_id">Select product:</label>
			<input type="hidden" name="product_id" id="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
			<input type="text" id="product_search" value="<?php echo esc_attr( $this->getProductTitle( $product_id ) ); ?>" />
		<p>Choose product to show in this display</p>
		</p>
		<script>
			jQuery(document).ready(function ($) {
				var products = <?php echo wp_json_encode( $this->getProductsList() ); ?>;
				$("#product_search").autocomplete({
					source: products,
					select: function (event, ui) {
						$("#product_id").val(ui.item.id);
					}
				});
			});
		</script>
		<?php
	}

	public function displayDisplayTypeMetaBox( $post ) {
		$display_type = get_post_meta( $post->ID, 'display_type', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="display_type">Display Type:</label>
			<select name="display_type" id="display_type" onchange="toggleHtmlMetaBox()">
				<option value="full_width_design_1" <?php selected( $display_type, 'full_width_design_1' ); ?>>Full-width Design
					1</option>
				<option value="full_width_design_2" <?php selected( $display_type, 'full_width_design_2' ); ?>>Full-width Design
					2</option>
				<option value="full_width_design_3" <?php selected( $display_type, 'full_width_design_3' ); ?>>Full-width Design
					3</option>
				<option value="cta_banner_left" <?php selected( $display_type, 'cta_banner_left' ); ?>>CTA Banner (left)
				</option>
				<option value="cta_banner_right" <?php selected( $display_type, 'cta_banner_right' ); ?>>CTA Banner (right)
				</option>
				<option value="custom" <?php selected( $display_type, 'custom' ); ?>>Custom</option>
			</select>
		</p>
		<script>
			function toggleHtmlMetaBox() {
				var displayType = document.getElementById('display_type').value;
				var htmlMetaBox = document.getElementById('product_display_html_meta_box');
				if (displayType === 'custom') {
					htmlMetaBox.style.display = 'block';
				} else {
					htmlMetaBox.style.display = 'none';
				}
			}
			document.addEventListener('DOMContentLoaded', function () {
				toggleHtmlMetaBox(); // Ensure correct state on page load
			});
		</script>
		<?php
	}

	public function getProductTitle( $product_id ) {
		$product = wc_get_product( $product_id );
		return $product ? $product->get_title() : '';
	}

	public function getProductsList() {
		$products = wc_get_products( array( 'limit' => -1 ) );
		$product_list = array();

		foreach ( $products as $product ) {
			$product_list[] = array(
				'label' => sanitize_text_field( $product->get_title() ), // Sanitizing the product title
				'value' => sanitize_text_field( $product->get_title() ), // Sanitizing the product title
				'id' => intval( $product->get_id() ) // Ensuring the ID is an integer
			);
		}

		return $product_list;
	}


	public function getDisplayTypeLabel( $display_type ) {
		$labels = array(
			'full_width_design_1' => 'Full-width Design 1',
			'full_width_design_2' => 'Full-width Design 2',
			'cta_banner_left' => 'CTA Banner (left)',
			'cta_banner_right' => 'CTA Banner (right)',
			'custom' => 'Custom'
		);
		return isset( $labels[ $display_type ] ) ? $labels[ $display_type ] : $display_type;
	}

	public function displayHTMLMetaBox( $post ) {
		$html = get_post_meta( $post->ID, 'html', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="html">HTML:</label>
			<textarea name="html" id="html" rows="10" style="width:100%;"><?php echo esc_textarea( $html ); ?></textarea>
		</p>
		<p>Use the following class names to bring in specific elements:</p>
		<ul>
			<li><strong>.trendpilot-product-image:</strong> Displays the product image. Example:
				<code>&lt;img src="image_url" class="trendpilot-product-image" /&gt;</code>
			</li>
			<li><strong>.trendpilot-product-title:</strong> Displays the product title. Example:
				<code>&lt;div class="trendpilot-product-title"&gt;Product Title&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-product-description:</strong> Displays the product description. Example:
				<code>&lt;div class="trendpilot-product-description"&gt;Product Description&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-cta-link:</strong> Displays the Call to Action link. Example:
				<code>&lt;a href="link_url" class="trendpilot-cta-link"&gt;Call to Action&lt;/a&gt;</code>
			</li>
			<li><strong>.trendpilot-design-heading:</strong> Displays the design heading. Example:
				<code>&lt;div class="trendpilot-design-heading"&gt;Design Heading&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-design-background-img:</strong> Displays the background image. Example:
				<code>&lt;img src="background_image_url" class="trendpilot-design-background-img" /&gt;</code>
			</li>
		</ul>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var editor = CodeMirror.fromTextArea(document.getElementById("html"), {
					mode: "htmlmixed",
					theme: "default",
					lineNumbers: true,
					lineWrapping: true,
					matchBrackets: true,
					autoCloseTags: true,
					extraKeys: {
						"Ctrl-Space": "autocomplete"
					}
				});
				editor.on("change", function (cm) {
					cm.save();
				});
			});
		</script>
		<?php
	}

	public function removeDefaultEditor() {
		remove_post_type_support( 'product_displays', 'editor' );
	}

	public function enqueueScripts() {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );

		wp_enqueue_script( 'codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js', array(), '5.65.5', true );
		wp_enqueue_style( 'codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css', array(), '5.65.5' );
		wp_enqueue_script( 'codemirror-mode-htmlmixed', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/htmlmixed/htmlmixed.min.js', array( 'codemirror' ), '5.65.5', true );
		wp_enqueue_script( 'codemirror-mode-javascript', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/javascript/javascript.min.js', array( 'codemirror' ), '5.65.5', true );
		wp_enqueue_script( 'codemirror-mode-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/css/css.min.js', array( 'codemirror' ), '5.65.5', true );
		wp_enqueue_script( 'codemirror-mode-xml', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/xml/xml.min.js', array( 'codemirror' ), '5.65.5', true );

		wp_add_inline_script( 'jquery-ui-autocomplete', $this->getCopyShortcodeScript() );

		// Enqueue the Spectrum JS
		wp_enqueue_script(
			'spectrum-js',
			'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js',
			array( 'jquery' ),
			'1.8.0',
			true
		);

		// Enqueue the Spectrum CSS
		wp_enqueue_style(
			'spectrum-css',
			'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css',
			array(),
			'1.8.0'
		);
	}

	public function getCopyShortcodeScript() {
		return <<<EOT
            function copyShortcode(input) {
                input.select();
                document.execCommand('copy');
                var message = input.nextElementSibling;
                message.style.display = 'inline';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 2000);
            }
        EOT;
	}



	public function renderProductDisplayShortcode( $atts ) {
		// Sanitize and validate the shortcode attributes
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'tp_product_display' );
		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		// Retrieve and validate product-related data
		$product_id = intval( get_post_meta( $post_id, 'product_id', true ) );
		if ( ! $product_id ) {
			return '';
		}

		$display_type = sanitize_text_field( get_post_meta( $post_id, 'display_type', true ) );

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		// Retrieve and sanitize product-related details
		$product_image_url = esc_url( wp_get_attachment_url( $product->get_image_id() ) );
		$product_title = esc_html( html_entity_decode( $product->get_title() ) );
		$product_description = wp_kses_post( $product->get_description() );
		$product_url = esc_url( get_permalink( $product_id ) );
		$design_heading = esc_html( get_post_meta( $post_id, 'design_heading_1', true ) ?: 'Trending...' );
		$background_image_url = esc_url( wp_get_attachment_url( get_post_meta( $post_id, 'background_image_1', true ) ) );
		$theme_color = sanitize_hex_color( get_post_meta( $post_id, 'theme_color', true ) ?: '' );
		$theme_color_2 = sanitize_hex_color( get_post_meta( $post_id, 'theme_color_2', true ) ?: '' );
		$cta_color = sanitize_hex_color( get_post_meta( $post_id, 'cta_color', true ) ?: '' );
		$text_color = sanitize_hex_color( get_post_meta( $post_id, 'text_color', true ) ?: '' );
		$cta_text_color = sanitize_hex_color( get_post_meta( $post_id, 'cta_text_color', true ) ?: '' );
		$cta_text = esc_html( get_post_meta( $post_id, 'cta_text', true ) ?: 'Shop Now' );

		// Select and sanitize HTML template based on display type
		if ( $display_type === 'custom' ) {
			$html_template = wp_kses_post( get_post_meta( $post_id, 'html', true ) );
		} elseif ( $display_type === 'full_width_design_1' ) {
			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-1.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-1">' . $html_template . '</div>';

			// Load and sanitize the CSS file content
			$css_file_path = plugin_dir_path( __FILE__ ) . '../public/css/product-displays/full-width-design-1.css';
			$css = wp_strip_all_tags( file_get_contents( $css_file_path ) );
		} elseif ( $display_type === 'full_width_design_2' ) {
			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-2.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-2">' . $html_template . '</div>';

			$css_file_path = plugin_dir_path( __FILE__ ) . '../public/css/product-displays/full-width-design-2.css';
			$css = wp_strip_all_tags( file_get_contents( $css_file_path ) );
		} elseif ( $display_type === 'full_width_design_3' ) {
			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-3.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-3">' . $html_template . '</div>';

			$css_file_path = plugin_dir_path( __FILE__ ) . '../public/css/product-displays/full-width-design-3.css';
			$css = wp_strip_all_tags( file_get_contents( $css_file_path ) );
		} else {
			return 'no display_type';
		}

		// Safely encode the product data as a JSON object
		$product_data = json_encode( array(
			'image_url' => $product_image_url,
			'title' => $product_title,
			'description' => $product_description,
			'design_heading' => $design_heading,
			'background_image' => $background_image_url,
			'theme_color' => $theme_color,
			'theme_color_2' => $theme_color_2,
			'cta_color' => $cta_color,
			'text_color' => $text_color,
			'cta_text_color' => $cta_text_color,
			'cta_text' => $cta_text
		), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		$html = $html_template;

		if ( isset( $css ) ) {
			$html .= "<style>" . esc_html( $css ) . "</style>";
		}

		if ( $display_type === 'custom' ) {
			$html .= "<script>
			document.addEventListener('DOMContentLoaded', function() {
				var productData = $product_data;
				var imageElement = document.querySelector('.trendpilot-product-image');
				var titleElement = document.querySelector('.trendpilot-product-title');
				var descriptionElement = document.querySelector('.trendpilot-product-description');
				var ctaLinkElements = document.querySelectorAll('.trendpilot-cta-link');
				var designHeadingElement = document.querySelector('.trendpilot-design-heading');
				var backgroundImageElement = document.querySelector('.trendpilot-design-background-img');
	
				if (imageElement) {
					imageElement.src = productData.image_url;
				}
				if (titleElement) {
					titleElement.textContent = productData.title;
				}
				if (descriptionElement) {
					descriptionElement.innerHTML = productData.description;
				}
				if (designHeadingElement) {
					designHeadingElement.textContent = productData.design_heading;
				}
				if (backgroundImageElement && productData.background_image) {
					backgroundImageElement.src = productData.background_image;
				}
				ctaLinkElements.forEach(function(element) {
					element.href = '" . esc_js( $product_url ) . "';
					var button = element.querySelector('.trendpilot-cta-button');
					if (button) {
						button.textContent = productData.cta_text;
					}
				});
			});
		</script>";
		}

		return $html;
	}

}