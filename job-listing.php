<?php
// Get query parameters
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';

// Build query args
$args = array(
    'post_type' => 'job_listing',
    'posts_per_page' => $atts['posts_per_page'],
    'paged' => $paged,
    'post_status' => 'publish'
);

// Add taxonomy filters
$tax_query = array();
if (!empty($category)) {
    $tax_query[] = array(
        'taxonomy' => 'job_category',
        'field' => 'slug',
        'terms' => $category
    );
}
if (!empty($type)) {
    $tax_query[] = array(
        'taxonomy' => 'job_type',
        'field' => 'slug',
        'terms' => $type
    );
}
if (!empty($location)) {
    $tax_query[] = array(
        'taxonomy' => 'job_location',
        'field' => 'slug',
        'terms' => $location
    );
}
if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

$jobs_query = new WP_Query($args);
?>

<div class="wp-job-board">
    <?php if ($atts['show_filters'] === 'true') : ?>
    <div class="job-filters">
        <form method="get" action="<?php echo get_post_type_archive_link('job_listing'); ?>">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="category"><?php _e('Category', 'wp-job-board'); ?></label>
                    <?php
                    wp_dropdown_categories(array(
                        'show_option_all' => __('All Categories', 'wp-job-board'),
                        'taxonomy' => 'job_category',
                        'name' => 'category',
                        'selected' => $category,
                        'value_field' => 'slug'
                    ));
                    ?>
                </div>
                
                <div class="filter-group">
                    <label for="type"><?php _e('Type', 'wp-job-board'); ?></label>
                    <?php
                    wp_dropdown_categories(array(
                        'show_option_all' => __('All Types', 'wp-job-board'),
                        'taxonomy' => 'job_type',
                        'name' => 'type',
                        'selected' => $type,
                        'value_field' => 'slug'
                    ));
                    ?>
                </div>
                
                <div class="filter-group">
                    <label for="location"><?php _e('Location', 'wp-job-board'); ?></label>
                    <?php
                    wp_dropdown_categories(array(
                        'show_option_all' => __('All Locations', 'wp-job-board'),
                        'taxonomy' => 'job_location',
                        'name' => 'location',
                        'selected' => $location,
                        'value_field' => 'slug'
                    ));
                    ?>
                </div>
                
                <div class="filter-group">
                    <input type="submit" value="<?php _e('Filter', 'wp-job-board'); ?>" class="button">
                    <a href="<?php echo get_post_type_archive_link('job_listing'); ?>" class="button">
                        <?php _e('Reset', 'wp-job-board'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="job-listings">
        <?php if ($jobs_query->have_posts()) : ?>
            <?php while ($jobs_query->have_posts()) : $jobs_query->the_post(); ?>
                <div class="job-listing">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    
                    <div class="job-meta">
                        <?php
                        $company = get_post_meta(get_the_ID(), '_company_name', true);
                        $location = get_the_terms(get_the_ID(), 'job_location');
                        $type = get_the_terms(get_the_ID(), 'job_type');
                        ?>
                        
                        <?php if (!empty($company)) : ?>
                            <span class="company"><?php echo esc_html($company); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($location)) : ?>
                            <span class="location"><?php echo esc_html($location[0]->name); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($type)) : ?>
                            <span class="job-type"><?php echo esc_html($type[0]->name); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="job-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <a href="<?php the_permalink(); ?>" class="view-job-button">
                        <?php _e('View Job', 'wp-job-board'); ?>
                    </a>
                </div>
            <?php endwhile; ?>
            
            <div class="job-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $jobs_query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => __('&laquo; Previous', 'wp-job-board'),
                    'next_text' => __('Next &raquo;', 'wp-job-board')
                ));
                ?>
            </div>
            
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php _e('No job listings found.', 'wp-job-board'); ?></p>
        <?php endif; ?>
    </div>
</div>