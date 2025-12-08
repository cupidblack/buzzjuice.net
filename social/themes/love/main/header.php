<!DOCTYPE html>
<html>
<head>
    <title><?php echo $data['title'];?></title>
    <?php require( $theme_path . 'main' . $_DS . 'meta.php' );?>
    <?php require( $theme_path . 'main' . $_DS . 'style.php' );?>
    <?php require( $theme_path . 'main' . $_DS . 'custom-header-js.php' );?>
    <?php
    if($config->push == 1) {
        require($theme_path . 'main' . $_DS . 'onesignal.php');
    }
    ?>
    <?php require( $theme_path . 'main' . $_DS . 'ajax.php' );?>
    <script src="https://cdn.jsdelivr.net/gh/tigrr/circle-progress@v0.2.4/dist/circle-progress.min.js"></script>
    <?php if ($config->recaptcha == 'on' && !empty($config->recaptcha_secret_key) && !empty($config->recaptcha_site_key)) { ?>
    <script type="text/javascript" src='https://www.google.com/recaptcha/api.js'></script>
    <?php } ?>
</head>
<body class="<?php echo $data['name'];?>-page <?php echo(!empty($_COOKIE['open_slide']) && $_COOKIE['open_slide'] == 'yes') ? 'side_open' : '' ?> <?php if( isset( $_SESSION['JWT'] ) ){ ?><?php } else { ?>no-padd<?php } ?>">
	<img src="<?php echo $theme_url;?>assets/img/login-banner-mask.svg" class="body_banner_mask">
    <div id="pop_up_18" class="modal matdialog et_plus">
		<div class="modal-dialog">
			<div class="modal-content">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" width="120" height="120" x="0" y="0" viewBox="0 0 479.635 479.635" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><g><path d="m50.516 54.186c-58.063 46.478-67.455 131.225-20.977 189.288 3.081 3.849 6.371 7.526 9.855 11.014l200.41 200.5 37.014-220.34-37.014-159.485c-46.478-58.063-131.225-67.455-189.288-20.977z" fill="#ff6378" data-original="#ff6378"></path><path d="m440.152 64.089c-55.958-56.654-150.957-51.3-200.349 11.074v379.825l200.41-200.41c52.586-52.619 52.558-137.904-.061-190.489z" fill="#fd5151" data-original="#c30047" class=""></path><path d="m174.616 169.647h19.827v134h30v-164h-49.827z" fill="#ffffff" data-original="#fbf4f4" class=""></path><path d="m337.227 213.936c6.42-7.78 10.281-17.747 10.281-28.598 0-24.813-20.187-45-45-45s-45 20.187-45 45c0 10.852 3.861 20.818 10.281 28.598-11.369 9.784-18.59 24.261-18.59 40.402 0 29.395 23.915 53.31 53.309 53.31s53.309-23.915 53.309-53.31c0-16.141-7.22-30.619-18.59-40.402zm-34.719-43.598c8.271 0 15 6.729 15 15s-6.729 15-15 15-15-6.729-15-15 6.729-15 15-15zm0 107.309c-12.853 0-23.309-10.457-23.309-23.31s10.457-23.309 23.309-23.309 23.309 10.456 23.309 23.309-10.456 23.31-23.309 23.31z" fill="#ffffff" data-original="#e9e9ee" class=""></path><path d="m140.347 180.98h-30v21.952h-21.952v30h21.952v21.951h30v-21.951h21.951v-30h-21.951z" fill="#ffffff" data-original="#fbf4f4" class=""></path></g></g></svg>
				<h4><?php echo(__('age_block_modal')) ?></h4>
				<p><?php echo(__('age_block_extra')) ?></p>

				<div class="modal-footer center">
					<button class="btn-flat waves-effect waves-light btn_primary white-text" id="pop_up_18_yes"><?php echo(__('yes')) ?></button>
					<button class="btn-flat waves-effect" id="pop_up_18_no"><?php echo(__('nopop')) ?></button>
				</div>
            </div>
		</div>
    </div>
	
    <?php 
        if (!empty($config->google_tag_code)) {
            echo openssl_decrypt($config->google_tag_code, "AES-128-ECB", 'mysecretkey1234');
        }
    ?>
    <?php if (file_exists($theme_path . 'third-party-theme.php')) { ?>
        <?php require( $theme_path . 'third-party-theme.php' );?>
    <?php } ?>
    <div id="loader" class="dt_ajax_progress"></div>
    <div class="modal modal-sm" id="authorize_modal" role="dialog" data-keyboard="false" style="overflow-y: auto;">
        <div class="modal-dialog">
            <div class="modal-content">
                <h4 class="modal-title"><?php echo __('Check out');?></h4>
                <form class="form form-horizontal" method="post" id="authorize_form" action="#">
                    <div class="modal-body authorize_modal">
                        <div id="authorize_alert"></div>
                        <div class="clear"></div>
                        <div class="row">
                            <div class="input-field col s12">
                                <input id="authorize_number" type="text" class="form-control input-md" autocomplete="off" placeholder="<?php echo __('card number');?>">
                            </div>
                            <div class="input-field col s4">
                                <select id="authorize_month" name="card_month" type="text" class="browser-default" autocomplete="off" placeholder="<?php echo __('month');?> (01)">
                                    <option value="01">01</option>
                                    <option value="02">02</option>
                                    <option value="03">03</option>
                                    <option value="04">04</option>
                                    <option value="05">05</option>
                                    <option value="06">06</option>
                                    <option value="07">07</option>
                                    <option value="08">08</option>
                                    <option value="09">09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="input-field col s4 no-padding-both">
                                <select id="authorize_year" name="card_year" type="text" class="browser-default" autocomplete="off" placeholder="<?php echo __('year');?> (2021)">
                                    <?php for ($i=date('Y'); $i <= date('Y')+15; $i++) {  ?>
                                        <option value="<?php echo($i) ?>"><?php echo($i) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="input-field col s4">
                                <input id="authorize_cvc" name="card_cvc" type="text" class="form-control input-md" autocomplete="off" placeholder="CVC" maxlength="3" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
                            </div>
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div class="modal-footer">
                        <div class="ball-pulse"><div></div><div></div><div></div></div>
                        <button type="button" class="waves-effect waves-light btn-flat btn_primary white-text" onclick="AuthorizeRequest()" id="authorize_btn"><?php echo __('pay');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-sm" id="paystack_wallet_modal" role="dialog" data-keyboard="false" style="overflow-y: auto;">
        <div class="modal-dialog">
            <div class="modal-content">
                <h4 class="modal-title"><?php echo __( 'PayStack');?></h4>
                <form class="form form-horizontal" method="post" id="paystack_wallet_form" action="#">
                    <div class="modal-body twocheckout_modal">
                        <div id="paystack_wallet_alert"></div>
                        <div class="clear"></div>
                        <div class="input-field col-md-12">
                            <label class="plr15" for="paystack_wallet_email"><?php echo __( 'Email');?></label>  
                            <input id="paystack_wallet_email" type="text" class="form-control input-md" autocomplete="off" placeholder="<?php echo __( 'Email');?>">
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                    <div class="modal-footer">
                        <div class="ball-pulse"><div></div><div></div><div></div></div>
                        <button type="button" class="waves-effect waves-light btn-flat btn_primary white-text" id="paystack_btn" onclick="InitializeWalletPaystack()"><?php echo __( 'Confirm' );?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- BlueCrownR&D: QuickDate WooCommerce Payment Gateway Bridge -->
    <div id="qdw-pgb_wallet_modal" class="modal">
        <div class="modal-content">
            
            <div class="popup-modal-header">
                <div class="ch_payment_head">
                                
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12,13A5,5 0 0,1 7,8H9A3,3 0 0,0 12,11A3,3 0 0,0 15,8H17A5,5 0 0,1 12,13M12,3A3,3 0 0,1 15,6H9A3,3 0 0,1 12,3M19,6H17A5,5 0 0,0 12,1A5,5 0 0,0 7,6H5C3.89,6 3,6.89 3,8V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V8C21,6.89 20.1,6 19,6Z"></path></svg>
                        
                    <h4>
                        <?php /*
                            // Ensure the variables are defined before use
                            $currency_symbol = isset($wo['config']['currency_symbol_array']) && isset($wo['config']['currency']) 
                                ? $wo['config']['currency_symbol_array'][$wo['config']['currency']] 
                                : ''; 

                            // Convert $wo['total'] to a numeric value if it is a string
                            $total_amount = isset($wo['total']) 
                                ? number_format((float)str_replace(',', '', $wo['total']), 2) 
                                : '0.00';

                            echo $currency_symbol . $total_amount;
                            */
                        ?>

                    </h4>

                </div>
                
                <div class="modal-payment-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"></path>
                    </svg>
                    <?php echo 'Choose a Payment Method' /*$wo['lang']['pay_from_wallet']*/ ?>
                </div>
                <style>
                    div#qdw-pgb_wallet_modal .popup-modal-header {
                        text-align: -webkit-center;
                    }
                    .ch_payment_head svg {
                        background-color: rgb(76 175 80 / 15%);
                        color: #4caf50;
                        padding: 17px;
                        border-radius: 50%;
                        width: 80px;
                        height: 80px;
                    }
                </style>
            </div>

            <div class="modal-body payment_box">

                <div class="active-payment-options">
                    <div class="ball-pulse"></div>
                    
                    <?php if ($config->qdw_payment == 'yes') { ?>
                        <!-- WooCommerce Payment Button -->
                        <button id="qdw-pgb_btn" class="waves-effect waves-light qd_btn" onclick="InitializeQDWPGB();">
                            <img src="<?php echo $theme_url;?>/assets/img/e-payment-modes.png">						
                            <?php echo __('Debit/Credit Card/ Mobile Money'); ?>
                        </button>
                    <?php } ?>
                    
                    <div class="ball-pulse"></div>
                    
                    <?php if (
                        $config->paystack_payment == "yes" &&
                        !empty($config->paystack_secret_key) &&
                        auth() &&
                        isset(auth()->username) &&
                        auth()->username == 'drenkaby'
                    ) { ?>
                        <!-- PayStack Payment Button -->
                        <button id="paystack-button1" class="valign-wrapper qd_btn btn-paystack-payment paystack" onclick="PayPaystack();">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" />
                            </svg>
                            <?php echo __('PayStack');?>
                        </button>
                    <?php } ?>

                    <?php if ($config->bank_payment == '1') { ?>
                        <!-- Bank Transfer or Cash Payment -->
                        <button id="bank_transfer" class="qd_btn valign-wrapper bank">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M15,14V11H18V9L22,12.5L18,16V14H15M14,7.7V9H2V7.7L8,4L14,7.7M7,10H9V15H7V10M3,10H5V15H3V10M13,10V12.5L11,14.3V10H13M9.1,16L8.5,16.5L10.2,18H2V16H9.1M17,15V18H14V20L10,16.5L14,13V15H17Z" />
                            </svg>
                            <?php echo __('Bank Transfer or Cash Payment');?>
                        </button>
                    <?php } ?>
                </div>
                <style>
                    /* Center the 'please wait' text in the modal */
                    button#qdw-pgb_btn {
                        display: flex;
                        justify-content: center;
                    }
                    button#qdw-pgb_btn .processing {
                        justify-content: center;
                        font-size: 24px;
                    }

                    .payment_box button.qd_btn img {
                        width: 32px;
                        height: 32px;
                        margin-right: 15px;
                    }
                    .payment_box button.qd_btn svg {
                        width: 32px;
                        height: 32px;
                        margin-right: 15px;
                    }
                    .payment_box button.qd_btn {
                        width: 100%;
                        background-color: white;
                        color: #2c2c2c;
                        text-align: inherit;
                        display: flex;
                        align-items: center;
                        border-bottom: 1px solid rgba(0, 0, 0, 0.07) !important;
                        border-radius: 0;
                        padding: 20px 25px;
                        transition: all 0.15s;
                        font-weight: 600;
                        box-shadow: none;
                        border: 0px;
                    }
                    .active-payment-options {
                        display: flex;
                        flex-direction: column;
                        align-items: flex-start;
                        padding: 20px 0px;
                    }
                    .modal-body {
                        position: relative;
                    }
                </style>

            </div>
            <div id="qdw-pgb_wallet_alert"></div>
        </div>
        <style>
            .modal .modal-content {
                padding: 24px 15px;
            }
            div#qdw-pgb_wallet_modal {
                max-width: 360px;
            }
            .payment_box button.qd_btn svg {
                width: 32px;
                height: 32px;
                margin-right: 15px  ;
            }
            button.btn.qdw-pgb_btn {
                display: flex;
            }
        </style>
    </div>











