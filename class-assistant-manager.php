<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPAI_Assistant_Manager {
    private static $instance = null;
    private $table_name;

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpai_assistants';
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text NOT NULL,
            type varchar(50) NOT NULL,
            settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function create_assistant($data) {
        global $wpdb;

        $defaults = array(
            'name' => '',
            'description' => '',
            'type' => '',
            'settings' => '{}',
            'status' => 'active'
        );

        $data = wp_parse_args($data, $defaults);
        
        // Basic validation
        if (empty($data['name']) || empty($data['type'])) {
            return new WP_Error('validation_error', 'Name and type are required fields.');
        }

        // Basic sanitization
        $data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'type' => sanitize_key($data['type']),
            'settings' => is_array($data['settings']) ? wp_json_encode($data['settings']) : sanitize_text_field($data['settings']),
            'status' => isset($data['status']) ? sanitize_key($data['status']) : 'active'
        );

        // Insert the assistant
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'settings' => is_array($data['settings']) ? json_encode($data['settings']) : $data['settings'],
                'status' => $data['status']
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', 'Could not create assistant: ' . $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    public function get_assistants($args = array()) {
        global $wpdb;

        $defaults = array(
            'type' => '',
            'status' => 'active',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 10,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        if (!empty($args['type'])) {
            $where[] = $wpdb->prepare('type = %s', $args['type']);
        }
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT {$args['limit']} OFFSET {$args['offset']}";

        return $wpdb->get_results($sql);
    }

    public function update_assistant($id, $data) {
        global $wpdb;

        if (empty($id)) {
            return new WP_Error('missing_id', 'Assistant ID is required.');
        }

        $result = $wpdb->update(
            $this->table_name,
            array(
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'settings' => is_array($data['settings']) ? json_encode($data['settings']) : $data['settings'],
                'status' => $data['status']
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        if (false === $result) {
            return new WP_Error('db_error', 'Could not update assistant: ' . $wpdb->last_error);
        }

        return true;
    }

    public function delete_assistant($id) {
        global $wpdb;

        if (empty($id)) {
            return new WP_Error('missing_id', 'Assistant ID is required.');
        }

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if (false === $result) {
            return new WP_Error('db_error', 'Could not delete assistant: ' . $wpdb->last_error);
        }

        return true;
    }
}
