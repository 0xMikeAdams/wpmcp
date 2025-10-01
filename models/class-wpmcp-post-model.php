<?php
/**
 * Post data model
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Post_Model {
    
    /**
     * Transform WordPress post to MCP format
     */
    public function transform($post, $include_content = false) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'post_type' => $post->post_type,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'author_id' => $post->post_author,
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'permalink' => get_permalink($post->ID)
        );
        
        // Add excerpt
        if ($post->post_excerpt) {
            $data['excerpt'] = $post->post_excerpt;
        } else {
            $data['excerpt'] = wp_trim_words(strip_tags($post->post_content), 55);
        }
        
        // Include full content if requested
        if ($include_content) {
            $data['content'] = apply_filters('the_content', $post->post_content);
            $data['raw_content'] = $post->post_content;
            
            // Custom fields
            $data['custom_fields'] = $this->get_custom_fields($post->ID);
            
            // Featured image
            $data['featured_image'] = $this->get_featured_image($post->ID);
            
            // Taxonomies
            $data['taxonomies'] = $this->get_post_taxonomies($post);
        }
        
        // Post type specific data
        if ($post->post_type === 'post') {
            $data['categories'] = wp_get_post_categories($post->ID, array('fields' => 'names'));
            $data['tags'] = wp_get_post_tags($post->ID, array('fields' => 'names'));
        }
        
        return $data;
    }
    
    /**
     * Get custom fields
     */
    private function get_custom_fields($post_id) {
        $custom_fields = get_post_meta($post_id);
        $filtered = array();
        
        foreach ($custom_fields as $key => $values) {
            // Skip private fields and WordPress internals
            if (strpos($key, '_') === 0) {
                continue;
            }
            
            // Skip large serialized data
            if (is_serialized($values[0]) && strlen($values[0]) > 1000) {
                continue;
            }
            
            $filtered[$key] = count($values) === 1 ? $values[0] : $values;
        }
        
        return $filtered;
    }
    
    /**
     * Get featured image data
     */
    private function get_featured_image($post_id) {
        if (!has_post_thumbnail($post_id)) {
            return null;
        }
        
        $attachment_id = get_post_thumbnail_id($post_id);
        $image_data = wp_get_attachment_image_src($attachment_id, 'full');
        
        if (!$image_data) {
            return null;
        }
        
        return array(
            'url' => $image_data[0],
            'width' => $image_data[1],
            'height' => $image_data[2],
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'caption' => wp_get_attachment_caption($attachment_id),
            'sizes' => $this->get_image_sizes($attachment_id)
        );
    }
    
    /**
     * Get image sizes
     */
    private function get_image_sizes($attachment_id) {
        $sizes = array();
        $image_sizes = get_intermediate_image_sizes();
        
        foreach ($image_sizes as $size) {
            $image_data = wp_get_attachment_image_src($attachment_id, $size);
            if ($image_data) {
                $sizes[$size] = array(
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2]
                );
            }
        }
        
        return $sizes;
    }
    
    /**
     * Get post taxonomies
     */
    private function get_post_taxonomies($post) {
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        $result = array();
        
        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }
            
            $terms = wp_get_post_terms($post->ID, $taxonomy->name);
            if (!is_wp_error($terms) && !empty($terms)) {
                $term_data = array();
                foreach ($terms as $term) {
                    $term_data[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description
                    );
                }
                
                $result[$taxonomy->name] = array(
                    'label' => $taxonomy->label,
                    'terms' => $term_data
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Validate post data
     */
    public function validate($data) {
        $errors = array();
        
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }
        
        if (empty($data['content'])) {
            $errors[] = 'Content is required';
        }
        
        if (!empty($data['post_type']) && !post_type_exists($data['post_type'])) {
            $errors[] = 'Invalid post type';
        }
        
        return empty($errors) ? true : $errors;
    }
}