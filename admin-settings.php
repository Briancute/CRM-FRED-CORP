<?php
// Add admin menu
function wpai_add_admin_menu() {
    add_options_page(
        'WP AI Assistant Settings',
        'AI Assistant',
        'manage_options',
        'wp-ai-assistant',
        'wpai_settings_page'
    );
}

// Register settings
function wpai_register_settings() {
    register_setting('wpai_settings', WPAI_SETTINGS_KEY);
    
    add_settings_section(
        'wpai_general_section',
        'General Settings',
        'wpai_general_section_callback',
        'wp-ai-assistant'
    );
    
    add_settings_field(
        'openai_api_key',
        'OpenAI API Key',
        'wpai_api_key_callback',
        'wp-ai-assistant',
        'wpai_general_section'
    );
    
    add_settings_field(
        'enable_features',
        'Enable Features',
        'wpai_features_callback',
        'wp-ai-assistant',
        'wpai_general_section'
    );
    
    add_settings_field(
        'chat_settings',
        'Chat Settings',
        'wpai_chat_settings_callback',
        'wp-ai-assistant',
        'wpai_general_section'
    );
}

// Section callback
function wpai_general_section_callback() {
    echo '<p>Configure your AI Assistant settings below.</p>';
}

// Settings field callbacks
function wpai_api_key_callback() {
    $settings = get_option(WPAI_SETTINGS_KEY);
    $api_key = isset($settings['openai_api_key']) ? $settings['openai_api_key'] : '';
    ?>
    <input type="password" 
           name="<?php echo WPAI_SETTINGS_KEY; ?>[openai_api_key]" 
           value="<?php echo esc_attr($api_key); ?>" 
           class="regular-text">
    <p class="description">Enter your OpenAI API key. Get one from <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Dashboard</a>.</p>
    <?php
}

function wpai_features_callback() {
    $settings = get_option(WPAI_SETTINGS_KEY);
    $enable_chat = isset($settings['enable_chat']) ? $settings['enable_chat'] : true;
    $enable_email = isset($settings['enable_email']) ? $settings['enable_email'] : true;
    $enable_support = isset($settings['enable_support']) ? $settings['enable_support'] : true;
    ?>
    <label>
        <input type="checkbox" 
               name="<?php echo WPAI_SETTINGS_KEY; ?>[enable_chat]" 
               value="1" 
               <?php checked($enable_chat, true); ?>>
        Enable Chat Widget
    </label><br>
    <label>
        <input type="checkbox" 
               name="<?php echo WPAI_SETTINGS_KEY; ?>[enable_email]" 
               value="1" 
               <?php checked($enable_email, true); ?>>
        Enable Email Automation
    </label><br>
    <label>
        <input type="checkbox" 
               name="<?php echo WPAI_SETTINGS_KEY; ?>[enable_support]" 
               value="1" 
               <?php checked($enable_support, true); ?>>
        Enable Customer Support
    </label>
    <?php
}

function wpai_chat_settings_callback() {
    $settings = get_option(WPAI_SETTINGS_KEY);
    $initial_message = isset($settings['chat_initial_message']) 
        ? $settings['chat_initial_message'] 
        : 'Hello! How can I help you today?';
    $model = isset($settings['model']) ? $settings['model'] : 'gpt-3.5-turbo';
    ?>
    <p>
        <label>Initial Message:<br>
            <input type="text" 
                   name="<?php echo WPAI_SETTINGS_KEY; ?>[chat_initial_message]" 
                   value="<?php echo esc_attr($initial_message); ?>" 
                   class="regular-text">
        </label>
    </p>
    <p>
        <label>GPT Model:<br>
            <select name="<?php echo WPAI_SETTINGS_KEY; ?>[model]">
                <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
            </select>
        </label>
    </p>
    <?php
}

// Render settings page
function wpai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wpai_settings');
            do_settings_sections('wp-ai-assistant');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}
