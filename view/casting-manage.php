<?php
// *************************************************************************************************** //
// Prepare Page

	session_start();

	header("Cache-control: private"); //IE 6 Fix

	global $wpdb;
	global $current_user, $wp_roles;
	get_currentuserinfo();

	// Get Settings
	$rb_agency_options_arr = get_option('rb_agency_options');
	$rb_agency_option_profilenaming  = (int)$rb_agency_options_arr['rb_agency_option_profilenaming'];
	$rb_agency_interact_option_registerconfirm = (int)$rb_agency_interact_options_arr['rb_agencyinteract_option_registerconfirm'];

	/* Check if users can register. */
	$registration = get_option( 'users_can_register' );

	function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}
	
	//fetch data from database
	$data_r = $wpdb->get_row("SELECT * FROM ". table_agency_casting . " WHERE CastingUserLinked = " . $current_user->ID);
		
	/* If user registered, input info. */
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'updatecasting' ) {
		
		// Error checking
		$error = "";
		$have_error = false;
		
		if (!$userdata['user_login']) {
			$error .= __("A username is required for registration.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( username_exists($userdata['user_login'])) {
			$error .= __("Sorry, that username already exists!<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( !is_email($userdata['user_email'], true)) {
			$error .= __("You must enter a valid email address.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( email_exists($userdata['user_email'])) {
			$error .= __("Sorry, that email address is already used!<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		
		if ( empty($_POST['casting_company'])) {
			$error .= __("Company is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['casting_website'])) {
			$error .= __("website is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['casting_address'])) {
			$error .= __("Address is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['casting_city'])) {
			$error .= __("City is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['CastingState'])) {
			$error .= __("State is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['casting_zip'])) {
			$error .= __("Zip is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_POST['CastingCountry'])) {
			$error .= __("Country is required.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}

		if ( $_POST['casting_agree'] <> "yes") {
			$error .= __("You must agree to the terms and conditions to register.<br />", rb_agency_interact_TEXTDOMAIN);
			$have_error = true;
		}

		// Bug Free!
		if($have_error == false){

			$new_user = wp_insert_user( $userdata );

			$CastingIsActive = 3;

			//create folder
			$CastingGallery = "casting-agent-" . $new_user;
			if (!is_dir(rb_agency_UPLOADPATH . $CastingGallery)) {
				mkdir(rb_agency_casting_UPLOADPATH . $CastingGallery, 0755);
				chmod(rb_agency_casting_UPLOADPATH . $CastingGallery, 0777);
			}

			if ($rb_agency_option_profilenaming == 0) { 
				$CastingContactDisplay = $CastingContactNameFirst . " ". $CastingContactNameLast;
			} elseif ($rb_agency_option_profilenaming == 1) { 
				$CastingContactDisplay = $CastingContactNameFirst . " ". substr($CastingContactNameLast, 0, 1);
			} elseif ($rb_agency_option_profilenaming == 2) { 
				$error .= "<b><i>". __(LabelSingular ." must have a display name identified", rb_agency_interact_TEXTDOMAIN) . ".</i></b><br>";
				$have_error = true;
			} elseif ($rb_agency_option_profilenaming == 3) { // by firstname
				$CastingContactDisplay = "ID ". $CastingID;
			} elseif ($rb_agency_option_profilenaming == 4) {
				$CastingContactDisplay = $CastingContactNameFirst;
			}

			// Create Record
			$insert = "INSERT INTO " . table_agency_casting .
						" (CastingUserLinked,
							CastingGallery,
							CastingContactDisplay,
							CastingContactNameFirst,
							CastingContactNameLast,
							CastingContactEmail,
							CastingContactCompany,
							CastingContactWebsite,
							CastingLocationStreet,
							CastingLocationCity,
							CastingLocationState,
							CastingLocationZip,
							CastingLocationCountry,
							CastingDateCreated,
							CastingIsActive)" .
						"VALUES (". $new_user . 
						",'" . $wpdb->escape($CastingGallery) . "','" . 
								$wpdb->escape($CastingContactDisplay) . 
						"','" . $wpdb->escape($first_name) . "','" . 
								$wpdb->escape($last_name) . 
						"','" . $wpdb->escape($user_email) . "','" . 
								$wpdb->escape($_POST['casting_company']) . "','" . 
								$wpdb->escape($_POST['casting_website']) . "','" . 
								$wpdb->escape($_POST['casting_street']) . "','" . 
								$wpdb->escape($_POST['casting_city']) . "','" . 
								$wpdb->escape($_POST['CastingState']) . "','" . 
								$wpdb->escape($_POST['casting_zip']) . "','" . 
								$wpdb->escape($_POST['CastingCountry']) . "'" . 
								",now(), ". 
								$CastingIsActive .")";

			$results = $wpdb->query($insert) or die(mysql_error());
			$CastingID = $wpdb->insert_id;

			// Log them in if no confirmation required.			
			if ($rb_agency_interact_option_registerconfirm == 1) {

				global $error;

				$login = wp_login( $user_login, $user_pass );
				$login = wp_signon( array( 'user_login' => $user_login, 'user_password' => $user_pass, 'remember' => 1 ), false );	
			
			}
				// Notify admin and user
				wp_new_user_notification($new_user, $user_pass);

		}

		// Log them in if no confirmation required.
		if ($rb_agency_interact_option_registerconfirm == 1) {
			if($login){
				header("Location: ". get_bloginfo("wpurl"). "/casting-member/");
			}
		}

	
	}


// *************************************************************************************************** //
// Prepare Page
	// add scripts
	wp_deregister_script('jquery'); 
	wp_register_script('jquery', 'http://code.jquery.com/jquery-1.11.0.min.js'); 
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'casting',  rb_agency_casting_BASEDIR . 'js/casting.js');
	
	get_header();
	
	echo "<div id=\"primary\" class=\"".$column_class." column rb-agency-interact rb-agency-interact-register\">\n";
	echo "  <div id=\"content\">\n";

   
		// ****************************************************************************************** //
		// Already logged in 

	if ( $error ) {
		echo "<p class=\"error\">". $error ."</p>\n";
	}

			// Self Registration
	if ( $registration || current_user_can("create_users") ) {
	echo "  <header class=\"entry-header\">";
		echo "<h1>Welcome ". $current_user->user_firstname ."</h1>\n";
		echo "<h1>We have registered you as Agent/Producer.</h1>\n";
	echo "  </header>";
	echo "  <div id=\"client-register\" class=\"rbform\">";
	echo "	<h3>". __("Account Information", rb_agency_interact_TEXTDOMAIN) ."</h3>\n";	
	echo "    <form method=\"post\" action=\"". get_bloginfo('wpurl') ."/casting-manage/\">\n";
				
	echo "       <div id=\"casting-first-name\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"casting_first_name\">". __("First Name", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_first_name\" type=\"text\" id=\"casting_first_name\" value='".$data_r->CastingContactNameFirst."' /></div>\n";
	echo "       </div><!-- #casting-first-name -->\n";
				
	echo "       <div id=\"casting-last-name\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"casting_last_name\">". __("Last Name", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_last_name\" type=\"text\" id=\"casting_last_name\" value='".$data_r->CastingContactNameLast."' /></div>\n";
	echo "       </div><!-- #casting_last_name -->\n";
				
	echo "       <div id=\"casting-email\" class=\"rbfield rbemail rbsingle\">\n";
	echo "       	<label for=\"email\">". __("E-mail (required)", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_email\" type=\"text\" id=\"casting_email\" value='".$data_r->CastingContactEmail."' /></div>\n";
	echo "       </div><!-- #casting-email -->\n";

	echo "       <div id=\"casting-company\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"company\">". __("Company", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_company\" type=\"text\" value='".$data_r->CastingContactCompany."' /></div>\n";
	echo "       </div><!-- #casting-company -->\n";

	echo "       <div id=\"casting-website\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"website\">". __("Website", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_website\" type=\"text\" id=\"casting_email\" value='".$data_r->CastingContactWebsite."' /></div>\n";
	echo "       </div><!-- #casting-website -->\n";
	
	echo "       <div id=\"casting-street-address\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"street-address\">". __("Street Address", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_address\" type=\"text\" value='".$data_r->CastingLocationStreet."' /></div>\n";
	echo "       </div><!-- #casting-street-address -->\n";

	echo "       <div id=\"casting-city\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"city\">". __("City", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_city\" type=\"text\" id=\"casting_email\" value='".$data_r->CastingLocationCity."' /></div>\n";
	echo "       </div><!-- #casting-city -->\n";	
	
	echo "<input type='hidden' value='".admin_url('admin-ajax.php')."' id='url'>";
	echo "       <div id=\"casting-country\" class=\"rbfield rbtext rbsingle\">\n";
				echo "		<label>". __("Country", rbagency_TEXTDOMAIN) ."</label>\n";
				echo "		<div>\n";
				$query_get ="SELECT * FROM `".table_agency_data_country."`" ;
				$result_query_get = $wpdb->get_results($query_get);
				echo '<select name="CastingCountry" id="CastingCountry"  onchange="javascript:populateStates();">';
				echo '<option value="">'. __("Select country", _TEXTDOMAIN) .'</option>';
					foreach($result_query_get as $r){
						echo '<option value='.$r->CountryID.' '.selected($data_r->CastingLocationCountry,$r->CountryID,false).' >'.$r->CountryTitle.'</option>';
					}
				echo '</select>';
	echo "       </div></div><!-- #casting-country -->\n";

	echo "       <div id=\"casting-state\" class=\"rbfield rbselect rbsingle\">\n";
				echo "		<label>". __("State", rbagency_TEXTDOMAIN) ."</label>\n";
				echo "		<div>\n";

				if(isset($_POST['CastingCountry']) && !empty($_POST['CastingCountry']) || $data_r->CastingLocationCountry != ""){
						$query_get ="SELECT * FROM ".table_agency_data_state." WHERE CountryID = " .$data_r->CastingLocationCountry ;
				} else {
						$query_get ="SELECT * FROM `".table_agency_data_state."`" ;
				}
				$result_query_get = $wpdb->get_results($query_get);
				echo '<select name="CastingState" id="CastingState">';
				echo '<option value="">'. __("Select state", rbagency_TEXTDOMAIN) .'</option>';
					foreach($result_query_get as $r){

						echo '<option value='.$r->StateID.' '.selected($data_r->CastingLocationState,$r->StateID,false).' >'.$r->StateTitle.'</option>';
					}
				echo '</select>';

	echo "       </div></div><!-- #casting-state -->\n";

	echo "       <div id=\"casting-zip\" class=\"rbfield rbtext rbsingle\">\n";
	echo "       	<label for=\"zip\">". __("Zip", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
	echo "       	<div><input class=\"text-input\" name=\"casting_zip\" type=\"text\" id=\"casting_email\" value='".$data_r->CastingLocationZip."' /></div>\n";
	echo "       </div><!-- #casting-zip -->\n";

		echo "	<h3>". __("Contact Phone", rb_agency_interact_TEXTDOMAIN) ."</h3>\n";
		echo "	<div id=\"profile-facebook\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Home", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkFacebook\" name=\"CastingContactLinkFacebook\" value=\"". $CastingContactLinkFacebook ."\" />\n";
		echo "	</div></div>\n";
		echo "	<div id=\"profile-twitter\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Cell", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkTwitter\" name=\"CastingContactLinkTwitter\" value=\"". $CastingContactLinkTwitter ."\" />\n";
		echo "	</div></div>\n";
		echo "	<div id=\"profile-youtube\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Work", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkYouTube\" name=\"CastingContactLinkYouTube\" value=\"". $CastingContactLinkYouTube ."\" />\n";
		echo "  </div></div>\n";

		// Show Social Media Links
		echo "	<h3>". __("Social Media Castings", rb_agency_interact_TEXTDOMAIN) ."</h3>\n";
		echo "	<div id=\"profile-facebook\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Facebook", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkFacebook\" name=\"CastingContactLinkFacebook\" value=\"". $CastingContactLinkFacebook ."\" />\n";
		echo "	</div></div>\n";
		echo "	<div id=\"profile-twitter\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Twitter", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkTwitter\" name=\"CastingContactLinkTwitter\" value=\"". $CastingContactLinkTwitter ."\" />\n";
		echo "	</div></div>\n";
		echo "	<div id=\"profile-youtube\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("YouTube", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkYouTube\" name=\"CastingContactLinkYouTube\" value=\"". $CastingContactLinkYouTube ."\" />\n";
		echo "  </div></div>\n";
		echo "	<div id=\"profile-flickr\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Flickr", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div><input type=\"text\" class=\"text-input\" id=\"CastingContactLinkFlickr\" name=\"CastingContactLinkFlickr\" value=\"". $CastingContactLinkFlickr ."\" />\n";
		echo "	</div></div>\n";

		if ($rb_agency_interact_option_registerallow  == 1) {
			echo "	<div id=\"profile-username\" class=\"rbfield rbtext rbsingle\">\n";
			echo "		<label>". __("Username", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
			echo "		<div>\n";
			if(isset($current_user->user_login)){
			echo "			<input type=\"text\" id=\"CastingUsername\"  name=\"CastingUsername\" disabled=\"disabled\" value=\"".$current_user->user_login."\" />\n";
			} else {
			echo "			<input type=\"text\" id=\"CastingUsername\"  name=\"CastingUsername\" value=\"\" />\n";	
			}
			echo "			<small class=\"rbfield-note\">Cannot be changed</small>";
			echo "		</div>\n";
			echo "  </div>\n";
		}
		echo "	<h3>". __("Login Settings", rb_agency_interact_TEXTDOMAIN) ."</h3>\n";		
		echo "	<div id=\"rbprofile-password\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Password", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div>";
		echo "			<input type=\"password\" id=\"CastingPassword\" name=\"CastingPassword\" />\n";
		echo "			<small class=\"rbfield-note\">Leave blank to keep same password</small>";	
		echo "	 	</div>\n";
		echo "	</div>\n";
		echo "	<div id=\"rbprofile-retype-password\" class=\"rbfield rbtext rbsingle\">\n";
		echo "		<label>". __("Retype Password", rb_agency_interact_TEXTDOMAIN) ."</label>\n";
		echo "		<div>";
		echo "			<input type=\"password\" id=\"CastingPasswordConfirm\" name=\"CastingPasswordConfirm\" />";
		echo "			<small class=\"rbfield-note\">Retype to Confirm</small>";	
		echo "		</div>\n";
		echo "	</div>\n";

	echo "       <div id=\"casting-submit\" class=\"rbfield rbsubmit rbsingle\">\n";
	echo "       	<input name=\"adduser\" type=\"submit\" id=\"addusersub\" class=\"submit button\" value='Update Information'/>";

					// if ( current_user_can("create_users") ) {  _e("Add User", rb_agency_interact_TEXTDOMAIN); } else {  _e("Register", rb_agency_interact_TEXTDOMAIN); } echo "\" />\n";
					
					wp_nonce_field("add-user");
					$fb_app_register_uri = "";

					if($rb_agency_interact_option_fb_app_register_uri == 1){
						$fb_app_register_uri = $rb_agency_interact_option_fb_app_register_uri;
					}else{
						$fb_app_register_uri = network_site_url("/")."casting-register/";
					}

					// Allow facebook login/registration
					if($rb_agency_interact_option_fb_registerallow ==1){
						echo "<div>\n";
						echo "<span>Or</span>\n";
						echo "<div id=\"fb_RegistrationForm\">\n";
						if ($rb_agency_interact_option_registerconfirm == 1) {	 // With custom password fields
							echo "<iframe src=\"https://www.facebook.com/plugins/registration?client_id=".$rb_agency_interact_option_fb_app_id."&redirect_uri=".$fb_app_register_uri."&fields=[ {'name':'name'}, {'name':'email'}, {'name':'location'}, {'name':'gender'}, {'name':'birthday'}, {'name':'username',  'description':'Username',  'type':'text'},{'name':'password'},{'name':'tos','description':'I agree to the terms of service','type':'checkbox'}]\"		 
								  scrolling=\"auto\"
								  frameborder=\"no\"
								  style=\"border:none\"
								  allowTransparency=\"true\"
								  width=\"100%\"
								  height=\"330\">
							</iframe>";
						}else{
							echo "<iframe src=\"https://www.facebook.com/plugins/registration?client_id=".$rb_agency_interact_option_fb_app_id."&redirect_uri=".$fb_app_register_uri."&fields=[ {'name':'name'}, {'name':'email'}, {'name':'location'}, {'name':'gender'}, {'name':'birthday'}, {'name':'username',  'description':'Username',  'type':'text'},{'name':'password'},{'name':'tos','description':'I agree to the terms of service','type':'checkbox'}]\"		 
								  scrolling=\"auto\"
								  frameborder=\"no\"
								  style=\"border:none\"
								  allowTransparency=\"true\"
								  width=\"100%\"
								  height=\"330\">
							</iframe>";
						}
					
						echo "</div>\n";
						
					}
					
	echo "       	<input name=\"action\" type=\"hidden\" id=\"action\" value=\"updatecasting\" />\n";
	echo "       </div><!-- #casting-submit -->\n";
	// Facebook connect
	?>
	
		 
	 
<?php	
	echo "   </form>\n";
	echo "   </div><!-- .rbform -->\n";

}

if(!$registration){ echo "<p class='alert'>The administrator currently disabled the registration.<p>"; }

echo "  </div><!-- #content -->\n";
echo "</div><!-- #container -->\n";
   
// Get Sidebar 
	$LayoutType = "";
	if ($rb_agency_interact_option_castingmanage_sidebar) {
		$LayoutType = "casting";
		get_sidebar(); 
	}
	
// Get Footer
get_footer();
?>
