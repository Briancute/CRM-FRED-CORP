<?php
class WPAI_Email_Automation {
    private $settings;

    public function __construct() {
        $this->settings = get_option(WPAI_SETTINGS_KEY);
        
        if ($this->settings['enable_email']) {
            add_filter('wp_mail', array($this, 'process_outgoing_email'));
            add_action('wp_mail_failed', array($this, 'log_email_error'));
            add_action('admin_post_wpai_test_email', array($this, 'handle_test_email'));
        }
    }

    public function process_outgoing_email($args) {
        if (empty($this->settings['openai_api_key'])) {
            return $args;
        }

        // Skip if email is already processed or is a system email
        if (isset($args['headers']['X-WPAI-Processed']) || $this->is_system_email($args['subject'])) {
            return $args;
        }

        try {
            // Generate AI response based on email content
            $enhanced_content = $this->get_ai_enhanced_content($args['message']);
            
            // Update email content
            $args['message'] = $enhanced_content;
            
            // Mark as processed
            if (!isset($args['headers'])) {
                $args['headers'] = array();
            }
            $args['headers']['X-WPAI-Processed'] = 'true';
            
        } catch (Exception $e) {
            error_log('WPAI Email Processing Error: ' . $e->getMessage());
        }

        return $args;
    }

    private function get_ai_enhanced_content($original_content) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $this->settings['openai_api_key'],
            'Content-Type' => 'application/json'
        );

        $prompt = "Please enhance this email while maintaining its core message and making it more engaging and professional:\n\n" . $original_content;

        $body = array(
            'model' => $this->settings['model'],
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert email writer. Enhance the email while maintaining its core message and making it more professional and engaging.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 500,
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

    private function is_system_email($subject) {
        $system_subjects = array(
            'Password Reset',
            'New User Registration',
            'Automatic Update',
            '[WordPress]'
        );

        foreach ($system_subjects as $system_subject) {
            if (stripos($subject, $system_subject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function log_email_error($error) {
        error_log('WPAI Email Error: ' . print_r($error, true));
    }

    public function handle_test_email() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('wpai_test_email');

        $to = get_option('admin_email');
        $subject = 'WPAI Test Email';
        $message = 'This is a test email from WP AI Assistant plugin. The email content should be enhanced by our AI.';

        $result = wp_mail($to, $subject, $message);

        wp_redirect(add_query_arg(
            array('page' => 'wp-ai-assistant', 'email_sent' => $result ? '1' : '0'),
            admin_url('options-general.php')
        ));
        exit;
    }
}
