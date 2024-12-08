<?php
if (!defined('ABSPATH')) exit;

class WP_Product_Describer_Meta_Box {

    public function __construct() {
     
        // Add a custom meta box for image descriptions
        add_action('add_meta_boxes', [$this,'gemini_add_meta_box']);
    }
   

    function gemini_add_meta_box() {
        add_meta_box(
            'gemini_meta_box',
            'Generate Image Description',
            [$this,'gemini_meta_box_callback'],
            ['products'], // Custom post type: products
            'side'
        );
    }

    // Meta box callback function
    function gemini_meta_box_callback($post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
        ?>
        <p>Generate an enticing description for the featured image.</p>
        <?php if ($thumbnail_url): ?>
            <button type="button" class="button button-primary" id="gemini-generate-description"
                    data-image-url="<?php echo esc_url($thumbnail_url); ?>"
                    data-post-id="<?php echo esc_attr($post->ID); ?>">Generate Description</button>
            <div id="gemini-response" style="margin-top: 10px;"></div>
        <?php else: ?>
            <p>Please add a featured image to this post.</p>
        <?php endif;
    }

}

new WP_Product_Describer_Meta_Box();