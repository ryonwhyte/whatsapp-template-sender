jQuery(document).ready(function($) {
    const $form = $('#wts-send-form');
    const $templateSelect = $('#template_name');
    const $parametersContainer = $('#template-parameters');
    const $submitButton = $('#submit');
    const $loadingSpinner = $('#wts-loading');
    const $statusSection = $('#wts-message-status');

    // Enable submit button when form is valid
    function validateForm() {
        const phoneNumber = $('#phone_number').val().trim();
        const templateName = $('#template_name').val();
        
        if (phoneNumber && templateName) {
            $submitButton.prop('disabled', false);
        } else {
            $submitButton.prop('disabled', true);
        }
    }

    // Update template parameters based on selected template
    function updateTemplateParameters() {
        const selectedTemplate = $templateSelect.val();
        
        if (!selectedTemplate || !window.wtsTemplates) {
            $parametersContainer.html('<p class="description">Select a template to see available parameters</p>');
            return;
        }

        const template = window.wtsTemplates.find(t => t.name === selectedTemplate);
        
        if (!template || !template.components) {
            $parametersContainer.html('<p class="description">No parameters required for this template</p>');
            return;
        }

        let parameterCount = 0;
        let bodyText = '';

        // Find body component and count parameters
        template.components.forEach(function(component) {
            if (component.type === 'BODY') {
                bodyText = component.text;
                const matches = bodyText.match(/\{\{\d+\}\}/g);
                parameterCount = matches ? matches.length : 0;
            }
        });

        if (parameterCount === 0) {
            $parametersContainer.html('<p class="description">No parameters required for this template</p>');
            return;
        }

        // Show template preview
        let html = '<div class="template-preview">';
        html += '<h4>Template Preview:</h4>';
        html += '<div class="template-body"><code>' + bodyText + '</code></div>';
        html += '</div>';

        // Create parameter inputs
        html += '<div class="parameter-inputs">';
        html += '<h4>Fill Template Parameters:</h4>';
        
        for (let i = 1; i <= parameterCount; i++) {
            html += '<div class="parameter-input">';
            html += '<label for="param_' + i + '">Parameter {{' + i + '}}:</label>';
            html += '<input type="text" id="param_' + i + '" name="parameters[]" class="regular-text" required />';
            html += '</div>';
        }
        
        html += '</div>';
        
        $parametersContainer.html(html);
    }

    // Handle form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'wts_send_message',
            nonce: wts_ajax.nonce,
            phone_number: $('#phone_number').val().trim(),
            template_name: $('#template_name').val(),
            template_language: $('#template_language').val(),
            parameters: []
        };

        // Collect parameters
        $('input[name="parameters[]"]').each(function() {
            const value = $(this).val().trim();
            if (value) {
                formData.parameters.push(value);
            }
        });

        // Show loading state
        $submitButton.prop('disabled', true);
        $loadingSpinner.show();
        $statusSection.hide();

        // Send AJAX request
        $.post(wts_ajax.ajax_url, formData)
            .done(function(response) {
                let messageClass = response.success ? 'notice-success' : 'notice-error';
                let message = response.message || 'Unknown error occurred';
                
                $statusSection.html(
                    '<div class="notice ' + messageClass + ' inline"><p>' + message + '</p></div>'
                ).show();

                // Clear form if successful
                if (response.success) {
                    $form[0].reset();
                    $parametersContainer.html('<p class="description">Select a template to see available parameters</p>');
                    validateForm();
                }
            })
            .fail(function(xhr, status, error) {
                $statusSection.html(
                    '<div class="notice notice-error inline"><p>Request failed: ' + error + '</p></div>'
                ).show();
            })
            .always(function() {
                $submitButton.prop('disabled', false);
                $loadingSpinner.hide();
            });
    });

    // Event listeners
    $('#phone_number, #template_name').on('input change', validateForm);
    $templateSelect.on('change', function() {
        updateTemplateParameters();
        validateForm();
    });

    // Format phone number as user types
    $('#phone_number').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        
        // Add formatting for display (optional)
        if (value.length >= 10) {
            // Format as: 1 (876) 123-4567
            if (value.length === 10) {
                value = '1' + value; // Add country code for US numbers
            }
            if (value.length === 11 && value.startsWith('1')) {
                const formatted = value.substring(0,1) + ' (' + value.substring(1,4) + ') ' + 
                                value.substring(4,7) + '-' + value.substring(7,11);
                // Don't update the input to avoid cursor jumping, just validate
            }
        }
        
        // Store clean number for validation
        $(this).data('clean-number', value);
    });

    // Phone number validation
    function isValidPhoneNumber(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10 && cleaned.length <= 15;
    }

    // Real-time validation feedback
    $('#phone_number').on('blur', function() {
        const $input = $(this);
        const phone = $input.val().trim();
        
        if (phone && !isValidPhoneNumber(phone)) {
            $input.css('border-color', '#dc3232');
            if (!$input.next('.error-message').length) {
                $input.after('<span class="error-message" style="color: #dc3232; font-size: 12px;">Please enter a valid phone number with country code</span>');
            }
        } else {
            $input.css('border-color', '');
            $input.next('.error-message').remove();
        }
    });

    // Initialize form validation
    validateForm();
});

// Utility functions for phone number formatting
function formatPhoneNumber(phone) {
    const cleaned = phone.replace(/\D/g, '');
    
    if (cleaned.length === 10) {
        return '1' + cleaned; // Add US country code
    }
    
    return cleaned;
}

function validatePhoneNumber(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return /^[1-9]\d{10,14}$/.test(cleaned);
}