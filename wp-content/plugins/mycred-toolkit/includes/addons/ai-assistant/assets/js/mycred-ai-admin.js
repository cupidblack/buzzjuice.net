/**
 * myCred AI Assistant Admin Javascript
 *
 * Handles chat communication, dynamically displays message bubbles with micro-animations,
 * updates conversation history, renders typing indicator, and handles suggested queries.
 *
 * @package myCred
 * @subpackage AI
 * @since 1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {
    const chatMessages = document.getElementById('mycred-ai-chat-messages');
    const chatForm = document.getElementById('mycred-ai-chat-form');
    const chatInput = document.getElementById('mycred-ai-chat-input');
    const sendBtn = document.getElementById('mycred-ai-send-btn');

    if (!chatMessages || !chatForm || !chatInput || !sendBtn) {
        return;
    }

    // In-memory conversation history state
    let conversationHistory = [];

    // Helper to scroll messages to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Auto-resize input textarea as user types
    chatInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight - 16) + 'px';
    });

    // Make sure Enter submits, but Shift+Enter inserts new line
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Simple markdown formatting helper to escape HTML and format strong, code, and lists
    function formatMessageContent(text) {
        if (!text) return '';
        
        // Escape HTML
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

        // Format code blocks: `code`
        escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Format bold: **bold** or *bold*
        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');

        // Parse block elements (tables, lists, paragraphs)
        const lines = escaped.split('\n');
        let inList = false;
        let inTable = false;
        let formatted = '';

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();
            
            // Empty line
            if (line === '') {
                if (inList) { formatted += '</ul>'; inList = false; }
                if (inTable) { formatted += '</tbody></table></div>'; inTable = false; }
                formatted += '<br>';
                continue;
            }

            // Table row
            if (line.startsWith('|') && line.endsWith('|')) {
                if (inList) { formatted += '</ul>'; inList = false; }
                
                // Separator row (e.g. |---|---|)
                if (line.match(/^\|[\s\-\:]+\|.*\|$/)) {
                    continue; // Skip rendering separator
                }

                if (!inTable) {
                    inTable = true;
                    formatted += '<div class="mycred-ai-table-wrapper"><table class="mycred-ai-table">';
                    // First row is header
                    let cells = line.split('|').slice(1, -1);
                    formatted += '<thead><tr>';
                    cells.forEach(cell => formatted += `<th>${cell.trim()}</th>`);
                    formatted += '</tr></thead><tbody>';
                } else {
                    let cells = line.split('|').slice(1, -1);
                    formatted += '<tr>';
                    cells.forEach(cell => formatted += `<td>${cell.trim()}</td>`);
                    formatted += '</tr>';
                }
                continue;
            } else {
                if (inTable) { formatted += '</tbody></table></div>'; inTable = false; }
            }

            // List item
            if (line.startsWith('- ') || line.startsWith('* ')) {
                if (!inList) {
                    formatted += '<ul>';
                    inList = true;
                }
                formatted += '<li>' + line.substring(2) + '</li>';
                continue;
            } else {
                if (inList) { formatted += '</ul>'; inList = false; }
            }

            // Normal text
            formatted += line + '<br>';
        }

        if (inList) formatted += '</ul>';
        if (inTable) formatted += '</tbody></table></div>';

        // Remove excessive trailing brs
        escaped = formatted.replace(/(<br>)+$/, '');

        return escaped;
    }

    function getBubbleClass(role) {
        if (role === 'assistant') {
            return 'ai-bubble';
        }
        return `${role}-bubble`;
    }

    function getAvatarClass(role) {
        if (role === 'assistant') {
            return 'assistant-avatar';
        }
        return `${role}-avatar`;
    }

    // Appends a new message bubble to the container
    function appendMessage(role, text, isHtml = false) {
        const messageRow = document.createElement('div');
        messageRow.className = `mycred-ai-message-row ${role}-row`;

        const avatarDiv = document.createElement('div');
        avatarDiv.className = `message-avatar ${getAvatarClass(role)}`;
        if (role === 'assistant' && mycredAi && mycredAi.avatar_url) {
            avatarDiv.innerHTML = `<img src="${mycredAi.avatar_url}" alt="" width="58" height="56" decoding="async" />`;
        } else if (role === 'user') {
            avatarDiv.textContent = (mycredAi && mycredAi.user_initials) ? mycredAi.user_initials : '??';
            if (mycredAi && mycredAi.user_name) {
                avatarDiv.setAttribute('aria-label', mycredAi.user_name);
            }
        } else {
            avatarDiv.textContent = role === 'assistant' ? 'AI' : '?';
        }

        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = `message-bubble ${getBubbleClass(role)}`;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';

        if (isHtml) {
            contentDiv.innerHTML = text;
        } else {
            contentDiv.innerHTML = formatMessageContent(text);
        }

        bubbleDiv.appendChild(contentDiv);
        
        messageRow.appendChild(avatarDiv);
        messageRow.appendChild(bubbleDiv);
        chatMessages.appendChild(messageRow);

        scrollToBottom();
        return messageRow;
    }

    // Appends typing indicator placeholder
    function showTypingIndicator() {
        const messageRow = document.createElement('div');
        messageRow.className = 'mycred-ai-message-row assistant-row typing-placeholder';

        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'message-avatar assistant-avatar';
        if (mycredAi && mycredAi.avatar_url) {
            avatarDiv.innerHTML = `<img src="${mycredAi.avatar_url}" alt="" width="58" height="56" decoding="async" />`;
        } else {
            avatarDiv.textContent = 'AI';
        }

        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = 'message-bubble ai-bubble';

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content mycred-ai-typing-content';

        const indicatorDiv = document.createElement('div');
        indicatorDiv.className = 'mycred-ai-typing-indicator';
        indicatorDiv.innerHTML = '<span></span><span></span><span></span>';

        contentDiv.appendChild(indicatorDiv);
        bubbleDiv.appendChild(contentDiv);
        messageRow.appendChild(avatarDiv);
        messageRow.appendChild(bubbleDiv);
        chatMessages.appendChild(messageRow);

        scrollToBottom();
        return messageRow;
    }

    // Main send message flow
    function sendMessage(messageText) {
        if (!messageText || messageText.trim() === '') {
            return;
        }

        // Add user message to display and to local in-memory history state
        appendMessage('user', messageText);
        conversationHistory.push({
            role: 'user',
            parts: [{
                channel: 'content',
                type: 'text',
                text: messageText
            }]
        });

        // Clear and reset textarea
        chatInput.value = '';
        chatInput.style.height = 'auto';

        // Disable interface
        chatInput.disabled = true;
        sendBtn.disabled = true;

        // Show typing indicator
        const typingIndicator = showTypingIndicator();

        // AJAX request using jQuery
        jQuery.ajax({
            url: mycredAi.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'mycred_ai_chat',
                nonce: mycredAi.nonce,
                message: messageText,
                history: conversationHistory.slice(0, -1) // Send exact previous message history
            },
            success: function (response) {
                // Remove typing indicator
                if (typingIndicator) {
                    typingIndicator.remove();
                }

                // Re-enable interface
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();

                if (response.success) {
                    // Display assistant response
                    appendMessage('assistant', response.data.reply);
                    
                    // Update full historical context in memory from server resolution loop
                    if (response.data.history) {
                        conversationHistory = response.data.history;
                    }
                } else {
                    const errMsg = response.data && response.data.message ? response.data.message : mycredAi.strings.error;
                    const isHtml = !!(response.data && response.data.is_html);
                    appendMessage('assistant', errMsg, isHtml);
                }
            },
            error: function () {
                // Remove typing indicator
                if (typingIndicator) {
                    typingIndicator.remove();
                }

                // Re-enable interface
                chatInput.disabled = false;
                sendBtn.disabled = false;

                appendMessage('assistant', mycredAi.strings.error);
            }
        });
    }

    // Submit handler
    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        sendMessage(text);
    });

    // Suggested query clicks
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('suggested-query')) {
            e.preventDefault();
            const query = e.target.textContent.replace(/"/g, '');
            sendMessage(query);
        }
    });

    // Initial focus on load
    chatInput.focus();
});
