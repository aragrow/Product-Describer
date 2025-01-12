<?php

use function PHPSTORM_META\elementType;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPProductDescriberSavePost {

    public function __construct() {

        // Hook into save_post to trigger description generation when a 'product' post is saved
        add_action('add_attachment', [$this,'detect_user_add_attachment'], 10, 1);
       //add_action('save_post', [$this,'detect_user_saved_post_products_info'], 20, 3);

    }

    // Hook to detect post save for custom post type "product"
    function detect_user_add_attachment( $post_ID ) {

        error_log("Exec->WPProductDescriberSavePost.detect_user_add_attachment()");
        
        try {

            $post = get_post($post_ID);
            //error_log(print_r(get_post($post),true));
            $image_uri = $post->guid;
            $api_response = (new WPProductCopyGenieAPIIntegration)->call_python_image_script($image_uri);
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
    function detect_user_saved_post_products_info ($post_ID, $post, $update) {
        
        error_log("Exec->WPProductDescriberSavePost.detect_user_saved_post_products_info()");

        /* When adding post, 
            saved post is exectuted 
            and 
                $_POST is empty, 
                $_GET has the post type,
                update is blank, 
                auto save is not defined, 
                revision is blank,
                post status is auto-draft */

        /* When save as draft,
            saved post is executed
            and  $_POST is not empty, 
                $_GET action == edit,
                update is 1, 
                auto save is not defined, 
                revision is blank,
                post status is draft */


        error_log('Post');
        error_log(print_r($post,true));

        error_log('Update');
        error_log(print_r($update,true));
        
        if ( defined('DOING_AUTOSAVE') ) {
            error_log("Is AutoSave");
            error_log(DOING_AUTOSAVE);

        }

        error_log('Revision:');
        error_log(wp_is_post_revision( $post_ID ));
        

        error_log('POST');
        error_log(print_r($_POST,true));

        error_log('GET');
        error_log(print_r($_GET,true));

        error_log('Status:');
        error_log($post->post_status);
    }      
        
        
    // Hook to detect post save for custom post type "product"
    function detect_user_saved_post_products ($post_ID, $post, $update) {
        
        error_log("Exec->WPProductDescriberSavePost.detect_user_saved_post_products()");

        // Update the post content
        try {

            if ($post->post_status == 'auto-draft') {
                error_log("Auto Draft");
                return;   
            }

            if ( 'products' != $post->post_type) {
                error_log("Not a product");
                return; 
            }

            // Check if the post is autosave or revision (saved by the system)
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
                error_log("Is AutoSave");
                return;
            }

            // Check if the post is a revision (saved by the system)
            if ( wp_is_post_revision( $post_ID ) ) {
                error_log("Is a Revision");
                return;
            }

            
            // Prevent to execute when the post_content is not blank
            if ( !empty($post->post_content)) {
                error_log("Post Content Not Blank");
                return;
            }

            error_log("Ok to continue with post save");

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

                $api_response = (new WPProductCopyGenieAPIIntegration)->call_python_product_script($attachment->post_content, $post_title, $attributes);
                
                error_log('Api:');
                error_log(print_r($api_response,true));
                error_log('Api Status:');
                error_log($api_response['status']);
                if($api_response['status']) {
                    $answer = $api_response['description']; // If description contains HTML
                    //error_log('Api Anwser:');
                   // error_log($answer);
                   // error_log(gettype($answer));

                    $jsonData = json_decode($answer, true);
                    if ($jsonData === null) {
                        error_log("Error decoding JSON!");
                        exit;
                    } 
                    //error_log('jsonData:');
                    //error_log(print_r($jsonData,true));
                    
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
                        'post_status'  => 'pending',
                        'post_title' => $post_title,
                        'post_name' => $post_name,
                        'post_content' => wp_kses_post( $english['Description'] ),
                        'post_excerpt' => wp_kses_post( $english['Summary'] )
                    ];

                    $updated_post_id = wp_update_post( $post_data );
                    // Check for errors
                    if (is_wp_error($updated_post_id)) {
                        echo 'Error updating post: ' . $updated_post_id->get_error_message();
                        exit();
                    } else {
                        $this->save_multi_language_versions ($post_ID, $jsonData['Spanish'], 'sp');
                        $this->save_multi_language_versions ($post_ID, $jsonData['Italian'], 'it');
                        $this->save_multi_language_versions ($post_ID, $jsonData['Norwegian'], 'no');
                        // Update the categories (terms) for the post
                        $categories = wp_set_post_terms($post_ID, $category_ids, 'product-category');
                        if (is_wp_error($categories)) {
                            echo 'Error updating post categories: ' . $categories->get_error_message();
                            exit();
                        } 
                    }

                    // Update the post


                } # End If Status

            } else {

                $post_data = [
                    'ID'           => $post_ID,
                    'post_status'  => 'draft',
                    'post_title' => $post_title
                ];

                $updated_post_id = wp_update_post( $post_data );

            }  # End If image_ID
        
        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }

    } # End function detect_user_saved_post_products()

    function save_multi_language_versions ($post_ID, $array, $language) {

        error_log("Exec->WPProductDescriberSavePost.save_multi_language_versions()");
        error_log("post_ID: {$post_ID}");
        try {
        
            $group_field_key = 'version_'.$language;
            error_log("group field key: {$group_field_key}");
            $group_value = get_field( $group_field_key, $post_ID );
            error_log('Group value before');
            error_log(print_r($group_value,true));

            $description = wp_kses_post( $array['Description']);
            $summary = wp_kses_post( $array['Summary']);
            
            if (!$description)  {
                // The field update failed
                error_log("description->Is Blank. Field update failed.");
            }
            
            if (!$summary)  {
                // The field update failed
                error_log("summary->Is Blank. Field update failed.");
            } 

            $group_value["description"] = $description; 
            $group_value["summary"] = $summary; 

            error_log('Group value after');
            error_log(print_r($group_value,true));

            $group = update_field( $group_field_key, $group_value, $post_ID );
            if(!$group) {
                error_log("{$group_field_key}->Group update failed.");
                 // Check for specific issues, like invalid sub-field keys or data type mismatch
                $sub_field_keys = array_keys($group_value);
                foreach ($sub_field_keys as $sub_field_key) {
                    $sub_field = get_field_object($sub_field_key, $post_ID);
                    if (!$sub_field) {
                        error_log("Sub-field with key '{$sub_field_key}' not found in post {$post_ID}");
                    } else {
                        error_log("Sub-field with key '{$sub_field_key}' found in post {$post_ID}");
                    }
                }
            } 

        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }
    }
  
}

new WPProductDescriberSavePost();
