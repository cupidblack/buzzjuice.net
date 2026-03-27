<?php
/**
 * Plugin Name: Buzzjuice Dashboard Progress Overview
 * Description: Shortcode [bz_progress_overview] - Total badge progress, summary, and milestone.
 */

if (!defined('ABSPATH')) exit;

add_action('init', function() {
    add_shortcode('bz_progress_overview', 'bz_progress_overview_shortcode');
});

function bz_progress_overview_shortcode() {
    if (!is_user_logged_in()) return '';
    $user_id = get_current_user_id();

    // List your badges—ensure these match your mycred badge IDs and display order
    $badges = [
        7336 => 'Lifestyle',
        7338 => 'Pollster',
        7339 => 'Publisher',
        7329 => 'Scholar',
        7334 => 'Wellness',
        7340 => 'Webster',
        7331 => 'Founder',
        7330 => 'Affiliate',
        7337 => 'Jewel',
        7332 => 'Gamer'
    ];

    $completed = 0;
    $in_progress = 0;
    $progress_total = 0;

    foreach ($badges as $badge_id => $label) {
        if (function_exists('mycred_has_user_badge') && mycred_has_user_badge($user_id, $badge_id)) {
            $completed++;
            $progress_total += 100;
        } elseif (function_exists('mycred_get_badge_progress')) {
            // If partial progress API exists, use it
            $badge_progress = mycred_get_badge_progress($user_id, $badge_id);
            if (isset($badge_progress['progress'])) {
                $progress_total += (float)$badge_progress['progress'];
            }
            $in_progress++;
        } else {
            $in_progress++;
        }
    }
    $total = count($badges);
    $progress_pct = $total > 0 ? round($progress_total / $total) : 0;

    // Intelligent next action message
    $remaining = $total - $completed;
    if ($remaining === 0) {
        $next_msg = "All badges completed 🎉";
    } elseif ($remaining === 1) {
        $next_msg = "Complete 1 more task to earn a badge";
    } else {
        $next_msg = "Complete $remaining more tasks to earn rewards";
    }

    ob_start(); ?>
    <div class="bz-progress-overview">
        <div class="bzpo-total">
            <div class="bzpo-label">Total Badge Progress</div>
            <div class="bzpo-bar"><div class="bzpo-fill" style="width:<?php echo $progress_pct; ?>%;"></div></div>
            <div class="bzpo-percent"><?php echo $progress_pct; ?>%</div>
        </div>
        <div class="bzpo-summary">
            <div class="bzpo-row"><span>Completed</span><strong><?php echo $completed; ?>/<?php echo $total; ?></strong></div>
            <div class="bzpo-row"><span>In Progress</span><strong><?php echo $in_progress; ?></strong></div>
        </div>
        <div class="bzpo-milestone">
            <div class="bzpo-title">🎯 Next Milestone</div>
            <div class="bzpo-text"><?php echo esc_html($next_msg); ?></div>
        </div>
    </div>
    <style>
    .bz-progress-overview {
        background:#fff;
        border:1px solid #e7eaf1;
        border-radius:10px;
        padding:14px 10px;
        /* margin-top:15px; */
    }
    .bzpo-total { margin-bottom:10px; }
    .bzpo-label { font-size:12px; font-weight:600; margin-bottom:5px;}
    .bzpo-bar { width:100%; height:8px; background:#edf0f5; border-radius:10px; overflow:hidden;}
    .bzpo-fill { height:100%; background:linear-gradient(90deg,#4cafef,#6edb8f); border-radius:10px; transition:width 0.4s;}
    .bzpo-percent { font-size:12px; text-align:right; margin-top:4px; color:#555;}
    .bzpo-row { display:flex; justify-content:space-between; font-size:12px; margin-top:6px;}
    .bzpo-row span { color:#777; }
    .bzpo-row strong { font-weight:600;}
    .bzpo-milestone { margin-top:10px; padding:8px; background:#f4f8ff; border-radius:6px; }
    .bzpo-title { font-size:12px; font-weight:600; margin-bottom:3px;}
    .bzpo-text { font-size:10px; color:#555;}
    @media (max-width:650px){ .bzpo-row { flex-direction:row; } }
    </style>
    <?php
    return ob_get_clean();
}