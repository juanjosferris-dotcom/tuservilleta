<?php
/**
 * Form Handler
 * Handles form submission, validation, sanitization, and email notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Handler Class
 */
class Tuservilleta_Form_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'handle_form_submission'));
        add_action('wp_ajax_tuservilleta_submit_form', array($this, 'ajax_handle_form_submission'));
        add_action('wp_ajax_nopriv_tuservilleta_submit_form', array($this, 'ajax_handle_form_submission'));
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Check if this is our form
        if (!isset($_POST['tuservilleta_form_nonce'])) {
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['tuservilleta_form_nonce'], 'tuservilleta_form_submit')) {
            wp_die(__('Security check failed. Please try again.', 'tuservilleta-form'));
        }
        
        // Validate and sanitize form data
        $validation_result = $this->validate_form_data($_POST);
        
        if (is_wp_error($validation_result)) {
            // Redirect with error
            $redirect_url = add_query_arg('form_status', 'error', wp_get_referer());
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        // Get sanitized data
        $form_data = $validation_result;
        
        // Send email notification
        $email_sent = $this->send_email_notification($form_data);
        
        // Optionally save to database
        $this->save_submission($form_data);
        
        // Redirect with success or error message
        $status = $email_sent ? 'success' : 'error';
        $redirect_url = add_query_arg('form_status', $status, wp_get_referer());
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Validate and sanitize form data
     * 
     * @param array $data Raw POST data
     * @return array|WP_Error Sanitized data or error
     */
    private function validate_form_data($data) {
        $errors = array();
        $sanitized = array();
        
        // Validate and sanitize name
        if (empty($data['tuservilleta_name'])) {
            $errors[] = __('Name is required.', 'tuservilleta-form');
        } else {
            $sanitized['name'] = sanitize_text_field($data['tuservilleta_name']);
        }
        
        // Validate and sanitize email
        if (empty($data['tuservilleta_email'])) {
            $errors[] = __('Email is required.', 'tuservilleta-form');
        } elseif (!is_email($data['tuservilleta_email'])) {
            $errors[] = __('Please provide a valid email address.', 'tuservilleta-form');
        } else {
            $sanitized['email'] = sanitize_email($data['tuservilleta_email']);
        }
        
        // Validate and sanitize phone (optional)
        if (!empty($data['tuservilleta_phone'])) {
            $sanitized['phone'] = sanitize_text_field($data['tuservilleta_phone']);
        } else {
            $sanitized['phone'] = '';
        }
        
        // Validate and sanitize subject
        if (empty($data['tuservilleta_subject'])) {
            $errors[] = __('Subject is required.', 'tuservilleta-form');
        } else {
            $sanitized['subject'] = sanitize_text_field($data['tuservilleta_subject']);
        }
        
        // Validate and sanitize message
        if (empty($data['tuservilleta_message'])) {
            $errors[] = __('Message is required.', 'tuservilleta-form');
        } else {
            $sanitized['message'] = sanitize_textarea_field($data['tuservilleta_message']);
        }
        
        // Add timestamp
        $sanitized['submitted_at'] = current_time('mysql');
        
        // Return errors or sanitized data
        if (!empty($errors)) {
            return new WP_Error('validation_error', implode(' ', $errors));
        }
        
        return $sanitized;
    }
    
    /**
     * Send email notification to admin
     * 
     * @param array $form_data Sanitized form data
     * @return bool Whether email was sent successfully
     */
    private function send_email_notification($form_data) {
        // Get admin email
        $to = get_option('admin_email');
        
        // Email subject
        $subject = sprintf(
            __('[%s] New Form Submission: %s', 'tuservilleta-form'),
            get_bloginfo('name'),
            $form_data['subject']
        );
        
        // Build email message
        $message = sprintf(__('New form submission from %s', 'tuservilleta-form'), get_bloginfo('name')) . "\n\n";
        $message .= __('Submission Details:', 'tuservilleta-form') . "\n";
        $message .= str_repeat('-', 50) . "\n\n";
        $message .= __('Name:', 'tuservilleta-form') . ' ' . $form_data['name'] . "\n";
        $message .= __('Email:', 'tuservilleta-form') . ' ' . $form_data['email'] . "\n";
        
        if (!empty($form_data['phone'])) {
            $message .= __('Phone:', 'tuservilleta-form') . ' ' . $form_data['phone'] . "\n";
        }
        
        $message .= __('Subject:', 'tuservilleta-form') . ' ' . $form_data['subject'] . "\n\n";
        $message .= __('Message:', 'tuservilleta-form') . "\n";
        $message .= $form_data['message'] . "\n\n";
        $message .= str_repeat('-', 50) . "\n";
        $message .= __('Submitted at:', 'tuservilleta-form') . ' ' . $form_data['submitted_at'] . "\n";
        
        // Email headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $form_data['name'] . ' <' . $form_data['email'] . '>'
        );
        
        // Send email
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Save form submission to database (optional)
     * 
     * @param array $form_data Sanitized form data
     * @return int|false Post ID on success, false on failure
     */
    private function save_submission($form_data) {
        // Get a valid user ID for post author
        $author_id = get_current_user_id();
        
        // If no user is logged in, use first administrator
        if (!$author_id) {
            $admins = get_users(array(
                'role'    => 'administrator',
                'number'  => 1,
                'orderby' => 'ID'
            ));
            
            if (!empty($admins)) {
                $author_id = $admins[0]->ID;
            } else {
                // Fallback to ID 1 if no admin found
                $author_id = 1;
            }
        }
        
        // Create a custom post type entry for the submission
        $post_data = array(
            'post_title'   => sprintf(
                __('Form Submission - %s', 'tuservilleta-form'),
                $form_data['subject']
            ),
            'post_content' => $form_data['message'],
            'post_status'  => 'private',
            'post_type'    => 'tuservilleta_submission',
            'post_author'  => $author_id
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Save meta data
            update_post_meta($post_id, '_tuservilleta_name', $form_data['name']);
            update_post_meta($post_id, '_tuservilleta_email', $form_data['email']);
            update_post_meta($post_id, '_tuservilleta_phone', $form_data['phone']);
            update_post_meta($post_id, '_tuservilleta_subject', $form_data['subject']);
            update_post_meta($post_id, '_tuservilleta_submitted_at', $form_data['submitted_at']);
        }
        
        return $post_id;
    }
    
    /**
     * Handle AJAX form submission
     */
    public function ajax_handle_form_submission() {
        // Verify nonce
        if (!check_ajax_referer('tuservilleta_form_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'tuservilleta-form')
            ));
        }
        
        // Validate and sanitize form data
        $validation_result = $this->validate_form_data($_POST);
        
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array(
                'message' => $validation_result->get_error_message()
            ));
        }
        
        // Get sanitized data
        $form_data = $validation_result;
        
        // Send email notification
        $email_sent = $this->send_email_notification($form_data);
        
        // Optionally save to database
        $this->save_submission($form_data);
        
        // Send response
        if ($email_sent) {
            wp_send_json_success(array(
                'message' => __('Thank you! Your message has been sent successfully.', 'tuservilleta-form')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('There was an error sending your message. Please try again.', 'tuservilleta-form')
            ));
        }
    }
}

// Initialize form handler
new Tuservilleta_Form_Handler();

/**
 * Register custom post type for form submissions
 */
function tuservilleta_register_submission_post_type() {
    $args = array(
        'labels' => array(
            'name'          => __('Form Submissions', 'tuservilleta-form'),
            'singular_name' => __('Form Submission', 'tuservilleta-form'),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'supports'     => array('title', 'editor'),
        'menu_icon'    => 'dashicons-email',
    );
    
    register_post_type('tuservilleta_submission', $args);
}

add_action('init', 'tuservilleta_register_submission_post_type');
