<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPAI_Assistant_Validator {
    public static function validate_assistant_data($data) {
        $errors = new WP_Error();

        // Validate name
        if (empty($data['name'])) {
            $errors->add('empty_name', 'Assistant name is required.');
        } elseif (strlen($data['name']) > 255) {
            $errors->add('name_too_long', 'Assistant name must be less than 255 characters.');
        }

        // Validate description
        if (empty($data['description'])) {
            $errors->add('empty_description', 'Assistant description is required.');
        }

        // Validate type
        $valid_types = array('customer-support', 'email-automation', 'on-site-interaction');
        if (empty($data['type'])) {
            $errors->add('empty_type', 'Assistant type is required.');
        } elseif (!in_array($data['type'], $valid_types)) {
            $errors->add('invalid_type', 'Invalid assistant type.');
        }

        // Validate settings
        if (!empty($data['settings'])) {
            if (is_string($data['settings'])) {
                if (!self::is_valid_json($data['settings'])) {
                    $errors->add('invalid_settings_json', 'Settings must be a valid JSON string.');
                }
            } elseif (is_array($data['settings'])) {
                // Ensure required settings exist
                $required_settings = array('model', 'initial_message');
                foreach ($required_settings as $setting) {
                    if (!isset($data['settings'][$setting])) {
                        $errors->add('missing_setting', "Missing required setting: {$setting}");
                    }
                }
            } else {
                $errors->add('invalid_settings_type', 'Settings must be either a JSON string or an array.');
            }
        }

        return $errors;
    }

    private static function is_valid_json($string) {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public static function sanitize_assistant_data($data) {
        return array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'type' => sanitize_key($data['type']),
            'settings' => is_array($data['settings']) ? 
                         wp_json_encode($data['settings']) : 
                         sanitize_text_field($data['settings']),
            'status' => isset($data['status']) ? sanitize_key($data['status']) : 'active'
        );
    }
}
