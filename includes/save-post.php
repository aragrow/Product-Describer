<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Product_Describer_Save_Post {

    public function __construct() {
     
        // Hook into save_post to trigger description generation when a 'product' post is saved
        add_action('add_attachment', [$this,'detect_user_add_attachment'], 10, 1);
        add_action('save_post', [$this,'detect_user_saved_post_products'], 20, 3);
    }

    // Hook to detect post save for custom post type "product"
    function detect_user_add_attachment( $post_ID ) {
        error_log("Exec->detect_user_add_attachment()");
        
        $post = get_post($post_ID);
        //error_log(print_r(get_post($post),true));
        $image_uri = $post->guid;
        $description = (new GeminiProductDescriberAPIIntegration)->call_python_script($image_uri );
        if($description != 502) {
            $description = sanitize_text_field( $description['description'] ); //Use appropriate sanitization based on context
            // Update the caption
            $attr = [
                'ID'           => $post_ID,
                'post_excerpt' => $description, // Captions are stored in post_excerpt for attachments
                'post_content' => $description,   // Description
                'meta_input' => ['_wp_attachment_image_alt' => $description,] // Alt text
            ];
                error_log(print_r($attr,true));
                $updated = wp_update_post( $attr );
        }

    }


    // Hook to detect post save for custom post type "product"
    function detect_user_saved_post_products ($post_ID, $post, $update) {

        if ( 'products' != $post->post_type && $post->post_type == 'attachment'  )
            return 
        error_log("Exec->detect_user_saved_post_products()");

        // Check if the post is autosave or revision (saved by the system)
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        // Check if the post is a revision (saved by the system)
        if ( wp_is_post_revision( $post_ID ) ) {
            return;
        }

        
        // Prevent to execute when the post_content is not blank
        if ( !empty($post->post_content) && $post->content != 'TEMP') {
            return;
        }
                
        // Check if the post was saved by the user (not a system-generated save)
        if ( isset($_POST['post_author']) && $_POST['post_author'] == get_current_user_id() ) {

            // Ensure we're working with the "product" custom post type
            if ( 'products' == $post->post_type ) {
                error_log("post_type({$post->post_type})");

                // Retrieve the featured image ID of the current post
                $image_ID = get_post_thumbnail_id( $post_ID );
                $featured_image_uri = get_the_post_thumbnail_url( $post_ID );
                $post_title = $post->post_title;
                $custom_fields = get_post_meta( $post_ID );
                $attributes= '';
            
                // Loop through the custom fields and display them
                foreach ( $custom_fields as $key => $value ) {
                    $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
                }
            
                error_log("featured_image_uri: {$featured_image_uri}");

                if ( $featured_image_uri ) {
                    error_log('Featured image URL: ' . $featured_image_uri);
                    $api_response = (new GeminiProductDescriberAPIIntegration)->generate_image_description($image_ID, $post_title, $attributes, $featured_image_uri );
                    error_log('api_response: '.print_r($api_response['anwser'], true));
                    // Update the post content
                    if($api_response['status']) {
                        $description = wp_kses_post( $api_response['anwser'] ); // If description contains HTML
                        wp_update_post( array(
                            'ID'           => $post_ID,
                            'post_content' => $description,
                        ) );
                    }

                } else {
                    echo 'No featured image set for this post.';
                }

            } elseif ( $post->post_type == 'attachment' ) {

                if ( trim( $post->post_excerpt ) === '' ) {

                    $image_uri = $post->GUID;
                    $post_title = $post->post_title;
                    $custom_fields = get_post_meta( $post_ID );
                    $attributes= '';
            
                    // Loop through the custom fields and display them
                    foreach ( $custom_fields as $key => $value ) {
                        $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
                    }

                    error_log('image URL: ' . $image_uri);
                    $description = (new GeminiProductDescriberAPIIntegration)->call_python_script($image_uri );
        
                    if($description['status']) {
                        $description = sanitize_text_field( $description ); //Use appropriate sanitization based on context
                        // Update the caption
                        $updated = wp_update_post( array(
                            'ID'           => $post_ID,
                            'post_excerpt' => $description, // Captions are stored in post_excerpt for attachments
                            'post_content' => $description,   // Description
                            'meta_input' => array(
                                '_wp_attachment_image_alt' => $description, // Alt text
                            ),
                        ) );
                    }
                }

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
