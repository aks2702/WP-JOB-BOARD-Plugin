<?php
/**
 * General utility functions for WP Job Board plugin.
 *
 * @package WP_Job_Board
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin option with default fallback.
 *
 * @param string $option  Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function wpjb_get_option( $option, $default = false ) {
	$options = get_option( 'wp_job_board_settings', array() );
	return isset( $options[ $option ] ) ? $options[ $option ] : $default;
}

/**
 * Update plugin option.
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 * @return bool
 */
function wpjb_update_option( $option, $value ) {
	$options = get_option( 'wp_job_board_settings', array() );
	$options[ $option ] = $value;
	return update_option( 'wp_job_board_settings', $options );
}

/**
 * Check if current user can manage job applications.
 *
 * @return bool
 */
function wpjb_current_user_can_manage_applications() {
	return current_user_can( 'manage_job_applications' ) || current_user_can( 'manage_assigned_applications' );
}

/**
 * Get application statuses with labels.
 *
 * @return array
 */
function wpjb_get_application_statuses() {
	return array(
		'new'       => __( 'New', 'wp-job-board' ),
		'assigned'  => __( 'Assigned', 'wp-job-board' ),
		'review'    => __( 'Under Review', 'wp-job-board' ),
		'interview' => __( 'Interview', 'wp-job-board' ),
		'rejected'  => __( 'Rejected', 'wp-job-board' ),
		'hired'     => __( 'Hired', 'wp-job-board' ),
	);
}

/**
 * Get application status label.
 *
 * @param string $status Status key.
 * @return string
 */
function wpjb_get_application_status_label( $status ) {
	$statuses = wpjb_get_application_statuses();
	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Format date for display.
 *
 * @param string $date   Date string.
 * @param string $format Date format.
 * @return string
 */
function wpjb_format_date( $date, $format = 'M j, Y' ) {
	if ( ! $date ) {
		return '';
	}
	
	$timestamp = strtotime( $date );
	if ( ! $timestamp ) {
		return $date;
	}
	
	return date_i18n( $format, $timestamp );
}

/**
 * Sanitize text field with allowed HTML.
 *
 * @param string $text Text to sanitize.
 * @return string
 */
function wpjb_sanitize_text_field( $text ) {
	$allowed_tags = array(
		'a' => array(
			'href'   => array(),
			'title'  => array(),
			'target' => array(),
		),
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'p'      => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
	);
	
	return wp_kses( $text, $allowed_tags );
}

/**
 * Log debug messages to WordPress debug log.
 *
 * @param mixed  $message Message to log.
 * @param string $prefix  Log prefix.
 */
function wpjb_log( $message, $prefix = 'WP Job Board' ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}
	
	if ( is_array( $message ) || is_object( $message ) ) {
		$message = print_r( $message, true );
	}
	
	error_log( sprintf( '[%s] %s', $prefix, $message ) );
}

/**
 * Get template part.
 *
 * @param string $slug Template slug.
 * @param string $name Template name.
 * @param array  $args Template arguments.
 */
function wpjb_get_template_part( $slug, $name = '', $args = array() ) {
	$template = '';
	$file     = $name ? "{$slug}-{$name}.php" : "{$slug}.php";
	
	// Check theme first.
	$template = locate_template( array( "wp-job-board/{$file}" ) );
	
	// If not found in theme, use plugin template.
	if ( ! $template ) {
		$template_path = WP_JOB_BOARD_PLUGIN_DIR . "templates/{$file}";
		if ( file_exists( $template_path ) ) {
			$template = $template_path;
		}
	}
	
	// Allow filtering of template path.
	$template = apply_filters( 'wpjb_get_template_part', $template, $slug, $name, $args );
	
	if ( $template ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}
		
		include $template;
	}
}

/**
 * Get template HTML as string.
 *
 * @param string $template_name Template name.
 * @param array  $args          Template arguments.
 * @return string
 */
function wpjb_get_template_html( $template_name, $args = array() ) {
	ob_start();
	wpjb_get_template_part( $template_name, '', $args );
	return ob_get_clean();
}

/**
 * Check if we're on a job board page.
 *
 * @return bool
 */
function wpjb_is_job_board_page() {
	global $post;
	
	if ( ! is_singular() ) {
		return false;
	}
	
	$job_board_pages = array(
		wpjb_get_option( 'jobs_page' ),
		wpjb_get_option( 'application_page' ),
		wpjb_get_option( 'hr_dashboard_page' ),
	);
	
	return in_array( $post->ID, array_filter( $job_board_pages ), true );
}

/**
 * Send email notification.
 *
 * @param string $to      Email address.
 * @param string $subject Email subject.
 * @param string $message Email message.
 * @param array  $headers Email headers.
 * @return bool
 */
function wpjb_send_email( $to, $subject, $message, $headers = array() ) {
	$default_headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
	);
	
	$headers = array_merge( $default_headers, $headers );
	
	return wp_mail( $to, $subject, $message, $headers );
}

/**
 * Get current URL.
 *
 * @return string
 */
function wpjb_get_current_url() {
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

/**
 * Array to CSV string.
 *
 * @param array  $data     Array data.
 * @param string $filename CSV filename.
 */
function wpjb_array_to_csv( $data, $filename = 'export.csv' ) {
	if ( empty( $data ) ) {
		return;
	}
	
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	
	$output = fopen( 'php://output', 'w' );
	
	// Add BOM to fix UTF-8 in Excel.
	fputs( $output, $bom = ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ) );
	
	// Add headers.
	fputcsv( $output, array_keys( $data[0] ) );
	
	// Add data.
	foreach ( $data as $row ) {
		fputcsv( $output, $row );
	}
	
	fclose( $output );
	exit;
}