/**
 * Derleiti Theme Settings Page JavaScript
 */
(function($) {
    // Document ready
    $(document).ready(function() {
        // Logo Upload Handling
        function handleLogoUpload(uploadButton, removeButton, inputField, imageContainer, uploadType) {
            // Upload Button
            uploadButton.on('click', function(e) {
                e.preventDefault();

                // Open media uploader
                const mediaUploader = wp.media({
                    title: derleitiSettingsData.strings[`${uploadType}UploadTitle`],
                    button: {
                        text: 'Auswählen'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Update hidden input
                    inputField.val(attachment.url);

                    // Update image preview
                    if (imageContainer.length) {
                        // Remove existing image if present
                        imageContainer.find('img').remove();
                        
                        // Create and append new image
                        const img = $('<img>')
                            .attr('src', attachment.url)
                            .attr('alt', `${uploadType} Logo`)
                            .css({
                                'max-width': '200px', 
                                'margin-top': '10px'
                            });
                        
                        imageContainer.append(img);
                    }

                    // AJAX upload to server for processing
                    const formData = new FormData();
                    formData.append('action', 'derleiti_upload_logo');
                    formData.append('nonce', derleitiSettingsData.nonce);
                    formData.append('type', uploadType);
                    
                    // Get the file from the media uploader
                    const fileInput = $('<input type="file">');
                    fetch(attachment.url)
                        .then(response => response.blob())
                        .then(blob => {
                            const file = new File([blob], attachment.filename, { type: attachment.mime });
                            formData.append('file', file);

                            // Send AJAX request
                            $.ajax({
                                url: derleitiSettingsData.ajaxUrl,
                                type: 'POST',
                                processData: false,
                                contentType: false,
                                data: formData,
                                success: function(response) {
                                    if (response.success) {
                                        // Optional: Additional processing after server upload
                                        console.log('Logo uploaded successfully');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Upload error:', error);
                                }
                            });
                        });
                });

                // Open the media uploader
                mediaUploader.open();
            });

            // Remove Button
            removeButton.on('click', function(e) {
                e.preventDefault();

                // Confirm removal
                if (confirm(derleitiSettingsData.strings.logoRemoveConfirm)) {
                    // Clear hidden input
                    inputField.val('');

                    // Remove image preview
                    imageContainer.find('img').remove();
                }
            });
        }

        // Initialize logo uploads
        // Site Logo
        handleLogoUpload(
            $('.derleiti-upload-logo'), 
            $('.derleiti-remove-logo'), 
            $('input[name="derleiti_general_options[site_logo]"]'), 
            $('.derleiti-logo-upload'),
            'logo'
        );

        // Favicon
        handleLogoUpload(
            $('.derleiti-upload-favicon'), 
            $('.derleiti-remove-favicon'), 
            $('input[name="derleiti_general_options[site_favicon]"]'), 
            $('.derleiti-favicon-upload'),
            'favicon'
        );

        // Login Logo
        handleLogoUpload(
            $('.derleiti-upload-login-logo'), 
            $('.derleiti-remove-login-logo'), 
            $('input[name="derleiti_permissions_options[login_logo]"]'), 
            $('.derleiti-login-settings'),
            'login_logo'
        );

        // Login Background
        handleLogoUpload(
            $('.derleiti-upload-login-background'), 
            $('.derleiti-remove-login-background'), 
            $('input[name="derleiti_permissions_options[login_background]"]'), 
            $('.derleiti-login-settings'),
            'login_background'
        );

        // Color Picker Initialization (if needed)
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }

        // Tab Navigation (optional enhancement)
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            const tab = $(this).attr('href').replace('#', '');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            // Show/hide tab contents
            $('.tab-content').hide();
            $(`#tab-${tab}`).show();
        });
    });
})(jQuery);
