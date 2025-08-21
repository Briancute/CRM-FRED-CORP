jQuery(document).ready(function($) {
    const chatWidget = $('#wpai-chat-widget');
    const chatIcon = chatWidget.find('.wpai-chat-icon');
    const chatContainer = chatWidget.find('.wpai-chat-container');
    const chatMessages = chatWidget.find('.wpai-chat-messages');
    const chatInput = chatWidget.find('textarea');
    const sendButton = chatWidget.find('.wpai-send-message');
    const closeButton = chatWidget.find('.wpai-close-chat');

    // Toggle chat container
    chatIcon.on('click', function() {
        chatContainer.slideToggle(300, function() {
            if ($(this).is(':visible')) {
                $('body').addClass('chat-open');
                chatInput.focus();
            } else {
                $('body').removeClass('chat-open');
            }
        });
    });

    // Close chat
    closeButton.on('click', function() {
        chatContainer.slideUp(300, function() {
            $('body').removeClass('chat-open');
        });
    });

    // Handle window resize
    let windowWidth = $(window).width();
    $(window).on('resize', function() {
        const newWindowWidth = $(window).width();
        if (newWindowWidth !== windowWidth) {
            windowWidth = newWindowWidth;
            if (windowWidth > 768) {
                $('body').removeClass('chat-open');
                chatContainer.css('display', '');
            }
        }
    });

    // Auto-resize textarea
    chatInput.on('input', function() {
        this.style.height = 'auto';
        const newHeight = Math.min(this.scrollHeight, 100); // Max height of 100px
        this.style.height = newHeight + 'px';
    });

    // Handle message sending
    function sendMessage() {
        const message = chatInput.val().trim();
        
        if (!message) return;

        // Add user message to chat
        appendMessage(message, 'user');
        
        // Clear input
        chatInput.val('').trigger('input');

        // Show loading message
        const loadingMessage = $('<div class="wpai-message ai loading"><p>Thinking...</p></div>');
        chatMessages.append(loadingMessage);
        scrollToBottom();

        // Send to server
        $.ajax({
            url: wpai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpai_chat_request',
                nonce: wpai_ajax.nonce,
                message: message
            },
            success: function(response) {
                loadingMessage.remove();
                if (response.success) {
                    appendMessage(response.data, 'ai');
                } else {
                    appendMessage('Sorry, I encountered an error. Please try again.', 'ai');
                }
            },
            error: function() {
                loadingMessage.remove();
                appendMessage('Sorry, I encountered an error. Please try again.', 'ai');
            }
        });
    }

    // Append message to chat
    function appendMessage(message, sender) {
        const messageElement = $(`
            <div class="wpai-message ${sender}">
                <p>${message}</p>
            </div>
        `);
        chatMessages.append(messageElement);
        scrollToBottom();
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // Send message on button click
    sendButton.on('click', sendMessage);

    // Send message on Enter (but allow Shift+Enter for new line)
    chatInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
