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

require_once plugin_dir_path(__FILE__) . 'includes/api-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/custom-post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-ui.php';
require_once plugin_dir_path(__FILE__) . 'includes/facebook-poster.php';
require_once plugin_dir_path(__FILE__) . 'includes/save-post.php';
//require_once plugin_dir_path(__FILE__) . 'guthenberg/top-ten/block.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-metabox.php';

// Register the block
//add_action('init', 'register_projects_dynamic_block_top_10');

// Enqueue JavaScript for AJAX functionality
add_action('admin_enqueue_scripts', 'gemini_enqueue_scripts');
function gemini_enqueue_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('gemini-script', plugin_dir_url(__FILE__) . 'gemini.js', ['jquery'], '1.0', true);
        wp_localize_script('gemini-script', 'geminiAjax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
}

// Handle AJAX request for generating descriptions
add_action('wp_ajax_generate_description', 'gemini_generate_description');
function gemini_generate_description() {
    error_log(print_r($_POST,true));
    $image_ID = intval($_POST['post_id']);
    $featured_image_uri = sanitize_text_field($_POST['image_url']);
    $title = '';
    $attributes= '';
    $description = (new GeminiProductDescriberAPIIntegration)->generate_image_description($image_ID, $title, $attributes, $featured_image_uri);

}

// Register the block
add_action('init', 'register_projects_dynamic_block');

function register_projects_dynamic_block() {
    register_block_type('custom/projects-list', [
        'editor_script'   => 'projects-block-script',
        'editor_style'    => 'projects-block-style',
        'render_callback' => 'render_projects_block',
    ]);
}

// Render callback for the block
function render_projects_block() {
    // Query for the 10 most recent "projects"
    $projects = new WP_Query([
        'post_type'      => 'projects',
        'posts_per_page' => 10,
    ]);

    // If no posts are found
    if (!$projects->have_posts()) {
        return '<p>No projects found.</p>';
    }

    // Generate the HTML output
    ob_start();
    echo '<ul class="projects-list">';
    while ($projects->have_posts()) {
        $projects->the_post();
        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }
    echo '</ul>';
    wp_reset_postdata();
    return ob_get_clean();
}
