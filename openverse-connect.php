<?php
/**
 * Plugin Name: Openverse Connect
 * Description: OpenVerse Connect seamlessly integrates Openverse with your WordPress site, enabling you to effortlessly search, browse, and embed high-quality, copyright-free media content directly into your posts and pages. 
 * Version: 1.0.0
 * Author: Rolly Bueno
 * Author URI: https://rollybueno.com
 * Text Domain: openverse-connect
 * Domain Path: /languages
 * License: GPL-2.0+
 *
 * @package Openverse_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'OPENVERSE_CONNECT_VERSION', '1.0.0' );
define( 'OPENVERSE_CONNECT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENVERSE_CONNECT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the main plugin class.
require_once OPENVERSE_CONNECT_PLUGIN_DIR . 'includes/class-openverse-connect.php';

/**
 * Main function to initialize the plugin.
 *
 * This function serves as the entry point for the plugin initialization.
 * It creates and returns a singleton instance of the main plugin class.
 *
 * @since 1.0.0
 * @return Openverse_Connect The main plugin class instance.
 */
function openverse_connect_init() {
	return Openverse_Connect::get_instance();
}

// Initialize the plugin.
$GLOBALS['openverse_connect'] = openverse_connect_init();

/**
 * Activation hook callback.
 *
 * Sets up the default options when the plugin is activated.
 * This function runs when the plugin is activated and creates necessary database options.
 *
 * @since 1.0.0
 * @return void
 */
function openverse_connect_activate() {
	// Add default options.
	add_option( 'openverse_connect_client_id', '' );
	add_option( 'openverse_connect_client_secret', '' );
}
register_activation_hook( __FILE__, 'openverse_connect_activate' );

/**
 * Clean up plugin data when the plugin is deleted.
 *
 * This function runs when the plugin is completely removed from WordPress.
 * It removes all plugin-related options and transients from the database.
 *
 * @since 1.0.0
 * @return void
 */
function openverse_connect_uninstall() {
	delete_option( 'openverse_connect_client_id' );
	delete_option( 'openverse_connect_client_secret' );
	delete_option( 'openverse_connect_access_token' );
}

// Register uninstall hook.
register_uninstall_hook( __FILE__, 'openverse_connect_uninstall' );

/**
 * Register block scripts and styles.
 *
 * This function handles the registration of the Openverse Search block.
 * It checks for the existence of build files and falls back to source files if needed.
 *
 * @since 1.0.0
 * @return void
 */
function openverse_connect_register_block() {
	// Check if build directory exists.
	$build_dir = OPENVERSE_CONNECT_PLUGIN_DIR . 'build/openverse-search';

	if ( file_exists( $build_dir ) && file_exists( $build_dir . '/block.json' ) ) {
		// Register block from build directory.
		register_block_type( $build_dir );
	} else {
		// Fallback to source directory for development.
		$source_dir = OPENVERSE_CONNECT_PLUGIN_DIR . 'blocks/openverse-search';
		if ( file_exists( $source_dir ) && file_exists( $source_dir . '/block.json' ) ) {
			register_block_type( $source_dir );
		} else {
			// Manual registration as last resort.
			wp_register_script(
				'openverse-connect-block',
				OPENVERSE_CONNECT_PLUGIN_URL . 'blocks/openverse-search/src/index.js',
				array(
					'wp-blocks',
					'wp-element',
					'wp-editor',
					'wp-components',
					'wp-i18n',
					'wp-api-fetch',
				),
				filemtime( OPENVERSE_CONNECT_PLUGIN_DIR . 'blocks/openverse-search/src/index.js' ),
				true
			);

			register_block_type(
				'openverse-connect/search',
				array(
					'editor_script' => 'openverse-connect-block',
				)
			);
		}
	}
}
add_action( 'init', 'openverse_connect_register_block' );
