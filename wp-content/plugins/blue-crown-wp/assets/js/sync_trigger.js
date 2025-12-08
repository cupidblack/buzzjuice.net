// WordPress BPBM - Trigger sync after message sent

function syncMessageToWoWonder(fromUserId, toUserId, message, mediaUrl = null) {
    $.ajax({
        url: '/wp-content/mu-plugins/snc_msg-wp-ww.php',
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
                console.log('Message synced to WoWonder');
            } else {
                console.warn('Sync error:', resp.error);
            }
        },
        error: function(xhr) {
            console.error('AJAX sync error:', xhr.responseText);
        }
    });
}

// Example usage: call after BPBM message is sent
// syncMessageToWoWonder(WP_USER_ID, WP_TO_ID, 'Hey!', null);