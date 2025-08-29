<?php
/**
 * Template-related functions for WP Job Board plugin.
 *
 * @package WP_Job_Board
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display job listings.
 *
 * @param array $args Query arguments.
 */
function wpjb_display_job_listings( $args = array() ) {
	$defaults = array(
		'posts_per_page' => wpjb_get_option( 'jobs_per_page', 10 ),
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	$query = new WP_Query( $args );
	
	if ( $query->have_posts() ) {
		echo '<div class="wpjb-job-listings">';
		
		while ( $query->have_posts() ) {
			$query->the_post();
			wpjb_get_template_part( 'content', 'job-listing' );
		}
		
		echo '</div>';
		
		// Pagination.
		if ( $query->max_num_pages > 1 ) {
			echo '<div class="wpjb-pagination">';
			echo paginate_links( array(
				'current'   => max( 1, get_query_var( 'paged' ) ),
				'total'     => $query->max_num_pages,
				'prev_text' => __( '&laquo; Previous', 'wp-job-board' ),
				'next_text' => __( 'Next &raquo;', 'wp-job-board' ),
			) );
			echo '</div>';
		}
		
		wp_reset_postdata();
	} else {
		echo '<p class="wpjb-no-jobs">' . esc_html__( 'No job listings found.', 'wp-job-board' ) . '</p>';
	}
}

/**
 * Display job application form.
 *
 * @param int $job_id Job ID.
 */
function wpjb_display_application_form( $job_id = 0 ) {
	if ( ! $job_id ) {
		global $post;
		$job_id = $post->ID;
	}
	
	$job = get_post( $job_id );
	
	if ( ! $job || 'job_listing' !== $job->post_type ) {
		echo '<p class="wpjb-error">' . esc_html__( 'Invalid job listing.', 'wp-job-board' ) . '</p>';
		return;
	}
	
	wpjb_get_template_part( 'application', 'form', array( 'job_id' => $job_id ) );
}

/**
 * Display HR dashboard navigation.
 */
function wpjb_hr_dashboard_navigation() {
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'overview';
	$pages        = array(
		'overview'     => __( 'Overview', 'wp-job-board' ),
		'applications' => __( 'Applications', 'wp-job-board' ),
		'recruiters'   => __( 'Recruiters', 'wp-job-board' ),
		'import'       => __( 'Import', 'wp-job-board' ),
	);
	?>
	<nav class="wpjb-hr-nav">
		<ul>
			<?php foreach ( $pages as $slug => $label ) : ?>
				<li class="<?php echo $current_page === $slug ? 'active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'page', $slug ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Display application status badge.
 *
 * @param string $status Application status.
 */
function wpjb_application_status_badge( $status ) {
	$statuses = wpjb_get_application_statuses();
	$label    = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	$class    = 'wpjb-status-' . sanitize_html_class( $status );
	
	printf(
		'<span class="wpjb-status-badge %s">%s</span>',
		esc_attr( $class ),
		esc_html( $label )
	);
}

/**
 * Display job meta information.
 *
 * @param int $job_id Job ID.
 */
function wpjb_job_meta( $job_id = 0 ) {
	if ( ! $job_id ) {
		global $post;
		$job_id = $post->ID;
	}
	
	$location = get_post_meta( $job_id, '_job_location', true );
	$type     = get_the_terms( $job_id, 'job_type' );
	$category = get_the_terms( $job_id, 'job_category' );
	?>
	<div class="wpjb-job-meta">
		<?php if ( $location ) : ?>
			<span class="wpjb-meta-item location">
				<i class="dashicons dashicons-location"></i>
				<?php echo esc_html( $location ); ?>
			</span>
		<?php endif; ?>
		
		<?php if ( $type && ! is_wp_error( $type ) ) : ?>
			<span class="wpjb-meta-item type">
				<i class="dashicons dashicons-clock"></i>
				<?php echo esc_html( $type[0]->name ); ?>
			</span>
		<?php endif; ?>
		
		<?php if ( $category && ! is_wp_error( $category ) ) : ?>
			<span class="wpjb-meta-item category">
				<i class="dashicons dashicons-category"></i>
				<?php echo esc_html( $category[0]->name ); ?>
			</span>
		<?php endif; ?>
		
		<span class="wpjb-meta-item date">
			<i class="dashicons dashicons-calendar"></i>
			<?php echo esc_html( get_the_date( '', $job_id ) ); ?>
		</span>
	</div>
	<?php
}

/**
 * Display pagination for custom queries.
 *
 * @param WP_Query $query WordPress query object.
 */
function wpjb_pagination( $query = null ) {
	if ( ! $query ) {
		global $wp_query;
		$query = $wp_query;
	}
	
	if ( $query->max_num_pages <= 1 ) {
		return;
	}
	
	$big = 999999999; // Need an unlikely integer.
	
	$pagination = paginate_links( array(
		'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		'format'    => '?paged=%#%',
		'current'   => max( 1, get_query_var( 'paged' ) ),
		'total'     => $query->max_num_pages,
		'type'      => 'array',
		'prev_text' => __( '&laquo; Previous', 'wp-job-board' ),
		'next_text' => __( 'Next &raquo;', 'wp-job-board' ),
	) );
	
	if ( ! empty( $pagination ) ) {
		echo '<nav class="wpjb-pagination"><ul>';
		foreach ( $pagination as $page ) {
			echo '<li>' . $page . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul></nav>';
	}
}

/**
 * Display flash messages.
 *
 * @param string $type    Message type (success, error, warning, info).
 * @param string $message Message content.
 * @param bool   $echo    Whether to echo or return.
 * @return string|void
 */
function wpjb_flash_message( $type = 'info', $message = '', $echo = true ) {
	if ( empty( $message ) ) {
		return;
	}
	
	$html = sprintf(
		'<div class="wpjb-flash-message wpjb-%s"><p>%s</p></div>',
		esc_attr( $type ),
		wp_kses_post( $message )
	);
	
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $html;
	}
}

/**
 * Display loading spinner.
 *
 * @param string $size Spinner size (small, medium, large).
 */
function wpjb_loading_spinner( $size = 'medium' ) {
	printf(
		'<div class="wpjb-loading wpjb-loading-%s"><span class="spinner"></span></div>',
		esc_attr( $size )
	);
}

/**
 * Display file upload field with restrictions.
 *
 * @param string $name        Field name.
 * @param string $label       Field label.
 * @param array  $attachments Current attachments.
 */
function wpjb_file_upload_field( $name, $label, $attachments = array() ) {
	$max_size    = wpjb_get_option( 'max_file_size', 8 ) * 1024 * 1024; // Convert to bytes.
	$file_types  = wpjb_get_option( 'allowed_file_types', array( 'pdf', 'doc', 'docx' ) );
	$accept      = '.' . implode( ',.', $file_types );
	?>
	<div class="wpjb-file-upload">
		<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
		
		<?php if ( ! empty( $attachments ) ) : ?>
			<div class="wpjb-current-files">
				<?php foreach ( $attachments as $attachment ) : ?>
					<div class="wpjb-file-item">
						<?php echo esc_html( basename( $attachment ) ); ?>
						<a href="#" class="wpjb-remove-file" data-field="<?php echo esc_attr( $name ); ?>">
							<?php esc_html_e( 'Remove', 'wp-job-board' ); ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		
		<input type="file" 
			   name="<?php echo esc_attr( $name ); ?>" 
			   id="<?php echo esc_attr( $name ); ?>"
			   accept="<?php echo esc_attr( $accept ); ?>"
			   data-max-size="<?php echo esc_attr( $max_size ); ?>">
		
		<small class="wpjb-file-help">
			<?php
			printf(
				/* translators: 1: Maximum file size, 2: Allowed file types */
				esc_html__( 'Maximum file size: %1$sMB. Allowed file types: %2$s.', 'wp-job-board' ),
				esc_html( wpjb_get_option( 'max_file_size', 8 ) ),
				esc_html( implode( ', ', $file_types ) )
			);
			?>
		</small>
	</div>
	<?php
}