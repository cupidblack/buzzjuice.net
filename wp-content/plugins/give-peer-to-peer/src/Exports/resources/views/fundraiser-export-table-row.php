<tr class="give-export-p2p-fundraisers">
    <td scope="row" class="row-title">
        <h3>
            <span><?php esc_html_e( 'Export Peer-to-Peer Fundraisers', 'give-peer-to-peer' ); ?></span>
        </h3>
        <p><?php esc_html_e( 'Download a CSV of peer-to-peer fundraisers', 'give-peer-to-peer' ); ?></p>
    </td>
    <td>
        <form method="post" id="give_p2p_fundraisers_export" class="give-export-form">

            <div class="give-sr-export">
                <?php
                echo Give()->html->select( array(
                        'name'        => 'p2p_fundraisers_export_campaign',
                        'id'          => 'p2p_fundraisers_export_campaign',
                        'chosen'      => true,
                        'placeholder' => esc_html__( 'Choose a campaign', 'give-peer-to-peer' ),
                        'options'     => $campaignOptions,
                        'show_option_all' => false,
                        'show_option_none' => false,
                    )
                );
                ?>
            </div>

            <input type="submit" value="<?php esc_attr_e( 'Generate CSV', 'give-peer-to-peer' ); ?>" class="button-secondary"/>
            <?php wp_nonce_field( 'give_ajax_export', 'give_ajax_export' ); ?>
            <input type="hidden" name="give-export-class" value="<?php echo $exportClass; ?>"/>
        </form>
    </td>
</tr>
