jQuery(document).ready(function($) {
    $('#generate-ai-content-btn').click(function() {
       
        // Ask the user if they are sure about overwriting the current description
        var userConfirmed = window.confirm("Are you sure you want to override the current content with the AI generated content?");
        
        // If the user cancels, stop the process
        if (!userConfirmed) {
            return; // Exit the function, do nothing
        }

        the_post_id = $(this).data('post-id');
        the_post_title = $(this).data('post-title');
        //console.log(gaicMetaBoxAjax.ajaxUrl);
        //console.log(gaicMetaBoxAjax.nonce);
        // Make the AJAX request
        $.ajax({
            url: productCopyGenieMetaBoxAjax.ajaxUrl, // The admin AJAX URL
            method: 'POST',
            async: false,  // Set async to false for synchronous request
            data: {
                action: 'generate_ai_content',          // The action hook to call
                nonce: productCopyGenieMetaBoxAjax.nonce,      // The nonce for security
                post_id: the_post_id,
                post_title: the_post_title
            },
            beforeSend: function() {
                console.log('API beforeSend');
                // Disable the button to prevent multiple clicks while processing
                $(this).prop('disabled', true);
                $('.editor-post-publish-button__button').prop('disabled', true);
                $('.editor-post-save-draft').prop('disabled', true);
                $(this).find('.fa-sync-alt').addClass('rotate');
                return;
            },
            success: function(response) {
                console.log('API Success');
                window.location.href = window.location.href; 
            },
            error: function(xhr, status, error) {
                console.log('API Request Error');
                console.log(error);
                alert('API Request failed');
            },
            complete: function(xhr, status) {
                // This runs after success or error (once request is complete)
                console.log('API Request completed');
                $(this).prop('disabled', false);
                $('.editor-post-publish-button__button').prop('disabled', false);
                $('.editor-post-save-draft').prop('disabled', false);
                $(this).find('.fa-sync-alt').removeClass('rotate');
            }
        });

    });
});