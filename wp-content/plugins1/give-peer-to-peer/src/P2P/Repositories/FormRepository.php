<?php

namespace GiveP2P\P2P\Repositories;

use Give\Framework\Database\DB;

/**
 * Class FormRepository
 * @package GiveP2P\P2P\Repositories
 *
 * @since 1.0.0
 */
class FormRepository
{
    /**
     * Get Donation Forms
     *
     * @since 1.6.6 Ignore V3 forms as they still aren't compatible with the P2P addon
     * @since      1.0.0
     *
     * @return array
     */
    public function getDonationForms()
    {
        global $wpdb;

        $forms = [];

        /**
         * Note: excluding v3 forms is a temporary way of preventing errors until peer-to-peer is made compatible with v3 forms,
         * at which point we'll release the exclusion
         */
        $result = DB::get_results("SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type='give_forms' AND p.post_status='publish'
        AND NOT EXISTS (select * from {$wpdb->formmeta} m WHERE m.form_id = p.ID AND m.meta_key = 'formBuilderSettings')");

        foreach ($result as $form) {
            $forms[] = [$form->ID, $form->post_title];
        }

        return $forms;
    }
}
