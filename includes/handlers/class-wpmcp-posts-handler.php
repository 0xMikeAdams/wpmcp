<?php
/**
 * Posts handler for MCP tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Posts_Handler {
    
    /**
     * Handle MCP method
     */
    public function handle($method, $params) {
        switch ($method) {
            case 'get_posts':
                return $this->get_posts($params);
            case 'get_post':
                return $this->get_post($params);
            default:
                throw new Exception('Method not supported by posts handler');
        }
    }
    
    /**
     * Get posts
     */
    private function get_posts($params) {
        $args = array(
            'post_status' => 'publish',
            'post_type' => $params['post_type'] ?? 'post',
            'posts_per_page' => min($params['limit'] ?? 10, 100),
            'offset' => $params['offset'] ?? 0,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Apply filters
        if (!empty($params['filters'])) {
            $args = $this->apply_filters($args, $params['filters']);
        }
        
        $posts = get_posts($args);
        $result = array();
        
        foreach ($posts as $post) {
            $result[] = $this->transform_post($post);
        }
        
        return array(
            'posts' => $result,
            'total' => $this->get_total_posts($args),
            'limit' => $args['posts_per_page'],
            'offset' => $args['offset']
        );
    }
    
    /**
     * Get single post
     */
    private function get_post($params) {
        if (empty($params['post_id']) && empty($params['slug'])) {
            throw new Exception('post_id or slug parameter required');
        }
        
        if (!empty($params['post_id'])) {
            $post = get_post($params['post_id']);
        } else {
            $post = get_page_by_path($params['slug'], OBJECT, 'post');
        }
        
        if (!$post || $post->post_status !== 'publish') {
            throw new Exception('Post not found or not published');
        }
        
        return $this->transform_post($post, true);
    }
    
    /**
     * Apply filters to query args
     */
    private function apply_filters($args, $filters) {
        // Date range filter
        if (!empty($filters['date_after']) || !empty($filters['date_before'])) {
            $date_query = array();
            
            if (!empty($filters['date_after'])) {
                $date_query['after'] = $filters['date_after'];
            }
            
            if (!empty($filters['date_before'])) {
                $date_query['before'] = $filters['date_before'];
            }
            
            $args['date_query'] = array($date_query);
        }
        
        // Author filter
        if (!empty($filters['author'])) {
            $args['author'] = $filters['author'];
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $args['category_name'] = $filters['category'];
        }
        
        // Tag filter
        if (!empty($filters['tag'])) {
            $args['tag'] = $filters['tag'];
        }
        
        // Meta query filter
        if (!empty($filters['meta_key']) && !empty($filters['meta_value'])) {
            $args['meta_query'] = array(
                array(
                    'key' => $filters['meta_key'],
                    'value' => $filters['meta_value'],
                    'compare' => $filters['meta_compare'] ?? '='
                )
            );
        }
        
        return $args;
    }
    
    /**
     * Transform post object
     */
    private function transform_post($post, $include_content = false) {
        $model = new WPMCP_Post_Model();
        return $model->transform($post, $include_content);
    }
    
    /**
     * Get total posts count
     */
    private function get_total_posts($args) {
        $count_args = $args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';
        unset($count_args['offset']);
        
        $posts = get_posts($count_args);
        return count($posts);
    }
}