<?php
class WP_Job_Board_HR_Manager {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_hr_menu'));
        add_action('wp_ajax_assign_recruiter', array(__CLASS__, 'assign_recruiter'));
        add_action('wp_ajax_get_recruiter_stats', array(__CLASS__, 'get_recruiter_stats'));
    }
    
    public static function add_hr_menu() {
        add_submenu_page(
            'edit.php?post_type=job_listing',
            __('HR Management', 'wp-job-board'),
            __('HR Management', 'wp-job-board'),
            'manage_job_applications',
            'hr-management',
            array(__CLASS__, 'hr_management_page')
        );
    }
    
    public static function hr_management_page() {
        // Check capabilities
        if (!current_user_can('manage_job_applications')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-job-board'));
        }
          /**
     * Create user roles and capabilities.
     */
    public static function create_roles() {
        // HR Manager role
        add_role( 'hr_manager', __( 'HR Manager', 'wp-job-board' ), array(
            'read' => true,
            'manage_job_applications' => true,
        ) );

        // Recruiter role
        add_role( 'recruiter', __( 'Recruiter', 'wp-job-board' ), array(
            'read' => true,
            'manage_assigned_applications' => true,
        ) );

        // Add capabilities to admin
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'manage_job_applications' );
            $admin->add_cap( 'manage_assigned_applications' );
        }
    }
        // Display HR management interface
        include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-dashboard.php';
    }
    
    public static function assign_recruiter() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        if (!current_user_can('assign_recruiters')) {
            wp_send_json_error(array('message' => __('You do not have permission to assign recruiters.', 'wp-job-board')));
        }
        
        $application_id = intval($_POST['application_id']);
        $recruiter_id = intval($_POST['recruiter_id']);
        
        // Update assignment in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'hr_assignments';
        
        // Check if assignment already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE application_id = %d",
            $application_id
        ));
        
        if ($existing) {
            $result = $wpdb->update(
                $table_name,
                array(
                    'hr_recruiter_id' => $recruiter_id,
                    'assignment_date' => current_time('mysql')
                ),
                array('application_id' => $application_id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'hr_manager_id' => get_current_user_id(),
                    'hr_recruiter_id' => $recruiter_id,
                    'application_id' => $application_id,
                    'assignment_date' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s')
            );
        }
        
        if ($result) {
            // Update application status
            $applications_table = $wpdb->prefix . 'job_applications';
            $wpdb->update(
                $applications_table,
                array('status' => 'assigned', 'assigned_to' => $recruiter_id),
                array('id' => $application_id),
                array('%s', '%d'),
                array('%d')
            );
            
            // Send notification to recruiter
            self::notify_recruiter($recruiter_id, $application_id);
            
            wp_send_json_success(array('message' => __('Recruiter assigned successfully.', 'wp-job-board')));
        } else {
            wp_send_json_error(array('message' => __('Failed to assign recruiter.', 'wp-job-board')));
        }
    }
    
    public static function get_recruiter_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        global $wpdb;
        
        $recruiters = get_users(array(
            'role' => 'hr_recruiter',
            'fields' => array('ID', 'display_name')
        ));
        
        $stats = array();
        $applications_table = $wpdb->prefix . 'job_applications';
        
        foreach ($recruiters as $recruiter) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $applications_table WHERE assigned_to = %d",
                $recruiter->ID
            ));
            
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $applications_table WHERE assigned_to = %d AND status = 'completed'",
                $recruiter->ID
            ));
            
            $stats[] = array(
                'recruiter_id' => $recruiter->ID,
                'name' => $recruiter->display_name,
                'total' => $total,
                'completed' => $completed,
                'pending' => $total - $completed
            );
        }
        
        wp_send_json_success($stats);
    }
    
    private static function notify_recruiter($recruiter_id, $application_id) {
        $recruiter = get_userdata($recruiter_id);
        $application = WP_Job_Board_Database::get_application($application_id);
        
        if ($recruiter && $application) {
            $subject = __('New Job Application Assigned', 'wp-job-board');
            
            $message = sprintf(__('Hello %s,', 'wp-job-board'), $recruiter->display_name) . "\n\n";
            $message .= sprintf(__('A new job application has been assigned to you:', 'wp-job-board')) . "\n\n";
            $message .= sprintf(__('Applicant: %s', 'wp-job-board'), $application->applicant_name) . "\n";
            $message .= sprintf(__('Position: %s', 'wp-job-board'), get_the_title($application->job_id)) . "\n";
            $message .= sprintf(__('Application Date: %s', 'wp-job-board'), $application->application_date) . "\n\n";
            $message .= __('Please log in to the HR dashboard to review this application.', 'wp-job-board');
            
            wp_mail($recruiter->user_email, $subject, $message);
        }
    }
    
    public static function get_applications_by_status($status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'job_applications';
        $where = '';
        
        if (!empty($status)) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        return $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY application_date DESC");
    }
    
    public static function get_recruiters() {
        return get_users(array(
            'role' => 'hr_recruiter',
            'fields' => array('ID', 'display_name', 'user_email')
        ));
    }
}00