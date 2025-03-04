/**
 * Derleiti AI Settings JavaScript
 *
 * Handles interactive functionality for the AI settings page.
 *
 * @package Derleiti_Plugin
 * @version 1.1.0
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initPasswordToggles();
        initRangeSliders();
        initConnectionTests();
        initFormSubmission();
        listenForProviderChange();
    });

    /**
     * Initialize password visibility toggles
     */
    function initPasswordToggles() {
        $('.toggle-password').on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            const passwordField = $('#' + targetId);
            const icon = $(this).find('.dashicons');

            if ( passwordField.attr('type') === 'password' ) {
                passwordField.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                $(this).attr('title', derleitiAiSettings.strings.hide);
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                $(this).attr('title', derleitiAiSettings.strings.show);
            }
        });
    }

    /**
     * Initialize range sliders with live value display
     */
    function initRangeSliders() {
        $('.derleiti-range').on('input', function() {
            const value = $(this).val();
            $(this).next('.temperature-value').text(value);
        });
    }

    /**
     * Initialize API connection testing functionality
     */
    function initConnectionTests() {
        $('.test-provider').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const provider = $button.data('provider');

            // Get the corresponding API key field (replace dashes with underscores)
            const apiKeyField = $('#' + provider.replace('-', '_') + '-api-key');

            // Check if API key is provided
            if (apiKeyField.length && apiKeyField.val().trim() === '') {
                showNotice('error', derleitiAiSettings.strings.apiKeyMissing);
                return;
            }

            // Set loading state and clear previous classes/results
            $button.addClass('loading').prop('disabled', true).removeClass('success error');
            $('#derleiti-ai-test-output').text('');
            $('#derleiti-ai-test-results').addClass('hidden');

            // Send AJAX request to test connection
            $.ajax({
                url: derleitiAiSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'derleiti_test_ai_connection',
                   nonce: derleitiAiSettings.nonce,
                   provider: provider
                },
                success: function(response) {
                    if (response.success) {
                        $button.addClass('success');
                        $('#derleiti-ai-test-results').removeClass('hidden');
                        $('#derleiti-ai-test-output').html(
                            '<strong>' + derleitiAiSettings.strings.success + '</strong><br><br>' +
                            response.data.message + '<br><br>' +
                            formatTestResults(response.data)
                        );
                    } else {
                        $button.addClass('error');
                        $('#derleiti-ai-test-results').removeClass('hidden');
                        $('#derleiti-ai-test-output').html(
                            '<strong>' + derleitiAiSettings.strings.error + '</strong><br><br>' +
                            (response.data && response.data.message ? response.data.message : derleitiAiSettings.strings.unknownError)
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.addClass('error');
                    $('#derleiti-ai-test-results').removeClass('hidden');
                    $('#derleiti-ai-test-output').text(derleitiAiSettings.strings.error + ' ' + error);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
    }

    /**
     * Format test results for display
     */
    function formatTestResults(data) {
        let output = '';

        if (data.models && data.models.length) {
            output += 'Available Models:<br>';
            data.models.forEach(function(model) {
                output += '- ' + model + '<br>';
            });
        }

        if (data.engines && data.engines.length) {
            output += 'Available Engines:<br>';
            data.engines.forEach(function(engine) {
                output += '- ' + engine + '<br>';
            });
        }

        return output;
    }

    /**
     * Initialize form submission handling
     */
    function initFormSubmission() {
        $('form').on('submit', function(e) {
            $('#derleiti-ai-settings-notice').addClass('hidden').removeClass('notice-success notice-error');

            const provider = $('#ai-provider').val();
            const apiKey = $('#' + provider + '-api-key').val();

            if ($('#ai-enabled').is(':checked') && !apiKey) {
                e.preventDefault();
                showNotice('error', derleitiAiSettings.strings.providerApiKeyMissing.replace('%s', $('#ai-provider option:selected').text()));
                return false;
            }

            $(window).on('load', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('settings-updated') && urlParams.get('settings-updated') === 'true') {
                    showNotice('success', derleitiAiSettings.strings.settingsSaved);
                }
            });
        });
    }

    /**
     * Listen for provider change to highlight the needed API key
     */
    function listenForProviderChange() {
        $('#ai-provider').on('change', function() {
            const provider = $(this).val();
            $('.api-key-field').removeClass('highlighted-field');
            $('#' + provider + '-api-key').addClass('highlighted-field');

            if ($('#' + provider + '-api-key').length) {
                const $field = $('#' + provider + '-api-key');
                const fieldPosition = $field.offset().top;
                const windowHeight = $(window).height();
                const scrollPosition = $(window).scrollTop();

                if (fieldPosition < scrollPosition || fieldPosition > scrollPosition + windowHeight) {
                    $('html, body').animate({
                        scrollTop: fieldPosition - (windowHeight / 2)
                    }, 500);
                }
            }
        });
        $('#ai-provider').trigger('change');
    }

    /**
     * Show a notice message
     */
    function showNotice(type, message) {
        const $notice = $('#derleiti-ai-settings-notice');
        $notice.removeClass('hidden notice-success notice-error')
        .addClass(type === 'error' ? 'notice-error' : 'notice-success')
        .find('p').text(message);

        $('html, body').animate({
            scrollTop: $notice.offset().top - 30
        }, 500);

        setTimeout(function() {
            $notice.fadeOut(500, function() {
                $(this).addClass('hidden').show();
            });
        }, 5000);
    }

})(jQuery);
