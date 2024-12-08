jQuery(document).ready(function ($) {
    $('#gemini-generate-description').on('click', function () {
        const imageUrl = $(this).data('image-url');
        const postId = $(this).data('post-id');
        const $responseDiv = $('#gemini-response');

        $responseDiv.text('Generating description...');

        $.ajax({
            url: geminiAjax.ajax_url,
            method: 'POST',
            async: false, // This makes the call synchronous
            data: {
                action: 'generate_description',
                image_url: imageUrl,
                post_id: postId,
            },
            success: function (response) {
                if (response.success) {
                    console_log(response.data.description);
                    $responseDiv.text('Description Created');
                    //$responseDiv.html('<strong>Description:</strong> ' + response.data.description);
                    
                } else {
                    $responseDiv.text('Error: ' + response.data.message);
                }
            },
            error: function () {
                $responseDiv.text('An error occurred while generating the description.');
            },
        });
    });
});
