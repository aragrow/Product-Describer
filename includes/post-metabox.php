<?php
if (!defined('ABSPATH')) exit;

class WPProductDescriberMetaBox {

    public function __construct() {
     
        // Add a custom meta box for image descriptions
        add_action('add_meta_boxes', [$this,'gaic_add_meta_box']);

        // Hook to enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Handle the AJAX request for 'generate-content'
        add_action('wp_ajax_generate_ai_content', [$this, 'generate_ai_content_callback']);

    }
   

    function gaic_add_meta_box() {
        error_log('Exec->WPProductDescriberMetaBox.gaic_add_meta_box()');
    
        add_meta_box(
            'generate_content_meta_box',
            'Generate AI Content',
            [$this,'generate_content_meta_box_callback'],
            ['products'], // Custom post type: products
            'side',
            'high'                          // Priority (high for top)
        );
        
    }

    // Callback function to render meta box content
    public function generate_content_meta_box_callback($post) {
        // Add a nonce for security
        wp_nonce_field('generate_content_nonce', 'generate_content_nonce_field');

        // Display the button to generate the description
        echo '<button type="button" id="generate-ai-content-btn" data-post-id="'.esc_attr($post->ID).'" data-post-titl="'.esc_attr($post->post_title).'" class="button"><i class="fas fa-sync-alt"></i> Generate Product AI Description</button>';

    }

    // Enqueue the JavaScript for handling button click
    public function enqueue_admin_scripts($hook) {

        error_log('Exec->WPProductDescriberMetaBox.enqueue_admin_scripts()');

        // Check if we're in development mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // In development mode, use the current date and time as the version
            $version = date('Ymd-His'); // Format: YYYYMMDD-HHMMSS
        } else {
            // In production, use filemtime() for cache busting or a fixed version number
            $style_path = GEMINIDIRURL . 'generate-ai-content-meta-box.css';
            $script_path = GEMINIDIRURL . 'generate-ai-content-meta-box.js';
            $version = filemtime($script_path); // Use the file's last modified timestamp
        }

        // Enqueue custom JavaScript for this meta box
        wp_enqueue_script(
            'gaic-meta-box',
            GEMINIDIRURL . 'generate-ai-content-meta-box.js', // Update with your script URL
            ['jquery'], // jQuery dependency
            $version, // Version number,
            true
        );

        // Pass the AJAX URL and a nonce to the script  
        wp_localize_script(
            'gaic-meta-box',
            'gaicMetaBoxAjax', // Global JS object
            [
                'version' => $version,  // Pass the version number
                'ajaxUrl' => admin_url('admin-ajax.php'),   // WordPress AJAX URL
                'nonce'   => wp_create_nonce('generate_ai_content_meta_box_nonce') // Nonce for security
            ]
        );

        wp_enqueue_style(
            'gaic-meta-box', // Handle for the stylesheet
            GEMINIDIRURL . 'generate-ai-content-meta-box.css', // Path to the stylesheet
            array(), // Dependencies (leave empty if no dependencies)
            $version, // Version of the stylesheet
            'all' // Media type (can be 'all', 'screen', 'print', etc.)
        );

    }

    function generate_ai_content_callback() {

        error_log('Exec->WPProductDescriberMetaBox.generate_ai_content_callback()');

        try {
            // Check nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_ai_content_meta_box_nonce')) {
                error_log('Nonce verification failed');
                wp_send_json_error(['message' => 'Nonce verification failed']);
            }
        
            error_log('$_POST:');
            error_log(print_r($_POST,true));

            $post_id = intval($_POST['post_id']);
            $post = get_post($post_id);
        
            $post_title = $post->post_title;

            // Retrieve the featured image ID of the current post
            $image_id = get_post_thumbnail_id( $post_id );
            $attachment = get_post( $image_id );

            $custom_fields = get_post_meta( $post_id );
            $attributes= '';
        
            // Loop through the custom fields and display them
            foreach ( $custom_fields as $key => $value ) {
                if (strpos($key, 'gaic') !== 0) continue;
                $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
            }
            $post_title = '';

            $api_response = (new GeminiProductDescriberAPIIntegration)->call_python_product_script($attachment->post_content, $post_title, $attributes);

            $jsonData = (new GeminiProductDescriberAPIIntegration)->process_response($api_response);

            if (!isset($jsonData['error'])) {
                $update = $this->udpate_post_ai_generated_content($post, $jsonData);
                return $update;
            } else {
                return $jsonData;
            }

        } catch (Exception $e) {
            
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            return json_encode(['error' => $e->getMessage()]);

        }  

    }

    function udpate_post_ai_generated_content($post, $jsonData) {
        error_log('Exec->WPProductDescriberMetaBox.udpate_post_ai_generated_content()');
        try {

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
                'ID'           => $post->ID,
                'post_status'  => 'pending',
                'post_title' => $post_title,
                'post_name' => $post_name,
                'post_content' => wp_kses_post( $english['Description'] ),
                'post_excerpt' => wp_kses_post( $english['Summary'] )
            ];

            $categories = wp_set_post_terms($post->ID, $category_ids, 'product-category');
            if (is_wp_error($categories)) {
                echo 'Error updating post categoris: ' . $categories->get_error_message();
                exit();
            } 

            $this->save_multi_language_versions ($post->ID, $jsonData['Spanish'], 'sp');
            $this->save_multi_language_versions ($post->ID, $jsonData['Italian'], 'it');
            $this->save_multi_language_versions ($post->ID, $jsonData['Norwegian'], 'no');

            $updated_post_id = wp_update_post( $post_data );
            // Check for errors
            if (is_wp_error($updated_post_id)) {
                echo 'Error updating post: ' . $updated_post_id->get_error_message();
                return json_encode(['error' => $updated_post_id->get_error_message()]);
            }

            $result = json_encode(['success' => 1]);
            error_log(print_r($result, true));
            return $result;

        } catch (Exception $e) {
            
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            return json_encode(['error' => $e->getMessage()]);

        }  

    }

    function save_multi_language_versions ($post_ID, $array, $language) {

        error_log("Exec->WPProductDescriberMetaBox.save_multi_language_versions()");
        error_log("post_ID: {$post_ID}");
        try {
        
            $group_field_key = 'version_'.$language;
            //error_log("group field key: {$group_field_key}");
            $group_value = get_field( $group_field_key, $post_ID );
            //error_log('Group value before');
            //error_log(print_r($group_value,true));

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

            //error_log('Group value after');
            //error_log(print_r($group_value,true));

            $group = update_field( $group_field_key, $group_value, $post_ID );
     /*       if(!$group) {
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
            } */

        } catch (Exception $e) {
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            exit;
        }
    }

}

new WPProductDescriberMetaBox();