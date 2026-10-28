jQuery(document).ready(function($) {
    if (typeof simescAdmin === 'undefined') {
        return;
    }

    // Add recipient
    $('#mea-add-recipient').on('click', function() {
        var newRow = $('<div class="mea-recipient-row" style="margin-bottom: 5px;">' +
            '<input type="email" name="simesc_recipients[]" value="" class="regular-text" placeholder="email@example.com" />' +
            '<button type="button" class="button mea-remove-recipient">Remove</button>' +
            '</div>');
        $('#mea-recipients-container').append(newRow);
    });
    
    // Remove recipient
    $(document).on('click', '.mea-remove-recipient', function() {
        var container = $('#mea-recipients-container');
        var rows = container.find('.mea-recipient-row');
        
        if (rows.length > 1) {
            $(this).closest('.mea-recipient-row').remove();
        } else {
            // Keep at least one row, just clear it
            $(this).closest('.mea-recipient-row').find('input').val('');
        }
    });
    
    // Validate before save
    $('#post').on('submit', function(e) {
        var hasValidRecipient = false;
        $('#mea-recipients-container input[type="email"]').each(function() {
            var email = $(this).val();
            if (email && email.indexOf('@') > 0) {
                hasValidRecipient = true;
                return false; // break
            }
        });
        
        if (!hasValidRecipient) {
            e.preventDefault();
            alert('Please add at least one valid email recipient.');
            return false;
        }
        
        var subject = $('#simesc_subject').val();
        if (!subject || subject.trim() === '') {
            e.preventDefault();
            alert('Please enter an email subject. This field is required.');
            $('#simesc_subject').focus();
            return false;
        }
        
        // Validate subject length (recommended max 78 characters for email clients)
        if (subject.length > 78) {
            if (!confirm('Email subject is longer than 78 characters. Some email clients may truncate it. Do you want to continue?')) {
                e.preventDefault();
                $('#simesc_subject').focus();
                return false;
            }
        }
    });
    
    // Load saved recipient list when selected
    $('#mea-recipient-list-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var recipientsData = selectedOption.data('recipients');
        
        if (recipientsData && recipientsData.length > 0) {
            // Clear existing recipients
            $('#mea-recipients-container').empty();
            
            // Add recipients from selected list
            $.each(recipientsData, function(index, email) {
                var newRow = $('<div class="mea-recipient-row" style="margin-bottom: 5px;">' +
                    '<input type="email" name="simesc_recipients[]" value="' + email + '" class="regular-text" placeholder="email@example.com" />' +
                    '<button type="button" class="button mea-remove-recipient">Remove</button>' +
                    '</div>');
                $('#mea-recipients-container').append(newRow);
            });
        }
    });
    
    // Save recipients list
    $('#mea-save-recipients').on('click', function() {
        var name = $('#mea-save-recipients-name').val().trim();
        if (!name) {
            alert('Please enter a name for this recipient list.');
            return;
        }
        
        // Collect current recipients
        var recipients = [];
        $('#mea-recipients-container input[type="email"]').each(function() {
            var email = $(this).val().trim();
            if (email && email.indexOf('@') > 0) {
                recipients.push(email);
            }
        });
        
        if (recipients.length === 0) {
            alert('Please add at least one recipient before saving.');
            return;
        }
        
        // Disable button
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');
        
        // AJAX request
        $.ajax({
            url: simescAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simesc_save_recipients',
                nonce: simescAdmin.nonce,
                name: name,
                recipients: recipients
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#mea-save-recipients-name').val('');
                    // Reload page to refresh dropdown
                    location.reload();
                } else {
                    alert(response.data.message || 'Error saving recipients.');
                    $btn.prop('disabled', false).text('Save Current Recipients');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Save Current Recipients');
            }
        });
    });
    
    // Load recipients list
    $('#mea-load-recipients').on('click', function() {
        var selectedKey = $('#mea-saved-recipients').val();
        if (!selectedKey) {
            alert('Please select a recipient list to load.');
            return;
        }
        
        var $option = $('#mea-saved-recipients option:selected');
        var recipients = JSON.parse($option.data('recipients') || '[]');
        
        if (recipients.length === 0) {
            alert('This list has no recipients.');
            return;
        }
        
        // Clear existing recipients
        $('#mea-recipients-container').empty();
        
        // Add loaded recipients
        recipients.forEach(function(email) {
            var newRow = $('<div class="mea-recipient-row" style="margin-bottom: 5px;">' +
                '<input type="email" name="simesc_recipients[]" value="' + email + '" class="regular-text" placeholder="email@example.com" />' +
                '<button type="button" class="button mea-remove-recipient">Remove</button>' +
                '</div>');
            $('#mea-recipients-container').append(newRow);
        });
        
        // Reset dropdown
        $('#mea-saved-recipients').val('');
    });
    
    // Delete recipients list
    $('#mea-delete-recipients').on('click', function() {
        var selectedKey = $('#mea-saved-recipients').val();
        if (!selectedKey) {
            alert('Please select a recipient list to delete.');
            return;
        }
        
        var $option = $('#mea-saved-recipients option:selected');
        var listName = $option.text();
        
        if (!confirm('Are you sure you want to delete "' + listName + '"? This cannot be undone.')) {
            return;
        }
        
        // Disable button
        var $btn = $(this);
        $btn.prop('disabled', true).text('Deleting...');
        
        // AJAX request
        $.ajax({
            url: simescAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simesc_delete_recipients',
                nonce: simescAdmin.nonce,
                key: selectedKey
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload page to refresh dropdown
                    location.reload();
                } else {
                    alert(response.data.message || 'Error deleting recipient list.');
                    $btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Delete');
            }
        });
    });
    
    // Send test / preview email
    $('#simesc-send-test-email').on('click', function() {
        var $btn = $(this);
        var postId = parseInt($btn.data('post-id'), 10) || 0;
        var email = $('#simesc_test_email').val();
        var $status = $('#simesc-test-email-status');
        var i18n = (simescAdmin && simescAdmin.i18n) ? simescAdmin.i18n : {};

        if (!postId) {
            alert(i18n.saveFirst || 'Please save this email first, then send a test.');
            return;
        }

        if (!email || email.indexOf('@') < 1) {
            alert(i18n.enterEmail || 'Please enter a valid test email address.');
            $('#simesc_test_email').focus();
            return;
        }

        $btn.prop('disabled', true).text(i18n.sending || 'Sending…');
        $status.text('').css('color', '');

        $.ajax({
            url: simescAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simesc_send_test_email',
                nonce: simescAdmin.testNonce,
                post_id: postId,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    $status.css('color', 'green').text(response.data.message || 'Sent.');
                } else {
                    $status.css('color', 'red').text((response.data && response.data.message) ? response.data.message : (i18n.errorGeneric || 'Error'));
                }
                $btn.prop('disabled', false).text(i18n.sendTest || 'Send Test / Preview');
            },
            error: function() {
                $status.css('color', 'red').text(i18n.errorGeneric || 'An error occurred. Please try again.');
                $btn.prop('disabled', false).text(i18n.sendTest || 'Send Test / Preview');
            }
        });
    });

    // Admin recipient lists page functionality
    // Add recipient
    $('#mea-admin-add-recipient').on('click', function() {
        var newRow = $('<div class="mea-recipient-row" style="margin-bottom: 5px;">' +
            '<input type="email" name="recipients[]" class="regular-text" placeholder="email@example.com" />' +
            '<button type="button" class="button mea-remove-recipient">Remove</button>' +
            '</div>');
        $('#mea-admin-recipients-container').append(newRow);
    });
    
    // Remove recipient (for admin page)
    $(document).on('click', '#mea-admin-recipients-container .mea-remove-recipient', function() {
        var container = $('#mea-admin-recipients-container');
        var rows = container.find('.mea-recipient-row');
        if (rows.length > 1) {
            $(this).closest('.mea-recipient-row').remove();
        } else {
            $(this).closest('.mea-recipient-row').find('input').val('');
        }
    });
});


