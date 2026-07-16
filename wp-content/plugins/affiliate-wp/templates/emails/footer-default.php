<?php
/**
 * Email Footer
 *
 * @package AffiliateWP/Templates/Emails
 * @version 1.6
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
$template_footer = "
	border-top:0;
	-webkit-border-radius:3px;
";

$credit = "
	border:0;
	color: #000000;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	font-size:12px;
	line-height:125%;
	text-align:center;
";
?>
															</div>
														</td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- Footer -->
            <table align="center" border="0" cellpadding="0" cellspacing="0" width="520" id="template_footer" style="margin: 16px auto 0 auto; <?php echo $template_footer; ?>">
                <tr>
                    <td valign="top">
                        <table border="0" cellpadding="10" cellspacing="0" width="100%">
                            <tr>
                                <td colspan="2" valign="middle" id="credit" style="<?php echo $credit; ?>">
									<?php
									/**
									 * Filters the email footer text.
									 *
									 * Note: the return value will be passed through wptexturize(), then wp_kses_post(), then wpautop().
									 *
									 * @since 1.6
									 *
									 * @param string $footer_text Email footer text.
									*/
									echo wpautop( wp_kses_post( wptexturize( apply_filters( 'affwp_email_footer_text', '<a href="' . esc_url( home_url() ) . '" style="color: #6b7280; text-decoration: none;">' . get_bloginfo( 'name' ) . '</a>' ) ) ) );
									?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- End Footer -->
        </div>
    </body>
</html>
