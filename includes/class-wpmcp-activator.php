<?php
/**
 * Plugin activation handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Activator {
    
    /**
     * Activate plugin
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('wpmcp_activated', true);
    }
    
    /**
     * Create database tables
     */
    private static function create_database_tables() {
        require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-database.php';
        WPMCP_Database::create_tables();
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'wpmcp_api_enabled' => true,
            'wpmcp_rate_limit' => 100,
            'wpmcp_allowed_post_types' => array('post', 'page'),
            'wpmcp_security_logging' => true,
            'wpmcp_debug_mode' => false,
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
}