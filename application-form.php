<?php
$job_id = $atts['job_id'];
if (empty($job_id) && isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);
}

if (empty($job_id)) {
    echo '<p>' . __('No job specified.', 'wp-job-board') . '</p>';
    return;
}

$job = get_post($job_id);
if (!$job || $job->post_type !== 'job_listing') {
    echo '<p>' . __('Invalid job.', 'wp-job-board') . '</p>';
    return;
}

// Check if application deadline has passed
$deadline = get_post_meta($job_id, '_application_deadline', true);
if (!empty($deadline) && strtotime($deadline) < time()) {
    echo '<p>' . __('Applications for this job are closed.', 'wp-job-board') . '</p>';
    return;
}
?>

<div class="job-application-form">
    <h3><?php printf(__('Apply for: %s', 'wp-job-board'), $job->post_title); ?></h3>
    
    <form id="wp-job-board-application-form" enctype="multipart/form-data">
        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
        <input type="hidden" name="action" value="submit_job_application">
        <?php wp_nonce_field('wp_job_board_nonce', 'nonce'); ?>
        
        <div class="form-group">
            <label for="applicant_name"><?php _e('Full Name', 'wp-job-board'); ?> *</label>
            <input type="text" id="applicant_name" name="applicant_name" required>
        </div>
        
        <div class="form-group">
            <label for="applicant_email"><?php _e('Email Address', 'wp-job-board'); ?> *</label>
            <input type="email" id="applicant_email" name="applicant_email" required>
        </div>
        
        <div class="form-group">
            <label for="message"><?php _e('Cover Letter', 'wp-job-board'); ?></label>
            <textarea id="message" name="message" rows="5"></textarea>
        </div>
        
        <div class="form-group">
            <label for="cv"><?php _e('Upload CV', 'wp-job-board'); ?> (PDF, DOC, DOCX)</label>
            <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx">
        </div>
        
        <div class="form-group">
            <button type="submit" class="submit-application-button">
                <?php _e('Submit Application', 'wp-job-board'); ?>
            </button>
        </div>
        
        <div class="form-message"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wp-job-board-application-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var button = form.find('.submit-application-button');
        
        button.prop('disabled', true).text('<?php _e('Submitting...', 'wp-job-board'); ?>');
        form.find('.form-message').removeClass('success error').html('');
        
        $.ajax({
            url: wp_job_board_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    form.find('.form-message').addClass('success').html(response.data.message);
                    form[0].reset();
                } else {
                    form.find('.form-message').addClass('error').html(response.data.message);
                }
            },
            error: function() {
                form.find('.form-message').addClass('error').html('<?php _e('An error occurred. Please try again.', 'wp-job-board'); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Submit Application', 'wp-job-board'); ?>');
            }
        });
    });
});
</script>