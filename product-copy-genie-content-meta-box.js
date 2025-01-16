jQuery(document).ready(function($) {

    $('#exec-prompt-btn').click(function() {
        alert('Executing Prompt.');
        $(this).prop('disabled', true);
        $('.editor-post-publish-button__button').prop('disabled', true);
        $('.editor-post-save-draft').prop('disabled', true);
        $('.interface-interface-skeleton__body').css('opacity', '0.25');
        $(this).find('.fa-sync-alt').addClass('rotate');

        the_post_id = $(this).data('post-id');
        the_prompt = $('#ai_prompt').val();


        $iframe = $('#acf-editor-42_ifr');
        // Ensure the iframe exists in the DOM
        if (!$iframe.length) {
            alert('Unable to process requests.');
            return
        }
            
        // Access the iframe's document and modify its content
        var iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;

        iframeDoc.open();
        iframeDoc.write('Generating Content');
        iframeDoc.close();

        setTimeout(function() {
        //console.log(gaicMetaBoxAjax.ajaxUrl);
        //console.log(gaicMetaBoxAjax.nonce);
        // Make the AJAX request
        $.ajax({
            url: productCopyGenieMetaBoxAjax.ajaxUrl, // The admin AJAX URL
            method: 'POST',
            async: false,  // Set async to false for synchronous request
            data: {
                action: 'execute_ai_prompt',          // The action hook to call
                nonce: productCopyGenieMetaBoxAjax.nonce,      // The nonce for security
                post_id: the_post_id,
                prompt: the_prompt
            },
            beforeSend: function() {
                console.log('API beforeSend');
                // Disable the button to prevent multiple clicks while processing
                
            },
            success: function(response) {
                console.log('API Success');
                console.log(response)
                iframeDoc.open();
                iframeDoc.write(response);
                iframeDoc.close();
                $(this).prop('disabled', false);
                $('.editor-post-publish-button__button').prop('disabled', false);
                $('.editor-post-save-draft').prop('disabled', false);
                $(this).find('.fa-sync-alt').removeClass('rotate');
                $('.interface-interface-skeleton__body').css('opacity', '1');
            },
            error: function(xhr, status, error) {
                console.log('API Request Error');
                console.log(error);
                alert('API Request failed');
                $(this).prop('disabled', false);
                $('.editor-post-publish-button__button').prop('disabled', false);
                $('.editor-post-save-draft').prop('disabled', false);
                $(this).find('.fa-sync-alt').removeClass('rotate');
                $('.interface-interface-skeleton__body').css('opacity', '1');
            },
            complete: function(xhr, status) {
                // This runs after success or error (once request is complete)
                console.log('API Request completed');

            }
        });
        }, 200);
    });

    $('#generate-ai-content-btn').click(function() {
       
        $(this).prop('disabled', true);
        $('.editor-post-publish-button__button').prop('disabled', true);
        $('.editor-post-save-draft').prop('disabled', true);
        $('.interface-interface-skeleton__body').css('opacity', '0.25');
        $(this).find('.fa-sync-alt').addClass('rotate');

        // Ask the user if they are sure about overwriting the current description
        var userConfirmed = window.confirm("Are you sure you want to override the current content with the AI generated content?");
        
        // If the user cancels, stop the process
        if (!userConfirmed) {
            return; // Exit the function, do nothing
        }

        the_post_id = $(this).data('post-id');
        the_post_title = $(this).data('post_title');
        the_post_content = $(this).data('post_content');

        setTimeout(function() {
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
                post_title: the_post_title,
                post_content: the_post_content
            },
            beforeSend: function() {
                console.log('API beforeSend');
                // Disable the button to prevent multiple clicks while processing
                
            },
            success: function(response) {
                console.log('API Success');
                window.location.href = window.location.href; 
            },
            error: function(xhr, status, error) {
                console.log('API Request Error');
                console.log(error);
                alert('API Request failed');
                $(this).prop('disabled', false);
                $('.editor-post-publish-button__button').prop('disabled', false);
                $('.editor-post-save-draft').prop('disabled', false);
                $(this).find('.fa-sync-alt').removeClass('rotate');
                $('.interface-interface-skeleton__body').css('opacity', '1');
            },
            complete: function(xhr, status) {
                // This runs after success or error (once request is complete)
                console.log('API Request completed');

            }
        });
        }, 200);
    });

});