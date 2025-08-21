<?php
if (!defined('ABSPATH')) {
    exit;
}

function wpai_render_create_assistant_page() {
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    
    if (isset($_POST['wpai_create_assistant'])) {
        // Handle form submission
        check_admin_referer('wpai_create_assistant');
        
        $name = sanitize_text_field($_POST['assistant_name']);
        $description = sanitize_textarea_field($_POST['assistant_description']);
        $type = sanitize_text_field($_POST['assistant_type']);
        
        // Save assistant data
        $assistant_data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'settings' => json_encode(array(
                'model' => get_option(WPAI_SETTINGS_KEY)['model'],
                'initial_message' => get_option(WPAI_SETTINGS_KEY)['chat_initial_message']
            )),
            'status' => 'active'
        );
        
        $assistant_manager = WPAI_Assistant_Manager::get_instance();
        $result = $assistant_manager->create_assistant($assistant_data);
        
        if (!is_wp_error($result)) {
            add_settings_error(
                'wpai_messages',
                'wpai_assistant_created',
                'Assistant created successfully!',
                'updated'
            );
        } else {
            add_settings_error(
                'wpai_messages',
                'wpai_assistant_error',
                $result->get_error_message(),
                'error'
            );
        }
    }
    
    ?>
    <div class="wrap wpai-create-assistant">
        <h1 class="wp-heading-inline">Create AI Assistant</h1>
        
        <?php settings_errors('wpai_messages'); ?>
        
        <form method="post" action="" class="wpai-form">
            <?php wp_nonce_field('wpai_create_assistant'); ?>
            
            <div class="wpai-form-field">
                <label for="assistant_name">Assistant Name</label>
                <input type="text" id="assistant_name" name="assistant_name" class="regular-text" required>
            </div>
            
            <div class="wpai-form-field">
                <label for="assistant_description">Description</label>
                <textarea id="assistant_description" name="assistant_description" class="large-text" rows="4" required></textarea>
            </div>
            
            <div class="wpai-form-field">
                <label for="assistant_type">Assistant Type</label>
                <select id="assistant_type" name="assistant_type" required>
                    <option value="">Select assistant type</option>
                    <option value="customer-support" <?php selected($type, 'customer-support'); ?>>Customer Support</option>
                    <option value="email-automation" <?php selected($type, 'email-automation'); ?>>Email Automation</option>
                    <option value="on-site-interaction" <?php selected($type, 'on-site-interaction'); ?>>On-Site Interaction</option>
                </select>
            </div>
            
            <div class="wpai-form-field">
                <input type="submit" name="wpai_create_assistant" class="button button-primary" value="Create Assistant">
            </div>
        </form>
    </div>
    <?php
}
