jQuery( document ).ready( function( $ ) {
	'use strict';

/* Add New page
------------------------------------------------------------------- */
	$( 'input#requires_confirmation' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-returnurl' ).slideDown();
			$( '#dk-speakup input#return_url' ).focus();
		} else {
			$( 'div.dk-speakup-returnurl' ).slideUp();
		}
	});

	// open or close signature goal settings
	$( 'input#has_goal' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-goal' ).slideDown();
			$( '#dk-speakup input#goal' ).focus();
		} else {
			$( 'div.dk-speakup-goal' ).slideUp();
		}
	});

	// open or close expiration date settings
	$( 'input#expires' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-date' ).slideDown();
		} else {
			$( 'div.dk-speakup-date' ).slideUp();
		}
	});

	// open or close address fields settings
	$( 'input#display-address' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-address' ).slideDown();
		} else {
			$( 'div.dk-speakup-address' ).slideUp();
		}
	});

	// open or close custom field settings
	$( 'input#displays-custom-field' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-custom-field' ).slideDown();
			$( '#dk-speakup input#custom-field-label' ).focus();
		} else {
			$( 'div.dk-speakup-custom-field' ).slideUp();
		}
	});

	// open or close email opt-in settings
	$( 'input#displays-optin' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-optin' ).slideDown();
			$( '#dk-speakup input#optin-label' ).focus();
		} else {
			$( 'div.dk-speakup-optin' ).slideUp();
		}
	});

	// open or close email header settings
	if ( $( 'input#sends_email' ).attr( 'checked' ) ) {
		$( 'div.dk-speakup-email-headers' ).hide();
	}
	$( 'input#sends_email' ).change( function () {
		if ( $( this ).attr( 'checked' ) ) {
			$( 'div.dk-speakup-email-headers' ).slideUp();
		} else {
			$( 'div.dk-speakup-email-headers' ).slideDown();
		}
	});

	// auto-focus the title field on add/edit petitions form if empty
	if ( $( '#dk-speakup input#title' ).val() === '' ) {
		$( '#dk-speakup input#title' ).focus();
	}

	// validate form values before submitting
	$( '#dk_speakup_submit' ).click( function() {

		$( '.dk-speakup-error' ).removeClass( 'dk-speakup-error' );

		var errors     = 0,
			emailRegEx = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,6})?$/,
			email      = $( '#dk-speakup-edit-petition #target_email' ).val(),
			subject    = $( '#dk-speakup-edit-petition #email_subject' ).val(),
			message    = $( '#dk-speakup-edit-petition #petition_message' ).val(),
			goal       = $( '#dk-speakup-edit-petition #goal' ).val(),
			day        = $( '#dk-speakup-edit-petition #day' ).val(),
			year       = $( '#dk-speakup-edit-petition #year' ).val(),
			hour       = $( '#dk-speakup-edit-petition #hour' ).val(),
			minutes    = $( '#dk-speakup-edit-petition #minutes' ).val();

		// if "Do not send email (only collect signatures)" checkbox is not checked
		if ( !$( 'input#sends_email' ).attr( 'checked' ) ) {
			// remove any spaces
			var emails = email.split( ',' );
			for ( var i=0; i < emails.length; i++ ) {
				if ( emails[i].trim() === '' || !emailRegEx.test( emails[i].trim() ) ) { // must include valid email address
					$( '#dk-speakup-edit-petition #target_email' ).addClass( 'dk-speakup-error' );
					errors ++;
				}
			}
			
			if ( subject === '' ) { // must include subject
				$( '#dk-speakup-edit-petition #email_subject' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
		}
		if ( message === '' ) { // must include petition message
			$( '#dk-speakup-edit-petition #petition_message' ).addClass( 'dk-speakup-error' );
			errors ++;
		}

		// if "Set signature goal" checkbox is checked
		if ( $( 'input#has_goal' ).attr( 'checked' ) ) {
			if ( isNaN( goal ) ) { // only numbers are allowed
				$( '#dk-speakup-edit-petition #goal' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
		}

		// if "Set expiration date" checkbox is checked
		if ( $( 'input#expires' ).attr( 'checked' ) ) {
			if ( isNaN( day ) ) { // only numbers are allowed
				$( '#dk-speakup-edit-petition #day' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
			if ( isNaN( year ) ) { // only numbers are allowed
				$( '#dk-speakup-edit-petition #year' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
			if ( isNaN( hour ) ) { // only numbers are allowed
				$( '#dk-speakup-edit-petition #hour' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
			if ( isNaN( minutes ) ) { // only numbers are allowed
				$( '#dk-speakup-edit-petition #minutes' ).addClass( 'dk-speakup-error' );
				errors ++;
			}
		}

		// if no errors found, submit the form
		if ( errors === 0 ) {

			// uncheck all address fields if "Display address fields" is not checked
			if ( ! $( 'input#display-address' ).attr( 'checked' ) ) {
				$( '#street' ).removeAttr( 'checked' );
				$( '#city' ).removeAttr( 'checked' );
				$( '#state' ).removeAttr( 'checked' );
				$( '#postcode' ).removeAttr( 'checked' );
				$( '#country' ).removeAttr( 'checked' );
			}

			$( 'form#dk-speakup-edit-petition' ).submit();
		}
		else {
			$( '.dk-speakup-error-msg' ).fadeIn();
		}

		return false;

	});

	// display character count for for Twitter Message field
	// max characters is 120 to accomodate the shortnened URL provided by Twitter when submitted
	function dkSpeakupTwitterCount() {
		var max_characters = 120;
		var text = $( '#twitter_message' ).val();
		var charcter_count = text.length;
		var charcters_left = max_characters - charcter_count;

		if ( charcter_count <= max_characters ) {
			$( '#twitter-counter' ).html( charcters_left ).css( 'color', '#000' );
		}
		else {
			$( '#twitter-counter' ).html( charcters_left ).css( 'color', '#c00' );
		}
	}
	if ( $( '#twitter_message' ).length > 0 ) {
		dkSpeakupTwitterCount();
	}
	$( '#twitter_message' ).keyup( function() {
		dkSpeakupTwitterCount();
	});

/* Petitions page
------------------------------------------------------------------- */
	// display confirmation box when user tries to delete a petition
	// warns that all signatures associated with the petition will also be deleted
	$( '.dk-speakup-delete-petition' ).click( function( e ) {
		e.preventDefault();

		var delete_link = $( this ).attr( 'href' );
		// confirmation message is contained in a hidden div in the HTML
		// so that it is accessible to PHP translation methods
		var confirm_message = $( '#dk-speakup-delete-confirmation' ).html();
		// add new line characters for nicer confirm msg display
		confirm_message = confirm_message.replace( '? ', '?\n\n' );
		// display confirmation box
		var confirm_delete = confirm( confirm_message );
		// if user presses OK, process delete link
		if ( confirm_delete === true ) {
			document.location = delete_link;
		}
	});

/* Signatures page
------------------------------------------------------------------- */
	// Select box navigation on Signatures page
	// to switch between different petitions
	$('#dk-speakup-switch-petition').change( function() {
		var page    = 'dk_speakup_signatures',
			action  = 'petition',
			pid     = $('#dk-speakup-switch-petition option:selected').val(),
			baseurl = String( document.location ).split( '?' ),
			newurl  = baseurl[0] + '?page=' + page + '&action=' + action + '&pid=' + pid;
		document.location = newurl;
	});

	// display confirmation box when user tries to re-send confirmation emails
	// warns that a bunch of emails will be sent out if they hit OK
	$( 'a#dk-speakup-reconfirm' ).click( function( e ) {
		e.preventDefault();

		var link = $( this ).attr( 'href' );
		// confirmation message is contained in a hidden div in the HTML
		// so that it is accessible to PHP translation methods
		var confirm_message = $( '#dk-speakup-reconfirm-confirmation' ).html();
		// add new line characters for nicer confirm msg display
		confirm_message = confirm_message.replace( '? ', '?\n\n' );
		// display confirm box
		var confirm_delete = confirm( confirm_message );
		// if user presses OK, process delete link
		if ( confirm_delete === true ) {
			document.location = link;
		}
	});

	// stripe the table rows
	$( 'tr.dk-speakup-tablerow:even' ).addClass( 'dk-speakup-tablerow-even' );

/* Pagination for Signatures and Petitions pages
------------------------------------------------------------------- */
	// when new page number is entered using the form on paginated admin pages,
	// construct a new url string to pass along the variables needed to update page
	// and redirect to the new url
	$( '#dk-speakup-pager' ).submit( function() {
		var page        = $( '#dk-speakup-page' ).val(),
			paged       = $( '#dk-speakup-paged' ).val(),
			total_pages = $( '#dk-speakup-total-pages' ).val(),
			baseurl     = String( document.location ).split( '?' ),
			newurl      = baseurl[0] + '?page=' + page + '&paged=' + paged + '&total_pages=' + total_pages;
		document.location = newurl;
		return false;
	});

/* Settings page
------------------------------------------------------------------- */
	// make the correct tab active on page load
	var currentTab = $( 'input#dk-speakup-tab' ).val();
	$( '#' + currentTab ).show();
	$( 'ul#dk-speakup-tabbar li a.' + currentTab ).addClass( 'dk-speakup-active' );

	// switch tabs when they are clicked
	$( 'ul#dk-speakup-tabbar li a' ).click( function( e ) {
		e.preventDefault();

		// tab bar display
		$( 'ul#dk-speakup-tabbar li a' ).removeClass( 'dk-speakup-active' );
		$( this ).addClass( 'dk-speakup-active' );

		// content sections display
		$( '.dk-speakup-tabcontent' ).hide();

		var newTab = $( this ).attr( 'rel' );
		$( 'input#dk-speakup-tab' ).val( newTab );

		$( '#' + newTab ).show();
	});

});