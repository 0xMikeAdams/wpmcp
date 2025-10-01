<?php
/**
 * Admin interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Admin {
    
    /**
     * Initialize admin
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wpmcp_generate_api_key', array($this, 'ajax_generate_api_key'));
        add_action('wp_ajax_wpmcp_revoke_api_key', array($this, 'ajax_revoke_api_key'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('WPMCP Settings', 'wpmcp'),
            __('WPMCP', 'wpmcp'),
            'manage_options',
            'wpmcp-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wpmcp_settings', 'wpmcp_api_enabled');
        register_setting('wpmcp_settings', 'wpmcp_rate_limit');
        register_setting('wpmcp_settings', 'wpmcp_allowed_post_types');
        register_setting('wpmcp_settings', 'wpmcp_security_logging');
        register_setting('wpmcp_settings', 'wpmcp_debug_mode');
        
        // API Configuration section
        add_settings_section(
            'wpmcp_api_section',
            __('API Configuration', 'wpmcp'),
            array($this, 'api_section_callback'),
            'wpmcp-settings'
        );
        
        add_settings_field(
            'wpmcp_api_enabled',
            __('Enable API', 'wpmcp'),
            array($this, 'api_enabled_callback'),
            'wpmcp-settings',
            'wpmcp_api_section'
        );
        
        add_settings_field(
            'wpmcp_endpoint_url',
            __('Endpoint URL', 'wpmcp'),
            array($this, 'endpoint_url_callback'),
            'wpmcp-settings',
            'wpmcp_api_section'
        );
        
        // Content Access section
        add_settings_section(
            'wpmcp_content_section',
            __('Content Access', 'wpmcp'),
            array($this, 'content_section_callback'),
            'wpmcp-settings'
        );
        
        add_settings_field(
            'wpmcp_allowed_post_types',
            __('Allowed Post Types', 'wpmcp'),
            array($this, 'allowed_post_types_callback'),
            'wpmcp-settings',
            'wpmcp_content_section'
        );
        
        // Security section
        add_settings_section(
            'wpmcp_security_section',
            __('Security Settings', 'wpmcp'),
            array($this, 'security_section_callback'),
            'wpmcp-settings'
        );
        
        add_settings_field(
            'wpmcp_rate_limit',
            __('Rate Limit (per hour)', 'wpmcp'),
            array($this, 'rate_limit_callback'),
            'wpmcp-settings',
            'wpmcp_security_section'
        );
        
        add_settings_field(
            'wpmcp_security_logging',
            __('Security Logging', 'wpmcp'),
            array($this, 'security_logging_callback'),
            'wpmcp-settings',
            'wpmcp_security_section'
        );
        
        add_settings_field(
            'wpmcp_debug_mode',
            __('Debug Mode', 'wpmcp'),
            array($this, 'debug_mode_callback'),
            'wpmcp-settings',
            'wpmcp_security_section'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'settings_page_wpmcp-settings') {
            return;
        }
        
        wp_enqueue_script(
            'wpmcp-admin',
            WPMCP_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            WPMCP_VERSION,
            true
        );
        
        wp_localize_script('wpmcp-admin', 'wpmcp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpmcp_admin_nonce')
        ));
        
        wp_enqueue_style(
            'wpmcp-admin',
            WPMCP_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            WPMCP_VERSION
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpmcp-admin-container">
                <div class="wpmcp-main-content">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('wpmcp_settings');
                        do_settings_sections('wpmcp-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="wpmcp-sidebar">
                    <?php $this->render_api_keys_section(); ?>
                    <?php $this->render_stats_section(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API keys section
     */
    private function render_api_keys_section() {
        $auth = new WPMCP_Auth();
        $api_keys = $auth->get_api_keys();
        ?>
        <div class="wpmcp-section">
            <h3><?php _e('API Keys', 'wpmcp'); ?></h3>
            
            <div class="wpmcp-api-key-form">
                <input type="text" id="api-key-name" placeholder="<?php _e('API Key Name', 'wpmcp'); ?>" />
                <button type="button" id="generate-api-key" class="button button-primary">
                    <?php _e('Generate API Key', 'wpmcp'); ?>
                </button>
            </div>
            
            <div class="wpmcp-api-keys-list">
                <?php if (empty($api_keys)): ?>
                    <p><?php _e('No API keys generated yet.', 'wpmcp'); ?></p>
                <?php else: ?>
                    <?php foreach ($api_keys as $key): ?>
                        <div class="wpmcp-api-key-item" data-key-id="<?php echo esc_attr($key['id']); ?>">
                            <div class="key-info">
                                <strong><?php echo esc_html($key['name']); ?></strong>
                                <span class="key-status <?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $key['is_active'] ? __('Active', 'wpmcp') : __('Inactive', 'wpmcp'); ?>
                                </span>
                            </div>
                            <div class="key-meta">
                                <small>
                                    <?php printf(__('Created: %s', 'wpmcp'), date('M j, Y', strtotime($key['created_at']))); ?>
                                    <?php if ($key['last_used_at']): ?>
                                        | <?php printf(__('Last used: %s', 'wpmcp'), date('M j, Y', strtotime($key['last_used_at']))); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php if ($key['is_active']): ?>
                                <button type="button" class="button button-small revoke-api-key">
                                    <?php _e('Revoke', 'wpmcp'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render stats section
     */
    private function render_stats_section() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'wpmcp_request_logs';
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_requests,
                COUNT(DISTINCT api_key_id) as active_keys,
                AVG(response_time) as avg_response_time
             FROM $logs_table 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        ?>
        <div class="wpmcp-section">
            <h3><?php _e('Usage Statistics (Last 7 Days)', 'wpmcp'); ?></h3>
            
            <div class="wpmcp-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats->total_requests ?? 0); ?></span>
                    <span class="stat-label"><?php _e('Total Requests', 'wpmcp'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats->active_keys ?? 0); ?></span>
                    <span class="stat-label"><?php _e('Active Keys', 'wpmcp'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats->avg_response_time ?? 0, 3); ?>s</span>
                    <span class="stat-label"><?php _e('Avg Response Time', 'wpmcp'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Settings callbacks
    public function api_section_callback() {
        echo '<p>' . __('Configure the MCP API endpoint and basic settings.', 'wpmcp') . '</p>';
    }
    
    public function content_section_callback() {
        echo '<p>' . __('Control which content types are accessible through the API.', 'wpmcp') . '</p>';
    }
    
    public function security_section_callback() {
        echo '<p>' . __('Configure security and logging settings.', 'wpmcp') . '</p>';
    }
    
    public function api_enabled_callback() {
        $value = get_option('wpmcp_api_enabled', true);
        echo '<input type="checkbox" name="wpmcp_api_enabled" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Enable or disable the MCP API endpoint.', 'wpmcp') . '</p>';
    }
    
    public function endpoint_url_callback() {
        $url = rest_url('wpmcp/v1/mcp');
        echo '<input type="text" value="' . esc_attr($url) . '" readonly class="regular-text" />';
        echo '<p class="description">' . __('This is your MCP endpoint URL. Use this in your MCP client configuration.', 'wpmcp') . '</p>';
    }
    
    public function allowed_post_types_callback() {
        $allowed_types = get_option('wpmcp_allowed_post_types', array('post', 'page'));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $allowed_types);
            echo '<label><input type="checkbox" name="wpmcp_allowed_post_types[]" value="' . esc_attr($post_type->name) . '" ' . checked($checked, true, false) . ' /> ' . esc_html($post_type->label) . '</label><br>';
        }
        echo '<p class="description">' . __('Select which post types should be accessible through the API.', 'wpmcp') . '</p>';
    }
    
    public function rate_limit_callback() {
        $value = get_option('wpmcp_rate_limit', 100);
        echo '<input type="number" name="wpmcp_rate_limit" value="' . esc_attr($value) . '" min="1" max="10000" class="small-text" />';
        echo '<p class="description">' . __('Maximum number of requests per API key per hour.', 'wpmcp') . '</p>';
    }
    
    public function security_logging_callback() {
        $value = get_option('wpmcp_security_logging', true);
        echo '<input type="checkbox" name="wpmcp_security_logging" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Log API requests for security monitoring.', 'wpmcp') . '</p>';
    }
    
    public function debug_mode_callback() {
        $value = get_option('wpmcp_debug_mode', false);
        echo '<input type="checkbox" name="wpmcp_debug_mode" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Enable detailed logging for debugging. Disable in production.', 'wpmcp') . '</p>';
    }
    
    /**
     * AJAX: Generate API key
     */
    public function ajax_generate_api_key() {
        check_ajax_referer('wpmcp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wpmcp'));
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        if (empty($name)) {
            wp_send_json_error(__('API key name is required', 'wpmcp'));
        }
        
        $auth = new WPMCP_Auth();
        $result = $auth->generate_api_key($name);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Revoke API key
     */
    public function ajax_revoke_api_key() {
        check_ajax_referer('wpmcp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wpmcp'));
        }
        
        $key_id = intval($_POST['key_id'] ?? 0);
        if (!$key_id) {
            wp_send_json_error(__('Invalid API key ID', 'wpmcp'));
        }
        
        $auth = new WPMCP_Auth();
        $result = $auth->revoke_api_key($key_id);
        
        if ($result === false) {
            wp_send_json_error(__('Failed to revoke API key', 'wpmcp'));
        }
        
        wp_send_json_success(__('API key revoked successfully', 'wpmcp'));
    }
}