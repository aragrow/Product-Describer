<?php
if (!defined('ABSPATH')) exit;

class WPProductCopyGenieMetaBox {

    public function __construct() {

        // Add a custom meta box for image descriptions
        add_action('add_meta_boxes', [$this,'gaic_add_meta_box_product']);
        add_action('add_meta_boxes', [$this,'gaic_add_meta_box_prompt']);

        // Hook to enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Handle the AJAX request for 'generate-content'
        add_action('wp_ajax_generate_ai_content', [$this, 'generate_ai_content_callback']);
        add_action('wp_ajax_execute_ai_prompt', [$this, 'execute_ai_prompt_callback']);
    }

    function gaic_add_meta_box_product() {
        error_log('Exec->WPProductCopyGenieMetaBox.gaic_add_meta_box_product()');
    
        add_meta_box(
            'generate_content_meta_box',
            'Generate AI Content',
            [$this,'generate_content_meta_box_product_callback'],
            ['products'], // Custom post type: products
            'side',
            'high'                          // Priority (high for top)
        );
        
    }

    // Callback function to render meta box content
    public function generate_content_meta_box_product_callback($post) {
        error_log('Exec->WPProductCopyGenieMetaBox.generate_content_meta_box_product_callback()');

        // Add a nonce for security
        wp_nonce_field('generate_content_nonce', 'generate_content_nonce_field');
        echo '<label>What to create?</label><br /><select id="generate-ai-content-select">
            <option value="content_translations" selected>Content and Translations</option>
            <option value="content_only">Content Only</option>
            <option value="translations_only">Translations Only</option>
            </select><br/>';
        // Display the button to generate the description
        echo '<br /><button type="button" id="generate-ai-content-btn" data-post-id="'.esc_attr($post->ID).'" data-post-titl="'.esc_attr($post->post_title).'" class="button"><i class="fas fa-sync-alt"></i> Generate Product AI Description</button>';

    }

    function gaic_add_meta_box_prompt() {
        error_log('Exec->WPProductCopyGenieMetaBox.gaic_add_meta_box_prompt()');
    
        add_meta_box(
            'prompt_meta_box',
            'AI Prompt Research',
            [$this,'prompt_meta_box_callback'],
            ['products'], // Custom post type: products
            'normal',
            'high'                          // Priority (high for top)
        );
        
    }

    // Callback function to render meta box content
    public function prompt_meta_box_callback($post) {
        error_log('Exec->WPProductCopyGenieMetaBox.prompt_meta_box_callback()');

        // Add a nonce for security
        wp_nonce_field('generate_content_nonce', 'generate_content_nonce_field');
        // Display the button to generate the description
        ?>
        <label for="ai_generated_content"><?php _e('Prompt:', 'text-domain'); ?></label>
        <textarea 
            id="ai_prompt" 
            name="ai_prompmt" 
            rows="5" 
            style="width:100%;"></textarea>
        <?php
        echo '<br /><button type="button" id="exec-prompt-btn" data-post-id="'.esc_attr($post->ID).'" class="button"><i class="fas fa-sync-alt"></i> Execute Prompt</button>';

    }

    // Enqueue the JavaScript for handling button click
    public function enqueue_admin_scripts($hook) {

        error_log('Exec->WPProductCopyGenieMetaBox.enqueue_admin_scripts()');

        // Check if we're in development mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // In development mode, use the current date and time as the version
            $version_css = $version_js = date('Ymd-His'); // Format: YYYYMMDD-HHMMSS
        } else {
            // In production, use filemtime() for cache busting or a fixed version number
            $style_path = PRODUCTCOPYGENIEURL . 'product-copy-genie-content-meta-box.css';
            $script_path = PRODUCTCOPYGENIEURL . 'product-copy-genie-content-meta-box.js';
            $version_js = filemtime($script_path); // Use the file's last modified timestamp
            $version_css = filemtime($style_path); // Use the file's last modified timestamp
        }

        // Enqueue custom JavaScript for this meta box
        wp_enqueue_script(
            'productcopy-genie-meta-box',
            PRODUCTCOPYGENIEURL . 'product-copy-genie-content-meta-box.js', // Update with your script URL
            ['jquery'], // jQuery dependency
            $version_js, // Version number,
            true
        );

        // Pass the AJAX URL and a nonce to the script  
        wp_localize_script(
            'productcopy-genie-meta-box',
            'productCopyGenieMetaBoxAjax', // Global JS object
            [
                'version' => $version_js,  // Pass the version number
                'ajaxUrl' => admin_url('admin-ajax.php'),   // WordPress AJAX URL
                'nonce'   => wp_create_nonce('generate_ai_content_meta_box_nonce') // Nonce for security
            ]
        );

        wp_enqueue_style(
            'productcopy-genie-meta-box', // Handle for the stylesheet
            PRODUCTCOPYGENIEURL . 'product-copy-genie-content-meta-box.css', // Path to the stylesheet
            array(), // Dependencies (leave empty if no dependencies)
            $version_css, // Version of the stylesheet
            'all' // Media type (can be 'all', 'screen', 'print', etc.)
        );

    }

    function generate_ai_content_callback() {

        error_log('Exec->WPProductCopyGenieMetaBox.generate_ai_content_callback()');

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
            $post_content = $post->post_content;

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
            //$post_title = '';

            $api_response = (new WPProductCopyGenieAPIIntegration)->call_python_product_script($attachment->post_content, $post_title, $post_content, $attributes);

            $jsonData = (new WPProductCopyGenieAPIIntegration)->process_response($api_response);

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
        error_log('Exec->WPProductCopyGenieMetaBox.udpate_post_ai_generated_content()');
        try {
            error_log(print_r($jsonData, true));

            if( !isset($jsonData['English']) ) {
                error_log('[No return');
                return;
            }
            error_log(print_r($jsonData['English'], true));

            $english = $jsonData['English'];
            $post_content = wp_kses_post( $english['Description'] );

            $post_excerpt = wp_kses_post( $english['Summary'] ); 
            // Replace <p> and </p> with wp:paragraph format
            $post_content = $this->answer_to_guthenberg($post_content);

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
                'post_content' => $post_content,
                'post_excerpt' => $post_excerpt
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

    function answer_to_guthenberg($text){
        $guthenbert = str_replace(
            ['<p>', 
            '</p>', 
            '<ul>',
            '</ul>',
            '<li>',
            '</li>'],
            ['<!-- wp:paragraph --><p>', 
            '</p><!-- /wp:paragraph -->',
            '<!-- wp:list --><ul class="wp-block-list">',
            '</ul><!-- /wp:list -->',
            '<!-- wp:list-item --><li>',
            '</li><!-- /wp:list-item -->'],
            $text
        );

        return $guthenbert;
    }

    function save_multi_language_versions ($post_ID, $array, $language) {

        error_log("Exec->WPProductCopyGenieMetaBox.save_multi_language_versions()");
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

    function execute_ai_prompt_callback() {

        error_log('Exec->WPProductCopyGenieMetaBox.execute_ai_prompt_callback()');

        try {
            // Check nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_ai_content_meta_box_nonce')) {
                error_log('Nonce verification failed');
                wp_send_json_error(['message' => 'Nonce verification failed']);
            }
        
            error_log('$_POST:');
            error_log(print_r($_POST,true));

            $post_id = intval($_POST['post_id']);
            $prompt = sanitize_text_field($_POST['prompt']);

            $post = get_post($post_id);
            $uri = $post->guid;
            $post_title = $post->post_title;
            $post_content = $post->post_content;

            // Retrieve the featured image ID of the current post
            $image_id = get_post_thumbnail_id( $post_id );
            $attachment = get_post( $image_id );
            $image_uri = $attachment->guid;

            $custom_fields = get_post_meta( $post_id );
            $attributes= '';
        
            // Loop through the custom fields and display them
            foreach ( $custom_fields as $key => $value ) {
                if (strpos($key, 'gaic') !== 0) continue;
                $attributes .= $key . ':' . implode( ', ', $value ) . '\n';
            }
            //$post_title = '';
            $api_response = (new WPProductCopyGenieAPIIntegration)->call_python_prompt_script($image_uri, $post_title, $post_content, $prompt);
            
            // Use a regular expression to extract the text between ````html` and `````
            $pattern = '/```html(.*?)```/s';

            error_log(print_r($api_response['description'],true));
            preg_match($pattern, $api_response['description'], $matches);

            if (isset($matches[1])) {
            // Extract the HTML content
            $html_content = $matches[1];

            // Optionally, you can further process the extracted HTML content here
            // (e.g., parse it with DOMDocument, extract specific elements)


            // Define allowed HTML tags and attributes (with table support)
            $allowed_html = array(
                'p' => array(),
                'strong' => array(),
                'a' => array(
                    'href' => array() 
                ),
                'table' => array(
                    'style' => true, // Allow inline styles for table
                ),
                'tr' => array(),
                'th' => array(
                    'style' => true, // Allow inline styles for table cells
                ),
                'td' => array(
                    'style' => true, 
                ),
                'style' => array(
                    'type' => array() // Allow 'type' attribute for <style> tag
                )
            );

            // Sanitize the user input using wp_kses()
            $sanitized_input = wp_kses($html_content, $allowed_html);
            $unescaped_html = htmlspecialchars_decode($sanitized_input);

            if(!empty($unescaped_html))
                echo $unescaped_html; 
            } else {
                echo "No response retrieve.";
            }

        } catch (Exception $e) {
            
            // Handle the exception
            echo "Error: " . $e->getMessage();  // Display the exception message
            return json_encode(['error' => $e->getMessage()]);

        }  

    }

}

new WPProductCopyGenieMetaBox();