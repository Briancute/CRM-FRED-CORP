<?php
class WPAI_Customer_Support {
    private $settings;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->settings = get_option(WPAI_SETTINGS_KEY);
        $this->table_name = $wpdb->prefix . 'wpai_tickets';
        
        if ($this->settings['enable_support']) {
            add_action('init', array($this, 'register_post_type'));
            add_action('wp_ajax_wpai_create_ticket', array($this, 'create_ticket'));
            add_action('wp_ajax_nopriv_wpai_create_ticket', array($this, 'create_ticket'));
            add_action('wp_ajax_wpai_get_ticket_response', array($this, 'get_ticket_response'));
            add_action('admin_menu', array($this, 'add_tickets_menu'));
            
            // Create tickets table if it doesn't exist
            $this->create_tickets_table();
        }
    }

    public function create_tickets_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id varchar(50) NOT NULL,
            user_email varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'open',
            ai_response text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_post_type() {
        register_post_type('wpai_ticket', array(
            'labels' => array(
                'name' => 'Support Tickets',
                'singular_name' => 'Support Ticket'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title', 'editor', 'custom-fields')
        ));
    }

    public function create_ticket() {
        check_ajax_referer('wpai-nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);
        
        if (empty($email) || empty($subject) || empty($message)) {
            wp_send_json_error('All fields are required');
        }

        try {
            // Generate ticket ID
            $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            // Get AI response
            $ai_response = $this->get_ai_response($message);
            
            // Insert ticket
            global $wpdb;
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'ticket_id' => $ticket_id,
                    'user_email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'ai_response' => $ai_response
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                throw new Exception('Failed to create ticket');
            }

            // Send confirmation email
            $this->send_confirmation_email($email, $ticket_id, $subject, $ai_response);
            
            wp_send_json_success(array(
                'ticket_id' => $ticket_id,
                'ai_response' => $ai_response
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function get_ai_response($message) {
        if (empty($this->settings['openai_api_key'])) {
            throw new Exception('OpenAI API key is not configured');
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $this->settings['openai_api_key'],
            'Content-Type' => 'application/json'
        );

        $body = array(
            'model' => $this->settings['model'],
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful customer support representative. Provide clear, concise, and helpful responses to customer inquiries.'
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 250,
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

    private function send_confirmation_email($email, $ticket_id, $subject, $ai_response) {
        $to = $email;
        $email_subject = "Support Ticket Created - $ticket_id";
        $message = "Thank you for contacting us!\n\n";
        $message .= "Your ticket ID is: $ticket_id\n\n";
        $message .= "Regarding: $subject\n\n";
        $message .= "AI Assistant Response:\n$ai_response\n\n";
        $message .= "We'll review your ticket and get back to you if needed.\n";
        $message .= "Please keep this ticket ID for future reference.";

        wp_mail($to, $email_subject, $message);
    }

    public function get_ticket_response() {
        check_ajax_referer('wpai-nonce', 'nonce');
        
        $ticket_id = sanitize_text_field($_POST['ticket_id']);
        
        if (empty($ticket_id)) {
            wp_send_json_error('Ticket ID is required');
        }

        global $wpdb;
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE ticket_id = %s",
            $ticket_id
        ));

        if (!$ticket) {
            wp_send_json_error('Ticket not found');
        }

        wp_send_json_success(array(
            'ai_response' => $ticket->ai_response
        ));
    }

    public function add_tickets_menu() {
        add_submenu_page(
            'options-general.php',
            'Support Tickets',
            'Support Tickets',
            'manage_options',
            'wpai-tickets',
            array($this, 'render_tickets_page')
        );
    }

    public function render_tickets_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $tickets = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        
        include(WPAI_PLUGIN_DIR . 'admin/tickets-page.php');
    }
}
