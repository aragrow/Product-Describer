<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPProductCopyGenieAdminUI {

    public function __construct() {

        // Add admin menu
        add_action('admin_menu', [$this, 'product_add_admin_menu']);

        // Register settings
        add_action('admin_init', [$this, 'product_settings_init']);
    }

    // Add the plugin settings page
    function product_add_admin_menu() {
        add_options_page(
            'Gemini Product Describer',
            'Gemini Describer',
            'manage_options',
            'gemini_product_describer',
            [$this, 'product_settings_page']
        );
    }

    // Register plugin settings
    function product_settings_init() {
        register_setting('gemini_product_settings', 'gemini_api_url');
        register_setting('gemini_product_settings', 'gemini_api_key');
        register_setting('gemini_product_settings', 'facebook_page_id');
        register_setting('gemini_product_settings', 'facebook_access_token');
    }

    // Display the settings page
    function product_settings_page() { ?>
        <div class="wrap">
        <h1>Gemini Product Describer Settings</h1>
            <form method="post" action="options.php">
                <h2>Gemini Google API</h1>
                <?php settings_fields('gemini_product_settings'); ?>
                <?php do_settings_sections('gemini_product_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="gemini_api_url">API URL</label></th>
                        <td><input type="text" class="widefat" name="gemini_api_url" id="gemini_api_url"
                                value="<?php echo esc_attr(get_option('gemini_api_url', '')); ?>" 
                                class="regular-text" required></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="gemini_api_key">API Key</label></th>
                        <td><input type="text" class="widefat" name="gemini_api_key" id="gemini_api_key" 
                                value="<?php echo esc_attr(get_option('gemini_api_key', '')); ?>" 
                                class="regular-text" required></td>
                    </tr>
                </table>
                <h2>Auto Post to Facebook</h1>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="facebook_page_id">Facebook Page ID</label></th>
                        <td>
                            <input type="text" class="widefat" name="facebook_page_id" id="facebook_page_id"
                                   value="<?php echo esc_attr(get_option('facebook_page_id')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="facebook_access_token">Facebook Access Token</label></th>
                        <td>
                            <input type="text" class="widefat" name="facebook_access_token" id="facebook_access_token"
                                   value="<?php echo esc_attr(get_option('facebook_access_token')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

        </div>
    <?php }

}

new WPProductCopyGenieAdminUI();