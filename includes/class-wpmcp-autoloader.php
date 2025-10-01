<?php
/**
 * Autoloader for WPMCP classes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Autoloader {
    
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
    
    /**
     * Autoload WPMCP classes
     */
    public static function autoload($class_name) {
        if (strpos($class_name, 'WPMCP_') !== 0) {
            return;
        }
        
        $class_file = self::get_class_file($class_name);
        
        if (file_exists($class_file)) {
            require_once $class_file;
        }
    }
    
    /**
     * Get class file path
     */
    private static function get_class_file($class_name) {
        $class_name = strtolower(str_replace('_', '-', $class_name));
        $class_file = 'class-' . $class_name . '.php';
        
        // Check different directories
        $directories = array(
            WPMCP_PLUGIN_DIR . 'includes/',
            WPMCP_PLUGIN_DIR . 'includes/handlers/',
            WPMCP_PLUGIN_DIR . 'models/',
            WPMCP_PLUGIN_DIR . 'admin/',
        );
        
        foreach ($directories as $dir) {
            $file_path = $dir . $class_file;
            if (file_exists($file_path)) {
                return $file_path;
            }
        }
        
        return WPMCP_PLUGIN_DIR . 'includes/' . $class_file;
    }
}

WPMCP_Autoloader::register();