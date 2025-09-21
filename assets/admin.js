/**
 * AISee SEO Admin JavaScript
 * 
 * Handles auto-save functionality for settings page
 */

jQuery(document).ready(function($) {
    'use strict';

    // Auto-save functionality for settings
    $('.aisee-auto-save').on('change input', function() {
        var $field = $(this);
        var fieldName = $field.attr('name');
        var fieldValue = $field.val();
        var $status = $('#aisee-save-status');

        // Show saving status
        $status.show().removeClass('saved error').addClass('saving');
        $status.find('.saving').show();
        $status.find('.saved, .error').hide();

        // Debounce rapid changes
        clearTimeout($field.data('timeout'));
        $field.data('timeout', setTimeout(function() {
            saveSettings(fieldName, fieldValue, $status);
        }, 500));
    });

    /**
     * Save settings via AJAX
     */
    function saveSettings(fieldName, fieldValue, $status) {
        $.ajax({
            url: aiseeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aisee_save_setting',
                nonce: aiseeAdmin.nonce,
                setting: fieldName,
                value: fieldValue
            },
            success: function(response) {
                if (response.success) {
                    $status.removeClass('saving').addClass('saved');
                    $status.find('.saving').hide();
                    $status.find('.saved').show();
                    
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 2000);
                } else {
                    showError($status, response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                showError($status, 'Network error: ' + error);
            }
        });
    }

    /**
     * Show error message
     */
    function showError($status, message) {
        $status.removeClass('saving').addClass('error');
        $status.find('.saving').hide();
        $status.find('.error').text(message).show();
        
        setTimeout(function() {
            $status.fadeOut();
        }, 5000);
    }

    // Add visual feedback for input focus
    $('.aisee-auto-save').on('focus', function() {
        $(this).addClass('aisee-focused');
    }).on('blur', function() {
        $(this).removeClass('aisee-focused');
    });
});