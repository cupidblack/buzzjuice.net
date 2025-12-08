// WoWonder - Trigger sync to WordPress (incoming endpoint)

function syncMessageToWordPressIncoming(fromUserId, toUserId, message, mediaUrl = null, wwMsgId) {
    $.ajax({
        url: '/wp-content/mu-plugins/snc_msg-sync-incoming.php',
        type: 'POST',
        data: {
            from_user_id: fromUserId,
            to_user_id: toUserId,
            message: message,
            media_url: mediaUrl,
            ww_msg_id: wwMsgId // NEW: include WoWonder message ID
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                console.log('Message delivered to WordPress inbox');
            } else {
                console.warn('Incoming sync error:', resp.error);
            }
        },
        error: function(xhr) {
            console.error('AJAX incoming sync error:', xhr.responseText);
        }
    });
}

// Example usage: call after WoWonder message is sent
// syncMessageToWordPressIncoming(USER_ID, RECIPIENT_ID, 'Hello!', null);