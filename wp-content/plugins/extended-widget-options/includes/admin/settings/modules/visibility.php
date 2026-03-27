<?php

/**
 * Visibility Settings Module
 * Settings > Widget Options :: Pages Visibility
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Create Card Module for Pages Visibility Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_visibility()
{
    global $widget_options;
    $tax_opts     = (isset($widget_options['settings']['taxonomies'])) ? $widget_options['settings']['taxonomies'] : array();
    $validLicense = widgetopts_global_check_license();

    //avoid issue after update
    if (!isset($widget_options['urls'])) {
        $widget_options['urls'] = '';
    }

    //avoid erro when free version also activated
    if (!isset($widget_options['taxonomies'])) {
        $widget_options['taxonomies'] = '';
    }

?>
    <li class="widgetopts-module-card <?php echo ($widget_options['visibility'] == 'activate') ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-visibility" data-module-id="visibility">
        <div class="widgetopts-module-card-content">
            <h2><?php _e('Pages Visibility', 'widget-options'); ?></h2>
            <p class="widgetopts-module-desc">
                <?php _e('Easily restrict any widgets visibility on specific WordPress pages.', 'widget-options'); ?>
            </p>
            <div class="widgetopts-module-actions hide-if-no-js">
                <?php if ($widget_options['visibility'] == 'activate') { ?>
                    <button class="button button-secondary widgetopts-toggle-settings"><?php _e('Configure Settings', 'widget-options'); ?></button>
                    <button class="button button-secondary widgetopts-toggle-activation"><?php _e('Disable', 'widget-options'); ?></button>
                <?php } else { ?>
                    <button class="button button-secondary widgetopts-toggle-settings"><?php _e('Learn More', 'widget-options'); ?></button>
                    <button class="button button-primary widgetopts-toggle-activation"><?php _e('Enable', 'widget-options'); ?></button>
                <?php } ?>

            </div>
        </div>

        <?php widgetopts_modal_start($widget_options['visibility']); ?>
        <span class="dashicons widgetopts-dashicons dashicons-visibility"></span>
        <h3 class="widgetopts-modal-header"><?php _e('Pages Visibility', 'widget-options'); ?></h3>
        <p>
            <?php _e('Visibility tab allows you to completely control each widgets visibility and restrict them on any WordPress pages. You can turn on/off the underlying tabs for post types, taxonomies and miscellanous options using the options below when this feature is enabled.', 'widget-options'); ?>
        </p>
        <table class="form-table widgetopts-settings-section">
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-misc"><?php _e('Pages Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-misc" name="visibility[misc]" <?php echo (isset($widget_options['settings']['visibility'])) ? widgetopts_is_checked($widget_options['settings']['visibility'], 'misc') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-misc"><?php _e('Enable Miscellaneous Options', 'widget-options'); ?></label>
                    <p class="description">
                        <?php _e('Restrict widgets or blocks visibility on WordPress new added page as well as default pages such as home page, blog page, 404, search, etc.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-left: 0;">
                    <p style="padding-left: 0;">
                        <?php _e('Allow child pages to inherit widget visibility pages restriction from parent.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-inherit"><?php _e('Inherit Visibility', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-inherit" name="visibility[inherit]" <?php echo (isset($widget_options['settings']['visibility'])) ? widgetopts_is_checked($widget_options['settings']['visibility'], 'inherit') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-inherit"><?php _e('Enable default pages and new pages options', 'widget-options'); ?></label>
                    <p class="description">
                        <?php _e('Check this option if you want your child pages to inherit pages widget or block visibility option from there parent.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-post_types"><?php _e('Post Types Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-post_types" name="visibility[post_type]" <?php echo (isset($widget_options['settings']['visibility'])) ? widgetopts_is_checked($widget_options['settings']['visibility'], 'post_type') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-post_types"><?php _e('Enable Post Types RestrictionThis feature will allow visibility restriction of every widgets per post types and per pages.', 'widget-options'); ?></label>
                    <p class="description">
                        <?php _e('This feature will allow visibility restriction of every widgets or blocks per post types and per pages.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-taxonomies"><?php _e('Taxonomies Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-taxonomies" name="visibility[taxonomies]" <?php echo (isset($widget_options['settings']['visibility'])) ? widgetopts_is_checked($widget_options['settings']['visibility'], 'taxonomies') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-taxonomies"><?php _e('Enable Taxonomies Restriction', 'widget-options'); ?></label>
                    <p class="description">
                        <?php _e('This feature will allow you to control visibility via taxonomy and terms archive pages.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>

            <?php if ($validLicense) { ?>
                <tr>
                    <td colspan="2" style="padding-left: 0;">
                        <p style="padding-left: 0;">
                            <?php _e('Extend each widget visibility for custom post types taxonomies and terms.', 'widget-options'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="widgetopts-visibility-extended-taxonomy"><?php _e('Extended Taxonomy Terms', 'widget-options'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="widgetopts-visibility-extended-taxonomy" name="module_activation[taxonomies]" <?php echo (isset($widget_options['taxonomies'])) && $widget_options['taxonomies'] == 'activate' ? 'checked="checked"' : ''; ?> value="1" />
                        <input type="hidden" name="module_list[taxonomies]" value="1" />
                        <label for="widgetopts-visibility-extended-taxonomy"><?php _e('Enable Extended Taxonomies', 'widget-options'); ?></label>
                        <p class="description"><?php _e('Show or hide each WordPress widget per post type, taxonomy and/or term! With extended functionality, whenever each term is selected; each post, page or custom post type assigned will inherit the visibility options.', 'widget-options'); ?></p>


                        <p class="widgetopts-settings-section">
                            <?php
                            $args = array(
                                'public'   => true

                            );
                            $output = 'objects'; // or objects
                            $operator = 'and'; // 'and' or 'or'
                            $taxonomies = get_taxonomies($args, $output, $operator);
                            unset($taxonomies['post_format']);
                            // print_r( $this->taxonomy_settings );
                            if (!empty($taxonomies)) {
                                foreach ($taxonomies as $tax) { ?>
                        <p class="widgetopts-settings-taxonomy-checkbox" <?php echo (isset($widget_options['taxonomies'])) && $widget_options['taxonomies'] == 'activate' ? '' : 'style="display: none;"'; ?>>
                            <input type="checkbox" name="taxonomies[<?php echo $tax->name; ?>]" id="widgetopts-tax-<?php echo $tax->name; ?>" value="1" <?php echo widgetopts_is_checked($tax_opts, $tax->name); ?> />
                            <label for="widgetopts-tax-<?php echo $tax->name; ?>"><?php echo $tax->label; ?></label>
                            <?php
                                    if (isset($tax->object_type) && isset($tax->object_type[0])) {
                                        echo ' <small>- ' . $tax->object_type[0] . '</small>';
                                    }
                            ?>
                        </p>
                <?php
                                }
                            } ?>
                </p>

                    </td>
                </tr>
            <?php } ?>

            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-authors"><?php _e('Authors Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-authors" name="roles[authors]" <?php echo (isset($widget_options['settings']['roles'])) ? widgetopts_is_checked($widget_options['settings']['roles'], 'authors') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-authors"><?php _e('Enable Authors Page Restriction', 'widget-options'); ?></label>
                    <p class="description"><?php _e('Restrict widget or block visibility per author archive or content pages.', 'widget-options'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-urls"><?php _e('Target URL Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-urls" name="module_activation[urls]" <?php echo (isset($widget_options['urls'])) && $widget_options['urls'] == 'activate' ? 'checked="checked"' : ''; ?> value="1" />
                    <input type="hidden" name="module_list[urls]" value="1" />
                    <label for="widgetopts-visibility-authors"><?php _e('Enable URL & Wildcards Restrictions', 'widget-options'); ?></label>
                    <p class="description"><?php _e('This feature will give you option to target specific URL to show or hide any widgets or blocks. You can use <code>*</code> as wildcard url, for example <code>sample-page/*</code> to target all subpages of "sample-page". This will give you a brand new level of managing your widget visibility!', 'widget-options'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="widgetopts-visibility-slug"><?php _e('Slug Keyword Tab', 'widget-options'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="widgetopts-visibility-slug" name="visibility[slug]" <?php echo (isset($widget_options['settings']['visibility'])) ? widgetopts_is_checked($widget_options['settings']['visibility'], 'slug') : ''; ?> value="1" />
                    <label for="widgetopts-visibility-slug"><?php _e('Enable Slug Keyword Filter', 'widget-options'); ?></label>
                    <p class="description">
                        <?php _e('Filter multiple pages by keyword-containing slug separated by coma, for example about, service, contact to target all pages with these keyword on URL.', 'widget-options'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php widgetopts_modal_end($widget_options['visibility']); ?>
    </li>
<?php
}
add_action('widgetopts_module_cards', 'widgetopts_settings_visibility', 12);
?>