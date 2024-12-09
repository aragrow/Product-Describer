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

    function set_prompt_image() {
        return 
        "Given an image, perform the following steps:
            1. Draft a Description in one paragraph:
            Use vibrant, colorful, and descriptive language to highlight the key features and benefits of the forefront item.
            Maintain a professional, engaging tone that resonates with a wide audience.
            Emphasize the product's unique attributes, functionality, and value to customers.
            Avoid describing the background of the image unless it directly complements the item.
            2. Format the Output:
            Write an SEO-friendly description to enhance online visibility.
            Structure the output using basic HTML tags
            Exclude html tags in the response.
            Additional Considerations:
            Write descriptions tailored to inspire trust and excitement in potential buyers.
            Highlight why the product stands out from competitors.
            Use natural language that feels human and relatable.
        ";
    }

    function set_prompt_post() {
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


    function generate_image_description ( $image_ID, $title, $attributes, $img_uri) {

        error_log("Exec->generate_image_description()");

        $api_url = $this->set_apiuri();
    
        // Set the API key from the environment variable
        $api_key = getenv('GEMINI_API_KEY');
        if (!$api_key) {
            $api_key = $this->set_apikey();
            if (!$api_key) 
                die("API_KEY eis not set.\n");
        }

        $prompt =  $this->set_prompt_post();
        $attachment = get_post($image_ID);
        $image_description = $attachment->post_content;
        if( empty($image_description)) {
            $result = $this->call_python_script($img_uri);
            $description = $result['awnser'];
         }  else {
            $description = $image_description;
         }

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
                            Image Description: {$description}"
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
        
        error_log("Exec->call_python_script()");

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

}