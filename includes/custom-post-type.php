<?php
if (!defined('ABSPATH')) exit;

class WP_Product_Describer_Register_Post_Type{

    public function __construct() {
        
        // Register Custom Post Type
        add_action('init', [$this, 'register_product_post_type']);
    
    }
   
    function register_product_post_type() {
        
        register_post_type('products', [
            'labels' => [
                'name' => __('Products'),
                'singular_name' => __('Product'),
                'add_new_item' => __('Add New Product'),
                'edit_item' => __('Edit Product'),
                'new_item' => __('New Product'),
                'view_item' => __('View Product'),
                'all_items' => __('All Products'),
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => ['category'], // Enable categories
            'has_archive' => true,
            'rewrite' => ['slug' => 'products'],
        ]);

    }

}


new WP_Product_Describer_Register_Post_Type();