jQuery(document).ready(function($) {
    // Application form handling
    $('#wp-job-board-application-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var button = form.find('.submit-application-button');
        
        button.prop('disabled', true).text(wp_job_board_ajax.loading_text || 'Submitting...');
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
                form.find('.form-message').addClass('error').html(wp_job_board_ajax.error_text || 'An error occurred. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text(wp_job_board_ajax.submit_text || 'Submit Application');
            }
        });
    });
    
    // Job filters
    $('.job-filters form').on('change', function() {
        if ($(this).hasClass('auto-submit')) {
            $(this).submit();
        }
    });
    
    // Initialize any other frontend functionality
    if (typeof initJobBoardFrontend === 'function') {
        initJobBoardFrontend();
    }
});