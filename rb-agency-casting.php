<?php 
/*
Plugin Name: RB Agency Casting
Text Domain: rb-agency-casting
Plugin URI: http://rbplugin.com/wordpress/model-talent-agency-software/
Description: Enhancement to the RB Agency software allowing casting directors to casting directly.
Author: Rob Bertholf
Author URI: http://rob.bertholf.com/
Version: 0.0.1
*/
$rb_agency_casting_VERSION = "0.0.1"; 
/*
License: CF Commercial-to-GPL License
Copyright 2007-2013 Rob Bertholf
This License is a legal agreement between You and the Developer for the use of the Software. 
By installing, copying, or otherwise using the Software, You agree to be bound by the terms of this License. 
If You do not agree to the terms of this License, do not install or use the Software.
See license.txt for full details.
*/

// *************************************************************************************************** //

/*
 * Security
 */

	// Avoid direct calls to this file, because now WP core and framework has been used
	if ( !function_exists('add_action') ) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
	}


// *************************************************************************************************** //

/*
 * Declare Global Constants
 */

	// Version
	define("rb_agency_casting_VERSION", $rb_agency_casting_VERSION); // e.g. 1.0
	// Paths
	define("rb_agency_casting_BASENAME", plugin_basename(__FILE__) );  // rb-agency/rb-agency.php
	$rb_agency_casting_WPURL = get_bloginfo("wpurl"); // http://domain.com/wordpress
	$rb_agency_casting_WPUPLOADARRAY = wp_upload_dir(); // Array  $rb_agency_casting_WPUPLOADARRAY['baseurl'] $rb_agency_casting_WPUPLOADARRAY['basedir']
	define("rb_agency_casting_BASEDIR", get_bloginfo("wpurl") ."/". PLUGINDIR ."/". dirname( plugin_basename(__FILE__) ) ."/" );  // http://domain.com/wordpress/wp-content/plugins/rb-agency-casting/
	define("rb_agency_casting_UPLOADDIR", $rb_agency_casting_WPUPLOADARRAY['baseurl'] ."/profile-media/" );  // http://domain.com/wordpress/wp-content/uploads/profile-media/
	define("rb_agency_casting_UPLOADPATH", $rb_agency_casting_WPUPLOADARRAY['basedir'] ."/profile-media/" ); // /home/content/99/6048999/html/domain.com/wordpress/wp-content/uploads/profile-media/
	define("rb_agency_casting_TEXTDOMAIN", basename(dirname( __FILE__ )) ); //   rb-agency


// *************************************************************************************************** //

/*
 * Declare Global WordPress Database Access
 */

	global $wpdb;


/*
 * Set Table Names
 */

	if (!defined("table_agency_casting"))
		define("table_agency_casting", "{$wpdb->prefix}agency_casting");
	if (!defined("table_agency_castingcart"))
		define("table_agency_castingcart", "{$wpdb->prefix}agency_castingcart");
	if (!defined("table_agency_casting_job"))
		define("table_agency_casting_job", "{$wpdb->prefix}agency_casting_job");
	if (!defined("table_agency_casting_job_type"))
		define("table_agency_casting_job", "{$wpdb->prefix}agency_casting_job_type");


// *************************************************************************************************** //


/*
 * Initialize
 */
	// Call the initialization function
	add_action('init',  array('RBAgencyCasting', 'init'));
	// Check if version number changed and upgrade required
	add_action('init',  array('RBAgencyCasting', 'check_update_needed'));


// *************************************************************************************************** //


/*
 * Call Function and Language
 */

	require_once(WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/functions.php");



// *************************************************************************************************** //

/*
 * RB Agency casting Class
 */


class RBAgencyCasting {

	/*
	 * Initialization
	 */

		public static function init(){

			/*
			 * Internationalization
			 */

				// Identify Folder for PO files
				load_plugin_textdomain( rb_agency_casting_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/translation/' ); 


			/*
			 * Admin Related
			 */
			if ( is_admin() ){

				// TODO:


				// Load Menus
				add_action('admin_menu', array('RBAgencyCasting', 'menu_admin'));

				// Register Settings
				add_action('admin_init', array('RBAgencyCasting', 'do_register_settings') );
			}

		}


	/*
	 * Plugin Activation
	 * Run when the plugin is installed.
	 */

		public static function activation(){

			// Required for all WordPress database manipulations
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			/*
			 * Check Permissions
			 */

				// Does the user have permission to activate the plugin
				if ( !current_user_can('activate_plugins') )
					return;
				// Check Admin Referer
				$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
				check_admin_referer( "activate-plugin_{$plugin}" );

			/*
			 * Initialize Options
			 */

				// Update the options in the database
				if(!get_option("rb_agency_casting_options")) {

					// Set Default Options
					$rb_agency_casting_options_arr = array(
						"rb_agency_casting_option_registerapproval" => 1,
						"rb_agency_casting_option_registerallow" => 1
						);
					// Add Options
					update_option("rb_agency_casting_options",$rb_agency_casting_options_arr);
				}

			/*
			 * Install Schema
			 */
			// Setup > Add to Casting Cart
				$sql = "CREATE TABLE IF NOT EXISTS ". table_agency_castingcart." (
					CastingCartID BIGINT(20) NOT NULL AUTO_INCREMENT,
					CastingCartProfileID VARCHAR(255),
					CastingCartTalentID VARCHAR(255),
					PRIMARY KEY (CastingCartID)
					);";
				dbDelta($sql);

				/*
				 * Casting 
				 */
				$sql = "CREATE TABLE IF NOT EXISTS " . table_agency_casting . " (
					CastingID BIGINT(20) NOT NULL AUTO_INCREMENT,
					CastingUserLinked BIGINT(20) NOT NULL DEFAULT '0',
					CastingGallery VARCHAR(255),
					CastingContactDisplay VARCHAR(255),
					CastingContactNameFirst VARCHAR(255),
					CastingContactNameLast VARCHAR(255),
					CastingLocationStreet VARCHAR(255),
					CastingLocationCity VARCHAR(255),
					CastingLocationState VARCHAR(255),
					CastingLocationZip VARCHAR(255),
					CastingLocationCountry VARCHAR(255),
					CastingContactEmail VARCHAR(255),
					CastingContactCompany VARCHAR(255),
					CastingContactWebsite VARCHAR(255),
					CastingContactPhoneHome VARCHAR(255),
					CastingContactPhoneCell VARCHAR(255),
					CastingContactPhoneWork VARCHAR(255),
					CastingContactLinkTwitter VARCHAR(255),
					CastingContactLinkFacebook VARCHAR(255),
					CastingContactLinkYoutube VARCHAR(255),
					CastingContactLinkFlickr VARCHAR(255),
					CastingDateCreated TIMESTAMP DEFAULT NOW(),
					CastingDateUpdated TIMESTAMP,
					CastingDateViewLast TIMESTAMP,
					CastingType VARCHAR(255),
					CastingIsActive INT(10) NOT NULL DEFAULT '0',
					CastingStatHits INT(10) NOT NULL DEFAULT '0',
					PRIMARY KEY (CastingID)
					);";
				dbDelta($sql);

			/*
			 * Casting Job
			 */
				$sql = "CREATE TABLE IF NOT EXISTS " . table_agency_casting_job . " (
					Job_ID BIGINT(20) NOT NULL AUTO_INCREMENT,
					Job_Title VARCHAR(255),
					Job_Text TEXT,
					Job_Date_Start VARCHAR(255),
					Job_Date_End VARCHAR(255),
					Job_Location VARCHAR(255),
					Job_Region VARCHAR(255),
					Job_Offering VARCHAR(255),
					Job_Visibility VARCHAR(255),
					Job_Criteria VARCHAR(255),
					Job_Type VARCHAR(255),
					PRIMARY KEY (Job_ID)
					);";
				dbDelta($sql);

			/*
			 * Casting Job Type
			 */
				$sql = "CREATE TABLE IF NOT EXISTS " . table_agency_casting_job_type . " (
					Job_Type_ID BIGINT(20) NOT NULL AUTO_INCREMENT,
					Job_Title_Title VARCHAR(255),
					Job_Type_Text TEXT,
					PRIMARY KEY (Job_Type_ID)
					);";
				dbDelta($sql);
				


		}


	/*
	 * Plugin Deactivation
	 * Cleanup when complete
	 */

		public static function deactivation(){

			// TODO: Enhance
		}


	/*
	 * Plugin Uninstall
	 * Cleanup when complete
	 */

		public static function uninstall(){

			// Does user have permission?
			if ( ! current_user_can( 'activate_plugins' ) )
				return;
			check_admin_referer( 'bulk-plugins' );

			// Important: Check if the file is the one that was registered during the uninstall hook.
			if ( __FILE__ != WP_UNINSTALL_PLUGIN )
				return;

			// Permission Granted... Remove
			global $wpdb; // Required for all WordPress database manipulations

			// Drop the tables
			$wpdb->query("DROP TABLE " . table_agency_castingcart);
			//$wpdb->query("DROP TABLE " . table_agency_casting_temp);

			// Delete Saved Settings
			delete_option('rb_agency_casting_options');

			$thepluginfile = "rb-agency-casting/rb-agency-casting.php";
			$current = get_settings('active_plugins');
			array_splice($current, array_search( $thepluginfile, $current), 1 );
			update_option('active_plugins', $current);
			do_action('deactivate_' . $thepluginfile );

			echo "<div style=\"padding:50px;font-weight:bold;\"><p>". __("Almost done...", rb_agency_casting_TEXTDOMAIN) ."</p><h1>". __("One More Step", rb_agency_casting_TEXTDOMAIN) ."</h1><a href=\"plugins.php?deactivate=true\">". __("Please click here to complete the uninstallation process", rb_agency_casting_TEXTDOMAIN) ."</a></h1></div>";
			die;

		}


	/*
	 * Update Needed
	 * Is this an updated version of the software and needs database upgrade?
	 */

		public static function check_update_needed(){

			// Hold the version in a seprate option
			if(!get_option("rb_agency_casting_version")) {
				update_option("rb_agency_casting_version", rb_agency_casting_VERSION);
			} else {
				// Version Exists, but is it out of date?
				if(get_option("rb_agency_casting_version") <> rb_agency_casting_VERSION){
					//require_once(WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/upgrade.php");
				} else {
					// Namaste, version is number is correct
				}
			}
		}


	/*
	 * Administrative Menu
	 * Create the admin menu items
	 */

		//Create Admin Menu
		public static function menu_admin(){
			// TODO: Menu Item for Admin
			//add_submenu_page('users.php', __('Approve Casting Agents', rb_agency_casting_TEXTDOMAIN), __('Approve Agents', rb_agency_casting_TEXTDOMAIN), 'edit_users', basename(__FILE__), 'rb_agency_casting_approveusers');
			
			// saved search for casting
			add_submenu_page("rb_agency_menu", __("Saved Searches", rb_agency_casting_TEXTDOMAIN), __("Saved Searches", rb_agency_casting_TEXTDOMAIN), 7,"rb_agency_searchsaved", array('RBAgencyCasting', 'menu_searchsaved'));
		
		}

		public static function menu_searchsaved(){
			include_once('view/admin-searchsaved.php');
		}

		//Pages
		public static function rb_agency_casting_settings(){
			// TODO add file
			//include_once('admin/settings.php');
		}
		public static function rb_agency_casting_approveusers(){
			//TODO change to casting-approve.php
			//include_once('admin/profile-approve.php');
		}

	/*
	 * Register Settings
	 * Register Settings group
	 */

		public static function do_register_settings() {
			register_setting('rb-agencycasting-settings-group', 'rb_agency_casting_options'); //, 'rb_agency_casting_options_validate'
		}

}


// *************************************************************************************************** //

/*
 * Plugin Actions
 */

	// Activate Plugin
	register_activation_hook(__FILE__, array('RBAgencyCasting', 'activation'));

	// Deactivate Plugin
	register_deactivation_hook(__FILE__, array('RBAgencyCasting', 'deactivation'));

	// Uninstall Plugin
	register_uninstall_hook(__FILE__, array('RBAgencyCasting', 'uninstall'));

// *************************************************************************************************** //






// *************************************************************************************************** //
// Add Widgets

	/*
	 * Login / Actions Widget
	 */

		add_action('widgets_init', create_function('', 'return register_widget("rb_agency_casting_widget_loginactions");'));

		class rb_agency_casting_widget_loginactions extends WP_Widget {

			// Setup
			function rb_agency_casting_widget_loginactions() {
				$widget_ops = array('classname' => 'rb_agency_casting_widget_profileaction', 'description' => __("Displays profile actions such as login and links to edit", rb_agency_casting_TEXTDOMAIN) );
				$this->WP_Widget('rb_agency_casting_widget_profileaction', __("Agency casting Login", rb_agency_casting_TEXTDOMAIN), $widget_ops);
			}

			// What Displays
			function widget($args, $instance) {
				extract($args, EXTR_SKIP);
				echo $before_widget;
				$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
				$count = $instance['trendShowCount'];
				$atts = array('count' => $count);

				if(!is_user_logged_in()){

					if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
							echo "<form name=\"loginform\" id=\"login\" action=\"". network_site_url("/") ."profile-login/\" method=\"post\">\n";
							echo "	<div class=\"box\">\n";
							echo "		<label for=\"user-name\">". __("Username", rb_agency_casting_TEXTDOMAIN). "</label><input type=\"text\" name=\"user-name\" value=\"". wp_specialchars( $_POST['user-name'], 1 ) ."\" id=\"user-name\" />\n";
							echo "	</div>\n";
							echo "	<div class=\"box\">\n";
							echo "		<label for=\"password\">". __("Password", rb_agency_casting_TEXTDOMAIN). "</label><input type=\"password\" name=\"password\" value=\"\" id=\"password\" /> <a href=\"". get_bloginfo('wpurl') ."/wp-login.php?action=lostpassword\">". __("forgot password", rb_agency_casting_TEXTDOMAIN). "?</a>\n";
							echo "	</div>\n";
							echo "	<div class=\"box\">\n";
							echo "		<input type=\"checkbox\" name=\"remember-me\" value=\"forever\" /> ". __("Keep me signed in", rb_agency_casting_TEXTDOMAIN). "\n";
							echo "	</div>\n";
							echo "	<div class=\"submit-box\">\n";
							echo "		<input type=\"hidden\" name=\"action\" value=\"log-in\" />\n";
							echo "		<input type=\"submit\" value=\"". __("Sign In", rb_agency_casting_TEXTDOMAIN). "\" /><br />\n";
							echo "	</div>\n";
							echo "</form>\n";
					} else {
						if(current_user_can('level_10')){
							if ( !empty( $title ) ) { echo $before_title . "RB Agency Settings" . $after_title; };
							echo "<ul>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu")."\">Overview</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu_profiles")."\">Manage Profiles</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_casting_menu_approvemembers")."\">Approve Profiles</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu_search")."\">Search Profiles</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu_searchsaved")."\">Saved Searches</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu_reports")."\">Tools &amp; Reports</a></li>";
							echo "	<li><a href=\"".admin_url("admin.php?page=rb_agency_menu_settings")."\">Settings</a></li>";
							echo "	<li><a href=\"/wp-login.php?action=logout&_wpnonce=3bb3c87a3d\">Logout</a></li>";
							echo "</ul>";
						} else{
							rb_agency_profilesearch(array("profilesearch_layout" =>"simple"));
						}
					}

				echo $after_widget;
			}

			// Update
			function update($new_instance, $old_instance) {
				$instance = $old_instance;
				$instance['title'] = strip_tags($new_instance['title']);
				$instance['trendShowCount'] = strip_tags($new_instance['trendShowCount']);
				return $instance;
			}

			// Form
			function form($instance) {
				$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
				$title = esc_attr($instance['title']);
				$trendShowCount = esc_attr($instance['trendShowCount']);
				?>
					<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
					<p><label for="<?php echo $this->get_field_id('trendShowCount'); ?>"><?php _e('Show Count:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('trendShowCount'); ?>" name="<?php echo $this->get_field_name('trendShowCount'); ?>" type="text" value="<?php echo $trendShowCount; ?>" /></label></p>
				<?php
			}

		} // class



// *************************************************************************************************** //
// Add Short Codes

	/*
	 * Registration Shortcode
	 */

		add_shortcode("agency_register","rb_agency_casting_shortcode_agencyregister");
			function rb_agency_casting_shortcode_agencyregister($atts, $content = null){
				ob_start();
				wp_register_form($atts);
				$output_string=ob_get_contents();
				ob_end_clean();
				return $output_string;
			}

?>