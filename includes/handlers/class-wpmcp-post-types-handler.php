<?php
/**
 * Post types handler for MCP tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Post_Types_Handler {
    
    /**
     * Handle MCP method
     */
    public function handle($method, $params) {
        switch ($method) {
            case 'get_post_types':
                return $this->get_post_types($params);
            default:
                throw new Exception('Method not supported by post types handler');
        }
    }
    
    /**
     * Get available post types
     */
    private function get_post_types($params) {
        $args = array(
            'public' => true,
            '_builtin' => false
        );
        
        // Include built-in types if requested
        if (!empty($params['include_builtin'])) {
            unset($args['_builtin']);
        }
        
        $post_types = get_post_types($args, 'objects');
        
        // Always include post and page
        if (empty($params['include_builtin'])) {
            $post_types['post'] = get_post_type_object('post');
            $post_types['page'] = get_post_type_object('page');
        }
        
        $result = array();
        
        foreach ($post_types as $post_type) {
            // Check if post type is allowed in settings
            if (!$this->is_post_type_allowed($post_type->name)) {
                continue;
            }
            
            $result[] = $this->transform_post_type($post_type);
        }
        
        return array(
            'post_types' => $result,
            'total' => count($result)
        );
    }
    
    /**
     * Check if post type is allowed
     */
    private function is_post_type_allowed($post_type) {
        $allowed_types = get_option('wpmcp_allowed_post_types', array('post', 'page'));
        return in_array($post_type, $allowed_types);
    }
    
    /**
     * Transform post type object
     */
    private function transform_post_type($post_type) {
        $data = array(
            'name' => $post_type->name,
            'label' => $post_type->label,
            'labels' => (array) $post_type->labels,
            'description' => $post_type->description,
            'public' => $post_type->public,
            'hierarchical' => $post_type->hierarchical,
            'supports' => get_all_post_type_supports($post_type->name),
            'taxonomies' => get_object_taxonomies($post_type->name),
            'rest_enabled' => $post_type->show_in_rest,
            'menu_icon' => $post_type->menu_icon,
            'capabilities' => (array) $post_type->cap
        );
        
        // Get post count
        $count_posts = wp_count_posts($post_type->name);
        $data['post_count'] = $count_posts->publish ?? 0;
        
        // Get available meta keys
        $data['meta_keys'] = $this->get_post_type_meta_keys($post_type->name);
        
        // Get taxonomies with terms
        $data['taxonomy_terms'] = $this->get_post_type_taxonomies($post_type->name);
        
        return $data;
    }
    
    /**
     * Get meta keys for post type
     */
    private function get_post_type_meta_keys($post_type) {
        global $wpdb;
        
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_key 
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_type = %s 
             AND meta_key NOT LIKE '\\_%'
             ORDER BY meta_key",
            $post_type
        ));
        
        return array_slice($meta_keys, 0, 20); // Limit to 20 meta keys
    }
    
    /**
     * Get taxonomies and terms for post type
     */
    private function get_post_type_taxonomies($post_type) {
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $result = array();
        
        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }
            
            $terms = get_terms(array(
                'taxonomy' => $taxonomy->name,
                'hide_empty' => true,
                'number' => 10 // Limit to 10 terms per taxonomy
            ));
            
            $term_data = array();
            foreach ($terms as $term) {
                $term_data[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'count' => $term->count
                );
            }
            
            $result[$taxonomy->name] = array(
                'label' => $taxonomy->label,
                'hierarchical' => $taxonomy->hierarchical,
                'terms' => $term_data
            );
        }
        
        return $result;
    }
}