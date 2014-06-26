<?php
include(rb_agency_BASEREL ."app/profile.class.php");
include(dirname(dirname(__FILE__)) ."/app/casting.class.php");

wp_deregister_script('jquery'); 
wp_register_script('jquery_latest', 'http://code.jquery.com/jquery-1.11.0.min.js',false,1,true); 
wp_enqueue_script('jquery_latest');
wp_enqueue_script( 'jqueryui',  'http://code.jquery.com/ui/1.10.4/jquery-ui.js',false,1,true); 
wp_register_script('jquery-timepicker',  plugins_url('../js/jquery-timepicker.js', __FILE__),false,1,true); 
wp_enqueue_script('jquery-timepicker');
wp_register_style( 'timepicker-style', plugins_url('../css/timepicker-addon.css', __FILE__) );
wp_enqueue_style( 'timepicker-style' );


echo $rb_header = RBAgency_Common::rb_header(); 

//===============================
// if sumitted process here	
//===============================

if(isset($_GET['save_job'])){
	
		// Error checking
		$error = "";
		$have_error = false;
		$date_confirm = 0;		
		
		if ( empty($_GET['Job_Title'])) {
			$error .= __("Job Title is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}

		if ( empty($_GET['Job_Text'])) {
			$error .= __("Job Description is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}

		if ( empty($_GET['Job_Offering'])) {
			$error .= __("Job Offer is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}

		if ( empty($_GET['Job_Date_Start'])) {
			$error .= __("Start Date is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
			$date_confirm++;
		} else {
			list($y,$m,$d)= explode('-',$_GET['Job_Date_Start']);
			if(checkdate($m,$d,$y)!==true){
				$error .= __("Start Date is invalid date.<br />", rb_agency_casting_TEXTDOMAIN);
				$have_error = true;
				$date_confirm++;
			}
		}

		if ( empty($_GET['Job_Date_End'])) {
			$error .= __("End Date is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
			$date_confirm++;
		} else {
			list($y,$m,$d)= explode('-',$_GET['Job_Date_End']);
			if(checkdate($m,$d,$y)!==true){
				$error .= __("End Date is invalid date.<br />", rb_agency_casting_TEXTDOMAIN);
				$have_error = true;
				$date_confirm++;
			}
		}


		if($date_confirm == 0){
			$date_start = strtotime($_GET['Job_Date_Start']);
			$date_end = strtotime($_GET['Job_Date_End']);
			if($date_start > $date_end){
				$error .= __("Start Date cannot be greate than the End Date.<br />", rb_agency_casting_TEXTDOMAIN);
				$have_error = true;
			} 
		}
	
		if ( empty($_GET['Job_Location'])) {
			$error .= __("Job Location is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_GET['Job_Region'])) {
			$error .= __("Job Region is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}
		if ( empty($_GET['Job_Type'])) {
			$error .= __("Job type is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}
		if ( $_GET['Job_Visibility'] == "") {
			$error .= __("Visibility is required.<br />", rb_agency_casting_TEXTDOMAIN);
			$have_error = true;
		}

		if(!$have_error){
			
			$sql_Insert = "INSERT INTO " . table_agency_casting_job ;
			
			$into = array();
			$calues = array();
			$criteria = array();
			
			//get string values
			foreach($_GET as $key => $val){
				if($key != "save_job"){
					if (strpos($key, "ProfileCustomID") > -1){
						if($val != "" && !empty($val)){ 
							if(is_array($val)){
								$n = "";
								foreach($val as $x){
									$n .= "-" . $x; 
								}
								$n = trim($n,"-");
							} else {
								$n = trim($val);
							}
							
							if($n != ""){
								$criteria[] = substr($key,15) . "/" . $n ;  			
							}
						}
					} else {
						//Normal String
						$into[] = $key;
						$values[] = "'". trim($val) . "'";
					} 
				}
			}	
			$job_talents_hash = RBAgency_Common::generate_random_string(10,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
			$sql_Insert .=  " ( " . implode(",",$into) . ", Job_Criteria, Job_Talents_Hash, Job_Date_Created) VALUES ( " . implode(",",$values) . ",'".implode("|",$criteria)."' ,'".$job_talents_hash."',Now())";
		
			$wpdb->query($sql_Insert) or die(mysql_error());
			
			echo "	<div id=\"primary\" class=\"".fullwidth_class()." column\">\n";
			echo "  	<div id=\"content\" role=\"main\" class=\"transparent\">\n";
			echo '			<div class="entry-content">';	
			echo "			<div class=\"cb\"></div>\n";
			echo '			<header class="entry-header">';
			echo '				<h4 class="entry-title">You have successfully added your new Job Posting! <a href="'.get_bloginfo('wpurl').'/casting-postjob">Add new Job Posting?</a></h4>';
			echo '				<p style="width:100%;"><a href="'.get_bloginfo('wpurl').'/casting-dashboard">Go Back to Casting Dashboard.</a></p>';
			echo '			</header>';
			echo "			<div class=\"cb\"></div>\n";
			echo "			</div><!-- .entry-content -->\n"; // .entry-content
			echo "			<input type=\"hidden\" name=\"favorite\" value=\"1\"/>";
			echo "  	</div><!-- #content -->\n"; // #content
			echo "	</div><!-- #primary -->\n"; // #primary
		
		} else {
		
			load_job_display($error);	
		
		}
	
} else {
		
	load_job_display();	

}
echo $rb_footer = RBAgency_Common::rb_footer(); 

function load_job_display($error = NULL){

	global $wpdb;
	global $current_user;
	
	echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">';
	echo '<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery( ".datepicker" ).datepicker();
					jQuery( ".datepicker" ).datepicker("option", "dateFormat", "yy-mm-dd");
					jQuery("#Job_Visibility").change(function(){
						if(jQuery(this).val() == 2){
							jQuery("#criteria").html("Loading Criteria List");
							jQuery.ajax({
									type: "POST",
									url: "'. admin_url('admin-ajax.php') .'",
									data: {
										action: "load_criteria_fields"
									},
									success: function (results) {
										jQuery("#criteria").html(results);
									},
									error: function (err){
										console.log(err);
									}
							});
						} else {
							jQuery("#criteria").html("");
						}
					});
					jQuery(".timepicker").timepicker({
						hourGrid: 4,
						minuteGrid: 10,
						timeFormat: "hh:mm tt"
					});
				});
		  </script>';

	if (is_user_logged_in()) {
	//if(RBAgency_Casting::rb_is_user_casting()){

		echo "	<div id=\"primary\" class=\"".fullwidth_class()." column\">\n";
		echo "  	<div id=\"content\" role=\"main\" class=\"transparent\">\n";
		echo '			<header class="entry-header">';
		echo '				<h1 class="entry-title">New Job Posting</h1>';
		echo '			</header>';
		
		if(isset($error) && $error != ""){
			echo '			<p>'.$error.'</p>';
		}
		
		echo '			<div class="entry-content">';
		
		//===============================
		//	table form
		//===============================
		echo " <form method='get' actipn='".(isset($_SERVER['PHP_SELF'])?$_SERVER['PHP_SELF']:"")."'>
					<table>
						
						<tr>
							<td><h3>Job Description</h3></td>
							<td></td>
						</tr>
						<tr>
							<td>Title:</td>
							<td><input type='text' name='Job_Title' value='".(isset($_GET['Job_Title'])?$_GET['Job_Title']:"")."'></td>
						</tr>
						<tr>
							<td>Description:</td>
							<td><textarea name='Job_Text'>".(isset($_GET['Job_Text'])?$_GET['Job_Text']:"")."</textarea></td>
						</tr>	
						<tr>
							<td>Offer:</td>
							<td><input type='text' name='Job_Offering' value='".(isset($_GET['Job_Offering'])?$_GET['Job_Offering']:"")."'></td>
						</tr>							
						<tr>
							<td><h3>Job Duration</h3></td><td></td>
						</tr>
						<tr>
							<td>Date Start:</td>
							<td>
								<input type='text' name='Job_Date_Start' class='datepicker' value='".(isset($_GET['Job_Date_Start'])?$_GET['Job_Date_Start']:"")."'>
							</td>
						</tr>
						<tr>
							<td>Date End:</td>
							<td>
								<input type='text' name='Job_Date_End' class='datepicker' value='".(isset($_GET['Job_Date_End'])?$_GET['Job_Date_End']:"")."'>
							</td>
						</tr>
						<tr>
							<td><h3>Job Location</h3></td><td></td>
						</tr>
						<tr>
							<td>Location:</td>
							<td><input type='text' name='Job_Location' value='".(isset($_GET['Job_Location'])?$_GET['Job_Location']:"")."'></td>
						</tr>
						<tr>
							<td>Region:</td>
							<td><input type='text' name='Job_Region' value='".(isset($_GET['Job_Region'])?$_GET['Job_Region']:"")."'></td>
						</tr>
						<tr>
							<td><h3>Job Audition</h3></td><td></td>
						</tr>
						<tr>
							<td>Date Start:</td>
							<td>
								<input type='text' name='Job_Audition_Date_Start' class='datepicker' value='".(isset($_GET['Job_Audition_Date_Start'])?$_GET['Job_Audition_Date_Start']:"")."'>
							</td>
						</tr>
						<tr>
							<td>Date End:</td>
							<td>
								<input type='text' name='Job_Audition_Date_End' class='datepicker' value='".(isset($_GET['Job_Audition_Date_End'])?$_GET['Job_Audition_Date_End']:"")."'>
							</td>
						</tr>
						<tr>
							<td>Time:</td>
							<td>
								<input type='text' name='Job_Audition_Time' class='timepicker' value='".(isset($_GET['Job_Audition_Time'])?$_GET['Job_Audition_Time']:"")."'>
							</td>
						</tr>
						<tr>
						<td>Venue:</td>
							<td>
								<textarea name='Job_Audition_Venue'>".(isset($_GET['Job_Audition_Venue'])?$_GET['Job_Audition_Venue']:"")."</textarea>
							</td>
						</tr>
						<tr>
							<td><h3>Job Criteria</h3></td><td></td>
						</tr>
						<tr>
							<td>Type:</td>
							<td>
								<select id='Job_Type' name='Job_Type'>
									<option value=''>-- Select Type --</option>";

									$get_job_type = $wpdb->get_results("SELECT * FROM " . table_agency_casting_job_type); // or die(mysql_error()
									if(count($get_job_type)){
										foreach($get_job_type as $jtype){
											echo "<option value='".$jtype->Job_Type_ID."' ".selected($jtype->Job_Type_ID,isset($_GET['Job_Type'])?$_GET['Job_Type']:"",false).">".$jtype->Job_Type_Title."</option>";
										}
									}

		 				echo "	</select>
							</td>
						</tr>
						<tr>
							<td>Visibility:</td>
							<td>
								<select id='Job_Visibility' name='Job_Visibility'>
									<option value=''>-- Select Type --</option>
									<option value='0' ".selected(isset($_GET['Job_Visibility'])?$_GET['Job_Visibility']:"","0",false).">Invite Only</option>
									<option value='1' ".selected(isset($_GET['Job_Visibility'])?$_GET['Job_Visibility']:"","1",false).">Open to All</option>
									<option value='2' ".selected(isset($_GET['Job_Visibility'])?$_GET['Job_Visibility']:"","2",false).">Matching Criteria</option>
								</select>
							</td>
						</tr>
						<tr>
							<td></td>
							<td id='criteria'></td>
						</tr>	
						<tr>
							<td></td>
							<td><input type='submit' name='save_job' value='Submit Job'></td>
						</tr>		
						<tr>
							<td></td>
							<td>
								<p style=\"width:100%;\"><a href='".get_bloginfo('wpurl')."/casting-dashboard'>Go Back to Casting Dashboard.</a></p>
							</td>
						</tr>		
					</table>
					<input type=\"hidden\" name=\"Job_UserLinked\" value=\"".$current_user->ID."\"/>
				</form>";
		echo "			<div class=\"cb\"></div>\n";
		echo "			</div><!-- .entry-content -->\n"; // .entry-content
		echo "  	</div><!-- #content -->\n"; // #content
		echo "	</div><!-- #primary -->\n"; // #primary

	} else {

		echo "	<div id=\"primary\" class=\"".fullwidth_class()." column\">\n";
		echo "  	<div id=\"content\" role=\"main\" class=\"transparent\">\n";
		echo '			<header class="entry-header">';
		echo '				<h1 class="entry-title">You are not permitted to access this page.</h1>';
		echo '			</header>';
		if(!is_user_logged_in()){
			require_once("include-login.php");
		}
		echo "  	</div><!-- #content -->\n"; // #content
		echo "	</div><!-- #primary -->\n"; // #primary
	
	}
}

?>
