<?php
/**
 * Database operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // API Keys table
        $api_keys_table = $wpdb->prefix . 'wpmcp_api_keys';
        $api_keys_sql = "CREATE TABLE $api_keys_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            api_key varchar(64) NOT NULL,
            name varchar(255) NOT NULL,
            permissions text,
            rate_limit int(11) DEFAULT 100,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used_at datetime,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Request logs table
        $logs_table = $wpdb->prefix . 'wpmcp_request_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            api_key_id bigint(20),
            endpoint varchar(255),
            method varchar(10),
            ip_address varchar(45),
            user_agent text,
            request_data text,
            response_code int(11),
            response_time float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY api_key_id (api_key_id),
            KEY created_at (created_at),
            KEY endpoint (endpoint)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($api_keys_sql);
        dbDelta($logs_sql);
        
        // Update database version
        update_option('wpmcp_db_version', WPMCP_VERSION);
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'wpmcp_api_keys',
            $wpdb->prefix . 'wpmcp_request_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('wpmcp_db_version');
    }
}