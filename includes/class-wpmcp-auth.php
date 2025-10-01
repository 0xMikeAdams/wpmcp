<?php
/**
 * Authentication and security
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Auth {
    
    /**
     * Validate API key
     */
    public function validate_api_key($api_key) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpmcp_api_keys';
        
        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE api_key = %s AND is_active = 1",
            $api_key
        ), ARRAY_A);
        
        if (!$key_data) {
            return new WP_Error('invalid_api_key', 'Invalid or inactive API key');
        }
        
        // Check rate limit
        if (!$this->check_rate_limit($key_data['id'], $key_data['rate_limit'])) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded');
        }
        
        // Update last used timestamp
        $wpdb->update(
            $table,
            array('last_used_at' => current_time('mysql')),
            array('id' => $key_data['id'])
        );
        
        return $key_data;
    }
    
    /**
     * Check rate limit
     */
    private function check_rate_limit($api_key_id, $rate_limit) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'wpmcp_request_logs';
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $request_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $logs_table 
             WHERE api_key_id = %d AND created_at > %s",
            $api_key_id,
            $one_hour_ago
        ));
        
        return $request_count < $rate_limit;
    }
    
    /**
     * Generate API key
     */
    public function generate_api_key($name, $permissions = array(), $rate_limit = 100) {
        global $wpdb;
        
        $api_key = $this->create_secure_key();
        $table = $wpdb->prefix . 'wpmcp_api_keys';
        
        $result = $wpdb->insert(
            $table,
            array(
                'api_key' => $api_key,
                'name' => sanitize_text_field($name),
                'permissions' => wp_json_encode($permissions),
                'rate_limit' => intval($rate_limit),
                'created_at' => current_time('mysql'),
                'is_active' => 1
            )
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create API key');
        }
        
        return array(
            'id' => $wpdb->insert_id,
            'api_key' => $api_key,
            'name' => $name
        );
    }
    
    /**
     * Create secure API key
     */
    private function create_secure_key() {
        return 'wpmcp_' . bin2hex(random_bytes(28));
    }
    
    /**
     * Revoke API key
     */
    public function revoke_api_key($api_key_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpmcp_api_keys';
        
        return $wpdb->update(
            $table,
            array('is_active' => 0),
            array('id' => intval($api_key_id))
        );
    }
    
    /**
     * Get API keys
     */
    public function get_api_keys() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpmcp_api_keys';
        
        return $wpdb->get_results(
            "SELECT id, name, rate_limit, created_at, last_used_at, is_active 
             FROM $table ORDER BY created_at DESC",
            ARRAY_A
        );
    }
    
    /**
     * Get API key usage stats
     */
    public function get_usage_stats($api_key_id, $days = 7) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'wpmcp_request_logs';
        $start_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as requests
             FROM $logs_table 
             WHERE api_key_id = %d AND created_at > %s
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            $api_key_id,
            $start_date
        ), ARRAY_A);
    }
}