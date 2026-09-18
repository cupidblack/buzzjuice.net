<?php
/**
 * Plugin Name: Buzzjuice Dashboard Courses Progress
 * Description: Shortcode [bz_courses_progress] — up to 3 courses, progress, smart resume/certificate.
 */

if (!defined('ABSPATH')) exit;

add_action('init', function() {
    add_shortcode('bz_courses_progress', 'bz_courses_progress_shortcode');
});

function bz_courses_progress_shortcode() {
    if (!is_user_logged_in()) return '';
    if (!function_exists('learndash_user_get_enrolled_courses')) return '<div class="bz-course-empty">You are not enrolled in any courses yet.<br><a href="/courses">Browse Courses &rarr;</a></div>';
    $user_id = get_current_user_id();
    $enrolled = learndash_user_get_enrolled_courses($user_id);
    if (empty($enrolled)) {
        return '<div class="bz-courses"><div class="bz-course-empty">You are not enrolled in any courses yet.<br><br><a href="/courses" class="bz-course-browse">Browse Courses &rarr;</a></div></div>';
    }
    // Gather & sort (incomplete first, progress desc)
    $courses = [];
    foreach ($enrolled as $cid) {
        $progress = 0; $completed = false;
        if (function_exists('learndash_course_progress')) {
            $d = learndash_course_progress(['user_id' => $user_id, 'course_id' => $cid]);
            $progress = isset($d['percentage']) ? (int)$d['percentage'] : 0;
            $completed = !empty($d['completed']);
        }
        $courses[] = [
            'id'    => $cid,
            'title' => get_the_title($cid),
            'thumb' => get_the_post_thumbnail_url($cid, 'thumbnail') ?: 'https://via.placeholder.com/60x60?text=Course',
            'progress' => $progress,
            'completed'=> $completed,
        ];
    }
    usort($courses, function($a,$b){
        if ($a['completed'] == $b['completed']) return $b['progress'] - $a['progress'];
        return $a['completed'] - $b['completed'];
    });
    $courses = array_slice($courses,0,3);
    ob_start();
?>
<div class="bz-courses">
<?php foreach ($courses as $c):
    $btn_label = $c['completed'] ? 'Certificate &rarr;' : 'Resume &rarr;';
    $btn_link = $c['completed'] && function_exists('learndash_get_course_certificate_link')
        ? learndash_get_course_certificate_link($c['id'],$user_id)
        : bzld_next_lesson_link($c['id'],$user_id);
    if (!$btn_link) $btn_link = get_permalink($c['id']);
?>
  <div class="bz-course-card">
    <div class="bz-course-top">
      <img src="<?php echo esc_url($c['thumb']); ?>" alt="">
      <div class="bz-course-meta">
        <div class="bz-course-title"><?php echo esc_html($c['title']); ?></div>
        <div class="bz-progress-bar"><div class="bz-progress-fill" style="width:<?php echo $c['progress']; ?>%"></div></div>
        <div class="bz-progress-text"><?php echo $c['progress']; ?>%</div>
      </div>
    </div>
    <a href="<?php echo esc_url($btn_link); ?>" class="bz-course-btn"><?php echo $btn_label; ?></a>
  </div>
<?php endforeach;?>
  <div class="bz-courses-footer"><a href="/courses">See All Courses &rarr;</a></div>
</div>
<style>
.bz-courses {background:#fff;border:1px solid #e7eaf1;border-radius:10px;padding:10px;/*margin-top:16px;*/}
.bz-course-card {margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #f3f3f3;}
.bz-course-card:last-child{border-bottom:none;}
.bz-course-top {display:flex;gap:8px;}
.bz-course-top img {width:50px;height:50px;border-radius:6px;object-fit:cover;}
.bz-course-meta {flex:1;}
.bz-course-title {font-size:12.5px;font-weight:600;margin-bottom:4px;}
.bz-progress-bar {height:6px;background:#eee;border-radius:4px;overflow:hidden;margin-bottom:3px;}
.bz-progress-fill {height:100%;background:#3e6cb8;}
.bz-progress-text {font-size:11px;color:#777;}
.bz-course-btn {display:inline-block;margin-top:6px;font-size:12px;color:#3e6cb8;text-decoration:none;}
.bz-courses-footer {margin-top:8px;text-align:center;}
.bz-courses-footer a {font-size:12px;color:#3e6cb8;text-decoration:underline;}
.bz-course-empty {font-size:13px;color:#555;}
.bz-course-browse{display:block;margin-top:6px;color:#3e6cb8;}
@media (max-width:650px){.bz-course-top{flex-direction:column;}}
</style>
<?php
    return ob_get_clean();
}
function bzld_next_lesson_link($course_id, $user_id) {
    $lessons = function_exists('learndash_get_course_lessons_list') ? learndash_get_course_lessons_list($course_id) : [];
    foreach ($lessons as $lesson) {
        $lid = $lesson['post']->ID;
        if (!learndash_is_lesson_complete($user_id, $lid)) return get_permalink($lid);
    }
    return get_permalink($course_id);
}