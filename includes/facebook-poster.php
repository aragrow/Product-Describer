<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Product_Describer_Facebook_Poster {
    
    public function __construct() {
        add_action('publish_post', [$this, 'post_to_facebook'], 10, 2);
    }

    function get_facebook_page_id() {
        return get_option('facebook_page_id');
    }

    function get_facebook_access_token() {
        return get_option('facebook_access_token');
    }


    // Post to Facebook
    public function post_to_facebook($post_id, $post) {

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

            // Get settings
            $facebook_page_id = $this->get_facebook_page_id();
            $facebook_access_token = $this->get_facebook_access_token();

            if (empty($facebook_page_id) || empty($facebook_access_token)) {
                error_log('Facebook API Error: ' . 'The Facebook API could not retrieve an anwser.');
                new WP_Error('api_response', 'The Facebook API could not retrieve an anwser.');
                return;
            }

            // Get post details
            $title = $post->post_title;
            $link = get_permalink($post_id);
            $excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 55, '...');
            if (has_post_thumbnail($post_id)) 
                $featured_image = wp_get_attachment_url(get_post_thumbnail_id($post_id));
            else
                $featured_image = '';

            // Build message
            $message = "New Post Published: $title\n\n";
            $message .= "$excerpt\n\n";
            $message .= "Read more: $link";

            // Facebook Graph API endpoint
            $url = "https://graph.facebook.com/v17.0/$facebook_page_id/feed";

            // API payload
            $payload = [
                'message' => $message,
                'link' => $link,
                'access_token' => $facebook_access_token,
            ];

            // Include the photo if available
            if (!empty($featured_image)) {
                $photo_id = $this->upload_photo_to_facebook($featured_image);
                if ($photo_id) {
                    $payload['attached_media'] = json_encode([['media_fbid' => $photo_id]]);
                }
            }

            // Send POST request
            $response = wp_remote_post($url, [
                'body' => $payload,
            ]);

            // Log response for debugging
            if (is_wp_error($response)) {
                error_log('Facebook API Error: ' . $response->get_error_message());
                new WP_Error('Facebook API Error: ' . $response->get_error_message());
            } else {
                $response_body = wp_remote_retrieve_body($response);
                error_log('Facebook API Response: ' . $response_body);
            }

        }
        
    }

    // Upload the photo to Facebook
    private function upload_photo_to_facebook($photo_url) {

        $facebook_page_id = $this->get_facebook_page_id();
        $facebook_access_token = $this->get_facebook_access_token();

        $url = "https://graph.facebook.com/v17.0/$facebook_page_id/photos";

        // API payload
        $payload = [
            'url' => $photo_url,
            'access_token' => $facebook_access_token,
            'published' => false, // Upload but do not publish
        ];

        // Send POST request
        $response = wp_remote_post($url, [
            'body' => $payload,
        ]);

        if (is_wp_error($response)) {
            error_log('Facebook Photo Upload Error: ' . $response->get_error_message());
            new WP_Error('Facebook API Error: ' . $response->get_error_message());
            return null;
        } else {
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            return isset($response_body['id']) ? $response_body['id'] : null;
        }
    }


}

new WP_Product_Describer_Facebook_Poster();

