<?php
/**
 * MCP Server implementation
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Server {
    
    /**
     * API namespace
     */
    const API_NAMESPACE = 'wpmcp/v1';
    
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'mcp';
    
    /**
     * Registered handlers
     */
    private $handlers = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Handlers will be initialized lazily when needed
    }
    
    /**
     * Initialize MCP tool handlers
     */
    private function init_handlers() {
        if (!empty($this->handlers)) {
            return; // Already initialized
        }
        
        $this->handlers = array(
            'get_posts' => new WPMCP_Posts_Handler(),
            'get_post' => new WPMCP_Posts_Handler(),
            'get_pages' => new WPMCP_Pages_Handler(),
            'get_page' => new WPMCP_Pages_Handler(),
            'get_post_types' => new WPMCP_Post_Types_Handler(),
            'search_content' => new WPMCP_Search_Handler(),
        );
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Register main MCP endpoint
        $registered = register_rest_route(
            self::API_NAMESPACE,
            '/' . self::API_ENDPOINT,
            array(
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'handle_request'),
                    'permission_callback' => array($this, 'check_permissions'),
                ),
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'handle_get_request'),
                    'permission_callback' => '__return_true',
                )
            )
        );
        
        // Register a simple test endpoint
        register_rest_route(
            self::API_NAMESPACE,
            '/test',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'test_endpoint'),
                'permission_callback' => '__return_true',
            )
        );
        
        // Debug logging if enabled
        if (get_option('wpmcp_debug_mode', false)) {
            error_log('WPMCP: REST route registration ' . ($registered ? 'successful' : 'failed') . ' for ' . self::API_NAMESPACE . '/' . self::API_ENDPOINT);
        }
    }
    
    /**
     * Test endpoint
     */
    public function test_endpoint($request) {
        return new WP_REST_Response(array(
            'status' => 'ok',
            'message' => 'WPMCP plugin is active and REST API is working',
            'version' => WPMCP_VERSION,
            'endpoint' => rest_url(self::API_NAMESPACE . '/' . self::API_ENDPOINT)
        ));
    }
    
    /**
     * Check request permissions
     */
    public function check_permissions($request) {
        // Always allow - authentication handled in handle_request
        return true;
    }
    
    /**
     * Handle MCP request
     */
    public function handle_request($request) {
        $start_time = microtime(true);
        
        try {
            // Get request body
            $body = $request->get_body();
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->error_response(-32700, 'Parse error', null);
            }
            
            // Validate JSON-RPC format
            if (!$this->validate_jsonrpc($data)) {
                return $this->error_response(-32600, 'Invalid Request', $data['id'] ?? null);
            }
            
            // Authenticate request
            $auth_result = $this->authenticate_request($request);
            if (is_wp_error($auth_result)) {
                return $this->error_response(1001, $auth_result->get_error_message(), $data['id']);
            }
            
            // Handle the method
            $response = $this->handle_method($data['method'], $data['params'] ?? array(), $data['id']);
            
            // Log request
            $this->log_request($request, $auth_result, $response, microtime(true) - $start_time);
            
            return $response;
            
        } catch (Exception $e) {
            return $this->error_response(5001, 'Internal Server Error', $data['id'] ?? null);
        }
    }
    
    /**
     * Validate JSON-RPC format
     */
    private function validate_jsonrpc($data) {
        return isset($data['jsonrpc']) && 
               $data['jsonrpc'] === '2.0' && 
               isset($data['method']) && 
               is_string($data['method']);
    }
    
    /**
     * Authenticate request
     */
    private function authenticate_request($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (!$api_key) {
            return new WP_Error('no_api_key', 'API key required');
        }
        
        $auth = new WPMCP_Auth();
        return $auth->validate_api_key($api_key);
    }
    
    /**
     * Handle MCP method
     */
    private function handle_method($method, $params, $id) {
        // Initialize handlers if not already done
        $this->init_handlers();
        
        if (!isset($this->handlers[$method])) {
            return $this->error_response(-32601, 'Method not found', $id);
        }
        
        $handler = $this->handlers[$method];
        
        try {
            $result = $handler->handle($method, $params);
            return $this->success_response($result, $id);
        } catch (Exception $e) {
            return $this->error_response(3001, $e->getMessage(), $id);
        }
    }
    
    /**
     * Success response
     */
    private function success_response($result, $id) {
        return new WP_REST_Response(array(
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id
        ));
    }
    
    /**
     * Error response
     */
    private function error_response($code, $message, $id) {
        return new WP_REST_Response(array(
            'jsonrpc' => '2.0',
            'error' => array(
                'code' => $code,
                'message' => $message
            ),
            'id' => $id
        ));
    }
    
    /**
     * Log request
     */
    private function log_request($request, $api_key_data, $response, $response_time) {
        if (!get_option('wpmcp_security_logging', true)) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpmcp_request_logs',
            array(
                'api_key_id' => $api_key_data['id'] ?? null,
                'endpoint' => self::API_ENDPOINT,
                'method' => $request->get_method(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $request->get_header('User-Agent'),
                'request_data' => wp_json_encode($request->get_json_params()),
                'response_code' => $response->get_status(),
                'response_time' => $response_time,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}