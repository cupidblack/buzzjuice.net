/**
 * socket-sync.js - Frontend Socket.IO client for WordPress/BuddyBoss live chat sync
 * Works with bluecrown-wp plugin and Node.js server
 */

// You must set wp_user_id and wp_sync.nonce via wp_localize_script in your plugin PHP
const socket = io("https://buzzjuice.net:3002");

socket.on('connect', () => {
  // Join the user's channel for live messaging
  socket.emit('join', { user_id: wp_user_id, platform: 'wordpress' });
});

/**
 * Send a message to another user (BuddyBoss user ID)
 * @param {number} to_id - Recipient user ID
 * @param {string} message - Message content
 * @param {string|null} media_url - Optional media/attachment URL
 */
function sendMessage(to_id, message, media_url = null) {
  socket.emit('send_message', {
    from_id: wp_user_id,
    to_id: to_id,
    message: message,
    media_url: media_url,
    platform: 'wordpress'
  });
}

// Listen for new incoming messages (from Node.js or fallback)
socket.on('new_message', function (data) {
  // Fallback: Insert message via REST API if live sync fails
  fetch('/wp-json/buddyboss-sync/v1/new-message', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wp_sync.nonce
    },
    body: JSON.stringify(data)
  });
});

// Optionally, handle disconnect/reconnect events
socket.on('disconnect', () => {
  console.warn('Disconnected from chat server, will fallback to REST/AJAX.');
});