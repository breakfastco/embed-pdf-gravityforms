<?php
/**
 * Embed PDF for Gravity Forms
 *
 * @package embed-pdf-gravityforms
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: Embed PDF for Gravity Forms
 * Plugin URI: https://breakfastco.xyz
 * Description: Add-on for Gravity Forms. Provides a PDF Viewer field.
 * Author: Breakfast Co.
 * Author URI: https://breakfastco.xyz
 * Version: 1.0.0
 * Text Domain: 'embed-pdf-gravityforms'
 */

define( 'EMBED_PDF_GRAVITYFORMS_PATH', __FILE__ );
define( 'EMBED_PDF_GRAVITYFORMS_VERSION', '1.0.0' );

add_action( 'gform_loaded', 'embed_pdf_gravityforms_init', 5 );
/**
 * Loads the plugin files and features.
 *
 * @return void
 */
function embed_pdf_gravityforms_init() {
	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	// Add-on init.
	require_once dirname( EMBED_PDF_GRAVITYFORMS_PATH ) . '/includes/class-gf-addon-pdf-viewer.php';
	GFAddOn::register( 'GF_Addon_PDF_Viewer' );
}
