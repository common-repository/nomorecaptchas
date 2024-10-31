<?php

/*
 * NoMoreCaptchas
 * Oxford BioChronometrics SA
 * Version: 3.0.6
 * Purpose: To configure NoMoreCaptchas Settings Page
 */
function xb_nmc_dashboard() {
	
	// If the social options don't exist, create them.
	if (false == get_option ( 'plugin:xb_nmc_dashboard' )) {
		add_option ( 'plugin:xb_nmc_dashboard' );
	} // end if
	add_settings_section ( 'dashboard_section_field_1', // ID used to identify this section and with which to register options
'Dashboard', // Title to be displayed on the administration page
'', // Callback used to render the description of the section
'xb_nmc_slug_dashboard' ) // Page on which to add this section of options
;
	add_settings_section ( 'dashboard_section_field_2', '', /*'xb_nmc_render_upgrade_section',*/ 'xb_nmc_slug_dashboard' );
}
function rcumc_add_nomorecaptchas_to_dhvc_form() {
	// Add NoMoreCaptchas to Visual Composer's VC Map.
	//
	// name -- Label to use when displaying icon for this item
	// when user can select it to add to a form.
	// base -- Shortcode Tag. Not quite sure how this is used
	// yet.
	// category -- Form Control
	// icon -- Icon to display for NoMoreCaptchas. Using the same
	// icon used by DHVC for captcha
	//
	// admin_enqueue_js -- An array of JavaScript scripts to be enqueued
	// for the shortcode. If only one script, a
	// string can be used instead of an array.
	// admin_enqueue_css -- An array of CSS files to be enqueued for the
	// shortcode. If only one CSS file, a string
	// can be used instead of an array.
	// html_template -- File that will display shortcode item.
	// content -- used with vc_raw_html.php template to
	// include raw HTML. Potential usage for injecting
	// NoMoreCaptchas <div>...</div> string.
	// NOTE: Can use filter functions to inject content.
	$our_map = array (
			// 'name' => __( 'Form NoMoreCaptchas', RCUMC_TOOLS_TD),
			'name' => 1,
			'base' => RCUMC_NOMORECAPTCHAS_SHORTCODE,
			// 'category' => __( 'Form Control', DHVC_FORM ),
			'category' => 2,
			'icon' => 'icon-dhvc-form-captcha' 
	);
	
	// Insert our NoMoreCaptchas code into Visual Composer (for DHVC Form)
	// vc_map( $our_map ); -- as we didn't have visual composer
	
	return;
}
// function xb_nmc_render_upgrade_section() {
//	global $current_user;
//	$user_id = $current_user->ID;
	/* Check that the user hasn't already clicked to ignore the message */
//	if (get_user_meta ( $user_id, 'example_ignore_notice' )) {
//		echo '<div class="upgrade_notice"><p>';
//		print ('<a href="http://nomorecaptchas.com/#!/pricing/" target="_blank"><strong>Upgrade</strong></a> for more detailed dashboard analytics.') ;
//		echo "</p></div>";
//	}
// }

add_action ( 'admin_init', 'disable_trackbacks_nag_ignore' );
add_action ( 'admin_init', 'register_nag_ignore' );
function xb_nmc_register_settings() {
    
	$option_name = 'plugin:xb_nmc_option_name';
	$option_values = get_option ( $option_name );
	$default_values = array();
	$default_values = array (
			'xb_nmc_authkey' => '',
			'xb_nmc_sub_level' => '',
			'xb_nmc_org_name' => '',
			'xb_nmc_org_address' => '',
			'xb_nmc_domain' => '',
			'xb_nmc_contact_name' => '',
			'xb_nmc_contact_email' => '',
			'xb_nmc_authcode' => '' 
	);
	
	$data = shortcode_atts ( $default_values, $option_values );
	
	$xb_authkey64 = esc_attr ( $data ['xb_nmc_authkey'] );
	$xb_authkey_array = array (
			'not-set',
			'not-set',
			'not-set',
			'not-set',
			'not-set',
			'not-set',
			'not-set',
			'not-set' 
	);
	if (strlen ( $xb_authkey64 ) > 50) {
		$xb_authkey64 = str_replace ( "=== START OF KEY ===", "", $xb_authkey64 );
		$xb_authkey64 = str_replace ( "=== END OF KEY ===", "", $xb_authkey64 );
		$xb_authkey = base64_decode ( $xb_authkey64 );
		$xb_authkey_array_raw = explode ( "|", $xb_authkey );
		if (count ( $xb_authkey_array_raw ) == 9) {
			$xb_authkey_array = $xb_authkey_array_raw;
			$xb_authkey_array [3] = $xb_authkey_array [3] . ' ' . $xb_authkey_array [4];
		}
	}
	
	register_setting ( 'plugin:xb_nmc_option_group', $option_name, '' );
	
	update_option ( 'xb_nmc_authkey', isset($xb_authkey_array [8]) ? $xb_authkey_array [8] : '' );
	
	add_settings_section ( 'section_2', 'Subscription Licence', 'xb_nmc_render_subscription_section', 'xb_nmc_slug' );
	
	add_settings_field ( 'section_2_field_3', 'Licence Key', 'xb_nmc_render_subscription_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label3',
			'name' => 'xb_nmc_authkey',
			'value' => esc_attr ( $data ['xb_nmc_authkey'] ),
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_9', 'Authenticating Code', 'xb_nmc_render_viewonly_and_copy_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label9',
			'name' => 'xb_nmc_authcode',
			'value' => isset($xb_authkey_array [8]) ? $xb_authkey_array [8] : '',
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_6a', 'Primary Domain', 'xb_nmc_render_viewonly_and_copy_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label6a',
			'name' => 'xb_nmc_domain',
			'value' => $xb_authkey_array [5],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_4', 'Subscription Level', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label4',
			'name' => 'xb_nmc_sub_level',
			'value' => $xb_authkey_array [1],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_5x', 'Valid From', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label5x',
			'name' => 'xb_nmc_valid_from',
			'value' => $xb_authkey_array [0],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_5', 'Organisation Name', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label5',
			'name' => 'xb_nmc_org_name',
			'value' => $xb_authkey_array [2],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_6', 'Organisation City & Country', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label6',
			'name' => 'xb_nmc_org_address',
			'value' => $xb_authkey_array [3],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_7', 'Contact Person', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label7',
			'name' => 'xb_nmc_contact_name',
			'value' => $xb_authkey_array [6],
			'option_name' => $option_name 
	) );
	
	add_settings_field ( 'section_2_field_8', 'Contact E-mail Address', 'xb_nmc_render_viewonly_field', 'xb_nmc_slug', 'section_2', array (
			'label_for' => 'label8',
			'name' => 'xb_nmc_contact_email',
			'value' => $xb_authkey_array [7],
			'option_name' => $option_name 
	) );
}
function xb_nmc_render_subscription_section() {
	print '<p><b>Don&#39;t have a Licence Key? Register <a href="http://nomorecaptchas.com/#!/register/" target="_blank">here</a> for one now!</b></p>';
	print '<p>Your Licence Key holds details about your organisation, domain and the subscription level you have purchased.' . ' In order to use NoMoreCapthas, you must first validate your Licence Key.</p>';
}
function xb_nmc_render_subscription_field($args) {
	printf ( '<textarea name="%1$s[%2$s]" id="%3$s" rows="8" cols="55" placeholder="Paste your NoMoreCaptchas Licence Key here......." class="code">%4$s</textarea>', $args ['option_name'], $args ['name'], $args ['label_for'], $args ['value'] );
}
function xb_nmc_render_viewonly_field($args) {
	printf ( '<input DISABLED name="%1$s[%2$s]" id="%3$s"  value="%4$s" class="regular-text">', $args ['option_name'], $args ['name'], $args ['label_for'], $args ['value'] );
}
function xb_nmc_render_viewonly_and_copy_field($args) {
	printf ( '<input name="%1$s[%2$s]" id="%3$s"  value="%4$s" class="regular-text">', $args ['option_name'], $args ['name'], $args ['label_for'], $args ['value'] );
}
function xb_nmc_register_logging() {
	$option_name_log = 'plugin:xb_nmc_option_name_log';
	$option_values_log = get_option ( $option_name_log );
	$default_values_log = array();
	$default_values_log = array (
			'xb_nmc_debug' => '',
//			'xb_nmc_error' => '',
//			'xb_nmc_warning' => '',
//			'xb_nmc_parse' => '',
			//'xb_nmc_notice' => '' 
	);
	
	$data_log = shortcode_atts ( $default_values_log, $option_values_log );
       
	register_setting ( 'plugin:xb_nmc_option_group_log', $option_name_log, '' );
	
	add_settings_section ( 'section_log', // ID used to identify this section and with which to register options
'', // Title to be displayed on the administration page
'', // Callback used to render the description of the section
'xb_nmc_slug_log' ) // Page on which to add this section of options
;
	
//	add_settings_field ( 'plugin_chk_Switch_logging', 'Switch Logging On', 'setting_chk_switchlogging', 'xb_nmc_slug_log', 'section_log', array (
//			'label_for' => 'Debug',
//			'name' => 'xb_nmc_debug',
//			'value' =>  $data_log ['xb_nmc_debug'] ,
//			'option_name' => $option_name_log 
//	) );
        
        add_settings_field ( 'plugin_chk_Switch_logging', '', 'setting_chk_switchlogging', 'xb_nmc_slug_log', 'section_log' );
        add_settings_field ( 'plugin_chk_Error', '', 'setting_chk_error', 'xb_nmc_slug_log', 'section_log' );
        add_settings_field ( 'plugin_chk_Warn', '', 'setting_chk_warn', 'xb_nmc_slug_log', 'section_log' );
        add_settings_field ( 'plugin_chk_Parse', '', 'setting_chk_Parse', 'xb_nmc_slug_log', 'section_log' );
        add_settings_field ( 'plugin_chk_Notice', '', 'setting_chk_Notice', 'xb_nmc_slug_log', 'section_log');     
}
function setting_chk_switchlogging() {
$GLOBALS['nmc_error_level']=get_user_meta('099999', 'nmc_error_level',true);
    $options = get_option( 'xb_nmc_slug_log' );
    $tempchk = $options;
    $log_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
    if ($log_arry !== 'aaa')
    {
       if (isset($log_arry ['logOn']) && $log_arry ['logOn'] == "1")
       {    $tempchk=true;
       }
    }
    else{
        $tempchk=get_previous_logOn_testing();
    }
  
    $html = '<input type="checkbox" id="checkbox_log" name="xb_nmc_slug_log[logOn]" onclick="checkLogging()" value="1"' . checked( 1, $tempchk, false ) . '/>';
    $html .= '<label for="checkbox_example">Switch Logging on</label>';

    echo $html;

}
function setting_chk_error() {
    $options = get_option( 'xb_nmc_slug_log' );
    $tempchk = $options;
    $log_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
    if ($log_arry !== 'aaa')
    {
       if (isset($log_arry ['error']) && $log_arry ['error'] == "1")
       {    $tempchk=true;
       }
    }
    else{
        $tempchk=get_previous_error_setting();
    }

    $html = '<input type="checkbox" id="checkbox_error" name="xb_nmc_slug_log[error]" value="1"' . checked( 1, $tempchk, false ) . '/>';
    $html .= '<label for="checkbox_example">Error</label>';

    echo $html;

}
function setting_chk_Parse() {
    $options = get_option( 'xb_nmc_slug_log' );
    $tempchk = $options;
    $log_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
    if ($log_arry !== 'aaa')
    {
       if (isset($log_arry ['parse']) && $log_arry ['parse'] == "1")
       {    $tempchk=true;
       }
    }
    else{
        $tempchk=get_previous_parse_setting();
       
    }

    $html = '<input type="checkbox" id="checkbox_parse" name="xb_nmc_slug_log[parse]" value="1"' . checked( 1, $tempchk, false ) . '/>';
    $html .= '<label for="checkbox_example">Parse</label>';

    echo $html;

}
function setting_chk_warn() {

    $options = get_option( 'xb_nmc_slug_log' );
    $tempchk = $options;
    $log_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
    if ($log_arry !== 'aaa')
    {
       if (isset($log_arry ['warn']) && $log_arry ['warn'] == "1")
       {    $tempchk=true;
       }
    }
    else{
        $tempchk=get_previous_warn_setting();
    }

    $html = '<input type="checkbox" id="checkbox_warn" name="xb_nmc_slug_log[warn]" value="1"' . checked( 1, $tempchk, false ) . '/>';
    $html .= '<label for="checkbox_example">Warn</label>';

    echo $html;

}
function setting_chk_Notice() {

    $options = get_option( 'xb_nmc_slug_log' );
    $tempchk = $options;
    $log_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
    if ($log_arry !== 'aaa')
    {
       if (isset($log_arry ['notice']) && $log_arry ['notice'] == "1")
       {    $tempchk=true;
       }
    }
    else{
        $tempchk=get_previous_notice_setting();
    }

    $html = '<input type="checkbox" id="checkbox_notice" name="xb_nmc_slug_log[notice]" value="1"' . checked( 1, $tempchk, false ) . '/>';
    $html .= '<label for="checkbox_example">Notice</label>';

    echo $html;

}
function xb_nmc_register_log_file_del() {
	$option_name_logfile = 'plugin:xb_nmc_option_name_logfile';
	$option_values_logfile = get_option ( $option_name_logfile );
	
	register_setting ( 'plugin:xb_nmc_option_log_file', $option_name_logfile, '' );
	
	add_settings_section ( 'section_logfile', // ID used to identify this section and with which to register options
'', // Title to be displayed on the administration page
'', // Callback used to render the description of the section
'xb_nmc_slug_logfile' ) // Page on which to add this section of options
;
	
	add_settings_field ( 'plugin_chk_logfile', 'Clean up log files older than 60 days', 'logfileChk', 'xb_nmc_slug_logfile', 'section_logfile' );
}
// CHECKBOX - Name: plugin_options[chkbox2]
function logfileChk() {
	$files = array ();
        echo "<input type='hidden' name= 'formname' id='formname' value='' />";
	$old_logs_count=0;
	global $current_user;
	$user_id = $current_user->ID;
	$home_path = get_user_meta ( '099999', 'home_path' );
	$month = current_time('Y-m');
        
	$dir = opendir ( $home_path [0] . 'nmclogs/' );
	while ( false != ($file = readdir ( $dir )) ) {
		if ($file == "." || $file == ".." || $file == "servicelog" || $file == "errorLog")
			continue;
		$files [] = $file;
	}
	
	natcasesort ( $files );
	$optionsfile = get_option ( 'plugin_options' );
    /*
	if(sizeof($files)>0) { ?>
        <tr>
            <td>
                <input type="checkbox" id="master_delete_ckbox">
                <label for="master_delete_ckbox"><i>Select all</i></label>
                or choose a custom date
                <input type="text" readonly id="delete_from_date" style="border: 1px solid;" placeholder="From">
                <input type="text" readonly id="delete_to_date" style="border: 1px solid;" placeholder="To">
            </td>
        <tr>
        <?php
		foreach ( $files as $key => $file ) {
			if(strpos($file, $month)===FALSE){
				$filepath = $home_path [0] . 'nmclogs/' . $file;
                $data_id = str_replace(['-', '.txt'], ['', ''], explode(' ', $file)[0]); ?>
                <tr>
                    <td class="select-all-col">
                        <input id="<?php echo "{$key}_ckbox"; ?>" 
                            data-id="<?php echo $data_id; ?>" 
                            name="select_files[]" 
                            type="checkbox" 
                            class="select delete_ckbox" 
                            value="<?php echo $filepath ?>"
                        />
                        <label for="<?php echo "{$key}_ckbox"; ?>"><?php echo $file; ?></label>
                    </td>
                </tr>
                <?php
                $old_logs_count++;
			}
		}
	}
    
	if($old_logs_count==0) {
		echo "<tr><td>There are no daily authentication/ verification logs to display.";
		echo "</td></tr>";
	}
     * 
     */
}
function get_previous_logOn_testing(){
$level=$GLOBALS['nmc_error_level'];
    if($level !=null && strpos($level, '1') !== false)
            return true;
    return false;
    
}
function get_previous_error_setting(){
$level=$GLOBALS['nmc_error_level'];
    if(strpos($level, '1', 3)!==false){
    if(strpos($level, '1', 3)=='3')
            return true;
    }
    
    return false;
}
function get_previous_warn_setting(){
$level=$GLOBALS['nmc_error_level'];
    if(strpos($level, '1', 2)!==false){
    if(strpos($level, '1', 2)=='2')
            return true;
    }
    
    return false;
    
}
function get_previous_parse_setting(){
$level=$GLOBALS['nmc_error_level'];
    if(strpos($level, '1', 1)!==false){
    if(strpos($level, '1', 1)=='1')
            return true;
    }
    
    return false;
    
}
function get_previous_notice_setting(){
$level=$GLOBALS['nmc_error_level'];
    if(strpos($level, '1', 0)!==false){
    if(strpos($level, '1', 0)=='0')
            return true;
    }
    
    return false;
    
}
?>