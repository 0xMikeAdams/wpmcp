<?php
/**
 * Plugin deactivation handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Deactivator {
    
    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('wpmcp_cleanup_logs');
        
        // Remove activation flag
        delete_option('wpmcp_activated');
    }
}