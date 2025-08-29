jQuery(document).ready(function($) {
    // HR Dashboard functionality
    function initHRDashboard() {
        // Load recruiter stats
        loadRecruiterStats();
        
        // Handle application status changes
        $('.application-status').on('change', function() {
            var applicationId = $(this).data('application-id');
            var newStatus = $(this).val();
            
            updateApplicationStatus(applicationId, newStatus);
        });
        
        // Handle recruiter assignments
        $('.assignment-dropdown').on('change', function() {
            var applicationId = $(this).data('application-id');
            var recruiterId = $(this).val();
            
            assignRecruiter(applicationId, recruiterId);
        });
        
        // CSV import handling
        $('#csv-upload-form').on('submit', function(e) {
            e.preventDefault();
            importCSV();
        });
    }
    
    function loadRecruiterStats() {
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_recruiter_stats',
                nonce: wp_job_board_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayRecruiterStats(response.data);
                }
            }
        });
    }
    
    function displayRecruiterStats(stats) {
        var container = $('#recruiter-stats-container');
        if (!container.length) return;
        
        container.empty();
        
        stats.forEach(function(stat) {
            var html = `
                <div class="recruiter-stat-card">
                    <div class="recruiter-info">
                        <h4>${stat.name}</h4>
                        <p>${stat.email}</p>
                    </div>
                    <div class="recruiter-numbers">
                        <div class="total">${stat.total}</div>
                        <div class="completed">${stat.completed} completed</div>
                        <div class="pending">${stat.pending} pending</div>
                    </div>
                </div>
            `;
            
            container.append(html);
        });
    }
    
    function updateApplicationStatus(applicationId, status) {
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_application_status',
                application_id: applicationId,
                status: status,
                nonce: wp_job_board_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            }
        });
    }
    
    function assignRecruiter(applicationId, recruiterId) {
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
                    // Update UI to reflect the assignment
                    $(`[data-application-id="${applicationId}"]`).closest('tr').find('.status-badge')
                        .removeClass('status-new')
                        .addClass('status-assigned')
                        .text('Assigned');
                } else {
                    showNotification(response.data.message, 'error');
                }
            }
        });
    }
    
    function importCSV() {
        var form = $('#csv-upload-form');
        var formData = new FormData(form[0]);
        formData.append('action', 'import_applications');
        formData.append('nonce', wp_job_board_admin_ajax.nonce);
        
        var button = form.find('button[type="submit"]');
        button.prop('disabled', true).text('Importing...');
        
        $.ajax({
            url: wp_job_board_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Reload the page to see imported applications
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred during import.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Import CSV');
            }
        });
    }
    
    function showNotification(message, type) {
        // Remove any existing notifications
        $('.job-board-notification').remove();
        
        var notification = $('<div class="job-board-notification notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Initialize HR dashboard if we're on that page
    if ($('.hr-dashboard-content').length) {
        initHRDashboard();
    }
    
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).attr('href').split('tab=')[1];
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show relevant content
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
        
        // Update URL
        history.pushState(null, null, $(this).attr('href'));
    });
    
    // Handle initial tab
    var urlParams = new URLSearchParams(window.location.search);
    var initialTab = urlParams.get('tab') || 'overview';
    
    $(`.nav-tab[href*="tab=${initialTab}"]`).addClass('nav-tab-active');
    $(`#tab-${initialTab}`).addClass('active');
});