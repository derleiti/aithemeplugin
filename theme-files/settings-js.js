/**
 * Derleiti Theme Settings Page JavaScript - Enhanced Version
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        initMediaUploads();
        initColorPickers();
        initTabs();
        initCustomActions();
    });

    /**
     * Initialize all media upload buttons
     */
    function initMediaUploads() {
        const uploadButtons = {
            logo: {
                upload: $('.derleiti-upload-logo'),
                remove: $('.derleiti-remove-logo'),
                input: $('input[name="derleiti_general_options[site_logo]"]'),
                container: $('.derleiti-logo-upload')
            },
            favicon: {
                upload: $('.derleiti-upload-favicon'),
                remove: $('.derleiti-remove-favicon'),
                input: $('input[name="derleiti_general_options[site_favicon]"]'),
                container: $('.derleiti-favicon-upload')
            },
            loginLogo: {
                upload: $('.derleiti-upload-login-logo'),
                remove: $('.derleiti-remove-login-logo'),
                input: $('input[name="derleiti_permissions_options[login_logo]"]'),
                container: $('.derleiti-login-settings')
            },
            loginBackground: {
                upload: $('.derleiti-upload-login-background'),
                remove: $('.derleiti-remove-login-background'),
                input: $('input[name="derleiti_permissions_options[login_background]"]'),
                container: $('.derleiti-login-settings')
            }
        };

        // Initialize each type of upload button
        Object.keys(uploadButtons).forEach(function(key) {
            const options = uploadButtons[key];
            initMediaUploader(
                options.upload,
                options.remove,
                options.input,
                options.container,
                key
            );
        });
    }

    /**
     * Initialize a specific media uploader
     */
    function initMediaUploader(uploadButton, removeButton, inputField, container, uploadType) {
        if (!uploadButton.length) return;

        // Upload Button Click
        uploadButton.on('click', function(e) {
            e.preventDefault();
            let mediaUploader;

            // If the media uploader already exists, reopen it
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Create the media uploader
            mediaUploader = wp.media({
                title: derleitiSettingsData.strings[`${uploadType}UploadTitle`] || 'Select Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false
            });

            // When an image is selected
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Update hidden input with the attachment URL
                inputField.val(attachment.url);

                // Update image preview
                updateImagePreview(container, attachment, uploadType);

                // Send to server using AJAX for processing (optional)
                uploadToServer(attachment, uploadType);
            });

            // Open the media uploader
            mediaUploader.open();
        });

        // Remove Button Click
        removeButton.on('click', function(e) {
            e.preventDefault();
            
            // Confirm before removing
            if (confirm(derleitiSettingsData.strings.logoRemoveConfirm || 'Are you sure you want to remove this image?')) {
                // Clear the input field
                inputField.val('');
                
                // Remove image preview
                container.find('img').remove();
                
                // Show a message or update UI as needed
                if (removeButton.data('removed-callback')) {
                    window[removeButton.data('removed-callback')]();
                }
            }
        });
    }

    /**
     * Update image preview
     */
    function updateImagePreview(container, attachment, uploadType) {
        // Remove existing image if present
        container.find('img').remove();
        
        // Create and append new image
        const img = $('<img>')
            .attr('src', attachment.url)
            .attr('alt', `${uploadType} Image`)
            .css({
                'max-width': '200px', 
                'margin-top': '10px'
            });
        
        container.append(img);
        
        // Show remove button if it exists
        container.find('.derleiti-remove-' + uploadType).show();
    }

    /**
     * Upload to server for additional processing (optional)
     */
    function uploadToServer(attachment, uploadType) {
        // Create FormData object for AJAX upload
        const formData = new FormData();
        formData.append('action', 'derleiti_process_upload');
        formData.append('nonce', derleitiSettingsData.nonce);
        formData.append('type', uploadType);
        formData.append('attachment_id', attachment.id);
        formData.append('url', attachment.url);

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
                    console.log('Upload processed successfully:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload processing error:', error);
            }
        });
    }

    /**
     * Initialize color pickers
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Optional: Custom handling on color change
                    $(this).trigger('color-change', [ui.color.toString()]);
                },
                clear: function() {
                    // Optional: Custom handling on color clear
                    $(this).trigger('color-clear');
                }
            });
        }
    }

    /**
     * Initialize tabbed navigation
     */
    function initTabs() {
        // Tab click handling
        $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Get the tab id from href attribute
            const tabId = $(this).attr('href').replace('#', '');
            
            // Update URL with the tab ID for better UX
            if (history.pushState) {
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=derleiti-settings&tab=' + tabId;
                window.history.pushState({path: newUrl}, '', newUrl);
            }
            
            // Update active tab styling
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show the selected tab content, hide others
            $('.tab-content').hide();
            $('#tab-' + tabId).fadeIn(300);
            
            // Trigger custom event in case other scripts need to respond
            $(document).trigger('derleiti_tab_changed', [tabId]);
        });
        
        // Initialize from URL parameter if present
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            $('.nav-tab[href="#' + tabParam + '"]').trigger('click');
        } else {
            // Default to first tab if no parameter
            $('.nav-tab-wrapper .nav-tab').first().trigger('click');
        }
    }

    /**
     * Initialize custom UI actions
     */
    function initCustomActions() {
        // Settings export button
        $('#export-settings').on('click', function(e) {
            e.preventDefault();
            
            // Show loading indicator
            const $button = $(this);
            $button.prop('disabled', true).addClass('updating-message');
            
            $.ajax({
                url: derleitiSettingsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'derleiti_export_settings',
                    nonce: derleitiSettingsData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create a download link for the JSON file
                        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data));
                        const downloadAnchorNode = document.createElement('a');
                        downloadAnchorNode.setAttribute("href", dataStr);
                        downloadAnchorNode.setAttribute("download", "derleiti-settings-" + new Date().toISOString().slice(0, 10) + ".json");
                        document.body.appendChild(downloadAnchorNode);
                        downloadAnchorNode.click();
                        downloadAnchorNode.remove();
                    } else {
                        alert(response.data.message || 'Error exporting settings');
                    }
                },
                error: function() {
                    alert('Error processing your request');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            });
        });
        
        // Settings import handling
        $('#import-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = new FormData(this);
            formData.append('action', 'derleiti_import_settings');
            formData.append('nonce', derleitiSettingsData.nonce);
            
            // Show loading state
            const $submitButton = $form.find('[type="submit"]');
            $submitButton.prop('disabled', true).addClass('updating-message');
            
            $.ajax({
                url: derleitiSettingsData.ajaxUrl,
                type: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success message and reload page
                        alert(response.data.message || 'Settings imported successfully');
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Error importing settings');
                    }
                },
                error: function() {
                    alert('Error processing your request');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).removeClass('updating-message');
                }
            });
        });
        
        // Initialize any custom toggle switches
        $('.derleiti-toggle-switch').on('change', function() {
            const targetId = $(this).data('toggle-target');
            if (targetId) {
                $('#' + targetId).toggle(this.checked);
            }
        }).each(function() {
            // Set initial state
            const targetId = $(this).data('toggle-target');
            if (targetId) {
                $('#' + targetId).toggle(this.checked);
            }
        });
        
        // Custom link management
        $('#add-custom-link').on('click', function() {
            const linkContainer = $('#custom-menu-links-container');
            const linkIndex = linkContainer.children().length;
            
            const linkHTML = `
                <div class="custom-menu-link">
                    <input type="text" name="derleiti_menu_options[custom_links][${linkIndex}][label]" 
                           placeholder="${derleitiSettingsData.strings.linkLabel || 'Link Label'}" value="">
                    <input type="url" name="derleiti_menu_options[custom_links][${linkIndex}][url]" 
                           placeholder="${derleitiSettingsData.strings.linkUrl || 'Link URL'}" value="">
                    <button type="button" class="button remove-custom-link">${derleitiSettingsData.strings.removeLink || 'Remove'}</button>
                </div>
            `;
            
            linkContainer.append(linkHTML);
        });
        
        // Remove custom link
        $(document).on('click', '.remove-custom-link', function() {
            if (confirm(derleitiSettingsData.strings.confirmDelete || 'Are you sure you want to remove this link?')) {
                $(this).closest('.custom-menu-link').remove();
            }
        });
        
        // Reset settings button
        $('#reset-settings').on('click', function() {
            if (confirm(derleitiSettingsData.strings.confirmReset)) {
                const settingsType = $(this).data('settings-type') || 'all';
                
                $.ajax({
                    url: derleitiSettingsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'derleiti_reset_settings',
                        nonce: derleitiSettingsData.nonce,
                        type: settingsType
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error processing your request');
                    }
                });
            }
        });
    }
})(jQuery);
