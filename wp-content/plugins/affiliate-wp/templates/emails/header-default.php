<?php
/**
 * Email Header
 *
 * @package AffiliateWP/Templates/Emails
 * @version 1.6
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$body = "
	background-color: #f6f6f6;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
";
$wrapper = "
	width:100%;
	-webkit-text-size-adjust:none !important;
	margin:0;
	padding: 36px 0 36px 0;
";
$template_container = "
	box-shadow: none !important;
	border-radius: 12px !important;
	background-color: #ffffff;
	border: 1px solid #f0f0f0;
	padding: 32px 32px 16px 32px;
";
$template_header = "
	color: #00000;
	border-top-left-radius:3px !important;
	border-top-right-radius:3px !important;
	border-bottom: 0;
	font-weight:bold;
	line-height:100%;
	text-align: center;
	vertical-align:middle;
";
$body_content = "
	border-radius:3px !important;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
";
$body_content_inner = "
	color: #000000;
	font-size:14px;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	line-height:150%;
	text-align:left;
";
$header_content_h1 = "
	color: #000000;
	margin:0;
	padding: 28px 24px;
	display:block;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	font-size:32px;
	font-weight: 500;
	line-height: 1.2;
";
$header_img = affiliate_wp()->settings->get( 'email_logo', '' );
$heading    = affiliate_wp()->emails->get_heading();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo get_bloginfo( 'name' ); ?></title>
		<style>
			a.affwp-email-link:hover { color: #1f2937 !important; text-decoration: underline !important; }
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="<?php echo $body; ?>">
		<div style="<?php echo $wrapper; ?>">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<?php if( ! empty( $header_img ) ) : ?>
						<div id="template_header_image">
							<?php echo '<p style="margin-top:0;"><img src="' . esc_url( $header_img ) . '" alt="' . get_bloginfo( 'name' ) . '" /></p>'; ?>
						</div>
					<?php endif; ?>
					<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_container" style="<?php echo $template_container; ?>">
						<?php if ( ! empty( $heading ) ) : ?>
						<tr>
							<td align="center" valign="top">
								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_header" style="<?php echo $template_header; ?>" bgcolor="#ffffff">
									<tr>
										<td>
											<h1 style="<?php echo $header_content_h1; ?>"><?php echo $heading; ?></h1>
										</td>
									</tr>
								</table>
								<!-- End Header -->
							</td>
						</tr>
					<?php endif; ?>
						<tr>
							<td align="center" valign="top">
								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="520" id="template_body">
									<tr>
										<td valign="top" style="<?php echo $body_content; ?>">
											<!-- Content -->
											<table border="0" cellpadding="8" cellspacing="0" width="100%">
												<tr>
													<td valign="top">
														<div style="<?php echo $body_content_inner; ?>">
