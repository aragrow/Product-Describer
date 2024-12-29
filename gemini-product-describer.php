<?php
/*
Plugin Name: Gemini Product Describer
Description: A plugin to describe images using Google Gemini API and update post content dynamically.
Version: 1.1
Author: Your Name
*/

// Ensure the genai library is installed and the gemini-pro-vision model is accessible.
// Replace "Write a short, engaging blog post based on this picture" with the specific prompt for your use case.

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
if (!defined('GEMINIDIRURL')) define('GEMINIDIRURL', plugin_dir_url(__FILE__));


require_once plugin_dir_path(__FILE__) . 'includes/api-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-ui.php';
require_once plugin_dir_path(__FILE__) . 'includes/facebook-poster.php';
require_once plugin_dir_path(__FILE__) . 'includes/save-post.php';
//require_once plugin_dir_path(__FILE__) . 'guthenberg/top-ten/block.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-metabox.php';
//require_once plugin_dir_path(__FILE__) . 'includes/fix-exif.php';

// Register the block
//add_action('init', 'register_projects_dynamic_block_top_10');

// Enqueue JavaScript for AJAX functionality
add_action('admin_enqueue_scripts', 'product_describer_enqueue_scripts');
function product_describer_enqueue_scripts($hook) {
    wp_enqueue_script('font-awesome', 'https://kit.fontawesome.com/3d560e6a09.js', array(), null, true);
}