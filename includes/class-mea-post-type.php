<?php
/**
 * Custom Post Type for Automated Emails
 */

if ( ! defined('ABSPATH')) {
    exit;
}

class SIMESC_Post_Type {
    
    public function init() {
        // Register post type and meta fields on init hook
        add_action('init', array($this, 'register_post_type'), 10);
        add_action('init', array($this, 'register_meta_fields'), 10);
    }
    
    /**
     * Register custom post type for automated emails
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Automated Emails', 'Post Type General Name', 'simple-email-scheduler'),
            'singular_name'         => _x('Automated Email', 'Post Type Singular Name', 'simple-email-scheduler'),
            'menu_name'             => __('Email Scheduler', 'simple-email-scheduler'),
            'name_admin_bar'        => __('Automated Email', 'simple-email-scheduler'),
            'archives'              => __('Email Archives', 'simple-email-scheduler'),
            'attributes'            => __('Email Attributes', 'simple-email-scheduler'),
            'parent_item_colon'     => __('Parent Email:', 'simple-email-scheduler'),
            'all_items'             => __('All Emails', 'simple-email-scheduler'),
            'add_new_item'          => __('Add New Email', 'simple-email-scheduler'),
            'add_new'               => __('Add New', 'simple-email-scheduler'),
            'new_item'              => __('New Email', 'simple-email-scheduler'),
            'edit_item'             => __('Edit Email', 'simple-email-scheduler'),
            'update_item'           => __('Update Email', 'simple-email-scheduler'),
            'view_item'             => __('View Email', 'simple-email-scheduler'),
            'view_items'            => __('View Emails', 'simple-email-scheduler'),
            'search_items'          => __('Search Email', 'simple-email-scheduler'),
            'not_found'             => __('Not found', 'simple-email-scheduler'),
            'not_found_in_trash'    => __('Not found in Trash', 'simple-email-scheduler'),
            'featured_image'        => __('Featured Image', 'simple-email-scheduler'),
            'set_featured_image'    => __('Set featured image', 'simple-email-scheduler'),
            'remove_featured_image' => __('Remove featured image', 'simple-email-scheduler'),
            'use_featured_image'    => __('Use as featured image', 'simple-email-scheduler'),
            'insert_into_item'      => __('Insert into email', 'simple-email-scheduler'),
            'uploaded_to_this_item' => __('Uploaded to this email', 'simple-email-scheduler'),
            'items_list'            => __('Emails list', 'simple-email-scheduler'),
            'items_list_navigation' => __('Emails list navigation', 'simple-email-scheduler'),
            'filter_items_list'     => __('Filter emails list', 'simple-email-scheduler'),
        );
        
        $args = array(
            'label'                 => __('Automated Email', 'simple-email-scheduler'),
            'description'           => __('Scheduled automated emails', 'simple-email-scheduler'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 30,
            'menu_icon'             => 'dashicons-email-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => array(
                'edit_post'          => 'edit_posts',
                'read_post'          => 'read',
                'delete_post'        => 'delete_posts',
                'edit_posts'         => 'edit_posts',
                'edit_others_posts'  => 'edit_others_posts',
                'publish_posts'      => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
            ),
            'map_meta_cap'          => false,
            'show_in_rest'          => false,
        );
        
        register_post_type( SIMESC_POST_TYPE, $args );
    }
    
    /**
     * Register meta fields for email configuration
     */
    public function register_meta_fields() {
        // Recipients (stored as JSON array)
        register_post_meta( SIMESC_POST_TYPE, '_simesc_recipients', array(
            'type' => 'string',
            'description' => 'Email recipients (JSON array)',
            'single' => true,
            'sanitize_callback' => array($this, 'sanitize_recipients'),
            'show_in_rest' => false,
        ));
        
        // Email subject
        register_post_meta( SIMESC_POST_TYPE, '_simesc_subject', array(
            'type' => 'string',
            'description' => 'Email subject line',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false,
        ));

        // Optional inbox preview / preheader
        register_post_meta( SIMESC_POST_TYPE, '_simesc_preheader', array(
            'type'              => 'string',
            'description'       => 'Inbox preview (preheader) text',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => false,
        ) );
        
        // Day of month (1-31)
        register_post_meta( SIMESC_POST_TYPE, '_simesc_day_of_month', array(
            'type' => 'integer',
            'description' => 'Day of month to send email (1-31)',
            'single' => true,
            'sanitize_callback' => 'absint',
            'show_in_rest' => false,
        ));
        
        // Time to send (HH:MM format)
        register_post_meta( SIMESC_POST_TYPE, '_simesc_send_time', array(
            'type' => 'string',
            'description' => 'Time to send email (HH:MM)',
            'single' => true,
            'sanitize_callback' => array($this, 'sanitize_time'),
            'show_in_rest' => false,
        ));
        
        // Status (active/inactive)
        register_post_meta( SIMESC_POST_TYPE, '_simesc_status', array(
            'type' => 'string',
            'description' => 'Email status (active/inactive)',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false,
            'default' => 'active',
        ));
        
        // Last sent date
        register_post_meta( SIMESC_POST_TYPE, '_simesc_last_sent', array(
            'type' => 'string',
            'description' => 'Last sent date',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false,
        ));
    }
    
    /**
     * Sanitize recipients JSON
     */
    public function sanitize_recipients($value) {
        if (is_string($value)) {
            $recipients = json_decode($value, true);
            if (is_array($recipients)) {
                $sanitized = array();
                foreach ($recipients as $email) {
                    $sanitized[] = sanitize_email($email);
                }
                return json_encode($sanitized);
            }
        }
        return '';
    }
    
    /**
     * Sanitize time format (HH:MM)
     */
    public function sanitize_time($value) {
        if (preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
            return $value;
        }
        return '09:00'; // Default time
    }
}

