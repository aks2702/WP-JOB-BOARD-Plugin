<?php
/**
 * Class autoloader for WP Job Board plugin.
 *
 * @package WP_Job_Board
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Job_Board_Autoloader {

    /**
     * Register autoloader.
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes.
     *
     * @param string $class_name Class name to load.
     */
    public static function autoload($class_name) {
        // Only handle our plugin classes
        if (0 !== strpos($class_name, 'WP_Job_Board_')) {
            return;
        }

        // Convert class name to file name
        $file_name = strtolower(str_replace(array('WP_Job_Board_', '_'), array('', '-'), $class_name));
        $file_path = WP_JOB_BOARD_PLUGIN_DIR . 'includes/classes/class-' . $file_name . '.php';

        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
    
    public function enqueue_scripts() {
        wp_enqueue_style('wp-job-board-style', WP_JOB_BOARD_PLUGIN_URL . 'assets/css/style.css', array(), WP_JOB_BOARD_VERSION);
        wp_enqueue_script('wp-job-board-script', WP_JOB_BOARD_PLUGIN_URL . 'assets/js/script.js', array('jquery'), WP_JOB_BOARD_VERSION, true);
        
        wp_localize_script('wp-job-board-script', 'wp_job_board_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_job_board_nonce')
        ));
    }
    
    public function enqueue_admin_scripts() {
        wp_enqueue_style('wp-job-board-admin-style', WP_JOB_BOARD_PLUGIN_URL . 'assets/css/admin-style.css', array(), WP_JOB_BOARD_VERSION);
        wp_enqueue_script('wp-job-board-admin-script', WP_JOB_BOARD_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), WP_JOB_BOARD_VERSION, true);
    }
    
    public function activate() {
        // Activation tasks
        if (class_exists('WP_Job_Board_Database')) {
            WP_Job_Board_Database::create_tables();
        }
        
        if (class_exists('WP_Job_Board_Post_Types')) {
            WP_Job_Board_Post_Types::register_post_types();
            flush_rewrite_rules();
        }
    }
}