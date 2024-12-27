<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPProductDescriberSavePost {

    public function __construct() {

        // Hook into save_post to trigger description generation when a 'product' post is saved
        add_action('add_attachment', [$this,'detect_user_add_attachment'], 10, 1);
        add_action('save_post', [$this,'detect_user_saved_post_products'], 20, 3);

    }

    // Hook to detect post save for custom post type "product"
    function detect_user_add_attachment( $post_ID ) {

        error_log("Exec->WPProductDescriberSavePost.detect_user_add_attachment()");
        
        try {

            $post = get_post($post_ID);
            //error_log(print_r(get_post($post),true));
            $image_uri = $post->guid;
            $api_response = (new GeminiProductDescriberAPIIntegration)->call_python_image_script($image_uri);
            error_log('Api:');
            error_log(print_r($api_response,true));
            error_log('Api Status:');
            error_log($api_response['status']);

            $content = $api_response['description']; // If description contains HTML

            if($content != 502) {
                $content = sanitize_text_field( $content ); //Use appropriate sanitization based on context
            
                // Update the caption
                $attr = [
                    'ID'           => $post_ID,
                    'post_excerpt' => $content, // Captions are stored in post_excerpt for attachments
                    'post_content' => $content,   // Description
                    'meta_input' => ['_wp_attachment_image_alt' => $content,] // Alt text
                ];
                    error_log(print_r($attr,true));
                    $updated = wp_update_post( $attr );
            }

        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }

    } # End function detect_user_add_attachment()
    
    // Hook to detect post save for custom post type "product"
    function detect_user_saved_post_products ($post_ID, $post, $update) {

        error_log("Exec->WPProductDescriberSavePost.detect_user_saved_post_products()");

        // Update the post content
        try {
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
            if ( !isset($_POST['post_author']) && $_POST['post_author'] != get_current_user_id() ) 
                return;

                // Ensure we're working with the "product" custom post type
            if ( 'products' != $post->post_type ) 
                return;

            error_log("post_type({$post->post_type})");

            // Retrieve the featured image ID of the current post
            $image_ID = get_post_thumbnail_id( $post_ID );
            $attachment = get_post( $image_ID );

            $post_title = $post->post_title;
            $custom_fields = get_post_meta( $post_ID );
            $attributes= '';
        
            // Loop through the custom fields and display them
            foreach ( $custom_fields as $key => $value ) {
                $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
            }

            if ( $image_ID ) {

                $api_response = (new GeminiProductDescriberAPIIntegration)->call_python_product_script($attachment->post_content, $post_title, $attributes);
                
                error_log('Api:');
                error_log(print_r($api_response,true));
                error_log('Api Status:');
                error_log($api_response['status']);
                if($api_response['status']) {
                    $answer = $api_response['description']; // If description contains HTML
                    error_log('Api Anwser:');
                    error_log($answer);
                    error_log(gettype($answer));

                    $jsonData = json_decode($answer, true);
                    if ($jsonData === null) {
                        error_log("Error decoding JSON!");
                        exit;
                    } 
                    error_log('jsonData:');
                    error_log(print_r($jsonData,true));
                    
                    $english = $jsonData['English'];
              
                    // Get category IDs from names
                    $category_ids = [];
                    foreach ($english['Categories'] as $category_name) {
                        $category = get_term_by('name', $category_name, 'product-category');
                        if ($category) {
                            $category_ids[] = $category->term_id; // Add the category ID to the array
                        }
                    }
                    $post_title = sanitize_text_field( $jsonData['Title'] );
                    $post_name = sanitize_title( $post_title );
                    // Prepare the post data array
                    $post_data = [
                        'ID'           => $post_ID,
                        'post_status'  => 'draft',
                        'post_title' => $post_title,
                        'post_name' => $post_name,
                        'post_content' => wp_kses_post( $english['Description'] ),
                        'post_excerpt' => wp_kses_post( $english['Summary'] )
                    ];

                    // Update the post
                    $updated_post_id = wp_update_post( $post_data );
                    // Check for errors
                    if (is_wp_error($updated_post_id)) {
                        echo 'Error updating post: ' . $updated_post_id->get_error_message();
                        exit();
                    } 

                    // Update the categories (terms) for the post
                    $categories = wp_set_post_terms($post_ID, $category_ids, 'product-category');
                    if (is_wp_error($categories)) {
                        echo 'Error updating post categoris: ' . $categories->get_error_message();
                        exit();
                    } 

                    $this->save_multi_language_versions ($post_ID, $jsonData['Spanish'], 'sp');
                    $this->save_multi_language_versions ($post_ID, $jsonData['Italian'], 'it');
                    $this->save_multi_language_versions ($post_ID, $jsonData['Norwegian'], 'no');

                } # End If Status

            } else {

                echo 'No featured image set for this post.';

            }  # End If image_ID
        
        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }

    } # End function detect_user_saved_post_products()
/*
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
*/
    /*
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
*/

    function save_multi_language_versions ($post_ID, $array, $language) {

        error_log("Exec->WPProductDescriberSavePost.save_multi_language_versions()");

        try {
        
            $group_field_key = 'version_'.$language;

            $group_value = get_field( $group_field_key, $post_ID );

            
            $description = wp_kses_post( $array['Description']);
            $summary = wp_kses_post( $array['Summary']);

            $group_value["description_{$language}"] = $description; 
            $group_value["summary_{$language}"] = $summary; 

            $group = update_field( $group_field_key, $group_value, $post_ID );

            if (!$description)  {
                // The field update failed
                error_log("Field update failed.");
            } else  
            error_log(get_field("description_{$language}", $post_ID));
            
            error_log("summary_{$language}");
            $summary = update_field( "summary_{$language}", wp_kses_post( $array['Summary']), $post_ID );
            if (!$summary)  {
                // The field update failed
                error_log("Field update failed.");
            } else
            error_log(get_field("summary_{$language}", $post_ID)); 

        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }
    }
  
}

new WPProductDescriberSavePost();
