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
 * Text Domain: 'embed-pdf-gravityforms'
 */

define( 'EMBED_PDF_GRAVITYFORMS_PATH', __FILE__ );
define( 'EMBED_PDF_GRAVITYFORMS_VERSION', '1.0.0' );

add_action( 'gform_loaded', 'embed_pdf_gravityforms_init' );
/**
 * Loads the plugin files and features.
 *
 * @return void
 */
function embed_pdf_gravityforms_init() {
	require_once dirname( EMBED_PDF_GRAVITYFORMS_PATH ) . '/includes/class-gf-field-pdf-viewer.php';
}