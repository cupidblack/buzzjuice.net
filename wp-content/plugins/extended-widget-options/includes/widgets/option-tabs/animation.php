<?php

/**
 * Settings Widget Options
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add Settings Widget Options Tab
 *
 * @since 1.0
 * @return void
 */

/**
 * Called on 'extended_widget_opts_tabs'
 * create new tab navigation for alignment options
 */
function widgetopts_tab_animation($args)
{ ?>
    <li class="extended-widget-opts-tab-animation">
        <a href="#extended-widget-opts-tab-<?php echo $args['id']; ?>-animation" title="<?php _e('Animation', 'widget-options'); ?>"><span class="dashicons dashicons-admin-customizer"></span> <span class="tabtitle"><?php _e('Animation', 'widget-options'); ?></span></a>
    </li>
<?php
}
add_action('extended_widget_opts_tabs', 'widgetopts_tab_animation', 10);

/**
 * Called on 'extended_widget_opts_tabcontent'
 * create new tab content options for alignment options
 */
function widgetopts_tabcontent_animation($args)
{
    global $widget_options;
    $validLicense = widgetopts_global_check_license();

    $id         = '';
    $classes    = '';
    $logic      = '';
    $selected   = 0;
    $check      = '';
    $fixed      = '';
    $link       = '';
    $nocache    = '';
    $target     = '';
    $nofollow   = '';
    $link_title = '';
    $http       = '';
    $animation  = '';
    $event      = '';
    $speed      = '';
    $offset     = '';
    $hidden     = '';
    $delay      = '';
    $is_url     = 'hide';
    $urls       = '';
    if (isset($args['params']) && isset($args['params']['class'])) {
        if (isset($args['params']['class']['id'])) {
            $id = $args['params']['class']['id'];
        }
        if (isset($args['params']['class']['classes'])) {
            $classes = $args['params']['class']['classes'];
        }
        if (isset($args['params']['class']['selected'])) {
            $selected = $args['params']['class']['selected'];
        }
        if (isset($args['params']['class']['logic'])) {
            $logic = $args['params']['class']['logic'];
        }
        if (isset($args['params']['class']['title']) && $args['params']['class']['title'] == '1') {
            $check = 'checked="checked"';
        }
        if (isset($args['params']['class']['fixed']) && $args['params']['class']['fixed'] == '1') {
            $fixed = 'checked="checked"';
        }
        if (isset($args['params']['class']['link'])) {
            $link = $args['params']['class']['link'];
        }
        if (isset($args['params']['class']['nocache'])) {
            $nocache = 'checked="checked"';
        }
        if (isset($args['params']['class']['target']) && $args['params']['class']['target'] == '1') {
            $target = 'checked="checked"';
        }
        if (isset($args['params']['class']['nofollow']) && $args['params']['class']['nofollow'] == '1') {
            $nofollow = 'checked="checked"';
        }
        if (isset($args['params']['class']['link_title']) && $args['params']['class']['link_title'] == '1') {
            $link_title = 'checked="checked"';
        }
        if (isset($args['params']['class']['http']) && $args['params']['class']['http'] == '1') {
            $http = 'checked="checked"';
        }
        if (isset($args['params']['class']['animation'])) {
            $animation = $args['params']['class']['animation'];
        }
        if (isset($args['params']['class']['event'])) {
            $event = $args['params']['class']['event'];
        }
        if (isset($args['params']['class']['speed'])) {
            $speed = $args['params']['class']['speed'];
        }
        if (isset($args['params']['class']['offset'])) {
            $offset = $args['params']['class']['offset'];
        }
        if (isset($args['params']['class']['hidden']) && $args['params']['class']['hidden'] == '1') {
            $hidden = 'checked="checked"';
        }
        if (isset($args['params']['class']['delay'])) {
            $delay = $args['params']['class']['delay'];
        }
        if (isset($args['params']['class']['urls'])) {
            $urls = $args['params']['class']['urls'];
        }
        if (isset($args['params']['class']['is_url'])) {
            $is_url = $args['params']['class']['is_url'];
        }
    }

    $predefined = array();
    if (isset($widget_options['settings']['classes']) && isset($widget_options['settings']['classes']['classlists']) && !empty($widget_options['settings']['classes']['classlists'])) {
        $predefined = $widget_options['settings']['classes']['classlists'];
    }
?>
    <div id="extended-widget-opts-tab-<?php echo $args['id']; ?>-animation" class="extended-widget-opts-tabcontent extended-widget-opts-inside-tabcontent extended-widget-opts-tabcontent-class">

        <div class="extended-widget-opts-settings-tabs extended-widget-opts-inside-tabs">
            <input type="hidden" id="extended-widget-opts-settings-selectedtab" value="<?php echo $selected; ?>" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][selected]" />
            <!--  start tab nav -->
            <ul class="extended-widget-opts-settings-tabnav-ul" style="display: none;">

                <?php if ('activate' == $widget_options['animation']) { ?>
                    <li class="extended-widget-opts-settings-tab-animation">
                        <a href="#extended-widget-opts-settings-tab-<?php echo $args['id']; ?>-animation" title="<?php _e('Animation', 'widget-options'); ?>"><?php _e('Animation', 'widget-options'); ?></a>
                    </li>
                <?php } ?>

                <div class="extended-widget-opts-clearfix"></div>
            </ul><!--  end tab nav -->
            <div class="extended-widget-opts-clearfix"></div>

            <?php if ('activate' == $widget_options['animation']) {
                $animation_array = array(
                    'Attention Seekers' => array(
                        'bounce',
                        'flash',
                        'pulse',
                        'rubberBand',
                        'shake',
                        'swing',
                        'tada',
                        'wobble',
                        'jello'
                    ),
                    'Bouncing Entrances' => array(
                        'bounceIn',
                        'bounceInDown',
                        'bounceInLeft',
                        'bounceInRight',
                        'bounceInUp',
                    ),

                    'Fading Entrances'   => array(
                        'fadeIn',
                        'fadeInDown',
                        'fadeInDownBig',
                        'fadeInLeft',
                        'fadeInLeftBig',
                        'fadeInRight',
                        'fadeInRightBig',
                        'fadeInUp',
                        'fadeInUpBig'
                    ),
                    'Flippers'          => array(
                        'flip',
                        'flipInX',
                        'flipInY',
                        'flipOutX',
                        'flipOutY'
                    ),
                    'Lightspeed'        => array(
                        'lightSpeedIn',
                        'lightSpeedOut'
                    ),

                    'Rotating Entrances' => array(
                        'rotateIn',
                        'rotateInDownLeft',
                        'rotateInDownRight',
                        'rotateInUpLeft',
                        'rotateInUpRight'
                    ),
                    'Sliding Entrances' => array(
                        'slideInUp',
                        'slideInDown',
                        'slideInLeft',
                        'slideInRight'
                    ),
                    'Zoom Entrances'    => array(
                        'zoomIn',
                        'zoomInDown',
                        'zoomInLeft',
                        'zoomInRight',
                        'zoomInUp'
                    ),
                    'Specials'          => array(
                        'hinge',
                        'rollIn'
                    )
                ); ?>
                <!--  start animation tab content -->
                <div id="extended-widget-opts-settings-tab-<?php echo $args['id']; ?>-animation" class="extended-widget-opts-settings-tabcontent extended-widget-opts-inner-tabcontent <?= (!$validLicense) ? 'disabled-section' : ''; ?>">
                    <?php if (!$validLicense) : ?>
                        <div class="extended-widget-opts-demo-warning">
                            <p class="widgetopts-unlock-features">
                                <span class="dashicons dashicons-lock"></span><br>
                                Your license has expired<br>or is not marked as active.<br><br>
                                <a href="https://widget-options.com/account/" class="button-primary" target="_blank">Learn More</a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="widget-opts-animation">
                        <p>
                            <label for="opts-class-animation-<?php echo $args['id']; ?>"><?php _e('Animation Type', 'widget-options'); ?></label>
                            <br />
                            <select class="widefat" id="opts-class-animation-<?php echo $args['id']; ?>" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][animation]">
                                <option value=""><?php _e('None', 'widget-options'); ?></option>
                                <?php foreach ($animation_array as $group => $anims) { ?>
                                    <optgroup label="<?php _e($group, 'widget-options'); ?>">
                                        <?php foreach ($anims as $anim => $aname) { ?>
                                            <option value="<?php echo $aname; ?>" <?php echo ($animation == $aname) ? 'selected="selected"' : ''; ?>><?php _e($aname, 'widget-options') ?></option>
                                        <?php } ?>
                                    </optgroup>
                                <?php } ?>
                            </select>
                            <small><em><?php _e('The type of animation for this event.', 'widget-options'); ?></em></small>
                        </p>

                        <p>
                            <label for="opts-class-event-<?php echo $args['id']; ?>"><?php _e('Animation Event', 'widget-options'); ?></label>
                            <br />
                            <select class="widefat" id="opts-class-event-<?php echo $args['id']; ?>" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][event]">
                                <option value="enters" <?php echo ('enters' == $event) ? 'selected="selected"' : ''; ?>><?php _e('Element Enters Screen', 'widget-options'); ?></option>
                                <option value="onScreen" <?php echo ('onScreen' == $event) ? 'selected="selected"' : ''; ?>><?php _e('Element In Screen', 'widget-options'); ?></option>
                                <option value="pageLoad" <?php echo ('pageLoad' == $event) ? 'selected="selected"' : ''; ?>><?php _e('Page Load', 'widget-options'); ?></option>
                            </select>
                            <small><em><?php _e('The event that triggers the animation', 'widget-options'); ?></em></small>
                        </p>

                        <p>
                            <label for="opts-class-speed-<?php echo $args['id']; ?>"><?php _e('Animation Speed', 'widget-options'); ?></label>
                            <br />
                            <input type="text" id="opts-class-speed-<?php echo $args['id']; ?>" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][speed]" value="<?php echo $speed; ?>" />
                            <small><em><?php _e('How many seconds the incoming animation should lasts.', 'widget-options'); ?></em></small>
                        </p>

                        <p>
                            <label for="opts-class-offset-<?php echo $args['id']; ?>"><?php _e('Screen Offset', 'widget-options'); ?></label>
                            <br />
                            <input type="text" id="opts-class-offset-<?php echo $args['id']; ?>" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][offset]" value="<?php echo $offset; ?>" />
                            <small><em><?php _e('How many pixels above the bottom of the screen must the widget be before animating.', 'widget-options'); ?></em></small>
                        </p>

                        <p>
                            <label for="opts-class-hidden-<?php echo $args['id']; ?>"><?php _e('Hide Before Animation', 'widget-options'); ?></label>
                            <br />
                            <input type="checkbox" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][hidden]" id="opts-class-hidden-<?php echo $args['id']; ?>" value="1" <?php echo $hidden; ?> />
                            <label for="opts-class-hidden-<?php echo $args['id']; ?>"><?php _e('Enabled', 'widget-options'); ?></label><br />
                            <small><em><?php _e('Hide widget before animating.', 'widget-options'); ?></em></small>
                        </p>

                        <p>
                            <label for="opts-class-delay-<?php echo $args['id']; ?>"><?php _e('Animation Delay', 'widget-options'); ?></label>
                            <br />
                            <input type="text" id="opts-class-delay-<?php echo $args['id']; ?>" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][class][delay]" value="<?php echo $delay; ?>" />
                            <small><em><?php _e('Number of seconds after the event to start the animation.', 'widget-options'); ?></em></small>
                        </p>
                    </div>
                </div><!--  end animation tab content -->
            <?php } ?>

        </div><!-- end .extended-widget-opts-settings-tabs -->


    </div>
<?php
}
add_action('extended_widget_opts_tabcontent', 'widgetopts_tabcontent_animation'); ?>