<?php
/**
 * Embed PDF for Gravity Forms Uninstaller
 *
 * @package embed-pdf-gravityforms
 */

// If uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$slug = 'embedpdfviewer';

delete_option( "gravityformsaddon_{$slug}_version" );
delete_option( "gravityformsaddon_{$slug}_settings" );
global $wpdb;
$wpdb->delete(
	"{$wpdb->prefix}gf_addon_feed",
	array( 'addon_slug' => $slug ),
	array( '%s' )
);
