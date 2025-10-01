<?php
/**
 * Main plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * MCP Server instance
     */
    private $server;
    
    /**
     * Admin instance
     */
    private $admin;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        add_action('admin_init', array($this, 'init_admin'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->server = new WPMCP_Server();
        
        if (is_admin()) {
            $this->admin = new WPMCP_Admin();
            $this->admin->init();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wpmcp', false, dirname(WPMCP_PLUGIN_BASENAME) . '/languages');
        
        // Initialize database if needed
        $this->maybe_upgrade_database();
        
        // Flush rewrite rules if needed (only once after activation)
        if (get_option('wpmcp_activated', false)) {
            flush_rewrite_rules();
            delete_option('wpmcp_activated');
        }
    }
    
    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        if ($this->server) {
            $this->server->register_routes();
            
            // Debug logging if enabled
            if (get_option('wpmcp_debug_mode', false)) {
                error_log('WPMCP: REST API initialized');
            }
        } else {
            error_log('WPMCP: Server instance not available during REST API init');
        }
    }
    
    /**
     * Initialize admin
     */
    public function init_admin() {
        // Admin is now initialized in init_components()
        // This method is kept for compatibility
    }
    
    /**
     * Maybe upgrade database
     */
    private function maybe_upgrade_database() {
        $current_version = get_option('wpmcp_db_version', '0');
        
        if (version_compare($current_version, WPMCP_VERSION, '<')) {
            require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-database.php';
            WPMCP_Database::create_tables();
            update_option('wpmcp_db_version', WPMCP_VERSION);
        }
    }
    
    /**
     * Get server instance
     */
    public function get_server() {
        return $this->server;
    }
    
    /**
     * Get admin instance
     */
    public function get_admin() {
        return $this->admin;
    }
}