<?php
// Check capabilities
if (!current_user_can('manage_job_applications')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-job-board'));
}

global $wpdb;

// Get statistics
$applications_table = $wpdb->prefix . 'job_applications';
$total_applications = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
$new_applications = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $applications_table WHERE status = %s", 
    'new'
));
$assigned_applications = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $applications_table WHERE status = %s", 
    'assigned'
));
$completed_applications = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $applications_table WHERE status = %s", 
    'completed'
));

// Get recent applications
$recent_applications = $wpdb->get_results(
    "SELECT * FROM $applications_table ORDER BY application_date DESC LIMIT 5"
);

// Get recruiter statistics
$recruiters = WP_Job_Board_HR_Manager::get_recruiters();
?>

<div class="hr-overview">
    <h2><?php _e('Dashboard Overview', 'wp-job-board'); ?></h2>
    
    <div class="hr-stats">
        <div class="stat-card">
            <h3><?php echo $total_applications; ?></h3>
            <p><?php _e('Total Applications', 'wp-job-board'); ?></p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $new_applications; ?></h3>
            <p><?php _e('New Applications', 'wp-job-board'); ?></p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $assigned_applications; ?></h3>
            <p><?php _e('Assigned Applications', 'wp-job-board'); ?></p>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $completed_applications; ?></h3>
            <p><?php _e('Completed Applications', 'wp-job-board'); ?></p>
        </div>
    </div>
    
    <div class="hr-dashboard-grid">
        <div class="dashboard-card">
            <h3><?php _e('Recent Applications', 'wp-job-board'); ?></h3>
            
            <?php if (!empty($recent_applications)) : ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th><?php _e('Applicant', 'wp-job-board'); ?></th>
                            <th><?php _e('Position', 'wp-job-board'); ?></th>
                            <th><?php _e('Date', 'wp-job-board'); ?></th>
                            <th><?php _e('Status', 'wp-job-board'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_applications as $application) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($application->applicant_name); ?></strong>
                                    <br>
                                    <small><?php echo esc_html($application->applicant_email); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $job_title = get_the_title($application->job_id);
                                    echo $job_title ? esc_html($job_title) : __('Job not found', 'wp-job-board'); 
                                    ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($application->application_date)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($application->status); ?>">
                                        <?php echo esc_html(ucfirst($application->status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No applications found.', 'wp-job-board'); ?></p>
            <?php endif; ?>
            
            <div class="view-all-link">
                <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=applications'); ?>">
                    <?php _e('View All Applications', 'wp-job-board'); ?>
                </a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3><?php _e('Recruiter Performance', 'wp-job-board'); ?></h3>
            
            <div id="recruiter-stats-container" class="recruiter-stats">
                <p><?php _e('Loading recruiter statistics...', 'wp-job-board'); ?></p>
            </div>
            
            <div class="view-all-link">
                <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=recruiters'); ?>">
                    <?php _e('Manage Recruiters', 'wp-job-board'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card quick-actions">
        <h3><?php _e('Quick Actions', 'wp-job-board'); ?></h3>
        
        <div class="action-buttons">
            <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=applications'); ?>" class="button button-primary">
                <?php _e('Manage Applications', 'wp-job-board'); ?>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=import'); ?>" class="button">
                <?php _e('Import Applications', 'wp-job-board'); ?>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=job_listing'); ?>" class="button">
                <?php _e('Manage Job Listings', 'wp-job-board'); ?>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load recruiter stats via AJAX
    $.ajax({
        url: wp_job_board_admin_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_recruiter_stats',
            nonce: wp_job_board_admin_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                $('#recruiter-stats-container').html('');
                
                if (response.data.length > 0) {
                    response.data.forEach(function(stat) {
                        $('#recruiter-stats-container').append(`
                            <div class="recruiter-stat-card">
                                <div class="recruiter-info">
                                    <h4>${stat.name}</h4>
                                    <p>${stat.email || ''}</p>
                                </div>
                                <div class="recruiter-numbers">
                                    <div class="total">${stat.total}</div>
                                    <div class="completed">${stat.completed} completed</div>
                                    <div class="pending">${stat.pending} pending</div>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    $('#recruiter-stats-container').html('<p><?php _e('No recruiters found.', 'wp-job-board'); ?></p>');
                }
            }
        },
        error: function() {
            $('#recruiter-stats-container').html('<p><?php _e('Error loading recruiter statistics.', 'wp-job-board'); ?></p>');
        }
    });
});
</script>