<?php
/**
 * Template Name: Courses Landing
 * Description: Full-width container for the Courses landing page.
 * Shows (in order):
 *  - Full-width featured image (from courses/courses-landing-top if present; fallback to guest)
 *  - Top page content (courses/courses-landing-top)
 *  - Dashboard (courses/courses-landing-dash) OR Guest content (courses/courses-landing-guest) — both full-width
 *  - Course grid/list (page-courses-landing-grid.php partial)
 *  - Bottom page content (courses/courses-landing-bottom) OR forums fallback
 *
 * Place this file in: wp-content/themes/buddyboss-theme-child/page-courses-landing.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<style>
/* Full-bleed hero container (neutralize parent layout width/padding) */
.fullwidth-hero-container {
	width: 100vw;
	position: relative;
	left: 50%;
	right: 50%;
	margin-left: -50vw;
	margin-right: -50vw;
	padding: 0;
	overflow: hidden;
	line-height: 0; /* remove whitespace */
	z-index: 1;
}
/* Image inside hero - responsive and covers horizontal space */
.fullwidth-hero-container .fullwidth-hero-image {
	display: block;
	width: 100%;
	height: auto;
	max-height: 720px; /* adjust as needed */
	object-fit: cover;
}

/* Full-bleed content container for embeds (top, dash, guest) */
.fullwidth-embed {
	width: 100vw;
	position: relative;
	left: 50%;
	right: 50%;
	margin-left: -50vw;
	margin-right: -50vw;
	padding-left: 0;
	padding-right: 0;
	overflow: visible;
	background: transparent;
}

/* Inner wrapper so editors' content retains some vertical breathing room */
.fullwidth-embed .bb-landing-part {
	padding: 28px 24px;
	max-width: 1200px; /* keep content readable on very wide screens */
	margin: 0 auto;
	box-sizing: border-box;
}

/* Tighten small gap if theme inserts header margin before first element */
#masthead + .fullwidth-hero-container,
#masthead + .fullwidth-embed,
.header + .fullwidth-hero-container,
.header + .fullwidth-embed {
	margin-top: 0;
	padding-top: 0;
}
</style>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

		<?php
		// ---------- Resolve top / dash / guest / bottom pages (flexible slugs) ----------
		$find_page_by_slugs = function( array $slugs ) {
			foreach ( $slugs as $s ) {
				$maybe = get_page_by_path( $s );
				if ( $maybe ) {
					return $maybe;
				}
			}
			return null;
		};

		$top_page  = $find_page_by_slugs( array( 'courses/courses-landing-top', 'courses-courses-landing-top', 'courses-landing-top', 'courses/landing-top' ) );
		$dash_page = $find_page_by_slugs( array( 'courses/courses-landing-dash', 'courses/courses-landing-dashboard', 'courses-landing-dash', 'courses-landing-dashboard', 'courses/landing-dashboard' ) );
		$guest_page = $find_page_by_slugs( array( 'courses/courses-landing-guest', 'courses-landing-guest', 'courses/landing-guest' ) );
		$bottom_page = $find_page_by_slugs( array( 'courses/courses-landing-bottom', 'courses-landing-bottom', 'courses/landing-bottom' ) );

		// ---------- HERO IMAGE: prefer top page featured image, fallback to guest page ----------
		$hero_page = null;
		if ( $top_page && has_post_thumbnail( $top_page->ID ) ) {
			$hero_page = $top_page;
		} elseif ( $guest_page && has_post_thumbnail( $guest_page->ID ) ) {
			$hero_page = $guest_page;
		}

		if ( $hero_page ) :
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $hero_page->ID ), 'full' );
			if ( ! empty( $thumb[0] ) ) :
				?>
				<div class="fullwidth-hero-container" aria-hidden="true">
					<img
						class="fullwidth-hero-image"
						src="<?php echo esc_url( $thumb[0] ); ?>"
						alt="<?php echo esc_attr( get_the_title( $hero_page->ID ) ); ?>"
						loading="eager"
					/>
				</div>
				<?php
			endif;
		endif;
		?>

		<?php
		// ---------- TOP PAGE CONTENT (full-width) ----------
		if ( $top_page && 'publish' === $top_page->post_status ) {
			?>
			<div class="fullwidth-embed top-landing-embed" aria-label="<?php echo esc_attr( get_the_title( $top_page->ID ) ); ?>">
				<div class="bb-landing-part">
					<?php echo apply_filters( 'the_content', $top_page->post_content ); ?>
				</div>
			</div>
			<?php
		}

		// ---------- Dashboard (logged-in) or Guest content (logged-out), BOTH full-width and below top ----------
		if ( is_user_logged_in() ) {
			if ( $dash_page && 'publish' === $dash_page->post_status ) {
				?>
				<div class="fullwidth-embed courses-landing-dashboard-embed" aria-label="<?php echo esc_attr( get_the_title( $dash_page->ID ) ); ?>">
					<div class="bb-landing-part">
						<?php echo apply_filters( 'the_content', $dash_page->post_content ); ?>
					</div>
				</div>
				<?php
			}
		} else {
			if ( $guest_page && 'publish' === $guest_page->post_status ) {
				?>
				<div class="fullwidth-embed courses-landing-guest-embed" aria-label="<?php echo esc_attr( get_the_title( $guest_page->ID ) ); ?>">
					<div class="bb-landing-part">
						<?php echo apply_filters( 'the_content', $guest_page->post_content ); ?>
					</div>
				</div>
				<?php
			}
		}
		?>

		<?php
		// ---------- Course grid/list partial (keeps standard archive behavior) ----------
		get_template_part( 'page-courses-landing-grid' );
		?>

		<?php
		// ---------- Bottom section OR Forums fallback ----------
		if ( $bottom_page && 'publish' === $bottom_page->post_status ) {
			?>
			<section class="courses-landing-bottom bb-landing-part">
				<?php echo apply_filters( 'the_content', $bottom_page->post_content ); ?>
			</section>
			<?php
		} else {
			// Forums fallback when no bottom content exists
			if ( shortcode_exists( 'bbp-forum-index' ) ) : ?>
				<section id="buzz-forums" class="buzz-forums-section">
					<h3 class="buzz-forums-title"><?php esc_html_e( 'Community Forums', 'buddyboss-theme' ); ?></h3>
					<div class="buzz-forums-wrap">
						<?php echo do_shortcode( '[bbp-forum-index]' ); ?>
					</div>
				</section>
			<?php elseif ( function_exists( 'bbp_has_forums' ) ) : ?>
				<section id="buzz-forums" class="buzz-forums-section">
					<h3 class="buzz-forums-title"><?php esc_html_e( 'Community Forums', 'buddyboss-theme' ); ?></h3>
					<div class="buzz-forums-wrap">
						<?php get_template_part( 'bbpress/loop' ); ?>
					</div>
				</section>
			<?php endif;
		}
		?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();