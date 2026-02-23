<?php
/**
 * Course listing partial for page-courses-landing.php
 * This replicates the archive behavior (search, filters, grid/list, pagination)
 *
 * Place this file in: wp-content/themes/buddyboss-theme-child/page-courses-landing-grid.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize GET inputs we use:
 * - search: simple text
 * - paged: integer
 * - order, orderby: passed through as-is to helper (BuddyBoss prints options)
 * - filter-categories / filter-instructors: left to BuddyBoss helper output (select options contain sanitized values)
 */
$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$paged  = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 ) );

// Posts per page: use theme option when available, otherwise fallback to WP posts_per_page.
$posts_per_page = get_option( 'posts_per_page', 10 );
if ( function_exists( 'buddyboss_theme_get_option' ) ) {
	$theme_ppp = buddyboss_theme_get_option( 'learndash_course_index_posts_per_page' );
	if ( ! empty( $theme_ppp ) ) {
		$posts_per_page = intval( $theme_ppp );
	}
}

// Build base query args
$query_args = array(
	'post_type'      => 'sfwd-courses',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_per_page,
	'paged'          => $paged,
);

if ( ! empty( $search ) ) {
	$query_args['s'] = $search;
}

// Allow other plugins/themes to modify the listing query args.
$query_args = apply_filters( 'bz_courses_landing_query_args', $query_args );

$course_query = new WP_Query( $query_args );
$courses_label = function_exists( 'LearnDash_Custom_Label' ) ? LearnDash_Custom_Label::get_label( 'courses' ) : __( 'Courses', 'buddyboss-theme' );
$course_label  = function_exists( 'LearnDash_Custom_Label' ) ? LearnDash_Custom_Label::get_label( 'course' ) : __( 'Course', 'buddyboss-theme' );
?>

<div id="learndash-content" class="learndash-course-list">
	<form id="bb-courses-directory-form" class="bb-courses-directory" method="get" action="">
		<input type="hidden" name="current_page" value="<?php echo esc_attr( $paged ); ?>">
		<div class="flex align-items-center bb-courses-header">
			<h4 class="bb-title"><?php echo esc_html( $courses_label ); ?></h4>
			<div id="courses-dir-search" class="bs-dir-search" role="search">
				<div id="search-members-form" class="bs-search-form">
					<label for="bs_members_search" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss-theme' ); ?></label>
					<input type="text" name="search" id="bs_members_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search courses', 'buddyboss-theme' ); ?>" />
					<button class="button" type="submit"><?php esc_html_e( 'Search', 'buddyboss-theme' ); ?></button>
				</div>
			</div>
		</div>

		<div class="ld-secondary-header">
			<?php
			if ( ! function_exists( 'bb_enable_content_counts' ) || bb_enable_content_counts() ) {
				$count = $course_query->found_posts;
				if ( false !== $count ) {
					printf(
						wp_kses(
							_n( '<span class="bb-count">%d</span> ' . $course_label, '<span class="bb-count">%d</span> ' . $courses_label, $count, 'buddyboss-theme' ),
							array( 'span' => array( 'class' => true ) )
						),
						(int) $count
					);
				}
			}
			?>
			<div class="bb-secondary-list-tabs flex align-items-center" id="subnav" role="navigation">
				<input type="hidden" id="course-order" name="order" value="<?php echo ! empty( $_GET['order'] ) ? esc_attr( wp_unslash( $_GET['order'] ) ) : 'desc'; ?>"/>
				<div class="sfwd-courses-filters flex push-right">
					<div class="select-wrap">
						<select id="sfwd_prs-order-by" name="orderby" aria-label="<?php esc_attr_e( 'Order by', 'buddyboss-theme' ); ?>">
							<?php echo buddyboss_theme()->learndash_helper()->print_sorting_options(); ?>
						</select>
					</div>

					<?php if ( buddyboss_theme_get_option( 'learndash_course_index_show_categories_filter' ) ) : ?>
						<div class="select-wrap">
							<?php if ( '' !== trim( buddyboss_theme()->learndash_helper()->print_categories_options() ) ) { ?>
								<select id="sfwd_cats-order-by" name="filter-categories" aria-label="<?php esc_attr_e( 'Filter by category', 'buddyboss-theme' ); ?>">
									<?php echo buddyboss_theme()->learndash_helper()->print_categories_options(); ?>
								</select>
							<?php } ?>
						</div>
					<?php endif; ?>

					<?php if ( buddyboss_theme_get_option( 'learndash_course_index_show_instructors_filter' ) ) : ?>
						<div class="select-wrap">
							<select id="sfwd_instructors-order-by" name="filter-instructors" aria-label="<?php esc_attr_e( 'Filter by instructor', 'buddyboss-theme' ); ?>">
								<?php echo buddyboss_theme()->learndash_helper()->print_instructors_options(); ?>
							</select>
						</div>
					<?php endif; ?>
				</div>

				<div class="grid-filters" data-view="ld-course">
					<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo esc_attr( ( 'grid' === bb_theme_get_directory_layout_preference( 'ld-course' ) ) ? 'active' : '' ); ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Grid view', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
					</a>

					<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo esc_attr( ( 'list' === bb_theme_get_directory_layout_preference( 'ld-course' ) ) ? 'active' : '' ); ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'List view', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-menu" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		</div>

		<div class="grid-view bb-grid">
			<div id="course-dir-list" class="course-dir-list bs-dir-list">
				<?php if ( $course_query->have_posts() ) : ?>
					<ul class="bb-course-items <?php echo esc_attr( bb_theme_get_directory_layout_preference( 'ld-course' ) === 'grid' ? 'grid-view bb-grid' : 'list-view bb-list' ); ?>" aria-live="assertive" aria-relevant="all">
						<?php
						while ( $course_query->have_posts() ) :
							$course_query->the_post();
							get_template_part( 'learndash/ld30/template-course-item' );
						endwhile;
						?>
					</ul>

					<div class="bb-lms-pagination">
						<?php
						$big = 999999999;
						echo paginate_links(
							array(
								'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
								'format'             => '?paged=%#%',
								'current'            => max( 1, $paged ),
								'total'              => $course_query->max_num_pages,
								'before_page_number' => '<span class="screen-reader-text">' . esc_html__( 'Page', 'buddyboss-theme' ) . ' </span>',
							)
						);
						?>
					</div>
				<?php else : ?>
					<aside class="bp-feedback bp-template-notice ld-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Sorry, no courses were found.', 'buddyboss-theme' ); ?></p>
					</aside>
				<?php endif; ?>

				<?php wp_reset_postdata(); ?>
			</div>
		</div>
	</form>
</div>