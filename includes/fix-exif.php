<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**

Explanation:

Hook into wp_handle_upload:
    The code uses the add_filter() function to hook into the wp_handle_upload action. This action is triggered before an uploaded 
    file (including images) is saved to the server.
Check for Images:
    The fix_exif_before_save() function checks if the uploaded file is an image by checking if the $filetype starts with "image/".
Load Image:
    The function loads the image from the temporary file using imagecreatefromstring().
Read Exif Data (with Error Suppression):
    It attempts to read the Exif data using @exif_read_data(). The @ symbol suppresses the warning if Exif reading fails.
Remove Exif Data (if necessary):
    If exif_read_data() returns false (meaning Exif reading failed):
    It creates a new image without Exif data using imagecreatetruecolor() and imagecopy().
    It saves the new image to a temporary file using imagejpeg().
    It updates the $file['tmp_name'] with the path to the temporary file.
Return Modified File Array:
    The function returns the modified $file array, which now contains the path to the image without Exif data if necessary.
How it Works:
    This code intercepts the image upload process before the image is saved to the server.
    It attempts to read the Exif data. If reading fails, it creates a new image without Exif data and updates the file information accordingly.
    This effectively removes the Exif data before the image is saved to the WordPress media library.
Note:
    This code provides a basic implementation. You may need to adjust it based on your specific requirements and image handling needs.
    Make sure to test this code thoroughly in a development environment before implementing it on a production site.
  
 */

class WP_Product_Describer_Fix_Exif {

    public function __construct() {

        error_log("Exit->WP_Product_Describer_Fix_Exif.__construct()");

        add_filter('wp_handle_upload', [$this, 'fix_exif_before_save'], 10, 2);

    }

    /**
     * Hook into the 'wp_handle_upload' action to modify the image before saving.
     */
    function fix_exif_before_save($file, $filetype) {

        error_log("Exec->WP_Product_Describer_Fix_Exif.fix_exif_before_save()");

        // Check if the file is an image
        if (strpos($filetype, 'image/') === 0) {
            // Load the image
            $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
            if (!$image) {
                return $file; // Return if image loading fails
            }

            // Attempt to read Exif data (suppress warnings)
            $exif = @exif_read_data($file['tmp_name']);

            // If Exif reading failed, remove Exif data
            if (!$exif) {
                // Create a new image without Exif data
                $new_image = imagecreatetruecolor(imagesx($image), imagesy($image));
                imagecopy($new_image, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

                // Save the image without Exif data to a temporary file
                $temp_file = tempnam(sys_get_temp_dir(), 'wp_');
                imagejpeg($new_image, $temp_file, 90);
                imagedestroy($new_image);

                // Update the file array with the temporary file
                $file['tmp_name'] = $temp_file;
            }
        }

        return $file;

    }

}

new WP_Product_Describer_Fix_Exif();