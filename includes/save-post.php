<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Product_Describer_Save_Post {

    public function __construct() {
     
        // Hook into save_post to trigger description generation when a 'product' post is saved
        add_action('save_post_products', [$this,'detect_user_saved_post'], 10, 3);
    }
   
    // Hook to detect post save for custom post type "product"
    function detect_user_saved_post( $post_id, $post, $update ) {
        // Check if the post is autosave or revision (saved by the system)
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        // Check if the post is a revision (saved by the system)
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Ensure we're working with the "product" custom post type
        if ( 'products' !== $post->post_type ) {
            return;
        }

        // Prevent to execute when the post_content is not blank
        if ( !empty($post->post_content) ) {
            return;
        }
                
        // Check if the post was saved by the user (not a system-generated save)
        if ( isset($_POST['post_author']) && $_POST['post_author'] == get_current_user_id() ) {
            
            // Retrieve the featured image ID of the current post
            $featured_image_uri = get_the_post_thumbnail_url( $post_id );
            $post_title = $post->post_title;
            $custom_fields = get_post_meta( $post_id );
            $attributes= '';
        
            // Loop through the custom fields and display them
            foreach ( $custom_fields as $key => $value ) {
                $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
            }
        

            if ( $featured_image_uri ) {
                error_log('Featured image URL: ' . $featured_image_uri);
                $description = (new GeminiProductDescriberAPIIntegration)->generate_image_description($post_title, $attributes, $featured_image_uri );
                //$description = (new GeminiProductDescriberAPIIntegration)->gemini_workflow($post_id);
                // Update the post content
                if($description['status']) {
                    wp_update_post( array(
                        'ID'           => $post_id,
                        'post_content' => $description['anwser'],
                    ) );

                    $this->update_featured_image_metadata( $post_id );
                }

            } else {
                echo 'No featured image set for this post.';
            }
            
        }
    }

    function get_post_featured_image_path( $post_id ) {
        // Get the post thumbnail (featured image) ID
        $featured_image_id = get_post_thumbnail_id( $post_id );

        // If the post has a featured image
        if ( $featured_image_id ) {
            // Get the file path of the featured image
            $featured_image_path = get_attached_file( $featured_image_id );
            
            return $featured_image_path; // Return the file path
        }

        return false; // Return false if no featured image is set
    }

    function update_featured_image_metadata( $post_id ) {
        // Get the featured image ID
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        $post_title = get_the_title( $post_id );

        // Check if the post has a featured image
        if ( ! $thumbnail_id ) {
            return;
        }

        if (!empty(get_the_excerpt( $thumbnail_id )))
            return;

        $img_text = $this->get_first_paragraph( $post_id );

        // Update the alt text (stored as post meta)
        update_post_meta( $thumbnail_id, '_wp_attachment_image_alt', $img_text );

        // Update the caption and description (stored in the attachment post itself)
        $attachment_data = array(
            'ID'            => $thumbnail_id,
            'post_excerpt'  => $img_text,        // Caption is stored in `post_excerpt`
            'post_content'  => $img_text,   // Description is stored in `post_content`
            'post_title'    => $post_title,
        );

        wp_update_post( $attachment_data );
    }

    function get_first_paragraph( $post_id ) {
        // Get the post content
        $post = get_post( $post_id );

        if ( ! $post || empty( $post->post_content ) ) {
            return '';
        }

        // Extract the first paragraph using a regex
        $content = apply_filters( 'the_content', $post->post_content ); // Apply content filters
        preg_match( '/<p>(.*?)<\/p>/', $content, $matches );

        return $matches[1] ?? ''; // Return the first paragraph or an empty string
    }

}

new WP_Product_Describer_Save_Post();
