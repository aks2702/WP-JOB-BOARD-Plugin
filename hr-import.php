<?php
/**
 * Template for the HR Import screen.
 * Handles CSV import for job applications.
 *
 * @package WP_Job_Board
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check capabilities.
if ( ! current_user_can( 'import_applications' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-job-board' ) );
}

// Handle form submission.
$import_result = null;
if ( isset( $_POST['wp_job_board_csv_import'] ) ) {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wp_job_board_csv_import' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-job-board' ) );
	}

	if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
		$file          = $_FILES['csv_file']['tmp_name'];
		$import_result = WP_Job_Board_CSV_Importer::process_csv( $file );
	} else {
		$import_result = new WP_Error( 'no_file', __( 'Please select a CSV file to import.', 'wp-job-board' ) );
	}
}
?>

<div class="wrap wp-job-board hr-import">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Import Applications', 'wp-job-board' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( is_wp_error( $import_result ) ) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $import_result->get_error_message() ); ?></p>
		</div>
	<?php elseif ( is_int( $import_result ) ) : ?>
		<div class="notice notice-success">
			<p>
				<?php
				printf(
					/* translators: %d: Number of applications imported */
					esc_html__( 'Successfully imported %d applications.', 'wp-job-board' ),
					intval( $import_result )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="import-section">
		<h2><?php esc_html_e( 'CSV Import', 'wp-job-board' ); ?></h2>
		
		<p>
			<?php esc_html_e( 'Upload a CSV file to import job applications in bulk. Each row will be created as a new application.', 'wp-job-board' ); ?>
		</p>
		
		<div class="csv-requirements">
			<h3><?php esc_html_e( 'CSV File Requirements:', 'wp-job-board' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'File format: CSV (Comma-separated values)', 'wp-job-board' ); ?></li>
				<li><?php esc_html_e( 'Required columns: job_id, applicant_name, applicant_email', 'wp-job-board' ); ?></li>
				<li><?php esc_html_e( 'Optional columns: application_date, status, assigned_to, message', 'wp-job-board' ); ?></li>
				<li><?php esc_html_e( 'First row should contain column headers', 'wp-job-board' ); ?></li>
				<li><?php esc_html_e( 'Maximum file size: 8MB', 'wp-job-board' ); ?></li>
			</ul>
		</div>
		
		<form id="csv-upload-form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'wp_job_board_csv_import' ); ?>
			<input type="hidden" name="wp_job_board_csv_import" value="1">
			
			<div class="upload-area" id="csv-upload-area">
				<i class="dashicons dashicons-cloud-upload"></i>
				<h4><?php esc_html_e( 'Drag & Drop your CSV file here', 'wp-job-board' ); ?></h4>
				<p><?php esc_html_e( 'or', 'wp-job-board' ); ?></p>
				<button type="button" class="button button-primary" id="browse-csv-button">
					<?php esc_html_e( 'Browse Files', 'wp-job-board' ); ?>
				</button>
				<input type="file" name="csv_file" id="csv_file" accept=".csv" style="display: none;">
				<p class="file-name" id="csv-file-name"></p>
			</div>
			
			<p class="submit">
				<button type="submit" class="button button-primary" id="import-csv-button" disabled>
					<?php esc_html_e( 'Import CSV', 'wp-job-board' ); ?>
				</button>
				
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_job_board_download_csv_template' ) ); ?>" class="button">
					<?php esc_html_e( 'Download CSV Template', 'wp-job-board' ); ?>
				</a>
			</p>
		</form>
	</div>
	
	<div class="csv-columns-info">
		<h2><?php esc_html_e( 'CSV Column Information', 'wp-job-board' ); ?></h2>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Column Name', 'wp-job-board' ); ?></th>
					<th><?php esc_html_e( 'Required', 'wp-job-board' ); ?></th>
					<th><?php esc_html_e( 'Description', 'wp-job-board' ); ?></th>
					<th><?php esc_html_e( 'Example', 'wp-job-board' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>job_id</code></td>
					<td><?php esc_html_e( 'Yes', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'The ID of the job listing', 'wp-job-board' ); ?></td>
					<td><code>123</code></td>
				</tr>
				<tr>
					<td><code>applicant_name</code></td>
					<td><?php esc_html_e( 'Yes', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Full name of the applicant', 'wp-job-board' ); ?></td>
					<td><code>John Smith</code></td>
				</tr>
				<tr>
					<td><code>applicant_email</code></td>
					<td><?php esc_html_e( 'Yes', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Email address of the applicant', 'wp-job-board' ); ?></td>
					<td><code>john@example.com</code></td>
				</tr>
				<tr>
					<td><code>application_date</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Date of application (YYYY-MM-DD format)', 'wp-job-board' ); ?></td>
					<td><code>2023-08-15</code></td>
				</tr>
				<tr>
					<td><code>status</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td>
						<?php
						esc_html_e( 'Application status: ', 'wp-job-board' );
						echo esc_html( implode( ', ', WP_Job_Board_HR_Manager::get_application_statuses() ) );
						?>
					</td>
					<td><code>new</code></td>
				</tr>
				<tr>
					<td><code>assigned_to</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'ID of the recruiter to assign the application to', 'wp-job-board' ); ?></td>
					<td><code>45</code></td>
				</tr>
				<tr>
					<td><code>message</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Application message/cover letter', 'wp-job-board' ); ?></td>
					<td><code>I am very interested in this position...</code></td>
				</tr>
				<tr>
					<td><code>phone</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Applicant phone number', 'wp-job-board' ); ?></td>
					<td><code>+1234567890</code></td>
				</tr>
				<tr>
					<td><code>location</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'Applicant location', 'wp-job-board' ); ?></td>
					<td><code>New York, USA</code></td>
				</tr>
				<tr>
					<td><code>resume_url</code></td>
					<td><?php esc_html_e( 'No', 'wp-job-board' ); ?></td>
					<td><?php esc_html_e( 'URL to applicant resume (if hosted externally)', 'wp-job-board' ); ?></td>
					<td><code>https://example.com/resume.pdf</code></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="import-tips">
		<h2><?php esc_html_e( 'Import Tips', 'wp-job-board' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Make sure the job_id corresponds to an existing job listing.', 'wp-job-board' ); ?></li>
			<li><?php esc_html_e( 'For dates, use the YYYY-MM-DD format to ensure proper parsing.', 'wp-job-board' ); ?></li>
			<li><?php esc_html_e( 'If status is not provided, applications will be set to "new".', 'wp-job-board' ); ?></li>
			<li><?php esc_html_e( 'The assigned_to field should contain a valid user ID of a recruiter.', 'wp-job-board' ); ?></li>
			<li><?php esc_html_e( 'Test with a small file first to verify your CSV format is correct.', 'wp-job-board' ); ?></li>
		</ul>
	</div>
</div>

<style>
.wp-job-board.hr-import .upload-area {
	border: 2px dashed #ccd0d4;
	padding: 40px;
	text-align: center;
	margin: 20px 0;
	background: #f9f9f9;
}

.wp-job-board.hr-import .upload-area.drag-over {
	border-color: #0073aa;
	background: #f0f6fc;
}

.wp-job-board.hr-import .upload-area .dashicons {
	font-size: 48px;
	width: 48px;
	height: 48px;
	color: #72777c;
}

.wp-job-board.hr-import .file-name {
	margin-top: 10px;
	font-weight: bold;
	color: #0073aa;
}

.wp-job-board.hr-import .csv-requirements,
.wp-job-board.hr-import .import-tips {
	background: #f9f9f9;
	padding: 15px;
	border-left: 4px solid #0073aa;
	margin: 20px 0;
}

.wp-job-board.hr-import .csv-requirements ul,
.wp-job-board.hr-import .import-tips ul {
	list-style: disc;
	margin-left: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
	var uploadArea = $('#csv-upload-area');
	var fileInput = $('#csv_file');
	var browseButton = $('#browse-csv-button');
	var fileName = $('#csv-file-name');
	var importButton = $('#import-csv-button');
	
	// Browse button click handler
	browseButton.on('click', function() {
		fileInput.click();
	});
	
	// File input change handler
	fileInput.on('change', function() {
		if (this.files.length > 0) {
			fileName.text(this.files[0].name);
			importButton.prop('disabled', false);
		}
	});
	
	// Drag and drop handlers
	uploadArea.on('dragover', function(e) {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.addClass('drag-over');
	});
	
	uploadArea.on('dragleave', function(e) {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.removeClass('drag-over');
	});
	
	uploadArea.on('drop', function(e) {
		e.preventDefault();
		e.stopPropagation();
		uploadArea.removeClass('drag-over');
		
		var files = e.originalEvent.dataTransfer.files;
		if (files.length > 0) {
			fileInput.prop('files', files);
			fileName.text(files[0].name);
			importButton.prop('disabled', false);
		}
	});
	
	// Prevent default drag behaviors
	$(document).on('dragover dragenter', function(e) {
		e.preventDefault();
		e.stopPropagation();
	});
	
	$(document).on('drop', function(e) {
		e.preventDefault();
		e.stopPropagation();
	});
});
</script>