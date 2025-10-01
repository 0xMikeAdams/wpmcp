jQuery(document).ready(function($) {
    
    // Generate API Key
    $('#generate-api-key').on('click', function() {
        const button = $(this);
        const nameInput = $('#api-key-name');
        const name = nameInput.val().trim();
        
        if (!name) {
            alert('Please enter an API key name.');
            return;
        }
        
        button.prop('disabled', true).html('Generating...');
        
        $.ajax({
            url: wpmcp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpmcp_generate_api_key',
                name: name,
                nonce: wpmcp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNewApiKey(response.data);
                    nameInput.val('');
                    setTimeout(() => location.reload(), 3000);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to generate API key. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('Generate API Key');
            }
        });
    });
    
    // Revoke API Key
    $('.revoke-api-key').on('click', function() {
        const button = $(this);
        const keyItem = button.closest('.wpmcp-api-key-item');
        const keyId = keyItem.data('key-id');
        
        if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
            return;
        }
        
        button.prop('disabled', true).html('Revoking...');
        
        $.ajax({
            url: wpmcp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpmcp_revoke_api_key',
                key_id: keyId,
                nonce: wpmcp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    keyItem.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to revoke API key. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('Revoke');
            }
        });
    });
    
    // Show new API key
    function showNewApiKey(data) {
        const html = `
            <div class="wpmcp-new-api-key">
                <h4>New API Key Generated</h4>
                <p><strong>Name:</strong> ${data.name}</p>
                <p><strong>API Key:</strong></p>
                <div class="wpmcp-api-key-display">${data.api_key}</div>
                <button type="button" class="wpmcp-copy-button" onclick="copyApiKey('${data.api_key}')">
                    Copy to Clipboard
                </button>
                <div class="wpmcp-warning">
                    <strong>Important:</strong> This is the only time you'll see this API key. 
                    Make sure to copy it and store it securely.
                </div>
            </div>
        `;
        
        $('.wpmcp-api-keys-list').prepend(html);
    }
    
    // Copy API key to clipboard
    window.copyApiKey = function(apiKey) {
        navigator.clipboard.writeText(apiKey).then(function() {
            const button = $('.wpmcp-copy-button');
            const originalText = button.text();
            button.text('Copied!').css('background', '#28a745');
            
            setTimeout(function() {
                button.text(originalText).css('background', '');
            }, 2000);
        }).catch(function() {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = apiKey;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            alert('API key copied to clipboard!');
        });
    };
    
    // Auto-dismiss new API key after 30 seconds
    setTimeout(function() {
        $('.wpmcp-new-api-key').fadeOut(500);
    }, 30000);
    
    // Form validation
    $('form').on('submit', function() {
        const allowedTypes = $('input[name="wpmcp_allowed_post_types[]"]:checked');
        
        if (allowedTypes.length === 0) {
            alert('Please select at least one post type to allow access to.');
            return false;
        }
        
        return true;
    });
    
    // Rate limit validation
    $('input[name="wpmcp_rate_limit"]').on('input', function() {
        const value = parseInt($(this).val());
        
        if (value < 1) {
            $(this).val(1);
        } else if (value > 10000) {
            $(this).val(10000);
        }
    });
    
    // Endpoint URL copy functionality
    $('input[readonly]').on('click', function() {
        $(this).select();
        document.execCommand('copy');
        
        const originalBg = $(this).css('background-color');
        $(this).css('background-color', '#d4edda');
        
        setTimeout(() => {
            $(this).css('background-color', originalBg);
        }, 1000);
    });
});