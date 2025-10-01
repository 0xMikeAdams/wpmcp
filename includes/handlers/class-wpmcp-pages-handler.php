<?php
/**
 * Pages handler for MCP tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Pages_Handler {
    
    /**
     * Handle MCP method
     */
    public function handle($method, $params) {
        switch ($method) {
            case 'get_pages':
                return $this->get_pages($params);
            case 'get_page':
                return $this->get_page($params);
            default:
                throw new Exception('Method not supported by pages handler');
        }
    }
    
    /**
     * Get pages
     */
    private function get_pages($params) {
        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => min($params['limit'] ?? 10, 100),
            'offset' => $params['offset'] ?? 0,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );
        
        // Parent filter
        if (isset($params['parent_id'])) {
            $args['post_parent'] = $params['parent_id'];
        }
        
        $pages = get_posts($args);
        $result = array();
        
        foreach ($pages as $page) {
            $transformed = $this->transform_page($page);
            
            // Include hierarchy if requested
            if (!empty($params['include_hierarchy'])) {
                $transformed['children'] = $this->get_child_pages($page->ID);
            }
            
            $result[] = $transformed;
        }
        
        return array(
            'pages' => $result,
            'total' => $this->get_total_pages($args),
            'limit' => $args['posts_per_page'],
            'offset' => $args['offset']
        );
    }
    
    /**
     * Get single page
     */
    private function get_page($params) {
        if (empty($params['page_id']) && empty($params['slug'])) {
            throw new Exception('page_id or slug parameter required');
        }
        
        if (!empty($params['page_id'])) {
            $page = get_post($params['page_id']);
        } else {
            $page = get_page_by_path($params['slug']);
        }
        
        if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish') {
            throw new Exception('Page not found or not published');
        }
        
        $result = $this->transform_page($page, true);
        
        // Include hierarchy information
        $result['parent'] = $page->post_parent ? $this->transform_page(get_post($page->post_parent)) : null;
        $result['children'] = $this->get_child_pages($page->ID);
        
        return $result;
    }
    
    /**
     * Get child pages
     */
    private function get_child_pages($parent_id) {
        $children = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1
        ));
        
        $result = array();
        foreach ($children as $child) {
            $result[] = $this->transform_page($child);
        }
        
        return $result;
    }
    
    /**
     * Transform page object
     */
    private function transform_page($page, $include_content = false) {
        $data = array(
            'id' => $page->ID,
            'title' => $page->post_title,
            'slug' => $page->post_name,
            'status' => $page->post_status,
            'author' => get_the_author_meta('display_name', $page->post_author),
            'date_created' => $page->post_date,
            'date_modified' => $page->post_modified,
            'menu_order' => $page->menu_order,
            'parent_id' => $page->post_parent,
            'permalink' => get_permalink($page->ID)
        );
        
        if ($include_content) {
            $data['content'] = apply_filters('the_content', $page->post_content);
            $data['excerpt'] = $page->post_excerpt ?: wp_trim_words($page->post_content, 55);
            
            // Custom fields
            $custom_fields = get_post_meta($page->ID);
            $data['custom_fields'] = $this->filter_custom_fields($custom_fields);
            
            // Featured image
            if (has_post_thumbnail($page->ID)) {
                $data['featured_image'] = wp_get_attachment_image_src(get_post_thumbnail_id($page->ID), 'full')[0];
            }
            
            // Page template
            $data['template'] = get_page_template_slug($page->ID) ?: 'default';
        }
        
        return $data;
    }
    
    /**
     * Filter custom fields
     */
    private function filter_custom_fields($custom_fields) {
        $filtered = array();
        
        foreach ($custom_fields as $key => $values) {
            // Skip private fields and WordPress internals
            if (strpos($key, '_') === 0) {
                continue;
            }
            
            $filtered[$key] = count($values) === 1 ? $values[0] : $values;
        }
        
        return $filtered;
    }
    
    /**
     * Get total pages count
     */
    private function get_total_pages($args) {
        $count_args = $args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';
        unset($count_args['offset']);
        
        $pages = get_posts($count_args);
        return count($pages);
    }
}