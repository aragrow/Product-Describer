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
            Return just the description and nothing else.
        ";
    }

    function set_prompt_post() {
        return 
        "Given an image, perform the following steps:
            Draft a Description:
                Use vibrant, colorful, and descriptive language to highlight the key features and benefits of the forefront item.
                Maintain a professional, engaging tone that resonates with a wide audience.
                Emphasize the product's unique attributes, functionality, and value to customers.
                Avoid describing the background of the image unless it directly complements the item.
                Include Price at the end.
            Summary: Also provide a summary of the description.
            Categories: Finally analize the description and provide to your best capabilities the list of categories that this items belong to. look
                at the the list of categories included and select the a max of three categories if any fit the description, if not
                provide a suggestion. 
                Categories:
                    Collectibles, American Decor, American Serving-ware American Wall Art, Chinese, Chinese Food Storage Chinese Pottery, Roman, Roman Glassware, 
                    Scandinavian, Scandivavian Furniture
            Instrunctions:
                Write an SEO-friendly description an summary to enhance online visibility.
                Write descriptions tailored to inspire trust and excitement in potential buyers.
                Highlight why the product stands out from competitors.
                Use natural language that feels human and relatable.
                Use <h1> for the product title or headline.
                Use <p> for detailed paragraphs describing the item.
                Use <ul> and <li> for listing key features or benefits.
                Exclude the <html>, <head>, or <body> tags in the response.
            Provide Multilingual Descriptions:
                Translate and adapt the enhanced description and summary for the following languages:
                    Norwegian
                    Spanish
                    Italian
            Output:
                Please return a well-organized JSON structure.
            Example of Description:
                Text
                Features
                    - Feature 1
                    - Feature 2
                    - Feature n
                Price
            Example of Json Structure:
            (
                [Title] => <h1>Example</h1>
                [English] => Array
                    (
                        [Description] => <p>Text</p>,
                        [Summary] => text,
                        [Categories] => Array,
                            (
                                [0] => Collectibles,
                                [1] => Scandinavian
                            )

                    )

                [Norwegian] => Array
                    (
                        [Description] => <p>text</p>,
                        [Summary] => text

                    )
            )
            ";
    }

    function call_python_image_script($uri) {
        
        error_log("Exec->GeminiProductDescriberAPIIntegration.call_python_image_script()");

        $python_api_uri = "http://127.0.0.1:5000/describe-image";

        // Define the payload for the request
        $requestPayLoad = [
            'image' => $uri,
            'prompt' => $this->set_prompt_image()
        ];
        error_log('requestPayLoad:');
        error_log(print_r($requestPayLoad,true));
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $python_api_uri); // Replace with the correct endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
        //curl_setopt($ch, CURLOPT_HTTPGET, true); // Specify GET method (optional)
        curl_setopt($ch, CURLOPT_POST, True); // Specify POST method (optional)
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestPayLoad));

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

    function call_python_product_script($description, $post_title, $attributes) {
        
        error_log("Exec->GeminiProductDescriberAPIIntegration.call_python_product_script()");

        $python_api_uri = "http://127.0.0.1:5000/describe-post";
        $fullUrl = $python_api_uri;
        $requestPayload = [];
        // Define the payload for the request
        $requestPayload = [
            'description' => $description,
            'post_title' => $post_title,
            'attributes' => $attributes,
        ];

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl); // Replace with the correct endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        //curl_setopt($ch, CURLOPT_HTTPGET, true); // Specify GET method (optional)
        curl_setopt($ch, CURLOPT_POST, True); // Specify GET method (optional)
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestPayload),);

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

/** 
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
       
        $prompt =  $this->set_prompt_image();

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
                $rawAnswer = $item;
            } else {
                $rawAnswer = '';  // Handle case where resume is missing
            }
        }
    
        curl_close($ch);
        
        $cleanAnswer = str_replace('json', '', $rawAnswer);
        $cleanAnswer = str_replace("```", "", $cleanAnswer);
        $answer = [
            'status' => (isset($responseData['error']))?false:true,
            'answer' => $cleanAnswer
        ];

        //error_log(print_r($anwser,true));

        if(empty($answer['answer'])) {

            // Create a WP_Error instance
            $error = new WP_Error('api_response', 'The API could not retrieve an anwser.');

            // Display the error and exit the script
            wp_die($error->get_error_message(), 'Error', [
                'response' => 502, // HTTP response code
            ]);
        
        }
        //error_log(print_r($answer,true));
        return $answer;
    
    }
*/

}