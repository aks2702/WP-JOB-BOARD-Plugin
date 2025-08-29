<?php
class WP_Job_Board_CSV_Importer {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'handle_csv_import'));
        add_action('wp_ajax_import_applications', array(__CLASS__, 'ajax_import_applications'));
    }
    
    public static function handle_csv_import() {
        if (isset($_POST['wp_job_board_csv_import']) && wp_verify_nonce($_POST['_wpnonce'], 'wp_job_board_csv_import')) {
            if (!empty($_FILES['csv_file']['tmp_name'])) {
                $file = $_FILES['csv_file']['tmp_name'];
                $result = self::process_csv($file);
                
                if (is_wp_error($result)) {
                    add_action('admin_notices', function() use ($result) {
                        echo '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() use ($result) {
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(__('Successfully imported %d applications.', 'wp-job-board'), $result) . 
                             '</p></div>';
                    });
                }
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . 
                         __('Please select a CSV file to import.', 'wp-job-board') . 
                         '</p></div>';
                });
            }
        }
    }
    
    public static function ajax_import_applications() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_job_board_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wp-job-board')));
        }
        
        if (!current_user_can('import_applications')) {
            wp_send_json_error(array('message' => __('You do not have permission to import applications.', 'wp-job-board')));
        }
        
        if (empty($_FILES['file']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'wp-job-board')));
        }
        
        $file = $_FILES['file']['tmp_name'];
        $result = self::process_csv($file);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully imported %d applications.', 'wp-job-board'), $result),
                'count' => $result
            ));
        }
    }
    
    public static function process_csv($file) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return new WP_Error('file_error', __('Could not open the CSV file.', 'wp-job-board'));
        }
        
        $headers = fgetcsv($handle); // Get column headers
        if (!$headers) {
            fclose($handle);
            return new WP_Error('file_error', __('The CSV file is empty or invalid.', 'wp-job-board'));
        }
        
        // Validate required columns
        $required_columns = array('job_id', 'applicant_name', 'applicant_email');
        foreach ($required_columns as $column) {
            if (!in_array($column, $headers)) {
                fclose($handle);
                return new WP_Error('missing_column', 
                    sprintf(__('Required column "%s" is missing from the CSV file.', 'wp-job-board'), $column));
            }
        }
        
        $imported = 0;
        $errors = array();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) !== count($headers)) {
                $errors[] = sprintf(__('Row %d: Column count mismatch', 'wp-job-board'), $imported + 1);
                continue;
            }
            
            $application_data = array_combine($headers, $data);
            
            // Validate data
            if (empty($application_data['job_id']) || !is_numeric($application_data['job_id'])) {
                $errors[] = sprintf(__('Row %d: Invalid job ID', 'wp-job-board'), $imported + 1);
                continue;
            }
            
            if (empty($application_data['applicant_name'])) {
                $errors[] = sprintf(__('Row %d: Applicant name is required', 'wp-job-board'), $imported + 1);
                continue;
            }
            
            if (empty($application_data['applicant_email']) || !is_email($application_data['applicant_email'])) {
                $errors[] = sprintf(__('Row %d: Valid email is required', 'wp-job-board'), $imported + 1);
                continue;
            }
            
            // Create application record
            $result = self::create_application($application_data);
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = sprintf(__('Row %d: Failed to create application', 'wp-job-board'), $imported + 1);
            }
        }
        
        fclose($handle);
        
        if (!empty($errors) {
            // Log errors
            error_log('CSV Import Errors: ' . implode(', ', $errors));
        }
        
        return $imported;
    }
    
    public static function create_application($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'job_applications';
        
        // Check if application already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE applicant_email = %s AND job_id = %d",
            $data['applicant_email'],
            $data['job_id']
        ));
        
        if ($existing) {
            return false; // Skip duplicates
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'job_id' => intval($data['job_id']),
                'applicant_name' => sanitize_text_field($data['applicant_name']),
                'applicant_email' => sanitize_email($data['applicant_email']),
                'application_date' => !empty($data['application_date']) ? $data['application_date'] : current_time('mysql'),
                'status' => !empty($data['status']) ? $data['status'] : 'new',
                'assigned_to' => !empty($data['assigned_to']) ? intval($data['assigned_to']) : 0,
                'message' => !empty($data['message']) ? sanitize_textarea_field($data['message']) : ''
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            // Create application post
            $application_post = array(
                'post_title' => sanitize_text_field($data['applicant_name']) . ' - ' . get_the_title($data['job_id']),
                'post_status' => 'publish',
                'post_type' => 'job_application'
            );
            
            $post_id = wp_insert_post($application_post);
            
            if ($post_id) {
                update_post_meta($post_id, '_application_id', $wpdb->insert_id);
            }
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function get_csv_template() {
        $headers = array('job_id', 'applicant_name', 'applicant_email', 'application_date', 'status', 'assigned_to', 'message');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="job_applications_template.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        fclose($output);
        
        exit;
    }
}