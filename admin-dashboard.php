<?php
if (!defined('ABSPATH')) {
    exit;
}

function wpai_render_dashboard_page() {
    ?>
    <div class="wrap wpai-dashboard">
        <h1 class="wp-heading-inline">AI Assistant Dashboard</h1>
        
        <div class="wpai-card-grid">
            <div class="wpai-card">
                <div class="wpai-card-header">
                    <h2>Customer Support</h2>
                </div>
                <div class="wpai-card-content">
                    <p>Manage your customer support AI assistants</p>
                    <a href="<?php echo admin_url('admin.php?page=wpai-create-assistant&type=customer-support'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Create Assistant
                    </a>
                </div>
            </div>

            <div class="wpai-card">
                <div class="wpai-card-header">
                    <h2>Email Automation</h2>
                </div>
                <div class="wpai-card-content">
                    <p>Set up email automation AI assistants</p>
                    <a href="<?php echo admin_url('admin.php?page=wpai-create-assistant&type=email-automation'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Create Assistant
                    </a>
                </div>
            </div>

            <div class="wpai-card">
                <div class="wpai-card-header">
                    <h2>On-Site Interactions</h2>
                </div>
                <div class="wpai-card-content">
                    <p>Configure on-site AI interaction assistants</p>
                    <a href="<?php echo admin_url('admin.php?page=wpai-create-assistant&type=on-site-interaction'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Create Assistant
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
