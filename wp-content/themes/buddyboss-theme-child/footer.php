<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package BuddyBoss_Theme
 */

?>

<?php do_action( THEME_HOOK_PREFIX . 'end_content' ); ?>

</div><!-- .bb-grid -->
</div><!-- .container -->
</div><!-- #content -->

<?php do_action( THEME_HOOK_PREFIX . 'after_content' ); ?>

<?php do_action( THEME_HOOK_PREFIX . 'before_footer' ); ?>
<?php do_action( THEME_HOOK_PREFIX . 'footer' ); ?>
<?php do_action( THEME_HOOK_PREFIX . 'after_footer' ); ?>

</div><!-- #page -->

<?php do_action( THEME_HOOK_PREFIX . 'after_page' ); ?>

<?php wp_footer(); ?>











<?php
// include shared footer menu (adjusted from this file's directory)
$shared = __DIR__ . '/../../../shared/footer-menu/wpqd-footer-menu.php';

// Fallback using DOCUMENT_ROOT if above path isn't correct on your host
$shared_fallback = (isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '') . '/buzzjuice.net/shared/footer-menu/wp-qd-footer-menu.php';

if (file_exists($shared)) {
    require_once $shared;
} elseif ($shared_fallback && file_exists($shared_fallback)) {
    require_once $shared_fallback;
} else {
    // fail-safe: optionally log or output a small marker to debug path issues
    // error_log("footer-menu.php not found: tried {$shared} and {$shared_fallback}");
}
?>











</body>
</html>
