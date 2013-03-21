<?php

// register shortcode to display signatures count
add_shortcode( 'signaturecount', 'dk_speakup_signaturescount_shortcode' );
function dk_speakup_signaturescount_shortcode( $attr ) {
	include_once( 'class.petition.php' );
	$petition = new dk_speakup_Petition();

	$id = 1; // default
	if ( isset( $attr['id'] ) && is_numeric( $attr['id'] ) ) {
		$id = $attr['id'];
	}
	
	$petition_exists = $petition->retrieve( $id );
	if ( $petition_exists ) {
		return $petition->signatures;
	}
	else {
		return '';
	}
}

// register shortcode to display petition form
add_shortcode( 'emailpetition', 'dk_speakup_emailpetition_shortcode' );
function dk_speakup_emailpetition_shortcode( $attr ) {

	// only query a petition if the "id" attribute has been set
	if ( isset( $attr['id'] ) && is_numeric( $attr['id'] ) ) {

		global $dk_speakup_version;
		include_once( 'class.speakup.php' );
		include_once( 'class.petition.php' );
		include_once( 'class.wpml.php' );
		$petition = new dk_speakup_Petition();
		$wpml     = new dk_speakup_WPML();
		$options  = get_option( 'dk_speakup_options' );

		// get petition data from database
		$id = absint( $attr['id'] );
		$petition_exists = $petition->retrieve( $id );

		// attempt to translate with WPML
		$wpml->translate_petition( $petition );
		$options   = $wpml->translate_options( $options );
		$wpml_lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '';

		if ( $petition_exists ) {

			$expired = ( $petition->expires == 1 && current_time( 'timestamp' ) >= strtotime( $petition->expiration_date ) ) ? 1 : 0;

			// shortcode attributes
			$width          = isset( $attr['width'] ) ? 'style="width: ' . $attr['width'] . ';"' : '';
			$height         = isset( $attr['height'] ) ? 'style="height: ' . $attr['height'] . ' !important;"' : '';
			$css_classes    = isset( $attr['class'] ) ? $css_classes = $attr['class'] : '';
			$progress_width = ( $options['petition_theme'] == 'basic' ) ? 300 : 200; // defaults
			$progress_width = isset( $attr['progresswidth'] ) ? $attr['progresswidth'] : $progress_width;

			if ( ! $expired ) {
				$userdata = dk_speakup_SpeakUp::userinfo();

				// compose the petition form
				$petition_form = '
					<!-- SpeakUp! Email Petitions ' . $dk_speakup_version . ' -->
					<div id="dk-speakup-windowshade"></div>
					<div class="dk-speakup-petition-wrap ' . $css_classes . '" id="dk-speakup-petition-' . $petition->id . '" ' . $width . '>
						<h3>' . stripslashes( esc_html( $petition->title ) ) . '</h3>
						<a id="dk-speakup-readme-' . $petition->id . '" class="dk-speakup-readme" rel="' . $petition->id . '" style="display: none;"><span>' . __( 'Read the petition', 'dk_speakup' ) . '</span></a>
						<div class="dk-speakup-response"></div>
						<form class="dk-speakup-petition">
							<input type="hidden" id="dk-speakup-posttitle-' . $petition->id . '" value="' . esc_attr( urlencode( stripslashes( $petition->title ) ) ) .'" />
							<input type="hidden" id="dk-speakup-tweet-' . $petition->id . '" value="' . dk_speakup_SpeakUp::twitter_encode( $petition->twitter_message ) .'" />
							<input type="hidden" id="dk-speakup-lang-' . $petition->id . '" value="' . $wpml_lang .'" />
							<input type="hidden" id="dk-speakup-textval-' . $petition->id . '" value="val" />
							<div class="dk-speakup-full">
								<label for="dk-speakup-first-name-' . $petition->id . '" class="required">' . __( 'First Name', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-first-name" id="dk-speakup-first-name-' . $petition->id . '" value="' . $userdata['firstname'] . '" type="text" />
							</div>
							<div class="dk-speakup-full">
								<label for="dk-speakup-last-name-' . $petition->id . '" class="required">' . __( 'Last Name', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-last-name" id="dk-speakup-last-name-' . $petition->id . '" value="' . $userdata['lastname'] . '" type="text" />
							</div>
							<div class="dk-speakup-full">
								<label for="dk-speakup-email-' . $petition->id . '" class="required">' . __( 'Email', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-email" id="dk-speakup-email-' . $petition->id . '" value="' . $userdata['email'] . '" type="text" />
							</div>';
				if ( $petition->requires_confirmation ) {
					$petition_form .= '
							<div class="dk-speakup-full">
								<label for="dk-speakup-email-confirm-' . $petition->id . '" class="required">' . __( 'Confirm Email', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-email-confirm" id="dk-speakup-email-confirm-' . $petition->id . '" value="" type="text" />
							</div>';
				}
				if ( in_array( 'street', $petition->address_fields ) ) {
					$petition_form .= '
							<div class="dk-speakup-full">
								<label for="dk-speakup-street-' . $petition->id . '">' . __( 'Street', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-street" id="dk-speakup-street-' . $petition->id . '" maxlength="200" type="text" />
							</div>';
				}
				$petition_form .= '<div>'; // need this div to give half-width fields a new parent - so we can style their margins differently by :nth-child
				if ( in_array( 'city', $petition->address_fields ) ) {
					$petition_form .= '
							<div class="dk-speakup-half">
								<label for="dk-speakup-city-' . $petition->id . '">' . __( 'City', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-city" id="dk-speakup-city-' . $petition->id . '" maxlength="200" type="text" />
							</div>';
				}
				if ( in_array( 'state', $petition->address_fields ) ) {
					$petition_form .= '
							<div class="dk-speakup-half">
								<label for="dk-speakup-state-' . $petition->id . '">' . __( 'State / Province', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-state" id="dk-speakup-state-' . $petition->id . '" maxlength="200" type="text" list="dk-speakup-states" />
								<datalist id="dk-speakup-states">
									<option value="Alabama"><option value="Alaska"><option value="Alberta"><option value="Arizona"><option value="Arkansas"><option value="British Columbia"><option value="California"><option value="Colorado"><option value="Connecticut"><option value="Washington DC"><option value="Delaware"><option value="Florida"><option value="Georgia"><option value="Hawaii"><option value="Idaho"><option value="Illinois"><option value="Indiana"><option value="Iowa"><option value="Kansas"><option value="Kentucky"><option value="Labrador"><option value="Louisiana"><option value="Maine"><option value="Manitoba"><option value="Maryland"><option value="Massachusetts"><option value="Michigan"><option value="Minnesota"><option value="Mississippi"><option value="Missouri"><option value="Montana"><option value="Nebraska"><option value="Nevada"><option value="New Brunswick"><option value="Newfoundland"><option value="New Hampshire"><option value="New Jersey"><option value="New Mexico"><option value="New York"><option value="North Carolina"><option value="North Dakota"><option value="North West Territory"><option value="Nova Scotia"><option value="Nunavut"><option value="Ohio"><option value="Oklahoma"><option value="Ontario"><option value="Oregon"><option value="Pennsylvania"><option value="Prince Edward Island"><option value="Quebec"><option value="Rhode Island"><option value="Saskatchewan"><option value="South Carolina"><option value="South Dakota"><option value="Tennessee"><option value="Texas"><option value="Utah"><option value="Vermont"><option value="Virginia"><option value="Washington"><option value="West Virginia"><option value="Wisconsin"><option value="Wyoming"><option value="Yukon">
								</datalist>
							</div>';
				}
				if ( in_array( 'postcode', $petition->address_fields ) ) {
					$petition_form .= '
							<div class="dk-speakup-half">
								<label for="dk-speakup-postcode-' . $petition->id . '">' . __( 'Post Code', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-postcode" id="dk-speakup-postcode-' . $petition->id . '" maxlength="200" type="text" />
							</div>';
				}
				if ( in_array( 'country', $petition->address_fields ) ) {
					$petition_form .= '
							<div class="dk-speakup-half">
								<label for="dk-speakup-country-' . $petition->id . '">' . __( 'Country', 'dk_speakup' ) . '</label>
								<input name="dk-speakup-country" id="dk-speakup-country-' . $petition->id . '" maxlength="200" type="text" list="dk-speakup-countries" />
								<datalist id="dk-speakup-countries">
									<option value="Afghanistan"><option value="Albania"><option value="Algeria"><option value="American Samoa"><option value="Andorra"><option value="Angola"><option value="Anguilla"><option value="Antarctica"><option value="Antigua and Barbuda"><option value="Argentina"><option value="Armenia"><option value="Aruba"><option value="Australia"><option value="Austria"><option value="Azerbaijan"><option value="Bahrain"><option value="Bangladesh"><option value="Barbados"><option value="Belarus"><option value="Belgium"><option value="Belize"><option value="Benin"><option value="Bermuda"><option value="Bhutan"><option value="Bolivia"><option value="Bosnia and Herzegovina"><option value="Botswana"><option value="Bouvet Island"><option value="Brazil"><option value="British Indian Ocean Territory"><option value="British Virgin Islands"><option value="Brunei"><option value="Bulgaria"><option value="Burkina Faso"><option value="Burundi"><option value="Côte d\'Ivoire"><option value="Cambodia"><option value="Cameroon"><option value="Canada"><option value="Cape Verde"><option value="Cayman Islands"><option value="Central African Republic"><option value="Chad"><option value="Chile"><option value="China"><option value="Christmas Island"><option value="Cocos (Keeling) Islands"><option value="Colombia"><option value="Comoros"><option value="Congo"><option value="Cook Islands"><option value="Costa Rica"><option value="Croatia"><option value="Cuba"><option value="Cyprus"><option value="Czech Republic"><option value="Democratic Republic of the Congo"><option value="Denmark"><option value="Djibouti"><option value="Dominica"><option value="Dominican Republic"><option value="East Timor"><option value="Ecuador"><option value="Egypt"><option value="El Salvador"><option value="Equatorial Guinea"><option value="Eritrea"><option value="Estonia"><option value="Ethiopia"><option value="Faeroe Islands"><option value="Falkland Islands"><option value="Fiji"><option value="Finland"><option value="Former Yugoslav Republic of Macedonia"><option value="France"><option value="French Guiana"><option value="French Polynesia"><option value="French Southern Territories"><option value="Gabon"><option value="Georgia"><option value="Germany"><option value="Ghana"><option value="Gibraltar"><option value="Greece"><option value="Greenland"><option value="Grenada"><option value="Guadeloupe"><option value="Guam"><option value="Guatemala"><option value="Guinea"><option value="Guinea-Bissau"><option value="Guyana"><option value="Haiti"><option value="Heard Island and McDonald Islands"><option value="Honduras"><option value="Hong Kong"><option value="Hungary"><option value="Iceland"><option value="India"><option value="Indonesia"><option value="Iran"><option value="Iraq"><option value="Ireland"><option value="Israel"><option value="Italy"><option value="Jamaica"><option value="Japan"><option value="Jordan"><option value="Kazakhstan"><option value="Kenya"><option value="Kiribati"><option value="Kuwait"><option value="Kyrgyzstan"><option value="Laos"><option value="Latvia"><option value="Lebanon"><option value="Lesotho"><option value="Liberia"><option value="Libya"><option value="Liechtenstein"><option value="Lithuania"><option value="Luxembourg"><option value="Macau"><option value="Madagascar"><option value="Malawi"><option value="Malaysia"><option value="Maldives"><option value="Mali"><option value="Malta"><option value="Marshall Islands"><option value="Martinique"><option value="Mauritania"><option value="Mauritius"><option value="Mayotte"><option value="Mexico"><option value="Micronesia"><option value="Moldova"><option value="Monaco"><option value="Mongolia"><option value="Montserrat"><option value="Morocco"><option value="Mozambique"><option value="Myanmar"><option value="Namibia"><option value="Nauru"><option value="Nepal"><option value="Netherlands"><option value="Netherlands Antilles"><option value="New Caledonia"><option value="New Zealand"><option value="Nicaragua"><option value="Niger"><option value="Nigeria"><option value="Niue"><option value="Norfolk Island"><option value="North Korea"><option value="Northern Marianas"><option value="Norway"><option value="Oman"><option value="Pakistan"><option value="Palau"><option value="Panama"><option value="Papua New Guinea"><option value="Paraguay"><option value="Peru"><option value="Philippines"><option value="Pitcairn Islands"><option value="Poland"><option value="Portugal"><option value="Puerto Rico"><option value="Qatar"><option value="Réunion"><option value="Romania"><option value="Russia"><option value="Rwanda"><option value="São Tomé and Príncipe"><option value="Saint Helena"><option value="Saint Kitts and Nevis"><option value="Saint Lucia"><option value="Saint Pierre and Miquelon"><option value="Saint Vincent and the Grenadines"><option value="Samoa"><option value="San Marino"><option value="Saudi Arabia"><option value="Senegal"><option value="Seychelles"><option value="Sierra Leone"><option value="Singapore"><option value="Slovakia"><option value="Slovenia"><option value="Solomon Islands"><option value="Somalia"><option value="South Africa"><option value="South Georgia and the South Sandwich Islands"><option value="South Korea"><option value="Spain"><option value="Sri Lanka"><option value="Sudan"><option value="Suriname"><option value="Svalbard and Jan Mayen"><option value="Swaziland"><option value="Sweden"><option value="Switzerland"><option value="Syria"><option value="Taiwan"><option value="Tajikistan"><option value="Tanzania"><option value="Thailand"><option value="The Bahamas"><option value="The Gambia"><option value="Togo"><option value="Tokelau"><option value="Tonga"><option value="Trinidad and Tobago"><option value="Tunisia"><option value="Turkey"><option value="Turkmenistan"><option value="Turks and Caicos Islands"><option value="Tuvalu"><option value="US Virgin Islands"><option value="Uganda"><option value="Ukraine"><option value="United Arab Emirates"><option value="United Kingdom"><option value="United States"><option value="United States Minor Outlying Islands"><option value="Uruguay"><option value="Uzbekistan"><option value="Vanuatu"><option value="Vatican City"><option value="Venezuela"><option value="Vietnam"><option value="Wallis and Futuna"><option value="Western Sahara"><option value="Yemen"><option value="Yugoslavia"><option value="Zambia"><option value="Zimbabwe">
								</datalist>
							</div>';
				}
				$petition_form .= '</div>';
				if ( $petition->displays_custom_field == 1 ) {
					$petition_form .= '
							<div class="dk-speakup-full">
								<label for="dk-speakup-custom-field-' . $petition->id . '">' . stripslashes( esc_html( $petition->custom_field_label ) ) . '</label>
								<input name="dk-speakup-custom-field" id="dk-speakup-custom-field-' . $petition->id . '" maxlength="400" type="text" />
							</div>';
				}
				if ( $petition->is_editable == 1 ) {
					$petition_form .= '
							<div class="dk-speakup-full dk-speakup-message-editable" id="dk-speakup-message-editable-' . $petition->id . '">
								<p class="dk-speakup-greeting">' . $petition->greeting . '</p>
								<textarea name="dk-speakup-message" class="dk-speakup-message-' . $petition->id . '" ' . $height . ' rows="8">' . stripslashes( esc_textarea( $petition->petition_message ) ) . '</textarea>
								<p class="dk-speakup-caps">[' . __( 'signature', 'dk-speakup' ) . ']</p>
							</div>';
				} else {
					$petition_form .= '
							<div class="dk-speakup-full dk-speakup-message" ' . $height . ' id="dk-speakup-message-' . $petition->id . '">
								<p class="dk-speakup-greeting">' . $petition->greeting . '</p>
								' . stripslashes( wpautop( $petition->petition_message ) ) . '
								<p class="dk-speakup-caps">[' . __( 'signature', 'dk-speakup' ) . ']</p>
							</div>';
				}
				if ( $petition->displays_optin == 1 ) {
					$optin_default = ( $options['optin_default'] == 'checked' ) ? ' checked="checked"' : '';
					$petition_form .= '
							<div class="dk-speakup-optin-wrap">
								<input type="checkbox" name="dk-speakup-optin" id="dk-speakup-optin-' . $petition->id . '"' . $optin_default . ' />
								<label for="dk-speakup-optin-' . $petition->id . '">' . stripslashes( esc_html( $petition->optin_label ) ) . '</label>
							</div>';
				}
				$petition_form .= '
							<div class="dk-speakup-submit-wrap">
								<div id="dk-speakup-ajaxloader-' . $petition->id . '" class="dk-speakup-ajaxloader" style="visibility: hidden;">&nbsp;</div>
								<a name="' . $petition->id . '" class="dk-speakup-submit"><span>' . stripslashes( esc_html( $options['button_text'] ) ) . '</span></a>
							</div>
						</form>';
				if ( $options['display_count'] == 1 ) {
					$petition_form .= '
						<div class="dk-speakup-progress-wrap">
							<div class="dk-speakup-signature-count">
								<span>' . number_format( $petition->signatures ) . '</span> ' . _n( 'signature', 'signatures', $petition->signatures, 'dk_speakup' ) . '
							</div>
							' . dk_speakup_SpeakUp::progress_bar( $petition->goal, $petition->signatures, $progress_width ) . '
						</div>';
				}
				$petition_form .= '
						<div class="dk-speakup-share">
							<div><p>' . stripslashes( esc_html( $options['share_message'] ) ) . '</p>
							<p>
								<a class="dk-speakup-facebook" href="#" title="Facebook" rel="' . $petition->id . '"><span>&nbsp;</span></a>
								<a class="dk-speakup-twitter" href="#" title="Twitter" rel="' . $petition->id . '"><span>&nbsp;</span></a>
							</p>
						</div>
							<div class="dk-speakup-clear"></div>
						</div>
					</div>';
			}
			// petition has expired
			else {
				$goal_text = ( $petition->goal != 0 ) ? '<p><strong>' . __( 'Signature goal', 'dk_speakup' ) . ':</strong> ' . $petition->goal . '</p>' : '';
				$petition_form = '
					<div class="dk-speakup-petition-wrap dk-speakup-expired" id="dk-speakup-petition-' . $petition->id . '">
						<h3>' . stripslashes( esc_html( $petition->title ) ) . '</h3>
						<p>' . stripslashes( esc_html( $options['expiration_message'] ) ) . '</p>
						<p><strong>' . __( 'End date', 'dk_speakup' ) . ':</strong> ' . date( 'M d, Y', strtotime( $petition->expiration_date ) ) . '</p>
						<p><strong>' . __( 'Signatures collected', 'dk_speakup' ) . ':</strong> ' . $petition->signatures . '</p>
						' . $goal_text . '
						<div class="dk-speakup-progress-wrap">
							<div class="dk-speakup-signature-count">
								<span>' . number_format( $petition->signatures ) . '</span> ' . _n( 'signature', 'signatures', $petition->signatures, 'dk_speakup' ) . '
							</div>
							' . dk_speakup_SpeakUp::progress_bar( $petition->goal, $petition->signatures, $progress_width ) . '
						</div>
					</div>';
			}

		}
		// petition doesn't exist
		else {
			$petition_form = '';
		}
	}

	// id attribute was left out, as in [emailpetition]
	else {
		$petition_form = '
			<div class="dk-speakup-petition-wrap dk-speakup-expired">
				<h3>' . __( 'Petition', 'dk_speakup' ) . '</h3>
				<div class="dk-speakup-notice">
					<p>' . __( 'Error: You must include a valid id.', 'dk_speakup' ) . '</p>
				</div>
			</div>';
	}

	return $petition_form;
}

// load public CSS on pages/posts that contain the [emailpetition] shortcode
add_filter( 'the_posts', 'dk_speakup_public_css_js' );
function dk_speakup_public_css_js( $posts ) {

	if ( empty( $posts ) ) return $posts;

	$options = get_option( 'dk_speakup_options' );
	$shortcode_found = false;

	foreach ( $posts as $post ) {
		if ( strstr( $post->post_content, '[emailpetition' ) ) {
			$shortcode_found = true;
			break;
		}
	}

	// load the CSS and JavaScript
	if ( $shortcode_found ) {
		$theme = $options['petition_theme'];

		switch( $theme ) {
			case 'default' :
				wp_enqueue_style( 'dk_speakup_css', plugins_url( 'speakup-email-petitions/css/theme-default.css' ) );
				break;
			case 'basic' :
				wp_enqueue_style( 'dk_speakup_css', plugins_url( 'speakup-email-petitions/css/theme-basic.css' ) );
				break;
			case 'none' :
				$parent_dir = get_template_directory_uri();
				$parent_petition_theme_url = $parent_dir . '/petition.css';

				// if a child theme is in use
				// attempt to load petition.css from child theme folder
				if ( is_child_theme() ) {
					$child_dir = get_stylesheet_directory_uri();
					$child_petition_theme_url = $child_dir . '/petition.css';
					$child_petition_theme_path = STYLESHEETPATH . '/petition.css';

					// use child theme if it exists
					if ( file_exists( $child_petition_theme_path ) ) {
						wp_enqueue_style( 'dk_speakup_css', $child_petition_theme_url );
					}
					// else try to load style from parent theme folder
					else {
						wp_enqueue_style( 'dk_speakup_css', $parent_petition_theme_url );
					}
				}
				// try to load style from active theme folder
				else {
					wp_enqueue_style( 'dk_speakup_css', $parent_petition_theme_url );
				}
				break;
		}

		// ensure ajax callback url works on both https and http
		$protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$params   = array( 'ajaxurl' => admin_url( 'admin-ajax.php', $protocol ) );
		wp_enqueue_script( 'dk_speakup_js', plugins_url( 'speakup-email-petitions/js/public.js' ), array( 'jquery' ) );
		wp_localize_script( 'dk_speakup_js', 'dk_speakup_js', $params );
	}

	return $posts;
}

?>