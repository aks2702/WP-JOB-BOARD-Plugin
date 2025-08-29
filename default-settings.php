<?php
/**
 * Default plugin settings.
 *
 * @package WP_Job_Board
 */

defined( 'ABSPATH' ) || exit;

return array(
    'job_post_type_slug'          => 'job-listing',
    'application_post_type_slug'  => 'job-application',
    'enable_application_status'   => true,
    'default_application_status'  => 'new',
    'max_file_size'               => 8, // MB
    'allowed_file_types'          => array( 'pdf', 'doc', 'docx' ),
    'hr_dashboard_page'           => '',
    'jobs_per_page'               => 10,
    'enable_email_notifications'  => true,
);