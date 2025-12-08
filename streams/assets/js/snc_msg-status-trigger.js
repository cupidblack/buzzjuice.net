// Send status/action update to WordPress
function syncStatusToWordPress(action, userId, messageId, reaction = null) {
    $.ajax({
        url: '/wp-content/mu-plugins/snc_msg-status-incoming.php',
        type: 'POST',
        data: {
            action: action, // 'read', 'react', 'pin', 'fav', 'delete'
            user_id: userId,
            message_id: messageId,
            reaction: reaction
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                console.log('Status synced to WordPress:', action);
            } else {
                console.warn('Status sync error:', resp.error);
            }
        },
        error: function(xhr) {
            console.error('AJAX status sync error:', xhr.responseText);
        }
    });
}

// Example usage:
// syncStatusToWordPress('read', USER_ID, MESSAGE_ID);
// syncStatusToWordPress('react', USER_ID, MESSAGE_ID, 'like');