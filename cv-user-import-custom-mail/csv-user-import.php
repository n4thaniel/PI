<?php

/*
Plugin Name: CSV User Import with custom email message
Plugin URI: http://luibh.ie/
Description: Allows the importation of users via an uploaded CSV file. Custom message for new users 
Author: N4thaniel (inspired from Andy Dunn import csv plugin)
Version: 1.0
Author URI: 
*/

// always find line endings
ini_set('auto_detect_line_endings', true);

// add admin menu
add_action('admin_menu', 'csvuserimport_menu');

function csvuserimport_menu() {	
	add_submenu_page( 'users.php', 'CSV User Import', 'Import', 'manage_options', 'csv-user-import', 'csvuserimport_page1');	
}
function generatethePassword($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);

    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }

    return $result;
}
// show import form
function csvuserimport_page1() {
			wp_enqueue_script('leaflet',plugins_url('/script.js', __FILE__),false,false,false);
	global $wpdb;
	
	// default messages for email
	$role = $_REQUEST['role'];
	
	

	$site_name = get_bloginfo('sitename');
	$site_url = get_bloginfo('url');
	$blog_id = get_current_blog_id();
	
// titre pour le mail // default
	$mail_title = get_option('user_custom_mail_title');
	if(empty($mail_title)){$mail_title = "Your credentials for ".$site_name;}else{
			$mail_title = stripslashes(get_option('user_custom_mail_title'));
	}
	$custom_message = stripslashes(get_option('user_custom_message'));

	


  	if (!current_user_can('manage_options')) {
    	wp_die( __('You do not have sufficient permissions to access this page.') );
  	}

	// if the form is submitted
	if ($_POST['mode'] == "submit") {
		$send_email = $_REQUEST["sends_email"];
		
		
		
		///// get email data
		
				if($send_email=="yes"){
					
					
				$custom_message = stripslashes($_REQUEST["custom_message"]);
				$mail_title = stripslashes($_REQUEST["mail_title"]);
			//echo $custom_message;
				update_option( 'user_custom_message', $custom_message );
				update_option( 'user_custom_mail_title', $mail_title );

					
				}
		
		
		
		///
		
		
		$arr_rows = file($_FILES['csv_file']['tmp_name']);

		// loop around
		if (is_array($arr_rows)) {
			foreach ($arr_rows as $row) {

				// split into values
				$arr_values = split(",", $row);

				// firstname, lastname, username, password
				$firstname 		= $arr_values[0];
				$lastname 		= $arr_values[1];
				$username 		= trim($arr_values[2]);
				$password 		= trim($arr_values[3]);
				$user_email 	= trim($arr_values[4]);				
				if (!$user_email) { $username."@donotreply.com"; }
				$user_nicename	= sanitize_title($username);

				// add the new user
				$arr_user = array( 	'user_login' => $username,
									'user_nicename' => $user_nicename,
									'user_email' => $user_email,
									'user_registered' => date( 'Y-m-d H:i:s' ),
									'user_status' => "0",
									'display_name' => $username,
													
							 		);
									if( !email_exists( $user_email ) or !username_exists( $username )) {
				$wpdb->insert( $wpdb->users, $arr_user );				
				$user_id = $wpdb->insert_id;		
				$password = generatethePassword($length = 6);
				wp_set_password($password, $user_id);
				
				if($send_email=="yes"){
		$message = stripslashes(get_option('user_custom_message'));
		$mail_title = stripslashes(get_option('user_custom_mail_title'));
		
		$message = str_replace("**usernicename**", $user_nicename, $message);
		$message = str_replace("**siteurl**", $site_url, $message);
		$message = str_replace("**login**", $username, $message);
		$message = str_replace("**password**", $password, $message);
		$message = nl2br($message);
	 $headers  = 'MIME-Version: 1.0' . "\r\n";
     $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	// $headers .= 'From: Anniversaire <anniversaire@example.com>' . "\r\n";
		wp_mail($user_email, $mail_title, $message,$headers);
		echo "Sending email to :".$user_nicename." - Login : ".$username." and password :".$password."<br>";			
				}
			
			
			
			$error = $user_email." has not been added - duplicate entry <br>";	
			$error_message = $error_message.$error;
			if($role =="subscriber"){
				// add default meta values
				$arr_meta_values = array(
									'nickname' => $username,
									'rich_editing' => "true",
									'comment_shortcuts' => "false",
									'admin_color' => "fresh",
									$wpdb->prefix . 'capabilities' => 'a:1:{s:10:"subscriber";b:1;}',
									'first_name' => $firstname,
									'last_name' => $lastname,
									'default_password_nag' => "1"
									);
									
			}
			if($role =="editor"){
				// add default meta values
				$arr_meta_values = array(
									'nickname' => $username,
									'rich_editing' => "true",
									'comment_shortcuts' => "false",
									'admin_color' => "fresh",
									$wpdb->prefix . 'capabilities' => 'a:1:{s:11:"editor";b:1;}',
									'first_name' => $firstname,
									'last_name' => $lastname,
									'default_password_nag' => "1"
									);
									
			}
						if($role =="author"){
				// add default meta values
				$arr_meta_values = array(
									'nickname' => $username,
									'rich_editing' => "true",
									'comment_shortcuts' => "false",
									'admin_color' => "fresh",
									$wpdb->prefix . 'capabilities' => 'a:1:{s:12:"author";b:1;}',
									'first_name' => $firstname,
									'last_name' => $lastname,
									'default_password_nag' => "1"
									);
									
			}			
						if($role =="administrator"){
				// add default meta values
				$arr_meta_values = array(
									'nickname' => $username,
									'rich_editing' => "true",
									'comment_shortcuts' => "false",
									'admin_color' => "fresh",
									$wpdb->prefix . 'capabilities' => 'a:1:{s:13:"administrator";b:1;}',
									'first_name' => $firstname,
									'last_name' => $lastname,
									'default_password_nag' => "1"
									);
									
			}

				foreach ($arr_meta_values as $key => $value) {
					$arr_meta = array(	'user_id' => $user_id,
										'meta_key' => $key,
										'meta_value' => $value
								 	);
					$wpdb->insert( $wpdb->usermeta, $arr_meta );
				}
				} // end user_exists
				$existing_user_id =  email_exists( $user_email );
				
				add_user_to_blog($blog_id, $existing_user_id, $role );
				
			}	// end of 'for each around arr_rows'

			$html_update = "<div class='updated'>All users appear to be have been imported successfully.</div>";
			
		} // end of 'if arr_rows is array'
		else {
			$html_update = "<div class='updated' style='color: red'>It seems the file was not uploaded correctly.</div>";		
				
		}
	} 	// end of 'if mode is submit'

?>
<div class="wrap">
	<?php echo $html_update; echo $error; ?>
	<div id="icon-users" class="icon32">
		<br />
	</div>
	<h2>CSV User Import</h2>
	<p>
		Please select the CSV file you want to import below.
	</p>

	<form action="users.php?page=csv-user-import" method="post" enctype="multipart/form-data">
	<p><input type="file" name="csv_file" /></p>
		<p>What role should be assigned to imported users? <select name = "role"><?wp_dropdown_roles('subscriber')?></select></p>
	<p>Do you wish to send credentials to news users? <input type="checkbox" name="sends_email" value = "yes" onclick="showMe('email_div')"></p>

		<input type="hidden" name="mode" value="submit">
		<div id="email_div" style="display:none">
			<h2>Use the form below to modify (or not) the message send to new users</h2>
		<? echo '	<p>Mail subject : <input name="mail_title" size="100" value="' . $mail_title . '" id="title" autocomplete="off" type="text"></p>'; ?>
		<?	the_editor($custom_message, 'custom_message'); ?>
		**login** = user login - **password** = user password - **siteurl** = current site's address
	</div>
		<p>
			<input name="submit" id="submit" class="button button-primary" value="Import" type="submit">
	</form>
	</p>
	<p>
		The CSV file should be in the following format:
	</p>

	<table>
		<tr>
			<td>firstname,</td>
			<td>lastname,</td>
			<td>username,</td>
			<td>password (plain text),</td>
			<td>email address</td>
		</tr>
	</table>
	<p style="color: red">
		Please make sure you back up your database before proceeding!
	</p>
</div>
<?php
}	// end of 'function csvuserimport_page1()'
?>
