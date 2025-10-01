<?php
/**
 * Search handler for MCP tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Search_Handler {
    
    /**
     * Handle MCP method
     */
    public function handle($method, $params) {
        switch ($method) {
            case 'search_content':
                return $this->search_content($params);
            default:
                throw new Exception('Method not supported by search handler');
        }
    }
    
    /**
     * Search content
     */
    private function search_content($params) {
        if (empty($params['query'])) {
            throw new Exception('Search query parameter required');
        }
        
        $args = array(
            's' => sanitize_text_field($params['query']),
            'post_status' => 'publish',
            'post_type' => $this->get_searchable_post_types($params),
            'posts_per_page' => min($params['limit'] ?? 10, 50),
            'offset' => $params['offset'] ?? 0,
            'orderby' => 'relevance'
        );
        
        // Apply additional filters
        if (!empty($params['filters'])) {
            $args = $this->apply_search_filters($args, $params['filters']);
        }
        
        // Perform search
        $search_query = new WP_Query($args);
        $results = array();
        
        foreach ($search_query->posts as $post) {
            $result = $this->transform_search_result($post, $params['query']);
            $results[] = $result;
        }
        
        return array(
            'results' => $results,
            'total' => $search_query->found_posts,
            'query' => $params['query'],
            'limit' => $args['posts_per_page'],
            'offset' => $args['offset']
        );
    }
    
    /**
     * Get searchable post types
     */
    private function get_searchable_post_types($params) {
        $allowed_types = get_option('wpmcp_allowed_post_types', array('post', 'page'));
        
        if (!empty($params['post_types'])) {
            $requested_types = (array) $params['post_types'];
            return array_intersect($requested_types, $allowed_types);
        }
        
        return $allowed_types;
    }
    
    /**
     * Apply search filters
     */
    private function apply_search_filters($args, $filters) {
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
            $args['author_name'] = $filters['author'];
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $args['category_name'] = $filters['category'];
        }
        
        // Tag filter
        if (!empty($filters['tag'])) {
            $args['tag'] = $filters['tag'];
        }
        
        return $args;
    }
    
    /**
     * Transform search result
     */
    private function transform_search_result($post, $query) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $this->get_search_excerpt($post, $query),
            'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'slug' => $post->post_name,
            'post_type' => $post->post_type,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'permalink' => get_permalink($post->ID),
            'relevance_score' => $this->calculate_relevance_score($post, $query)
        );
        
        // Add post type specific data
        if ($post->post_type === 'page') {
            $data['menu_order'] = $post->menu_order;
            $data['parent_id'] = $post->post_parent;
        }
        
        // Categories and tags
        if ($post->post_type === 'post') {
            $data['categories'] = wp_get_post_categories($post->ID, array('fields' => 'names'));
            $data['tags'] = wp_get_post_tags($post->ID, array('fields' => 'names'));
        }
        
        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $data['featured_image'] = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium')[0];
        }
        
        return $data;
    }
    
    /**
     * Get search excerpt with highlighted terms
     */
    private function get_search_excerpt($post, $query) {
        $content = strip_tags($post->post_content);
        $query_terms = explode(' ', $query);
        
        // Find the best excerpt around search terms
        $excerpt_length = 200;
        $best_position = 0;
        $best_score = 0;
        
        foreach ($query_terms as $term) {
            $position = stripos($content, $term);
            if ($position !== false) {
                $score = substr_count(strtolower(substr($content, max(0, $position - 100), 200)), strtolower($term));
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_position = max(0, $position - 100);
                }
            }
        }
        
        $excerpt = substr($content, $best_position, $excerpt_length);
        
        // Add ellipsis if needed
        if ($best_position > 0) {
            $excerpt = '...' . $excerpt;
        }
        if (strlen($content) > $best_position + $excerpt_length) {
            $excerpt .= '...';
        }
        
        return trim($excerpt);
    }
    
    /**
     * Calculate relevance score
     */
    private function calculate_relevance_score($post, $query) {
        $score = 0;
        $query_terms = explode(' ', strtolower($query));
        
        $title = strtolower($post->post_title);
        $content = strtolower(strip_tags($post->post_content));
        
        foreach ($query_terms as $term) {
            // Title matches are worth more
            $title_matches = substr_count($title, $term);
            $score += $title_matches * 10;
            
            // Content matches
            $content_matches = substr_count($content, $term);
            $score += $content_matches * 1;
            
            // Exact phrase bonus
            if (stripos($title, $query) !== false) {
                $score += 20;
            }
            if (stripos($content, $query) !== false) {
                $score += 5;
            }
        }
        
        // Normalize score (0-100)
        return min(100, $score);
    }
}