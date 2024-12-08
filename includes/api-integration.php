<?php
if (!defined('ABSPATH')) exit;

class GeminiProductDescriberAPIIntegration {

    public function __construct() {

    }

    function set_apiuri() {
        return get_option('gemini_api_url');
    }

    function set_apikey() {
        return get_option('gemini_api_key');
    }

    function set_prompt() {
        return 
        "Given an image, perform the following steps:
            1. Draft a Description:
            Use vibrant, colorful, and descriptive language to highlight the key features and benefits of the forefront item.
            Maintain a professional, engaging tone that resonates with a wide audience.
            Emphasize the product's unique attributes, functionality, and value to customers.
            Avoid describing the background of the image unless it directly complements the item.
            2. Format the Output:
            Write an SEO-friendly description to enhance online visibility.
            Structure the output using basic HTML tags:
            Use <h1> for the product title or headline.
            Use <p> for detailed paragraphs describing the item.
            Use <ul> and <li> for listing key features or benefits.
            Exclude the <html>, <head>, or <body> tags in the response.
            3. Provide Multilingual Descriptions:
            Translate and adapt the enhanced description for the following languages:
            English
            Spanish
            Italian
            Additional Considerations:
            Write descriptions tailored to inspire trust and excitement in potential buyers.
            Highlight why the product stands out from competitors.
            Use natural language that feels human and relatable.
            Include Price at the end.";
    }


    function generate_image_description ( $title, $attributes, $img_uri) {

        $api_url = $this->set_apiuri();
    
        // Set the API key from the environment variable
        $api_key = getenv('GEMINI_API_KEY');
        if (!$api_key) {
            $api_key = $this->set_apikey();
            if (!$api_key) 
                die("API_KEY eis not set.\n");
        }

        $prompt =  $this->set_prompt();
        
        $description = $this->call_python_script($img_uri);

        $apiURI = $api_url."?key=$api_key";
    
        //error_log("apiURI : $apiURI");

        // Define the payload for the request
        $requestPayload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => "$prompt,
                            Title: $title, 
                            Attibutes: $attributes
                            Image Description: {$description['description']}"
                        ]
                    ]
                ]
            ]
        ];
    
        error_log("requestPayload : ".print_r($requestPayload,true));

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiURI); // Replace with the correct endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload),);
    
        // Disable SSL verification (for local testing only)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        // Execute the request
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return;
        }
    
        $responseJson = json_decode($response, true);
    
        if (isset($responseJson['error'])) {
            error_log("API Error: " . $responseJson['error']['message']);
        } else {
            $item = $responseJson['candidates'][0]['content']['parts'][0]['text'];
            if (isset($item) ) {
                $anwser = $item;
            } else {
                $anwser = '';  // Handle case where resume is missing
            }
        }
    
        curl_close($ch);
        
        $anwser = [
            'status' => (isset($responseData['error']))?false:true,
            'anwser' => $anwser
        ];

        error_log(print_r($anwser,true));

        if(empty($anwser['anwser'])) {

            // Create a WP_Error instance
            $error = new WP_Error('api_response', 'The API could not retrieve an anwser.');

            // Display the error and exit the script
            wp_die($error->get_error_message(), 'Error', [
                'response' => 502, // HTTP response code
            ]);
        
        }

        return $anwser;
    
    }

    function call_python_script($uri) {

        $python_api_uri = "http://127.0.0.1:5000/describe-image";
        $fullUrl = $python_api_uri . "?image=" . urlencode($uri);
        $requestPayload = [];


        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl); // Replace with the correct endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_HTTPGET, true); // Specify GET method (optional)
        //curl_setopt($ch, CURLOPT_POST, true); // Specify GET method (optional)
        //curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload),);

        // Disable SSL verification (for local testing only)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the request
        $response = curl_exec($ch);
        error_log('------ Response --------');
        error_log($response);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return;
        }

        $responseJson = json_decode($response, true);
       
        return $responseJson;
    }

    function serialize_image_for_api($image_id) {
        // Get the image URL by attachment ID
        $image_path = get_attached_file($image_id);
        
        if (!file_exists($image_path)) {
            return new WP_Error('image_not_found', 'Image file not found.');
        }
    
        // Get the image content and encode in Base64
        $image_data = file_get_contents($image_path);
        $base64_image = base64_encode($image_data);
    
        return $base64_image;
    }

    function serialize_image_from_url($image_url) {
        // Fetch the image from the URL
        $response = wp_remote_get($image_url);
    
        // Check for errors
        if (is_wp_error($response)) {
            return new WP_Error('image_fetch_error', 'Failed to fetch the image from the URL: ' . $response->get_error_message());
        }
    
        // Get the image body (binary data)
        $image_data = wp_remote_retrieve_body($response);
    
        if (empty($image_data)) {
            return new WP_Error('image_empty', 'The image data is empty.');
        }
    
        // Encode the image data in Base64
        $base64_image = base64_encode($image_data);
    
        return $base64_image;

    }

/*****************************
 * 
 *  WIP -  UPLOAD IMAGE THEN, AND THEN USE.
 * 
 */

    function upload_to_gemini($image_id) {

        $api_url = $this->set_apiuri();
    
        // Set the API key from the environment variable
        $api_key = getenv('GEMINI_API_KEY');
        if (!$api_key) {
            $api_key = $this->set_apikey();
            if (!$api_key) 
                die("API_KEY eis not set.\n");
        }

        $image_path = get_attached_file($image_id);

        // Check if the file exists
        if (!file_exists($image_path)) 
            return new WP_Error('file_not_found', 'File not found: ' . $image_path);
        
        // Get the file extension and MIME type
        $image_info = wp_check_filetype($image_path);

        if (empty($image_info['type']))
            return new WP_Error('invalid_file', 'Could not determine the MIME type for: ' . $image_path);
        else 
            $mime_type = $image_info['type'];
        
        // Prepare the file for upload
        $file = curl_file_create($image_path, $mime_type, basename($image_path));
    
        // Prepare the payload
        $payload = [
            'file' => $file
        ];
    
        // cURL request to upload file
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: multipart/form-data'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Disable SSL verification (for local testing only)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        // Check for errors
        if ($status_code !== 200) {
            return new WP_Error('upload_failed', 'File upload failed with status code ' . $status_code . ': ' . $response);
        }
    
        $response_data = json_decode($response, true);
    
        // Ensure response is valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Failed to decode response: ' . json_last_error_msg());
        }
    
        return $response_data['uri']; // Assuming the API response includes a 'uri' field
    }
    
    function send_to_gemini($file_uri) {
        $api_url = $this->set_apiuri();
    
        // Set the API key from the environment variable
        $api_key = getenv('GEMINI_API_KEY');
        if (!$api_key) {
            $api_key = $this->set_apikey();
            if (!$api_key) 
                die("API_KEY eis not set.\n");
        }

        $prompt =  $this->set_prompt();
    
        // Prepare the payload
        $payload = [
            'model_name' => 'gemini-1.5-flash',
            'generation_config' => [
                'temperature' => 1,
                'top_p' => 0.95,
                'top_k' => 40,
                'max_output_tokens' => 8192,
                'response_mime_type' => 'text/plain',
            ],
            'history' => [
                [
                    'role' => 'user',
                    'parts' => [ $prompt . ":  " . $file_uri],
                ],
            ],
        ];
    
        // cURL request to send generation request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Disable SSL verification (for local testing only)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        // Check for errors
        if ($status_code !== 200) {
            return new WP_Error('api_error', 'API call failed with status code ' . $status_code . ': ' . $response);
        }
    
        $response_data = json_decode($response, true);
    
        // Ensure response is valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Failed to decode response: ' . json_last_error_msg());
        }
    
        return $response_data['response_text'] ?? ''; // Adjust based on actual response structure
    }
    
    // Example Usage
    function gemini_workflow($post_id) {

        $featured_image_id = get_post_thumbnail_id( $post_id );
    
        // Upload the file
        $file_uri = $this->upload_to_gemini($featured_image_id);
    
        if (is_wp_error($file_uri)) {
            wp_die('Error uploading file: ' . $file_uri->get_error_message());
        }
    
        // Generate content based on the file
        $response = $this->send_to_gemini($file_uri);
    
        if (is_wp_error($response)) {
            wp_die('Error generating content: ' . $response->get_error_message());
        }
    
        wp_die('Generated Content: <pre>' . esc_html($response) . '</pre>');
    }


}