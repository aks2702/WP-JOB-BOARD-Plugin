<?php
// Check capabilities
if (!current_user_can('manage_job_applications')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-job-board'));
}

global $wpdb;

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$job_filter = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$recruiter_filter = isset($_GET['recruiter_id']) ? intval($_GET['recruiter_id']) : 0;

// Build query
$applications_table = $wpdb->prefix . 'job_applications';
$where_conditions = array();
$query_params = array();

if (!empty($status_filter)) {
    $where_conditions[] = 'status = %s';
    $query_params[] = $status_filter;
}

if ($job_filter > 0) {
    $where_conditions[] = 'job_id = %d';
    $query_params[] = $job_filter;
}

if ($recruiter_filter > 0) {
    $where_conditions[] = 'assigned_to = %d';
    $query_params[] = $recruiter_filter;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Prepare and execute query
$query = "SELECT * FROM $applications_table $where_sql ORDER BY application_date DESC";
if (!empty($query_params)) {
    $query = $wpdb->prepare($query, $query_params);
}

$applications = $wpdb->get_results($query);

// Get all jobs for filter
$jobs = get_posts(array(
    'post_type' => 'job_listing',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));

// Get all recruiters for filter
$recruiters = WP_Job_Board_HR_Manager::get_recruiters();

// Status options
$status_options = array(
    '' => __('All Statuses', 'wp-job-board'),
    'new' => __('New', 'wp-job-board'),
    'assigned' => __('Assigned', 'wp-job-board'),
    'review' => __('In Review', 'wp-job-board'),
    'interview' => __('Interview', 'wp-job-board'),
    'rejected' => __('Rejected', 'wp-job-board'),
    'hired' => __('Hired', 'wp-job-board')
);
?>

<div class="hr-applications">
    <h2><?php _e('Applications Management', 'wp-job-board'); ?></h2>
    
    <div class="applications-filters">
        <form method="get">
            <input type="hidden" name="post_type" value="job_listing">
            <input type="hidden" name="page" value="hr-management">
            <input type="hidden" name="tab" value="applications">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status"><?php _e('Status', 'wp-job-board'); ?></label>
                    <select id="status" name="status">
                        <?php foreach ($status_options as $value => $label) : ?>
                            <option value="<?php echo $value; ?>" <?php selected($status_filter, $value); ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="job_id"><?php _e('Job', 'wp-job-board'); ?></label>
                    <select id="job_id" name="job_id">
                        <option value="0"><?php _e('All Jobs', 'wp-job-board'); ?></option>
                        <?php foreach ($jobs as $job) : ?>
                            <option value="<?php echo $job->ID; ?>" <?php selected($job_filter, $job->ID); ?>>
                                <?php echo esc_html($job->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="recruiter_id"><?php _e('Recruiter', 'wp-job-board'); ?></label>
                    <select id="recruiter_id" name="recruiter_id">
                        <option value="0"><?php _e('All Recruiters', 'wp-job-board'); ?></option>
                        <?php foreach ($recruiters as $recruiter) : ?>
                            <option value="<?php echo $recruiter->ID; ?>" <?php selected($recruiter_filter, $recruiter->ID); ?>>
                                <?php echo esc_html($recruiter->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="submit" value="<?php _e('Filter', 'wp-job-board'); ?>" class="button button-primary">
                    <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=applications'); ?>" class="button">
                        <?php _e('Reset', 'wp-job-board'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="applications-list">
        <?php if (!empty($applications)) : ?>
            <table class="applications-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Applicant', 'wp-job-board'); ?></th>
                        <th><?php _e('Job Position', 'wp-job-board'); ?></th>
                        <th><?php _e('Applied On', 'wp-job-board'); ?></th>
                        <th><?php _e('Status', 'wp-job-board'); ?></th>
                        <th><?php _e('Assigned To', 'wp-job-board'); ?></th>
                        <th><?php _e('Actions', 'wp-job-board'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application) : 
                        $job_title = get_the_title($application->job_id);
                        $recruiter = $application->assigned_to > 0 ? get_userdata($application->assigned_to) : null;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($application->applicant_name); ?></strong>
                                <br>
                                <small><?php echo esc_html($application->applicant_email); ?></small>
                                <?php if (!empty($application->cv_url)) : ?>
                                    <br>
                                    <a href="<?php echo esc_url($application->cv_url); ?>" target="_blank" class="cv-link">
                                        <?php _e('View CV', 'wp-job-board'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($job_title) : ?>
                                    <a href="<?php echo get_edit_post_link($application->job_id); ?>">
                                        <?php echo esc_html($job_title); ?>
                                    </a>
                                <?php else : ?>
                                    <?php _e('Job not found', 'wp-job-board'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($application->application_date)); ?></td>
                            <td>
                                <select class="application-status" data-application-id="<?php echo $application->id; ?>">
                                    <?php foreach ($status_options as $value => $label) : 
                                        if (empty($value)) continue; ?>
                                        <option value="<?php echo $value; ?>" <?php selected($application->status, $value); ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select class="assignment-dropdown" data-application-id="<?php echo $application->id; ?>">
                                    <option value="0"><?php _e('— Unassigned —', 'wp-job-board'); ?></option>
                                    <?php foreach ($recruiters as $recruiter_option) : ?>
                                        <option value="<?php echo $recruiter_option->ID; ?>" 
                                            <?php selected($application->assigned_to, $recruiter_option->ID); ?>>
                                            <?php echo esc_html($recruiter_option->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button class="button view-application" data-application-id="<?php echo $application->id; ?>">
                                    <?php _e('View Details', 'wp-job-board'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info">
                <p><?php _e('No applications found matching your criteria.', 'wp-job-board'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="application-details-modal" style="display: none;">
    <div class="application-details-content"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle status changes
    $('.application-status').on('change', function() {
        var applicationId = $(this).data('application-id');
        var newStatus = $(this).val();
        
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_application_status',
                application_id: applicationId,
                status: newStatus,
                nonce: wp_job_board_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification(response.data.message, 'success');
                } else {
                    // Show error message
                    showNotification(response.data.message, 'error');
                    // Revert the selection
                    $(this).val($(this).data('previous-value'));
                }
            }.bind(this),
            error: function() {
                showNotification('<?php _e('An error occurred. Please try again.', 'wp-job-board'); ?>', 'error');
                $(this).val($(this).data('previous-value'));
            }.bind(this)
        });
        
        // Store previous value for revert
        $(this).data('previous-value', $(this).val());
    });
    
    // Handle recruiter assignments
    $('.assignment-dropdown').on('change', function() {
        var applicationId = $(this).data('application-id');
        var recruiterId = $(this).val();
        
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'assign_recruiter',
                application_id: applicationId,
                recruiter_id: recruiterId,
                nonce: wp_job_board_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Update status to assigned if it was new
                    if (recruiterId > 0) {
                        var statusDropdown = $('.application-status[data-application-id="' + applicationId + '"]');
                        if (statusDropdown.val() === 'new') {
                            statusDropdown.val('assigned').trigger('change');
                        }
                    }
                } else {
                    showNotification(response.data.message, 'error');
                    $(this).val($(this).data('previous-value'));
                }
            }.bind(this),
            error: function() {
                showNotification('<?php _e('An error occurred. Please try again.', 'wp-job-board'); ?>', 'error');
                $(this).val($(this).data('previous-value'));
            }.bind(this)
        });
        
        // Store previous value for revert
        $(this).data('previous-value', $(this).val());
    });
    
    // View application details
    $('.view-application').on('click', function() {
        var applicationId = $(this).data('application-id');
        
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_application_details',
                application_id: applicationId,
                nonce: wp_job_board_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var application = response.data;
                    var modalContent = `
                        <h2>${application.applicant_name} - Application Details</h2>
                        <div class="application-details">
                            <p><strong>Email:</strong> ${application.applicant_email}</p>
                            <p><strong>Job ID:</strong> ${application.job_id}</p>
                            <p><strong>Applied On:</strong> ${application.application_date}</p>
                            <p><strong>Status:</strong> ${application.status}</p>
                            ${application.message ? `<p><strong>Message:</strong><br>${application.message}</p>` : ''}
                            ${application.cv_url ? `<p><strong>CV:</strong> <a href="${application.cv_url}" target="_blank">Download CV</a></p>` : ''}
                        </div>
                    `;
                    
                    $('#application-details-modal .application-details-content').html(modalContent);
                    $('#application-details-modal').dialog({
                        modal: true,
                        width: 600,
                        title: 'Application Details'
                    });
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('<?php _e('An error occurred. Please try again.', 'wp-job-board'); ?>', 'error');
            }
        });
    });
    
    function showNotification(message, type) {
        // Remove any existing notifications
        $('.job-board-notification').remove();
        
        var notification = $('<div class="job-board-notification notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.hr-applications h2').after(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>