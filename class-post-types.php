<?php
class WP_Job_Board_Post_Types {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_boxes'));
        add_filter('manage_job_listing_posts_columns', array(__CLASS__, 'add_custom_columns'));
        add_action('manage_job_listing_posts_custom_column', array(__CLASS__, 'custom_column_content'), 10, 2);
    }
    
    public static function register_post_types() {
        // Job Listing post type
        $labels = array(
            'name' => __('Jobs', 'wp-job-board'),
            'singular_name' => __('Job', 'wp-job-board'),
            'add_new' => __('Add New Job', 'wp-job-board'),
            'add_new_item' => __('Add New Job', 'wp-job-board'),
            'edit_item' => __('Edit Job', 'wp-job-board'),
            'new_item' => __('New Job', 'wp-job-board'),
            'view_item' => __('View Job', 'wp-job-board'),
            'search_items' => __('Search Jobs', 'wp-job-board'),
            'not_found' => __('No jobs found', 'wp-job-board'),
            'not_found_in_trash' => __('No jobs found in Trash', 'wp-job-board'),
            'menu_name' => __('Job Board', 'wp-job-board')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'jobs'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-businessman',
            'capability_type' => 'post',
            'map_meta_cap' => true
        );
        
        register_post_type('job_listing', $args);
        
        // Application post type (for HR management)
        $app_labels = array(
            'name' => __('Applications', 'wp-job-board'),
            'singular_name' => __('Application', 'wp-job-board'),
            'add_new' => __('Add Application', 'wp-job-board'),
            'add_new_item' => __('Add New Application', 'wp-job-board'),
            'edit_item' => __('Edit Application', 'wp-job-board'),
            'view_item' => __('View Application', 'wp-job-board'),
            'search_items' => __('Search Applications', 'wp-job-board'),
            'not_found' => __('No applications found', 'wp-job-board'),
            'menu_name' => __('Applications', 'wp-job-board')
        );
        
        $app_args = array(
            'labels' => $app_labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=job_listing',
            'capability_type' => 'post',
            'supports' => array('title'),
            'map_meta_cap' => true
        );
        
        register_post_type('job_application', $app_args);
    }
    
    public static function register_taxonomies() {
        // Job Category taxonomy
        $category_labels = array(
            'name' => __('Job Categories', 'wp-job-board'),
            'singular_name' => __('Job Category', 'wp-job-board'),
            'search_items' => __('Search Job Categories', 'wp-job-board'),
            'all_items' => __('All Job Categories', 'wp-job-board'),
            'parent_item' => __('Parent Job Category', 'wp-job-board'),
            'parent_item_colon' => __('Parent Job Category:', 'wp-job-board'),
            'edit_item' => __('Edit Job Category', 'wp-job-board'),
            'update_item' => __('Update Job Category', 'wp-job-board'),
            'add_new_item' => __('Add New Job Category', 'wp-job-board'),
            'new_item_name' => __('New Job Category Name', 'wp-job-board'),
            'menu_name' => __('Job Categories', 'wp-job-board')
        );
        
        $category_args = array(
            'labels' => $category_labels,
            'hierarchical' => true,
            'rewrite' => array('slug' => 'job-category'),
            'show_admin_column' => true,
            'show_in_rest' => true
        );
        
        register_taxonomy('job_category', 'job_listing', $category_args);
        
        // Job Type taxonomy
        $type_labels = array(
            'name' => __('Job Types', 'wp-job-board'),
            'singular_name' => __('Job Type', 'wp-job-board'),
            'search_items' => __('Search Job Types', 'wp-job-board'),
            'all_items' => __('All Job Types', 'wp-job-board'),
            'parent_item' => __('Parent Job Type', 'wp-job-board'),
            'parent_item_colon' => __('Parent Job Type:', 'wp-job-board'),
            'edit_item' => __('Edit Job Type', 'wp-job-board'),
            'update_item' => __('Update Job Type', 'wp-job-board'),
            'add_new_item' => __('Add New Job Type', 'wp-job-board'),
            'new_item_name' => __('New Job Type Name', 'wp-job-board'),
            'menu_name' => __('Job Types', 'wp-job-board')
        );
        
        $type_args = array(
            'labels' => $type_labels,
            'hierarchical' => false,
            'rewrite' => array('slug' => 'job-type'),
            'show_admin_column' => true,
            'show_in_rest' => true
        );
        
        register_taxonomy('job_type', 'job_listing', $type_args);
        
        // Location taxonomy
        $location_labels = array(
            'name' => __('Job Locations', 'wp-job-board'),
            'singular_name' => __('Job Location', 'wp-job-board'),
            'search_items' => __('Search Job Locations', 'wp-job-board'),
            'all_items' => __('All Job Locations', 'wp-job-board'),
            'parent_item' => __('Parent Job Location', 'wp-job-board'),
            'parent_item_colon' => __('Parent Job Location:', 'wp-job-board'),
            'edit_item' => __('Edit Job Location', 'wp-job-board'),
            'update_item' => __('Update Job Location', 'wp-job-board'),
            'add_new_item' => __('Add New Job Location', 'wp-job-board'),
            'new_item_name' => __('New Job Location Name', 'wp-job-board'),
            'menu_name' => __('Job Locations', 'wp-job-board')
        );
        
        $location_args = array(
            'labels' => $location_labels,
            'hierarchical' => true,
            'rewrite' => array('slug' => 'job-location'),
            'show_admin_column' => true,
            'show_in_rest' => true
        );
        
        register_taxonomy('job_location', 'job_listing', $location_args);
    }
    
    public static function add_meta_boxes() {
        add_meta_box(
            'job_listing_details',
            __('Job Details', 'wp-job-board'),
            array(__CLASS__, 'job_details_meta_box'),
            'job_listing',
            'normal',
            'high'
        );
        
        add_meta_box(
            'job_application_details',
            __('Application Details', 'wp-job-board'),
            array(__CLASS__, 'application_details_meta_box'),
            'job_application',
            'normal',
            'high'
        );
    }
    
    public static function job_details_meta_box($post) {
        wp_nonce_field('job_listing_details_nonce', 'job_listing_details_nonce');
        
        $company_name = get_post_meta($post->ID, '_company_name', true);
        $company_website = get_post_meta($post->ID, '_company_website', true);
        $application_email = get_post_meta($post->ID, '_application_email', true);
        $salary = get_post_meta($post->ID, '_salary', true);
        $deadline = get_post_meta($post->ID, '_application_deadline', true);
        
        echo '<table class="form-table">';
        echo '<tr><th><label for="company_name">' . __('Company Name', 'wp-job-board') . '</label></th>';
        echo '<td><input type="text" id="company_name" name="company_name" value="' . esc_attr($company_name) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="company_website">' . __('Company Website', 'wp-job-board') . '</label></th>';
        echo '<td><input type="url" id="company_website" name="company_website" value="' . esc_attr($company_website) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="application_email">' . __('Application Email', 'wp-job-board') . '</label></th>';
        echo '<td><input type="email" id="application_email" name="application_email" value="' . esc_attr($application_email) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="salary">' . __('Salary', 'wp-job-board') . '</label></th>';
        echo '<td><input type="text" id="salary" name="salary" value="' . esc_attr($salary) . '" class="regular-text"></td></tr>';
        
        echo '<tr><th><label for="application_deadline">' . __('Application Deadline', 'wp-job-board') . '</label></th>';
        echo '<td><input type="date" id="application_deadline" name="application_deadline" value="' . esc_attr($deadline) . '" class="regular-text"></td></tr>';
        echo '</table>';
    }
    
    public static function application_details_meta_box($post) {
        $application_id = get_post_meta($post->ID, '_application_id', true);
        
        if ($application_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'job_applications';
            $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $application_id));
            
            if ($application) {
                echo '<div class="application-details">';
                echo '<p><strong>' . __('Applicant Name:', 'wp-job-board') . '</strong> ' . esc_html($application->applicant_name) . '</p>';
                echo '<p><strong>' . __('Applicant Email:', 'wp-job-board') . '</strong> ' . esc_html($application->applicant_email) . '</p>';
                echo '<p><strong>' . __('Job ID:', 'wp-job-board') . '</strong> ' . esc_html($application->job_id) . '</p>';
                echo '<p><strong>' . __('Status:', 'wp-job-board') . '</strong> ' . esc_html($application->status) . '</p>';
                echo '<p><strong>' . __('Application Date:', 'wp-job-board') . '</strong> ' . esc_html($application->application_date) . '</p>';
                
                if (!empty($application->message)) {
                    echo '<p><strong>' . __('Message:', 'wp-job-board') . '</strong><br>' . esc_html($application->message) . '</p>';
                }
                
                if (!empty($application->cv_url)) {
                    echo '<p><strong>' . __('CV:', 'wp-job-board') . '</strong> <a href="' . esc_url($application->cv_url) . '" target="_blank">' . __('Download CV', 'wp-job-board') . '</a></p>';
                }
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No application details found.', 'wp-job-board') . '</p>';
        }
    }
    
    public static function save_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        // Job Listing meta
        if (isset($_POST['job_listing_details_nonce']) && wp_verify_nonce($_POST['job_listing_details_nonce'], 'job_listing_details_nonce')) {
            if (isset($_POST['company_name'])) {
                update_post_meta($post_id, '_company_name', sanitize_text_field($_POST['company_name']));
            }
            if (isset($_POST['company_website'])) {
                update_post_meta($post_id, '_company_website', esc_url_raw($_POST['company_website']));
            }
            if (isset($_POST['application_email'])) {
                update_post_meta($post_id, '_application_email', sanitize_email($_POST['application_email']));
            }
            if (isset($_POST['salary'])) {
                update_post_meta($post_id, '_salary', sanitize_text_field($_POST['salary']));
            }
            if (isset($_POST['application_deadline'])) {
                update_post_meta($post_id, '_application_deadline', sanitize_text_field($_POST['application_deadline']));
            }
        }
    }
    
    public static function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['company'] = __('Company', 'wp-job-board');
                $new_columns['applications'] = __('Applications', 'wp-job-board');
            }
        }
        
        return $new_columns;
    }
    
    public static function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'company':
                $company = get_post_meta($post_id, '_company_name', true);
                echo $company ? esc_html($company) : '—';
                break;
                
            case 'applications':
                global $wpdb;
                $table_name = $wpdb->prefix . 'job_applications';
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE job_id = %d", $post_id));
                echo '<a href="' . admin_url('edit.php?post_type=job_application&job_id=' . $post_id) . '">' . intval($count) . '</a>';
                break;
        }
    }
}