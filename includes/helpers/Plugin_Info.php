<?php

class SCR_ITJ_Plugin_Info
{
    protected static $initd = false;

    /**
     * init pluggable functions.
     *
     * @return  void
     */
    public static function init()
    {
        // Do nothing if pluggable functions already initd.
        if (self::$initd) {
            return;
        }
        add_action('init', [__CLASS__, 'get_cpt_cart_email_reminder'], 0);
        // State that initialization completed.
        self::$initd = true;
    }


    public static function get_cpt_cart_email_reminder()
    {
        $labels = array(
            'name' => _x('Simple Cart Reminder for WooCommerce', 'Simple Cart Reminder for WooCommerce','scrlang'),
            'singular_name' => _x('simple-cart-reminder', 'Simple Cart Reminder','scrlang'),
            'menu_name' => __('Simple Cart Reminder','scrlang'),
            'name_admin_bar' => __('Simple Cart Reminder','scrlang'),
            'archives' => __('Email Archives','scrlang'),
            'attributes' => __('Email Attributes','scrlang'),
            'parent_item_colon' => __('Parent Email:','scrlang'),
            'all_items' => __('Email Templates','scrlang'),
            'add_new_item' => __('Add New Email','scrlang'),
            'add_new' => __('Add New','scrlang'),
            'new_item' => __('New Email','scrlang'),
            'edit_item' => __('Edit Email','scrlang'),
            'update_item' => __('Update Email','scrlang'),
            'view_item' => __('View Email','scrlang'),
            'view_items' => __('View Emails','scrlang'),
            'search_items' => __('Search Email','scrlang'),
            'not_found' => __('Not found','scrlang'),
            'not_found_in_trash' => __('Not found in Trash','scrlang'),
            'featured_image' => __('Featured Image','scrlang'),
            'set_featured_image' => __('Set featured image','scrlang'),
            'remove_featured_image' => __('Remove featured image','scrlang'),
            'use_featured_image' => __('Use as featured image','scrlang'),
            'insert_into_item' => __('Insert item','scrlang'),
            'uploaded_to_this_item' => __('Uploaded to this item','scrlang'),
            'items_list' => __('Emails list','scrlang'),
            'items_list_navigation' => __('Emails list navigation','scrlang'),
            'filter_items_list' => __('Filter items list','scrlang'),
        );
        $args = array(
            'label' => __('Simple Cart Reminder','scrlang'),
            'description' => __('Simple Cart Reminder for WooCommerce','scrlang'),
            'labels' => $labels,
            'supports' => array('title', 'editor'),
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-cart',//'dashicons-email-alt',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'has_archive' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'rewrite' => false,
            'capability_type' => 'page',
        );
        register_post_type('simple_cart_reminder', $args);
    }

}
