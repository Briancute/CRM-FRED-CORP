<?php
class WPAI_Chat {
    private $settings;

    public function __construct() {
        $this->settings = get_option(WPAI_SETTINGS_KEY);
        
        if ($this->settings['enable_chat']) {
            add_action('wp_footer', array($this, 'render_chat_interface'));
            add_action('wp_ajax_wpai_chat_request', array($this, 'handle_chat_request'));
            add_action('wp_ajax_nopriv_wpai_chat_request', array($this, 'handle_chat_request'));
        }
    }

    public function render_chat_interface() {
        ?>
        <div id="wpai-chat-widget" class="wpai-chat-widget">
            <div class="wpai-chat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#000000">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"/>
                </svg>
            </div>
            <div class="wpai-chat-container" style="display: none;">
                <div class="wpai-chat-header">
                    <h3>Berry</h3>
                    <button class="wpai-close-chat">&times;</button>
                </div>
                <div class="wpai-chat-messages">
                    <div class="wpai-message ai">
                        <p><?php echo esc_html($this->settings['chat_initial_message']); ?></p>
                    </div>
                </div>
                <div class="wpai-chat-input">
                    <textarea placeholder="Type your message..." rows="1"></textarea>
                    <button class="wpai-send-message">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#006400">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                <div class="wpai-credits">
                    <p>Developed by Brian and Vladimir</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_chat_request() {
        check_ajax_referer('wpai-nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message']);
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
        }

        try {
            $response = $this->get_ai_response($message);
            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function get_ai_response($message) {
        $api_key = $this->settings['openai_api_key'];
        
        if (empty($api_key)) {
            throw new Exception('OpenAI API key is not configured');
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );

        $body = array(
            'model' => $this->settings['model'],
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful customer support assistant.'
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 150,
            'temperature' => 0.7
        );

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            throw new Exception($body['error']['message']);
        }

        return $body['choices'][0]['message']['content'];
    }
}
