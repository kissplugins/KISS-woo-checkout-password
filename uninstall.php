<?php
/**
 * Uninstall script for WooCommerce Checkout Password Protection.
 *
 * Fired when the plugin is deleted (not just deactivated).
 * Removes all plugin data from the database.
 *
 * @package WC_Checkout_Password_Protection
 * @since 1.0.0
 */

// Exit if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'wccpp_protected_urls' );
delete_option( 'wccpp_password_hash' );

// Clear any cached data.
wp_cache_flush();
