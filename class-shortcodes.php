<?php
/**
 * Shortcodes handler for WP Job Board.
 *
 * @package WP_Job_Board
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Job_Board_Shortcodes {

    /**
     * Initialize shortcodes.
     */
    public static function init() {
        add_shortcode( 'job_listings', array( __CLASS__, 'job_listings' ) );
        add_shortcode( 'job_application_form', array( __CLASS__, 'application_form' ) );
        add_shortcode( 'hr_dashboard', array( __CLASS__, 'hr_dashboard' ) );
    }

    /**
     * Job listings shortcode.
     */
    public static function job_listings( $atts ) {
        ob_start();
        wpjb_get_template_part( 'job-listings' );
        return ob_get_clean();
    }

    /**
     * Application form shortcode.
     */
    public static function application_form( $atts ) {
        ob_start();
        wpjb_get_template_part( 'application-form' );
        return ob_get_clean();
    }

    /**
     * HR Dashboard shortcode.
     */
    public static function hr_dashboard( $atts ) {
        if ( ! wpjb_current_user_can_manage_applications() ) {
            return '<p>' . __( 'You do not have permission to access this page.', 'wp-job-board' ) . '</p>';
        }

        ob_start();
        wpjb_get_template_part( 'hr-dashboard' );
        return ob_get_clean();
    }
}