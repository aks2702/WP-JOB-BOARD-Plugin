<?php
/**
 * Plugin constants.
 *
 * @package WP_Job_Board
 */

defined( 'ABSPATH' ) || exit;

// Plugin version.
define( 'WP_JOB_BOARD_VERSION', '1.0.0' );

// Plugin paths.
define( 'WP_JOB_BOARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JOB_BOARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Database table names.
define( 'WP_JOB_BOARD_APPLICATIONS_TABLE', $wpdb->prefix . 'job_applications' );

// Capabilities.
define( 'WP_JOB_BOARD_HR_CAPABILITY', 'manage_job_applications' );
define( 'WP_JOB_BOARD_RECRUITER_CAPABILITY', 'manage_assigned_applications' );