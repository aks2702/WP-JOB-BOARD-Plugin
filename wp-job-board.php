<?php
/**
 * Plugin Name: WP Job Board
 * Plugin URI: https://github.com/aks2702/WP-JOB-BOARD-Plugin.git
 * Description: A complete job board system with HR management capabilities.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wp-job-board
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', 'wp_job_board_php_version_notice');
    return;
}

function wp_job_board_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                __('WP Job Board requires PHP 7.4 or higher. Your current version is %s. Please upgrade PHP.', 'wp-job-board'),
                PHP_VERSION
            );
            ?>
        </p>
    </div>
    <?php
}

// Define constants
define('WP_JOB_BOARD_VERSION', '1.0.0');
define('WP_JOB_BOARD_PLUGIN_FILE', __FILE__);
define('WP_JOB_BOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_JOB_BOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include utility functions
require_once WP_JOB_BOARD_PLUGIN_DIR . 'includes/functions.php';
require_once WP_JOB_BOARD_PLUGIN_DIR . 'includes/template-functions.php';

// Include the autoloader
require_once WP_JOB_BOARD_PLUGIN_DIR . 'includes/classes/class-autoloader.php';

// Initialize autoloader
WP_Job_Board_Autoloader::register();

/**
 * Main plugin class wrapper for WordPress
 */
class WP_Job_Board_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Initialize components
        add_action('init', array($this, 'init_components'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init_components() {
        // Initialize post types
        if (class_exists('WP_Job_Board_Post_Types')) {
            WP_Job_Board_Post_Types::init();
        }
        
        // Initialize shortcodes
        if (class_exists('WP_Job_Board_Shortcodes')) {
            WP_Job_Board_Shortcodes::init();
        }
        
        // Initialize HR Manager
        if (class_exists('WP_Job_Board_HR_Manager')) {
            WP_Job_Board_HR_Manager::init();
        }
    }
    
    public function activate() {
        // Create database tables
        if (class_exists('WP_Job_Board_Database')) {
            WP_Job_Board_Database::create_tables();
        }
        
        // Create roles
        if (class_exists('WP_Job_Board_HR_Manager')) {
            WP_Job_Board_HR_Manager::create_roles();
        }
        
        // Set default options
        update_option('wp_job_board_version', WP_JOB_BOARD_VERSION);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'wp-job-board-frontend',
            WP_JOB_BOARD_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WP_JOB_BOARD_VERSION
        );
        
        wp_enqueue_script(
            'wp-job-board-frontend',
            WP_JOB_BOARD_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WP_JOB_BOARD_VERSION,
            true
        );
        
        wp_localize_script(
            'wp-job-board-frontend',
            'wp_job_board',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_job_board_nonce')
            )
        );
    }
    
    public function enqueue_admin_scripts() {
        wp_enqueue_style(
            'wp-job-board-admin',
            WP_JOB_BOARD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_JOB_BOARD_VERSION
        );
        
        wp_enqueue_script(
            'wp-job-board-admin',
            WP_JOB_BOARD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_JOB_BOARD_VERSION,
            true
        );
    }
}

// Initialize the plugin
function wp_job_board() {
    return WP_Job_Board_Plugin::get_instance();
}

// Start the plugin after WordPress loads
add_action('plugins_loaded', 'wp_job_board');

// Load text domain
add_action('init', 'wp_job_board_load_textdomain');
function wp_job_board_load_textdomain() {
    load_plugin_textdomain(
        'wp-job-board',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}