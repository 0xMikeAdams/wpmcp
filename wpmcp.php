<?php
/**
 * Plugin Name: WPMCP - WordPress Model Context Protocol
 * Plugin URI: https://github.com/0xMikeAdams/wpmcp
 * Description: Exposes WordPress content through the Model Context Protocol (MCP) for AI assistants and compatible tools.
 * Version: 1.0.0
 * Author: 0xMikeAdams
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.html
 * Text Domain: wpmcp
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPMCP_VERSION', '1.0.0');
define('WPMCP_PLUGIN_FILE', __FILE__);
define('WPMCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPMCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPMCP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-autoloader.php';

/**
 * Main plugin initialization
 */
function wpmcp_init() {
    WPMCP_Plugin::get_instance();
}

// Initialize plugin
add_action('plugins_loaded', 'wpmcp_init');

/**
 * Plugin activation hook
 */
function wpmcp_activate() {
    require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-activator.php';
    WPMCP_Activator::activate();
}
register_activation_hook(__FILE__, 'wpmcp_activate');

/**
 * Plugin deactivation hook
 */
function wpmcp_deactivate() {
    require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-deactivator.php';
    WPMCP_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'wpmcp_deactivate');