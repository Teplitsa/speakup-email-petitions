<?php

// capture confirmation_code variable from links clicked in confirmation emails
if ( isset( $_REQUEST['dkspeakupconfirm'] ) ) {
	add_action( 'template_redirect', 'dk_speakup_confirm_email' );
}

/**
 * Displays the confirmation page
 */
function dk_speakup_confirm_email() {

	// set WPML language
	global $sitepress;
	$lang = isset( $_REQUEST['lang'] ) ? $_REQUEST['lang'] : '';

	if ( isset( $sitepress ) ) {
		$sitepress->switch_lang( $lang, true );
	}

	include_once( 'class.signature.php' );
	include_once( 'class.petition.php' );
	include_once( 'class.mail.php' );
	include_once( 'class.wpml.php' );
	$the_signature = new dk_speakup_Signature();
	$the_petition  = new dk_speakup_Petition();
	$wpml          = new dk_speakup_WPML();

	// get the confirmation code from url
	$confirmation_code = $_REQUEST['dkspeakupconfirm'];

	// try to confirm the signature
	$try_confirm = $the_signature->confirm( $confirmation_code );

	// if our attempt to confirm the signature was successful
	if ( $try_confirm ) {

		// retrieve the signature data
		$the_signature->retrieve_confirmed( $confirmation_code );

		// retrieve the petition data
		$the_petition->retrieve( $the_signature->petitions_id );
		$wpml->translate_petition( $the_petition );


		// send the petition email
		if ( $the_petition->sends_email ) {
			dk_speakup_Mail::send_petition( $the_petition, $the_signature );
		}

		// set up the status message
		$message = __( 'Thank you. Your signature has been added to the petition.', 'dk_speakup' );
	}
	else {
		// has the signature already been confirmed?
		if ( $the_signature->check_confirmation( $confirmation_code ) ) {
			$message = __( 'Your signature has already been confirmed.', 'dk_speakup' );
		}
		else {
			// the confirmation code is fubar or an admin has already deleted the signature
			$message = __( 'The confirmation code you provided is invalid.', 'dk_speakup' );
		}
	}

	// display the confirmation page
	$confirmation_page = '
		<!doctype html>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=' . get_bloginfo( "charset" ) . '" />
			<meta http-equiv="refresh" content="10;' . $the_petition->return_url . '"> 
			<title>' . get_bloginfo( "name" ) . '</title>
			<style type="text/css">
				body {
					background: #666;
					font-family: arial, sans-serif;
					font-size: 14px;
				}
				#confirmation {
					background: #fff url(' . plugins_url( "speakup-email-petitions/images/mail-stripes.png" ) . ') repeat top left;
					border: 1px solid #fff;
					width: 515px;
					margin: 200px auto 0 auto;
					box-shadow: 0px 3px 5px #333;
				}
				#confirmation-content {
					background: #fff url(' . plugins_url( "speakup-email-petitions/images/postmark.png" ) . ') no-repeat top right;
					margin: 10px;
					padding: 40px 0 20px 100px;
				}
			</style>
		</head>
		<body>
			<div id="confirmation">
				<div id="confirmation-content">
					<h2>' . __( "Email Confirmation", "dk_speakup" ) . '</h2>
					<p>' . $message . '</p>
					<p>' . __( "You will be redirected momentarily.", "dk_speakup" ) . '</p>
					<p><a href="' . home_url() . '">' . get_bloginfo( "name" ) . '  &raquo;</a></p>
				</div>
			</div>
		</body>
		</html>
	';

	echo $confirmation_page;

	// stop page rendering here
	// without this, the home page will display below the confirmation message
	die();
}

?>