<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPAI_Assistant_Tester {
    private static $instance = null;
    private $assistant_manager;
    private $last_error = null;

    private function __construct() {
        $this->assistant_manager = WPAI_Assistant_Manager::get_instance();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run_tests() {
        $results = array(
            'success' => true,
            'messages' => array()
        );

        // Test database connection
        if (!$this->test_database_connection()) {
            $results['success'] = false;
            $results['messages'][] = 'Database connection test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Database connection test passed';

        // Test table creation
        if (!$this->test_table_creation()) {
            $results['success'] = false;
            $results['messages'][] = 'Table creation test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Table creation test passed';

        // Test assistant creation
        $assistant_id = $this->test_assistant_creation();
        if (!$assistant_id) {
            $results['success'] = false;
            $results['messages'][] = 'Assistant creation test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Assistant creation test passed';

        // Test assistant retrieval
        if (!$this->test_assistant_retrieval($assistant_id)) {
            $results['success'] = false;
            $results['messages'][] = 'Assistant retrieval test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Assistant retrieval test passed';

        // Test assistant update
        if (!$this->test_assistant_update($assistant_id)) {
            $results['success'] = false;
            $results['messages'][] = 'Assistant update test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Assistant update test passed';

        // Test assistant deletion
        if (!$this->test_assistant_deletion($assistant_id)) {
            $results['success'] = false;
            $results['messages'][] = 'Assistant deletion test failed: ' . $this->last_error;
            return $results;
        }
        $results['messages'][] = 'Assistant deletion test passed';

        return $results;
    }

    private function test_database_connection() {
        global $wpdb;
        
        if (!$wpdb->check_connection()) {
            $this->last_error = 'Could not connect to the database';
            return false;
        }
        
        return true;
    }

    private function test_table_creation() {
        global $wpdb;
        
        $this->assistant_manager->create_tables();
        
        $table_name = $wpdb->prefix . 'wpai_assistants';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            $this->last_error = 'Assistant table was not created';
            return false;
        }
        
        return true;
    }

    private function test_assistant_creation() {
        $test_data = array(
            'name' => 'Test Assistant',
            'description' => 'This is a test assistant',
            'type' => 'customer-support',
            'settings' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'initial_message' => 'Hello, I am a test assistant'
            ))
        );
        
        $result = $this->assistant_manager->create_assistant($test_data);
        
        if (is_wp_error($result)) {
            $this->last_error = $result->get_error_message();
            return false;
        }
        
        return $result;
    }

    private function test_assistant_retrieval($assistant_id) {
        $assistants = $this->assistant_manager->get_assistants(array(
            'limit' => 1
        ));
        
        if (empty($assistants)) {
            $this->last_error = 'Could not retrieve created assistant';
            return false;
        }
        
        return true;
    }

    private function test_assistant_update($assistant_id) {
        $update_data = array(
            'name' => 'Updated Test Assistant',
            'description' => 'This is an updated test assistant',
            'type' => 'customer-support',
            'settings' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'initial_message' => 'Hello, I am an updated test assistant'
            )),
            'status' => 'active'
        );
        
        $result = $this->assistant_manager->update_assistant($assistant_id, $update_data);
        
        if (is_wp_error($result)) {
            $this->last_error = $result->get_error_message();
            return false;
        }
        
        return true;
    }

    private function test_assistant_deletion($assistant_id) {
        $result = $this->assistant_manager->delete_assistant($assistant_id);
        
        if (is_wp_error($result)) {
            $this->last_error = $result->get_error_message();
            return false;
        }
        
        return true;
    }

    public function get_last_error() {
        return $this->last_error;
    }
}
