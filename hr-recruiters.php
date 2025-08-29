<?php
// Check capabilities
if (!current_user_can('manage_job_applications')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-job-board'));
}

// Get all recruiters
$recruiters = WP_Job_Board_HR_Manager::get_recruiters();

// Get recruiter statistics
global $wpdb;
$applications_table = $wpdb->prefix . 'job_applications';

$recruiter_stats = array();
foreach ($recruiters as $recruiter) {
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $applications_table WHERE assigned_to = %d",
        $recruiter->ID
    ));
    
    $completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $applications_table WHERE assigned_to = %d AND status = 'completed'",
        $recruiter->ID
    ));
    
    $recruiter_stats[$recruiter->ID] = array(
        'total' => $total,
        'completed' => $completed,
        'pending' => $total - $completed
    );
}
?>

<div class="hr-recruiters">
    <h2><?php _e('Recruiters Management', 'wp-job-board'); ?></h2>
    
    <div class="recruiters-list">
        <?php if (!empty($recruiters)) : ?>
            <table class="recruiters-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Recruiter', 'wp-job-board'); ?></th>
                        <th><?php _e('Email', 'wp-job-board'); ?></th>
                        <th><?php _e('Total Applications', 'wp-job-board'); ?></th>
                        <th><?php _e('Completed', 'wp-job-board'); ?></th>
                        <th><?php _e('Pending', 'wp-job-board'); ?></th>
                        <th><?php _e('Completion Rate', 'wp-job-board'); ?></th>
                        <th><?php _e('Actions', 'wp-job-board'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recruiters as $recruiter) : 
                        $stats = $recruiter_stats[$recruiter->ID] ?? array('total' => 0, 'completed' => 0, 'pending' => 0);
                        $completion_rate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($recruiter->display_name); ?></strong>
                            </td>
                            <td><?php echo esc_html($recruiter->user_email); ?></td>
                            <td><?php echo $stats['total']; ?></td>
                            <td>
                                <span class="completed-count"><?php echo $stats['completed']; ?></span>
                            </td>
                            <td>
                                <span class="pending-count"><?php echo $stats['pending']; ?></span>
                            </td>
                            <td>
                                <div class="completion-rate">
                                    <div class="rate-value"><?php echo $completion_rate; ?>%</div>
                                    <div class="rate-bar">
                                        <div class="rate-progress" style="width: <?php echo $completion_rate; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $recruiter->ID); ?>" class="button">
                                    <?php _e('Edit Profile', 'wp-job-board'); ?>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=job_listing&page=hr-management&tab=applications&recruiter_id=' . $recruiter->ID); ?>" class="button">
                                    <?php _e('View Applications', 'wp-job-board'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info">
                <p><?php _e('No recruiters found.', 'wp-job-board'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="add-recruiter-section">
        <h3><?php _e('Add New Recruiter', 'wp-job-board'); ?></h3>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="wp_job_board_add_recruiter">
            <?php wp_nonce_field('wp_job_board_add_recruiter'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="recruiter_username"><?php _e('Username', 'wp-job-board'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="recruiter_username" name="username" required class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="recruiter_email"><?php _e('Email', 'wp-job-board'); ?> *</label>
                    </th>
                    <td>
                        <input type="email" id="recruiter_email" name="email" required class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="recruiter_first_name"><?php _e('First Name', 'wp-job-board'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="recruiter_first_name" name="first_name" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="recruiter_last_name"><?php _e('Last Name', 'wp-job-board'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="recruiter_last_name" name="last_name" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="recruiter_password"><?php _e('Password', 'wp-job-board'); ?> *</label>
                    </th>
                    <td>
                        <input type="password" id="recruiter_password" name="password" required class="regular-text">
                        <p class="description"><?php _e('Set a strong password for the new recruiter.', 'wp-job-board'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add Recruiter', 'wp-job-board'); ?>">
            </p>
        </form>
    </div>
</div>

<style>
.completion-rate {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rate-value {
    min-width: 40px;
    font-weight: 600;
}

.rate-bar {
    flex-grow: 1;
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.rate-progress {
    height: 100%;
    background: #46b450;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.completed-count {
    color: #46b450;
    font-weight: 600;
}

.pending-count {
    color: #ffb900;
    font-weight: 600;
}
</style>