<?php
if (!defined('ABSPATH')) exit;

function register_projects_dynamic_block_top_10() {
    register_block_type('custom/projects-top-10', [
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

// Enqueue block assets
add_action('enqueue_block_editor_assets', 'enqueue_projects_block_assets');

function enqueue_projects_block_assets() {
    // Block editor script
    wp_register_script(
        'projects-block-script',
        plugins_url('block.js', __FILE__), // Adjust path to your file
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
        '1.0',
        true
    );

    // Block editor style
    wp_register_style(
        'projects-block-style',
        plugins_url('editor.css', __FILE__), // Adjust path to your file
        [],
        '1.0'
    );
}
