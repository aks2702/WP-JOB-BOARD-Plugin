<?php
/**
 * Database handler for WP Job Board.
 *
 * @package WP_Job_Board
 */


defined( 'ABSPATH' ) || exit;


class WP_Job_Board_Database {
    public static function init() {
        // Hooks for database operations
        add_action('wp_ajax_get_applications', array(__CLASS__, 'get_applications'));
        add_action('wp_ajax_nopriv_get_applications', array(__CLASS__, 'get_applications'));
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Applications table
        $table_name = $wpdb->prefix . 'job_applications';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            job_id mediumint(9) NOT NULL,
            applicant_name varchar(100) NOT NULL,
            applicant_email varchar(100) NOT NULL,
            application_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) DEFAULT 'new',
            assigned_to mediumint(9) DEFAULT 0,
            cv_url varchar(255) DEFAULT '',
            message text,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY status (status),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        
        // HR assignments table
        $table_name_assignments = $wpdb->prefix . 'hr_assignments';
        $sql2 = "CREATE TABLE $table_name_assignments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hr_manager_id mediumint(9) NOT NULL,
            hr_recruiter_id mediumint(9) NOT NULL,
            application_id mediumint(9) NOT NULL,
            assignment_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY hr_manager_id (hr_manager_id),
            KEY hr_recruiter_id (hr_recruiter_id),
            KEY application_id (application_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        // Add custom capabilities to admin role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_job_applications');
            $admin_role->add_cap('assign_recruiters');
            $admin_role->add_cap('import_applications');
        }
        
        // Create HR Manager role
        add_role('hr_manager', 'HR Manager', array(
            'read' => true,
            'manage_job_applications' => true,
            'assign_recruiters' => true,
            'import_applications' => true
        ));
        
        // Create HR Recruiter role
        add_role('hr_recruiter', 'HR Recruiter', array(
            'read' => true,
            'manage_job_applications' => true
        ));
    }
    
    public static function get_applications() {
        global $wpdb;
        
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_nonce')) {
            wp_die('Security check failed');
        }
        
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        $table_name = $wpdb->prefix . 'job_applications';
        
        $where = array();
        if ($job_id > 0) {
            $where[] = $wpdb->prepare('job_id = %d', $job_id);
        }
        if (!empty($status)) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }
        
        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }
        
        $applications = $wpdb->get_results("SELECT * FROM $table_name $where_sql ORDER BY application_date DESC");
        
        wp_send_json_success($applications);
    }
    
    public static function insert_application($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'job_applications';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'job_id' => $data['job_id'],
                'applicant_name' => $data['applicant_name'],
                'applicant_email' => $data['applicant_email'],
                'application_date' => current_time('mysql'),
                'status' => 'new',
                'assigned_to' => 0,
                'cv_url' => $data['cv_url'],
                'message' => $data['message']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function update_application_status($application_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'job_applications';
        
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $application_id),
            array('%s'),
            array('%d')
        );
    }
}