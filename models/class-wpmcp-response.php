<?php
/**
 * MCP Response model
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Response {
    
    /**
     * JSON-RPC version
     */
    public $jsonrpc = '2.0';
    
    /**
     * Request ID
     */
    public $id;
    
    /**
     * Response result
     */
    public $result;
    
    /**
     * Response error
     */
    public $error;
    
    /**
     * Create success response
     */
    public static function success($data, $id = null) {
        $response = new self();
        $response->result = $data;
        $response->id = $id;
        return $response;
    }
    
    /**
     * Create error response
     */
    public static function error($code, $message, $id = null, $data = null) {
        $response = new self();
        $response->error = array(
            'code' => $code,
            'message' => $message
        );
        
        if ($data !== null) {
            $response->error['data'] = $data;
        }
        
        $response->id = $id;
        return $response;
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        $data = array(
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id
        );
        
        if ($this->result !== null) {
            $data['result'] = $this->result;
        }
        
        if ($this->error !== null) {
            $data['error'] = $this->error;
        }
        
        return $data;
    }
    
    /**
     * Convert to JSON
     */
    public function to_json() {
        return wp_json_encode($this->to_array());
    }
    
    /**
     * Get HTTP status code
     */
    public function get_http_status() {
        if ($this->error !== null) {
            switch ($this->error['code']) {
                case 1001: // Invalid API Key
                case 1003: // Insufficient Permissions
                    return 401;
                case 1002: // Rate Limit Exceeded
                    return 429;
                case 2001: // Invalid Request Format
                case 2002: // Missing Required Parameters
                case 2003: // Invalid Parameter Values
                    return 400;
                case 3001: // Content Not Found
                    return 404;
                case 3002: // Content Access Denied
                    return 403;
                case 5001: // Internal Server Error
                case 5002: // Database Connection Error
                    return 500;
                default:
                    return 400;
            }
        }
        
        return 200;
    }
    
    /**
     * Create WordPress REST response
     */
    public function to_wp_response() {
        return new WP_REST_Response(
            $this->to_array(),
            $this->get_http_status()
        );
    }
}

/**
 * Error code constants
 */
class WPMCP_Error_Codes {
    
    // Authentication errors (1000-1999)
    const INVALID_API_KEY = 1001;
    const RATE_LIMIT_EXCEEDED = 1002;
    const INSUFFICIENT_PERMISSIONS = 1003;
    
    // Request errors (2000-2999)
    const INVALID_REQUEST_FORMAT = 2001;
    const MISSING_REQUIRED_PARAMETERS = 2002;
    const INVALID_PARAMETER_VALUES = 2003;
    
    // Content errors (3000-3999)
    const CONTENT_NOT_FOUND = 3001;
    const CONTENT_ACCESS_DENIED = 3002;
    
    // Server errors (5000-5999)
    const INTERNAL_SERVER_ERROR = 5001;
    const DATABASE_CONNECTION_ERROR = 5002;
    
    /**
     * Get error message for code
     */
    public static function get_message($code) {
        $messages = array(
            self::INVALID_API_KEY => 'Invalid or inactive API key',
            self::RATE_LIMIT_EXCEEDED => 'Rate limit exceeded',
            self::INSUFFICIENT_PERMISSIONS => 'Insufficient permissions',
            self::INVALID_REQUEST_FORMAT => 'Invalid request format',
            self::MISSING_REQUIRED_PARAMETERS => 'Missing required parameters',
            self::INVALID_PARAMETER_VALUES => 'Invalid parameter values',
            self::CONTENT_NOT_FOUND => 'Content not found',
            self::CONTENT_ACCESS_DENIED => 'Content access denied',
            self::INTERNAL_SERVER_ERROR => 'Internal server error',
            self::DATABASE_CONNECTION_ERROR => 'Database connection error'
        );
        
        return $messages[$code] ?? 'Unknown error';
    }
}