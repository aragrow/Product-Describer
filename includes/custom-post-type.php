<?php
if (!defined('ABSPATH')) exit;

class WP_Product_Describer_Register_Post_Type{

    public function __construct() {
        
        // Register Custom Post Type
        add_action('init', [$this, 'register_product_post_type']);
        add_action( 'init', [$this, 'register_product_category_taxonomy'], 0 );
    
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
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'product-fields'],
            'taxonomies' => ['product-category'], // Enable categories
            'has_archive' => true,
            'rewrite' => ['slug' => 'products'],
        ]);

    }

    function register_product_category_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Product Categories', 'Taxonomy General Name', 'textdomain' ),
            'singular_name'              => _x( 'Product Category', 'Taxonomy Singular Name', 'textdomain' ),
            'menu_name'                  => __( 'Product Categories', 'textdomain' ),
            'all_items'                  => __( 'All Product Categories', 'textdomain' ),
            'parent_item'                => __( 'Parent Product Category', 'textdomain' ),
            'parent_item_colon'          => __( 'Parent Product Category:', 'textdomain' ),
            'new_item_name'              => __( 'New Product Category Name', 'textdomain' ),
            'add_new_item'               => __( 'Add New Product Category', 'textdomain' ),
            'edit_item'                  => __( 'Edit Product Category', 'textdomain' ),
            'update_item'                => __( 'Update Product Category', 'textdomain' ),
            'view_item'                  => __( 'View Product Category', 'textdomain' ),
            'separate_items_with_commas' => __( 'Separate product categories with commas', 'textdomain' ),
            'add_or_remove_items'        => __( 'Add or remove product categories', 'textdomain' ),
            'choose_from_most_used'      => __( 'Choose from the most used product categories', 'textdomain' ),
            'popular_items'              => __( 'Popular Product Categories', 'textdomain' ),
            'search_items'               => __( 'Search Product Categories', 'textdomain' ),
            'not_found'                  => __( 'Not Found', 'textdomain' ),
            'no_terms'                   => __( 'No product categories', 'textdomain' ),
            'items_list'                 => __( 'Product Categories list', 'textdomain' ),
            'items_list_navigation'      => __( 'Product Categories list navigation', 'textdomain' ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true, // Set to true for hierarchical categories (like regular categories)
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_in_rest'               => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array( 'slug' => 'product-category' ), //Productize the slug
        );
        register_taxonomy( 'product-category', array( 'products' ), $args ); //'product_post_type' needs to be your product post type's name

    }

}
new WP_Product_Describer_Register_Post_Type();