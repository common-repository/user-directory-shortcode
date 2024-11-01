<?php 
/*
 * Plugin Name: User Directory Shortcode
 * Version: 0.3
 * Plugin URI: 
 * Description: Displays profile information about a site's users in table form.  Go to Users - User Directory to choose what to include in the directory, and use the shortcode [mackerel-user-directory directory="x"] to generate the table.
 * Author: Ed Goode
 * Author URI: http://mackerelsky.co.nz/
 */
 //database table version
 global $mackereluserdirectory_db_version;
 $mackereluserdirectory_db_version = "0.2";
 //call database table creation
 register_activation_hook(__FILE__,'mackereluserdirectory_install');
 //create new table for directories
 function mackereluserdirectory_install () {
	global $wpdb;
	global $mackereluserdirectory_db_version;
	$table_name = $wpdb->prefix . "mackereluserdirectory"; 
	
	$sql = "CREATE TABLE $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	name text NOT NULL,
	role text NOT NULL,
	userfields text,
	usermetafields text,
	UNIQUE KEY id (id)
	);";
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);   
   add_option("mackereluserdirectory_db_version", "0.2");
} 
//create new menu for building directory
add_action('admin_menu', 'mackereluserdirectory_menu'); 
function mackereluserdirectory_menu() {	
	add_submenu_page( 'users.php', 'User Directory', 'User Directory', 'manage_options', 'mackerel-user-directory', 'mackereluserdirectory_display');	
}
//function for new menu
function mackereluserdirectory_display() {
	global $wpdb;
	$table_name = $wpdb->prefix . "mackereluserdirectory"; 
	//must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
	//save the selected settings to the new database table
	if ($_POST['save']) {
		$userkeystring='';
		$metakeystring='';
		$name_to_save = $_POST['directoryname'];
		$role_to_save = $_POST['userroles'];
		$user_keys_to_save = $_POST['userfields'];
		$meta_keys_to_save = $_POST['metafields'];
				//turn arrays into strings
		foreach ($user_keys_to_save as $userkey) {
			$userkeystring .= $userkey.",";
		}
		foreach ($meta_keys_to_save as $metakey) {
			$metakeystring .= $metakey.",";
		}
		$userkeystring = substr($userkeystring, 0, -1);
		$metakeystring = substr($metakeystring, 0, -1);			
		$rows_affected = $wpdb->insert($table_name, array('name' => $name_to_save, 'role' => $role_to_save, 'userfields' => $userkeystring, 'usermetafields' => $metakeystring ));
		//display success message
		echo "Directory saved.";
	}
	//form to bring up to add a new directory
	if ($_POST['new']) {
	?>
			<form action="users.php?page=mackerel-user-directory" method="post" enctype="multipart/form-data">
		<h3>Directory Name</h3>
		Directory name: <input type="text" name="directoryname">
		
		<h3>User roles to display</h3>
		<select name="userroles" id="userroles">
		<option value="0">All users</option>
		<?php wp_dropdown_roles() ?>
		</select>	
		
		<h3>Fields to display</h3>
		<p>Username: <input type='checkbox' name='userfields[]' value=user_login /></p>
		<p>Email Address: <input type='checkbox' name='userfields[]' value=user_email /></p>
		<?php
			//one checkbox for each usermeta field
			$meta_fields = $wpdb->get_results("SELECT DISTINCT  `meta_key` FROM  $wpdb->usermeta ");
			foreach($meta_fields as $meta_field)
			{
				$field=$meta_field->meta_key;
				$display_field = str_replace("_", " ", $field);
				echo "<p> $display_field: <input type='checkbox' name='metafields[]' value=$field /></p>";
			}
		?>
		<input type="submit" name="save" value="Save" />
	</form>
	<?php
	}
	//form to bring up if user selects to load an existing directory
	if ($_POST['load']) {
		$dir_id = $_POST['directories'];
		$dir_id = substr($dir_id, 0, -2);
		$directory_to_load = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $dir_id");
		$userfields = $directory_to_load->userfields;
		$metafields = $directory_to_load->usermetafields;
		echo "<p>".$directory_to_load->name." loaded.</p>";
		?>
		<form action="users.php?page=mackerel-user-directory" method="post" enctype="multipart/form-data">
		<h3>Directory Name</h3>
		Directory name: <input type="text" name="directoryname" value="<?php echo $directory_to_load->name; ?>">
		
		<h3>User roles to display</h3>
		<select name="userroles" id="userroles">
		<option value="0">All users</option>
		<?php wp_dropdown_roles() ?>
		</select>	
		
		<h3>Fields to display</h3>
		<p>Username: <input type='checkbox' <?php if(strpos($userfields,'user_login') !== false){echo "checked='checked'";}?>name='userfields[]' value=user_login /></p>
		<p>Email Address: <input type='checkbox' <?php if(strpos($userfields,'user_email') !== false){echo "checked='checked'";}?>name='userfields[]' value=user_email /></p>
		<?php
			//one checkbox for each usermeta field
			$meta_fields = $wpdb->get_results("SELECT DISTINCT  `meta_key` FROM  $wpdb->usermeta ");
			foreach($meta_fields as $meta_field)
			{
				$field=$meta_field->meta_key;
				$display_field = str_replace("_", " ", $field);
				echo "<p> $display_field: <input type='checkbox'";
				if(strpos($metafields,$field) !== false){echo "checked='checked'";}
				echo"name='metafields[]' value=$field /></p>";
			}
		?>
		<input type="hidden" name="directoryid" value="<?php echo $dir_id; ?>" />
		<input type="submit" name="update" value="Save" />
	</form>
	<?php
	}	
	//update an existing directory
	if ($_POST['update']) {
		$userkeystring='';
		$metakeystring='';
		$dir_to_update = $_POST['directoryid'];
		$name_to_save = $_POST['directoryname'];
		$role_to_save = $_POST['userroles'];
		$user_keys_to_save = $_POST['userfields'];
		$meta_keys_to_save = $_POST['metafields'];
				//turn arrays into strings
		foreach ($user_keys_to_save as $userkey) {
			$userkeystring .= $userkey.",";
		}		
		foreach ($meta_keys_to_save as $metakey) {
			$metakeystring .= $metakey.",";
		}
		$userkeystring = substr($userkeystring, 0, -1);
		$metakeystring = substr($metakeystring, 0, -1);
			
		$rows_affected = $wpdb->update($table_name, array('name' => $name_to_save, 'role' => $role_to_save, 'userfields' => $userkeystring, 'usermetafields' => $metakeystring ), array('id' => $dir_to_update));
		//display success message
		echo "Directory saved.  To add this to a post, type [mackerel-user-directory directory='".$dir_to_update."']";
	}
	//generate the new/load form
	?>
	<form action="users.php?page=mackerel-user-directory" method="post" enctype="multipart/form-data">
		<h3>Load directory</h3>
		<p>If you would like to edit an existing directory, please select it here.</p>
		<?php
		$tablename = $wpdb->prefix . "mackereluserdirectory";
			$directories = $wpdb->get_results("SELECT id, name FROM $tablename");
				echo '<select name="directories">';
				foreach($directories as $directory) {
					echo "<option value=".$directory->id.'">'.$directory->name."(#".$directory->id.")".'</option>';
				}
				echo '</SELECT>';
		?>
		<input type="submit" name="load" value="Load" />
	</form>
	<form action="users.php?page=mackerel-user-directory" method="post" enctype="multipart/form-data">
		<p>Otherwise click the new button to start creating a new directory</p>
		<input type="submit" name="new" value="New" />
	</form>
	<?php	
}
function register_shortcodes(){
	add_shortcode('mackerel-user-directory', 'mackereluserdirectory');
}
add_action( 'init', 'register_shortcodes');
//the shortcode
function mackereluserdirectory($atts){
	//call dragtable script so table can be rearranged (with thanks to http://www.danvk.org/wp/dragtable/)
	wp_enqueue_script('dragtable', plugins_url('dragtable.js', __FILE__), array('jquery'));
	global $wpdb;
	$wpdb->directorylisting = $wpdb->prefix . "mackereluserdirectory";
	//pull out the attributes
	extract(shortcode_atts(array(
        'directory' => '1',
    ), $atts ));
	//call the plugin table and get the row with the ID in the atribute
	$directory_info = $wpdb->get_row("SELECT * FROM  $wpdb->directorylisting WHERE id = $directory");
	$role = $directory_info->role;
	$userfields = $directory_info->userfields;
	$usermetafields = $directory_info->usermetafields;
	//explode all fields into arrays
	$userarray = explode(",", $userfields);
	$metaarray = explode(",", $usermetafields);
	sort($metaarray);
	//start the table
	$directory_table = "<table class=' mackerel-table draggable sortable'><tr><th>ID</th>";
	$selectlist = "SELECT id,";
	$wherelist = "(";
	//create table header with the fields
	foreach($userarray as $uheader){
		$directory_table .= "<th>$uheader</th>";
		$selectlist .= $wpdb->users.".".$uheader.",";
	}
	foreach($metaarray as $mheader){
		$directory_table .= "<th>$mheader</th>";
		$wherelist .= "'".$mheader."',";
	}
	//remove final comma from selectlist and from wherelist
	$selectlist = substr($selectlist, 0, -1);
	$wherelist = substr($wherelist, 0, -1);
	$wherelist .= ")";
	$directory_table .= "</tr>";
	//get users with the role in question (or get all users)
	if ($role!= '0'){
		$ulist="(";
		$capabilities_prefix = $wpdb->prefix;
		$sql = "SELECT * FROM ".$wpdb->usermeta." WHERE meta_key = '".$capabilities_prefix."capabilities' AND meta_value LIKE '%".$role."%'";
		$uml = $wpdb->get_results($sql);	
		foreach ($uml as $u){
			$ulist .= "".$u->user_id.",";
		}
		$ulist = substr($ulist, 0 , -1);
		$ulist .=")";
		$userlist = $wpdb->get_results("$selectlist FROM  $wpdb->users WHERE id IN $ulist");
	}
	else{
		$userlist = $wpdb->get_results("$selectlist FROM  $wpdb->users");
	}	
	//for each user, organise the attributes and add to the table
	foreach($userlist as $user){
		$directory_table .= "<tr>";
		$array = get_object_vars($user);
		//user table values
		foreach($array as $a){
			$directory_table .="<td>".$a."</td>";
		}
		//meta table values
		$metavalues = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE user_id = $user->id  AND meta_key IN $wherelist ORDER By meta_key");
		foreach ($metavalues as $mv){
			$directory_table .= "<td>".$mv->meta_value."</td>";
		}
		$directory_table .= "</tr>";
		$metavalues = "";
	}
	$directory_table .= "</table>";
	//return the table for display
	return $directory_table;
}


 ?>