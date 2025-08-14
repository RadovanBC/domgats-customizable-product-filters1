<?php
/**
 * Plugin Name:       DomGats Customizable Product Filters
 * Plugin URI:        https://example.com/
 * Description:       A custom product filter for WooCommerce and more to come.
 * Version:           1.3.1
 * Author:            Radovan Gataric DomGat
 * Author URI:        
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-product-filters
 * Domain Path:       /languages
 *
 * @package           DomGats_Customizable_Product_Filters
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Constants
 */
define( 'DGCPF_VERSION', '1.3.1' );
define( 'DGCPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DGCPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// --- Include the Composer autoloader ---
if ( file_exists( DGCPF_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once DGCPF_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	// Optional: Add a notice for the admin if composer is not installed.
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p><strong>DomGats Product Filters:</strong> Composer autoloader not found. Please run `composer install` in the plugin directory.</p></div>';
	});
	return; // Stop the plugin from loading if the autoloader is missing.
}


/**
 * The function that runs during plugin activation.
 */
function dgcpf_activate_plugin() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dgcpf_activate_plugin' );


/**
 * Enqueues scripts and styles for the front end.
 */
function dgcpf_enqueue_assets() {
	$is_mobile = wp_is_mobile();
	
	// DEPENDENCIES
	$style_dependencies = [ 'hello-elementor-theme-style' ];
	// Add 'imagesloaded' as it was a dependency for the old script we are merging
	$script_dependencies = [ 'jquery', 'elementor-frontend', 'imagesloaded' ];

	// Conditionally load Flickity for carousels on non-mobile, non-shop pages
	if ( ! $is_mobile && ! is_shop() ) {
		wp_register_style( 'flickity-css', 'https://unpkg.com/flickity@2/dist/flickity.min.css', [], '2.3.0' );
		wp_register_script( 'flickity-js', 'https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js', [ 'jquery' ], '2.3.0', true );

		$style_dependencies[]  = 'flickity-css';
		$script_dependencies[] = 'flickity-js';
	}

	// STYLES
	wp_enqueue_style(
		'dgcpf-main-style',
		DGCPF_PLUGIN_URL . 'assets/css/main.css',
		$style_dependencies,
		DGCPF_VERSION
	);

	// SCRIPTS
	wp_enqueue_script(
		'dgcpf-main-script',
		DGCPF_PLUGIN_URL . 'assets/js/main.js',
		$script_dependencies,
		DGCPF_VERSION,
		true
	);

	// LOCALIZE SCRIPT (for AJAX)
	wp_localize_script(
		'dgcpf-main-script',
		'ahh_maa_filter_params',
		[
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'product_filter_nonce' ),
			'page_id'  => get_the_ID(),
		]
	);
}
// Use priority 30 to ensure it runs after other common scripts
add_action( 'wp_enqueue_scripts', 'dgcpf_enqueue_assets', 30 );
/**
 * Initializes the plugin's classes.
 */
function dgcpf_initialize_plugin() {
	// Use the fully qualified class names with their new namespace.
	new \DomGats\ProductFilter\DGCPF_Shortcodes();
	new \DomGats\ProductFilter\DGCPF_Ajax();

	if ( is_admin() ) {
		new \DomGats\ProductFilter\DGCPF_Admin();
	}
}
add_action( 'plugins_loaded', 'dgcpf_initialize_plugin' );
