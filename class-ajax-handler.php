<?php
class WP_Job_Board_AJAX_Handler {
    public static function init() {
        // Frontend AJAX actions
        add_action('wp_ajax_submit_job_application', array(__CLASS__, 'submit_job_application'));
        add_action('wp_ajax_nopriv_submit_job_application', array(__CLASS__, 'submit_job_application'));
        
        // Admin AJAX actions
        add_action('wp_ajax_get_application_details', array(__CLASS__, 'get_application_details'));
        add_action('wp_ajax_update_application_status', array(__CLASS__, 'update_application_status'));
    }
    
    public static function submit_job_application() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $name = isset($_POST['applicant_name']) ? sanitize_text_field($_POST['applicant_name']) : '';
        $email = isset($_POST['applicant_email']) ? sanitize_email($_POST['applicant_email']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        // Handle file upload
        $cv_url = '';
        if (!empty($_FILES['cv']['name'])) {
            $upload = wp_handle_upload($_FILES['cv'], array('test_form' => false));
            if (!isset($upload['error'])) {
                $cv_url = $upload['url'];
            }
        }
        
        if (empty($name) || empty($email) || empty($job_id)) {
            wp_send_json_error(array('message' => __('Please fill all required fields.', 'wp-job-board')));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'wp-job-board')));
        }
        
        $application_data = array(
            'job_id' => $job_id,
            'applicant_name' => $name,
            'applicant_email' => $email,
            'message' => $message,
            'cv_url' => $cv_url
        );
        
        $application_id = WP_Job_Board_Database::insert_application($application_data);
        
        if ($application_id) {
            // Create application post
            $application_post = array(
                'post_title' => $name . ' - ' . get_the_title($job_id),
                'post_status' => 'publish',
                'post_type' => 'job_application'
            );
            
            $post_id = wp_insert_post($application_post);
            
            if ($post_id) {
                update_post_meta($post_id, '_application_id', $application_id);
            }
            
            // Send notification email
            self::send_notification_email($application_data);
            
            wp_send_json_success(array('message' => __('Application submitted successfully!', 'wp-job-board')));
        } else {
            wp_send_json_error(array('message' => __('There was an error submitting your application.', 'wp-job-board')));
        }
    }
    
    public static function get_application_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        
        if ($application_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'job_applications';
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $application_id));
            
            if ($application) {
                wp_send_json_success($application);
            }
        }
        
        wp_send_json_error(array('message' => __('Application not found.', 'wp-job-board')));
    }
    
    public static function update_application_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if ($application_id && $status) {
            $result = WP_Job_Board_Database::update_application_status($application_id, $status);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Status updated successfully.', 'wp-job-board')));
            }
        }
        
        wp_send_json_error(array('message' => __('Failed to update status.', 'wp-job-board')));
    }
    
    private static function send_notification_email($application_data) {
        $job = get_post($application_data['job_id']);
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('New Job Application for: %s', 'wp-job-board'), $job->post_title);
        
        $message = sprintf(__('A new job application has been submitted:', 'wp-job-board')) . "\n\n";
        $message .= sprintf(__('Job: %s', 'wp-job-board'), $job->post_title) . "\n";
        $message .= sprintf(__('Applicant: %s', 'wp-job-board'), $application_data['applicant_name']) . "\n";
        $message .= sprintf(__('Email: %s', 'wp-job-board'), $application_data['applicant_email']) . "\n";
        
        if (!empty($application_data['message'])) {
            $message .= sprintf(__('Message: %s', 'wp-job-board'), $application_data['message']) . "\n";
        }
        
        wp_mail($admin_email, $subject, $message);
    }
}