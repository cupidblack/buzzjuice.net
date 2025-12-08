// WoWonder - Trigger sync after message sent

function syncMessageToWordPress(fromUserId, toUserId, message, mediaUrl = null) {
    $.ajax({
        url: '/streams/assets/snc_msg-ww-wp.php',
        type: 'POST',
        data: {
            from_user_id: fromUserId,
            to_user_id: toUserId,
            message: message,
            media_url: mediaUrl
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                console.log('Message synced to WordPress');
            } else {
                console.warn('Sync error:', resp.error);
            }
        },
        error: function(xhr) {
            console.error('AJAX sync error:', xhr.responseText);
        }
    });
}

// Example usage: call after WoWonder message is sent
// syncMessageToWordPress(USER_ID, RECIPIENT_ID, 'Hello!', null);