<?php
if (!defined('ABSPATH')) {
    exit;
}

function wpai_render_test_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Run tests if requested
    $test_results = null;
    if (isset($_POST['wpai_run_tests']) && check_admin_referer('wpai_run_tests')) {
        $tester = WPAI_Assistant_Tester::get_instance();
        $test_results = $tester->run_tests();
    }
    ?>
    <div class="wrap">
        <h1>Berry AI System Tests</h1>
        
        <div class="wpai-test-section">
            <form method="post" action="">
                <?php wp_nonce_field('wpai_run_tests'); ?>
                <p>Click the button below to run system tests. This will verify:</p>
                <ul class="ul-disc">
                    <li>Database connection and table structure</li>
                    <li>Assistant creation, retrieval, update, and deletion</li>
                    <li>Data validation and sanitization</li>
                    <li>Error handling</li>
                </ul>
                <input type="submit" name="wpai_run_tests" class="button button-primary" value="Run System Tests">
            </form>
        </div>

        <?php if ($test_results): ?>
            <div class="wpai-test-results">
                <h2>Test Results</h2>
                <div class="wpai-test-status <?php echo $test_results['success'] ? 'success' : 'error'; ?>">
                    <p><strong>Status: <?php echo $test_results['success'] ? 'All Tests Passed' : 'Tests Failed'; ?></strong></p>
                </div>
                <div class="wpai-test-messages">
                    <h3>Test Messages:</h3>
                    <ul>
                        <?php foreach ($test_results['messages'] as $message): ?>
                            <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <style>
                .wpai-test-section {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .ul-disc {
                    list-style-type: disc;
                    margin-left: 20px;
                }
                .wpai-test-results {
                    margin-top: 30px;
                }
                .wpai-test-status {
                    padding: 15px;
                    border-radius: 4px;
                    margin: 10px 0;
                }
                .wpai-test-status.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .wpai-test-status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .wpai-test-messages {
                    background: white;
                    padding: 20px;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                .wpai-test-messages ul {
                    margin-left: 20px;
                }
                .wpai-test-messages li {
                    margin: 5px 0;
                }
            </style>
        <?php endif; ?>
    </div>
    <?php
}
