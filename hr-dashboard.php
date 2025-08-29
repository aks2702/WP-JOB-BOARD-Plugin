<?php
// Check capabilities
if (!current_user_can('manage_job_applications')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-job-board'));
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
$tabs = array(
    'overview' => __('Overview', 'wp-job-board'),
    'applications' => __('Applications', 'wp-job-board'),
    'recruiters' => __('Recruiters', 'wp-job-board'),
    'import' => __('Import', 'wp-job-board')
);
?>

<div class="wrap">
    <h1><?php _e('HR Management Dashboard', 'wp-job-board'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab => $name) : ?>
            <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=' . $tab); ?>" 
               class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                <?php echo $name; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="hr-dashboard-content">
        <?php
        switch ($current_tab) {
            case 'overview':
                include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-overview.php';
                break;
            case 'applications':
                include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-applications.php';
                break;
            case 'recruiters':
                include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-recruiters.php';
                break;
            case 'import':
                include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-import.php';
                break;
            default:
                include WP_JOB_BOARD_PLUGIN_DIR . 'templates/hr-overview.php';
                break;
        }
        ?>
    </div>
</div>