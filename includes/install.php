<?php

// plugin installation routine
function dk_speakup_install() {

	global $wpdb, $db_petitions, $db_signatures, $dk_speakup_version;

	dk_speakup_translate();

	$sql_create_tables = "
		CREATE TABLE `$db_petitions` (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`title` TEXT CHARACTER SET utf8 NOT NULL,
			`target_email` VARCHAR(300) CHARACTER SET utf8 NOT NULL,
			`email_subject` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`greeting` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`petition_message` LONGTEXT CHARACTER SET utf8 NOT NULL,
			`address_fields` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`expires` CHAR(1) BINARY NOT NULL,
			`expiration_date` DATETIME NOT NULL,
			`created_date` DATETIME NOT NULL,
			`goal` INT(11) NOT NULL,
			`sends_email` CHAR(1) BINARY NOT NULL,
			`twitter_message` VARCHAR(120) CHARACTER SET utf8 NOT NULL,
			`requires_confirmation` CHAR(1) BINARY NOT NULL,
			`return_url` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`displays_custom_field` CHAR(1) BINARY NOT NULL,
			`custom_field_label` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`displays_optin` CHAR(1) BINARY NOT NULL,
			`optin_label` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`is_editable` CHAR(1) BINARY NOT NULL,
			UNIQUE KEY  (`id`)
		);
		CREATE TABLE `$db_signatures` (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`petitions_id` BIGINT(20) NOT NULL,
			`first_name` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`last_name` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`email` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`street_address` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`city` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`state` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`postcode` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`country` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			`custom_field` VARCHAR(400) CHARACTER SET utf8 NOT NULL,
			`optin` CHAR(1) BINARY NOT NULL,
			`date` DATETIME NOT NULL,
			`confirmation_code` VARCHAR(32) NOT NULL,
			`is_confirmed` CHAR(1) BINARY NOT NULL,
			`custom_message` LONGTEXT CHARACTER SET utf8 NOT NULL,
			`language` VARCHAR(10) CHARACTER SET utf8 NOT NULL,
			UNIQUE KEY  (`id`)
		);";

	// create database tables
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_create_tables );

	// set default options
	$options = array(
		"petitions_rows"         => "20",
		"signatures_rows"        => "50",
		"petition_theme"         => "default",
		"button_text"            => __( "Sign Now", "dk_speakup" ),
		"expiration_message"     => __( "This petition is now closed.", "dk_speakup" ),
		"success_message"        => "<strong>" . __( "Thank you", "dk_speakup" ) . ", %first_name%.</strong>\r\n<p>" . __( "Your signature has been added.", "dk_speakup" ) . "</p>",
		"already_signed_message" => __( "This petition has already been signed using your email address.", "dk_speakup"),
		"share_message"          => __( "Share this with your friends:", "dk_speakup" ),
		"confirm_subject"        => __( "Please confirm your email address", "dk_speakup" ),
		"confirm_message"        => __( "Hello", "dk_speakup" ) . " %first_name%\r\n\r\n" . __( "Thank you for singing our petition", "dk_speakup" ) . ". " . __( "Please confirm your email address by clicking the link below:", "dk_speakup" ) . "\r\n%confirmation_link%\r\n\r\n" . get_bloginfo( "name" ),
		"confirm_email"          => get_bloginfo( "name" ) . " <" . get_bloginfo( "admin_email" ) . ">",
		"optin_default"          => "unchecked",
		"display_count"          => "1",
		"signaturelist_theme"    => "default",
		"signaturelist_header"   => __( "Latest Signatures", "dk_speakup" ),
		"signaturelist_rows"     => "50",
		"signaturelist_columns"  => serialize( array( "sig_date" ) ),
		"widget_theme"           => "default",
		"csv_signatures"         => "all"
	);
	// add plugin options to wp_options table
	add_option( 'dk_speakup_options', $options );
	add_option( 'dk_speakup_version', $dk_speakup_version );

	// register options for translation in WPML
	include_once( 'class.wpml.php' );
	$wpml = new dk_speakup_WPML();
	$wpml->register_options( $options );
}

// run plugin update script if needed
add_action( 'plugins_loaded', 'dk_speakup_update' );
function dk_speakup_update() {

	global $wpdb, $db_petitions, $db_signatures, $dk_speakup_version;
	$installed_version = get_option( 'dk_speakup_version' );
	$options           = get_option( 'dk_speakup_options' );

	// Update to version 1.6
	if ( version_compare( $installed_version, '1.6', '<' ) == 1 ) {
		error_log( 'updating to 1.6' );

		// change old 'address' column name to 'street_address' in signatures table
		$sql_update = "
			ALTER TABLE $db_signatures
			CHANGE address street_address VARCHAR(200) CHARACTER SET utf8 NOT NULL
		;";
		$wpdb->query( $sql_update );

		// drop display_address column in petitions table
		$sql_update = "
			ALTER TABLE $db_petitions
			CHANGE display_address address_fields VARCHAR(200) CHARACTER SET utf8 NOT NULL
		;";
		$wpdb->query( $sql_update );

		// make existing petitions that were set to display the address field now display the new street field
		$address_fields = serialize( array( 'street' ) );
		$sql_update = "
			UPDATE $db_petitions
			SET `address_fields` = '%s'
			WHERE `address_fields` = '1'
		;";
		$wpdb->query( $wpdb->prepare( $sql_update, $address_fields ) );
	}

	// Update to version 1.7
	if ( version_compare( $installed_version, '1.7', '<' ) == 1 ) {
		error_log( 'updating to 1.7' );

		// make existing petitions that were NOT set to display the address field contain a serialized array
		$address_fields = serialize( array( 'none' ) );
		$sql_update = "
			UPDATE $db_petitions
			SET `address_fields` = '%s'
			WHERE `address_fields` = '0'
		;";
		$wpdb->query( $wpdb->prepare( $sql_update, $address_fields ) );

		$sql_update = "
			UPDATE $db_petitions
			SET `address_fields` = '%s'
			WHERE `address_fields` = 'Array'
		;";
		$wpdb->query( $wpdb->prepare( $sql_update, $address_fields ) );
	}


	// Update to version 2.0
	if ( version_compare( $installed_version, '2.0', '<' ) == 1 ) {
		error_log( 'updating to 2.0' );

		$sql_update = "
			ALTER TABLE $db_petitions
			DROP COLUMN has_signature_goal,
			CHANGE petition_title title TEXT CHARACTER SET utf8 NOT NULL,
			CHANGE petition_email target_email VARCHAR(300) CHARACTER SET utf8 NOT NULL,
			CHANGE petition_subject email_subject VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			CHANGE petition_greeting greeting VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			CHANGE signature_goal goal INT(11) NOT NULL,
			CHANGE send_email sends_email CHAR(1) BINARY NOT NULL,
			CHANGE confirm requires_confirmation CHAR(1) BINARY NOT NULL,
			CHANGE display_custom_field displays_custom_field CHAR(1) BINARY NOT NULL,
			CHANGE display_email_optin displays_optin CHAR(1) BINARY NOT NULL,
			CHANGE email_optin_label optin_label VARCHAR(200) CHARACTER SET utf8 NOT NULL
		;";
		$wpdb->query( $sql_update );

		$sql_update = "
			ALTER TABLE $db_signatures
			CHANGE email_optin optin CHAR(1) BINARY NOT NULL,
			CHANGE confirmed is_confirmed CHAR(1) BINARY NOT NULL
		;";
		$wpdb->query( $sql_update );

		if ( $options['petition_theme'] == 'standard' ) {
			$options['petition_theme'] = 'default';
		}
	}


	if ( $installed_version != $dk_speakup_version ) {

		// create database tables and options
		dk_speakup_install();

		// options added after initial release
		if ( ! array_key_exists( 'confirm_subject', $options ) ) {
			$options['confirm_subject'] = __( 'Please confirm your email address', 'dk_speakup' );
		}
		if ( ! array_key_exists( 'confirm_message', $options ) ) {
			$options['confirm_message'] = __( "Hello", "dk_speakup" ) . " %first_name%\r\n\r\n" . __( "Thank you for singing our petition", "dk_speakup" ) . ". " . __( "Please confirm your email address by clicking the link below:", "dk_speakup" ) . "\r\n%confirmation_link%\r\n\r\n" . get_bloginfo( "name" );
		}
		if ( ! array_key_exists( 'confirm_email', $options ) ) {
			$options['confirm_email'] = get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>';
		}
		if ( ! array_key_exists( 'signaturelist_header', $options ) ) {
			$options['signaturelist_header'] = __( 'Latest Signatures', 'dk_speakup' );
		}
		if ( ! array_key_exists( 'signaturelist_rows', $options ) ) {
			$options['signaturelist_rows'] = '50';
		}
		if ( ! array_key_exists( 'optin_default', $options ) ) {
			$options['optin_default'] = 'unchecked';
		}
		if ( ! array_key_exists( 'display_count', $options ) ) {
			$options['display_count'] = '1';
		}
		if ( ! array_key_exists( 'signaturelist_columns', $options ) ) {
			$options['signaturelist_columns'] = serialize( array( 'sig_date' ) );
		}
		if ( ! array_key_exists( 'signaturelist_theme', $options ) ) {
			$options['signaturelist_theme'] = "default";
		}
		if ( ! array_key_exists( 'widget_theme', $options ) ) {
			$options['widget_theme'] = "default";
		}
		if ( ! array_key_exists( 'csv_signatures', $options ) ) {
			$options['csv_signatures'] = "all";
		}
		update_option( 'dk_speakup_options', $options );
	}

	// update plugin version tag in db
	if ( $installed_version != $dk_speakup_version ) {
		update_option( 'dk_speakup_version', $dk_speakup_version );
	}
}

?>