<?php
/*
 * NoMoreCaptchas
 * Oxford BioChronometrics SA
 * Version: 3.0.6
 * Purpose: Main()
 */
@header('Cache-Control: no-store, no-cache, must-revalidate');
@header('Cache-Control: post-check=0, pre-check=0', false);
@header('Pragma: no-cache'); 
nocache_headers();

require_once ("geoip-api/src/geoip.inc");

define('RCUMC_NOMORECAPTCHAS_SHORTCODE', 'rcumc_form_nomorecaptchas');
define('RCUMC_NOMORECAPTCHS_IN_USE', 'rcumc_nomorecaptchas');
if (!session_id()) {
    session_start();
}
if (!empty($GLOBALS ['pagenow']) and ( 'options-general.php' === $GLOBALS ['pagenow'] or 'options.php' === $GLOBALS ['pagenow'])) {
    add_action('admin_init', 'xb_nmc_register_settings');
    add_action('admin_init', 'xb_nmc_register_logging');
    add_action('admin_init', 'xb_nmc_register_log_file_del');
    add_action('admin_init', 'xb_nmc_dashboard');
    add_action('admin_init', 'rcumc_add_nomorecaptchas_to_dhvc_form');
}

add_action('admin_menu', 'xb_options');

function xb_options() {
    add_options_page('NoMoreCaptchas Settings Page', 'NoMoreCaptchas', 'manage_options', 'xb_nmc_config', 'xb_nmc_render_page');
}

register_activation_hook(plugin_dir_path(__FILE__) . 'no-more-captchas.php', 'nomorecaptchas_activate');

function nomorecaptchas_activate() {
//    global $current_user;
//    $user_id = $current_user->ID;
    add_user_meta('099999', 'home_path', get_home_path(), true);
    add_user_meta('099999', 'nmc_error_level', '1111', true);
}


add_action('register_form', 'xb_iframe');
add_action('login_form', 'xb_iframe');
//add_action('bp_after_register_page', 'xb_iframe');
add_action('comment_form', 'xb_iframe');

if (isset($_REQUEST ['xb_bot_error'])) {
    add_action('comment_form', 'xb_bot_error');
}

function xb_iframe() {
    echo "<div id=xb-nmc-frm><iframe src='//ox-bio.com/ad.server/?v3=true&t=" . microtime(true) . "' width=270 height=180 frameBorder='0' ></iframe></div>";
    generateValidationTokens();
}


add_filter('the_content', 'addNmcIframe', 99);
function addNmcIframe($content) {
    preg_match_all("/form\s.*?(?:action=['\"].*?contact.*?['\"]|action=['\"].*?wpcf7.*?['\"]|id=['\"].*?signup.*?['\"]|id=['\"].*?gform.*?['\"]|id=['\"].*?wpforms.*?['\"]|id=['\"].*?CF.*?['\"]|id=['\"].*?cntctfrm.*?['\"]|class=['\"]wpcf7.*?).*?(?:input|button).*?type=['\"]submit/s", $content, $match);
    if(count($match[0])) {
        $content = preg_replace(
            '/<\/form>/', 
            "<div id=xb-nmc-frm style='padding-top: 15px;'><iframe src='//ox-bio.com/ad.server/?v3=true&t=" . microtime(true) . "' width=270 height=180 frameBorder='0' ></iframe></div></form>", 
            $content
        );
        generateValidationTokens();
    }
    return $content;
}

function xb_bot_error() {
    echo "<div id=form-bot-error>You're a bot.</div>";
}

function xb_enqueue_script() {
    $plugin = plugin_dir_url(__FILE__);
    wp_register_script('name-of-script', $plugin . 'nmc-script.js?t='.microtime(), array(
        'jquery'
    ), false, true);
    wp_enqueue_script('name-of-script');
}
function xb_enqueue_admin_script() {
    $plugin = plugin_dir_url(__FILE__);
    wp_register_script('name-of-script', $plugin . 'admin-script.js', array(
        'jquery'
    ));
    wp_enqueue_script('name-of-script');
}

function xb_custom_scripts() {
    $plugin = plugin_dir_url(__FILE__);
    wp_register_script('name-of-script', $plugin . 'nmc-script.js?t='.microtime(), array(
        'jquery'
    ), false, true);
    wp_enqueue_script('name-of-script');
}

function xb_custom_style($hook) {
//    var_dump("<pre><div style='margin-left: 200px;'>", $hook, "</div></pre>");
    if($hook == 'settings_page_xb_nmc_config') {
        $plugin = plugin_dir_url(__FILE__);
        wp_enqueue_style('prefix-style', $plugin . 'nmc-style.css', __FILE__);
    }
}

add_action('login_enqueue_scripts', 'xb_enqueue_script', 1);
add_action('admin_enqueue_scripts', 'xb_custom_style');
add_action('admin_enqueue_scripts', 'xb_enqueue_admin_script');
add_action('wp_enqueue_scripts', 'xb_custom_scripts');
add_action( 'plugins_loaded', 'my_plugin_override' );    
    
function my_plugin_override() {
	$home_path = get_user_meta('099999', 'home_path');
    if (is_null($home_path[0]))
 	{
		$home_path = str_replace('\\', '/', ABSPATH);
		add_user_meta('099999', 'home_path', $home_path, true); 
		add_user_meta('099999', 'nmc_error_level', '1111', true); 
	}   
}

// Include Iframe for Contact form

/* //Shekhar - commented this function as it caused double iframes... refer to the function below
function rl_wpcf7_form_elements($content) {
    $content .= '<script type="text/javascript">
    jQuery( document ).ready(function() {

        jQuery(".wpcf7-submit").parent().before(\'<div id="xb-nmc-frm"><iframe src="//ox-bio.com/ad.server/" width="270" height="180 frameBorder="0" ></iframe></div>\');
    });


</script>';

    return $content;
}
*/

/*
 * Removed in v2.4 and replaced with addNmcIframe for general purpose
 * 
add_filter('wpcf7_form_elements', 'rl_wpcf7_form_elements');
function rl_wpcf7_form_elements($content) {
    // global $wpcf7_contact_form;

    $rl_pfind = '/<p><input/';
    $rl_preplace = "<p> <div id=xb-nmc-frm><iframe src='//ox-bio.com/ad.server/' width=270 height=180 frameBorder='0' ></iframe></div></p><input/";
    $content = preg_replace($rl_pfind, $rl_preplace, $content, 2);

    return $content;
}
 * 
 */


function generateValidationTokens() {
    
	/* Crypto Codeblock	-START- */
	$rk16 =(string)mt_rand(pow(2,14),pow(2,15));
	$rk16.=(string)mt_rand(pow(2,14),pow(2,15));
	$rk16.=(string)mt_rand(pow(2,14),pow(2,15));
	$rk16.=(string)mt_rand(pow(2,14),pow(2,15));
	
	$kx10 =(string)mt_rand(pow(2,14),pow(2,15));
	$kx10.=(string)mt_rand(pow(2,14),pow(2,15));
	$kx10.=(string)mt_rand(pow(2,14),pow(2,15));
	$kt10=(string)mt_rand(pow(2,14),pow(2,15));
	$kt10.=(string)mt_rand(pow(2,14),pow(2,15));
	$kt10.=(string)mt_rand(pow(2,14),pow(2,15));
	
	$kj=(int)mt_rand(pow(2,14),pow(2,15));
	$kx=substr($kx10,0,10);
	$rk=(int)substr($rk16,0,16);
	$kt=$kv=(int)substr($kt10,0,10);

	for($ix=0; $ix<$kj; $ix++){
		$kt=round(sqrt($kt),7);
		$ak=explode('.',number_format($kt,7));
		$sv.=$ak[1];
		$sk=str_pad($ak[1],7,"0",STR_PAD_RIGHT);
		$kt=(int)$sk;
	}

	$sk=substr($sv,strlen($sv)-15,8);
	$ut=substr(time(),0,8);
	$tx=substr($kx,4,4).$ut.substr($kx,0,5).$kv;
	$ty=$kj.$ut.substr($kx,3,5);
	$tz=substr($kx,0,5).$rk.substr($kj,0,5);
	$sk=(int)$sk;
	$ut=(int)$ut;
	$rk=(string)$rk;
	$rk=(int)substr($rk,2,8);

	/* Crypto Codeblock -END- (c) 2011,2013 AMDS Ltd -  */
	 if(!isset($_SESSION)) {
		session_start();
	}
	
	$_SESSION['nmc']=$rk^($sk^$ut);
	 echo '<script type="text/javascript">var xbts1="'.$tx.'",xbts2="'.$ty.'",xbts3="'.$tz.'";</script>';

}


define(
    'NMC_EMAIL_FOOTER', 
    "\r\n\r\n\r\n==================================================\r\n\r\n NoMoreCaptchas Validated this e-mail as Not Spam \r\n\r\n==================================================\r\n\r\n\r\n"
);

add_action('wpcf7_before_send_mail', 'xb_ct7');
function xb_ct7($ct7_ContactForm) {
    $WPCF7_ContactForm = WPCF7_Submission::get_instance();
    if ($WPCF7_ContactForm) {
        $xb_data = $WPCF7_ContactForm->get_posted_data();
        if (xb_validate($xb_data)) {
            xb_nmc_wp_bot_log_file("human", "contact-us", False);
            $mail = $ct7_ContactForm->prop('mail');
            // $mail['subject'] .= " [Validated Not Spam by NoMoreCaptchas]";
            $mail ['body'] .= NMC_EMAIL_FOOTER;
            $ct7_ContactForm->set_properties(array(
                'mail' => $mail
            ));
        } else {
            xb_nmc_wp_bot_log_file("bot", "contact-us", True);
            $WPCF7_ContactForm->skip_mail = true;
        }
    }
}


//Action and validation for Contact Form by BestWebSoft - https://wordpress.org/plugins/contact-form-plugin/
add_action('cntctfrm_get_mail_data', 'NMC_form_validation');

//Action and validation for Gravity Forms
//the hook applies to all forms https://www.gravityhelp.com/documentation/article/gform_pre_submission/
add_action('gform_pre_submission', 'NMC_form_validation');

//Filter and validation for WP-Members: Membership Framework - https://wordpress.org/plugins/wp-members/
//http://rocketgeek.com/plugins/wp-members/docs/filter-hooks/wpmem_pre_validate_form/
add_action('wpmem_pre_validate_form', 'NMC_form_validation');


function NMC_form_validation($data) {
    
    $tempPath=getRequestMethod();
    if ($tempPath === "POST") {
        $tempPost = filter_input_array(INPUT_POST);
//        unset($tempPost['xbk0'], $tempPost['xbt0'], $tempPost['xbz0']);
//        var_dump("<pre>", $tempPost, xb_validate($tempPost)); exit();
        
        switch(current_filter()) {
            case 'cntctfrm_get_mail_data':
                $form_title = "[BestWebSoft] contact-form submission";
                break;
            
            case 'gform_pre_submission':
                $form_title = "[Gravity Forms] {$data['title']} submission";
                break;
            
            case 'wpmem_pre_validate_form':
                $form_title = "[WP-Members] registration-form submission";
                break;
        }
        
        if (!xb_validate($tempPost)) {
            xb_nmc_wp_bot_log_file("bot", $form_title, True);
            sendException("BOT DETECTED - KILLING THE PROCESS- WHILE ENTERING COMMENT FORM ");
            wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
        } else {
            xb_nmc_wp_bot_log_file("human", $form_title, False);
        }
    }
    
    return $data;
}



function xb_validate($post_data) {
//    if (isset($post_data ['xbt0']) || isset($post_data ['xbk0']) || isset($post_data ['xbz0'])) {
//        return true;
//    } else {
//        return false;
//    }

    if(!isset($_SESSION['nmc']) || !$_SESSION['nmc'] || !isset($_REQUEST['xbk0']) || !$_REQUEST['xbk0']) {
		
        return false;
    }
    return $_SESSION['nmc'] == $_REQUEST['xbk0'] ? true : false;
}

function nmc_get_current_month_files_list() {
    // get list of current month authentications
    global $current_user;
    global $hmPath;
    $user_id = $current_user->ID;
    $home_path = get_user_meta('099999', 'home_path');
    $hmPath = $home_path;
    $month = current_time('Y-m');
    $j = 0;

    $file_list = glob($home_path[0] . 'nmclogs/' . $month . "*.txt");
    if (sizeof($file_list) > 0) {
        for ($i = 0; $i < sizeof($file_list); $i ++) {
            $root_path_len = strlen($home_path [0] . "nmclogs/");
            $files [$i] = substr($file_list [$i], $root_path_len);
        }

        return $files;
    }
    return;
}

// function get_files_list() {
// // get list of all nmclogs
// global $current_user;
// $user_id = $current_user->ID;
// $home_path = get_user_meta($user_id, 'home_path');
//
//
// $file_list = glob($home_path[0] . 'nmclogs/' . "*.txt");
// if (sizeof($file_list) > 0) {
// for ($i = 0; $i < sizeof($file_list); $i++) {
// $files[$i] = ltrim($file_list[$i], $home_path[0] . "nmclogs/");
// }
//
// return $files;
// }
// return;
// }
function read_file_by_filename($filename) {
    global $current_user;
    $user_id = $current_user->ID;
    $home_path = get_user_meta('099999', 'home_path');
    if (($logfile = @fopen($home_path [0] . 'nmclogs/' . $filename, "r")) != FALSE) {
        $myfile = fopen($home_path [0] . 'nmclogs/' . $filename, "r") or die("Unable to open file!");
        $log_data = fread($myfile, filesize($home_path [0] . 'nmclogs/' . $filename));
        fclose($myfile);

        return $log_data;
    }
    return;
}

function get_total_authentication_by_month() {
    global $current_user;
    $user_id = $current_user->ID;
    $home_path = get_user_meta('099999', 'home_path');
    $files = nmc_get_current_month_files_list();
    $month = current_time('m');
    $total_authentications = 0;
    $cur_month_total_auth = 0;
    $cur_month_total_auth1 = 0;
    if ($files) {
        foreach ($files as $file) {
            $pos = strpos(substr($file, - 9, 2), $month);
            $linecount = 0;
            $week4 = $week3 = $week2 = $week1 = 0;
            $cur_month_linecount = 0;
            if (($logfile = @fopen($home_path [0] . 'nmclogs/' . $file, "r")) != FALSE) {
                $myfile = fopen($home_path [0] . 'nmclogs/' . $file, "r") or die("Unable to open file!");
                while (!feof($myfile)) {
                    $line = fgets($myfile);
                    if (substr($line, 5, 2) == $month) {
                        $cur_month_total_auth1 ++;
                    }
                    $linecount ++;
                    if ($pos !== false) {
                        $cur_month_linecount ++;
                    }
                }

                $total_authentications = $total_authentications + $linecount - 1;
                if ($pos !== false) {
                    $cur_month_total_auth = $cur_month_total_auth + $cur_month_linecount - 1;
                }
            }
        }
    }

    return $cur_month_total_auth1;
}

function get_authentication_code() {
    $option_name = 'plugin:xb_nmc_option_name';
    $option_values = get_option($option_name);
    return $option_values ['xb_nmc_authcode'];
}

function get_domain_name() {
    $option_name = 'plugin:xb_nmc_option_name';
    $option_values = get_option($option_name);
    return $option_values ['xb_nmc_domain'];
}

function dashboard_head_output($html_pagination = "",$searchCriteria="") {
    $total_authentications = get_total_authentication_by_month();

    // Creating Sorting parametes for Table heads hrefs
    if (isset($_GET ['ord'])) {
        $sort_params = array("&col=".$_GET ['col']."&ord=SORT_ASC", "&col=".$_GET ['col']."&ord=SORT_DESC");
        $sorting_param = str_replace($sort_params, '', $_SERVER['REQUEST_URI']);
    } else {
        $sorting_param = $_SERVER ['REQUEST_URI'];
    }
   
    //Retain SearchCriteria through session variable
   if($searchCriteria!=='' ){
    if(strpos($sorting_param,'searchCriteria') !== false)
    $sorting_param=str_replace('&searchCriteria='.$_SESSION['OldSearchC'], '&searchCriteria='.$searchCriteria, $sorting_param);
    else
    $sorting_param=$sorting_param.'&searchCriteria='.$searchCriteria;
   }else{if(strpos($sorting_param,'searchCriteria') !== false)
    $sorting_param=str_replace('&searchCriteria='.$_SESSION['OldSearchC'], $searchCriteria, $sorting_param);       
   }
       
   $_SESSION['OldSearchC']=$searchCriteria;
   
    $ord = isset($_GET ['ord']) ? $_GET ['ord'] : 'SORT_DESC';
    if ($ord == 'SORT_DESC')
        $ord = 'SORT_ASC&';
    else if ($ord == 'SORT_ASC')
        $ord = 'SORT_DESC';

    // Show/Hide reload label
    $ReloadLabelVisibility = "display:none;";
    $searchCriteria = getSearchCriteria();
//    var_dump($searchCriteria);
    if (!empty($searchCriteria)) {
        $ReloadLabelVisibility = "display:block;";
    }

    // Table Header divs and header
    $html = '
    		<div class="dashboard-info-div">
		<table class="dashboard-info" id="dashboard-info" width="100%">
		<tr>
		<td width="100">
		<div class="refresh_btn"><a href="' . $_SERVER ['PHP_SELF'] . '?page=xb_nmc_config" class="refresh_btn">Refresh</a></div>
 		</td>
 		<td align="left"><div><b>Verifications for the Current Month </b> ' . $total_authentications . '</div></td>
 		<td align="center"><div id = "myDiv" style=' . $ReloadLabelVisibility . '><b><p style="color:red;">Click on refresh to reload the logs</p></b></div></td>
 		<td align="right"><input type="text" id="searchCriteria" size="20" maxlength= "20" name="searchCriteria" value="' . $searchCriteria . '" /></td>
 		<td width="100"><div class="refresh_btn"><input type="submit" value="Search" class="refresh_btn" /></div></td>
 		</tr>
 		</table>
 		</div>' . $html_pagination . '
 		<div class="dashboard-table-div">
 		<table class="dashboard-table" id="dashboard-table">
        <thead>
        <tr>
            <th>No.</th>
            <th><a href="' . $sorting_param . '&col=0&ord=' . $ord . '">Time</a></th>
            <th><a href="' . $sorting_param . '&col=1&ord=' . $ord . '">Device IP</a></th>
            <th><a href="' . $sorting_param . '&col=4&ord=' . $ord . '">State</a></th>
            <th><a href="' . $sorting_param . '&col=2&ord=' . $ord . '">Page Accessed</a></th>
            <th><a href="' . $sorting_param . '&col=3&ord=' . $ord . '">Country of Origin</a></th>
        </tr>
        </thead>
        <tbody>
        ';
    return $html;
}

function dashboard_values_output($i, $gmt, $ip, $state, $page, $country) {

    $html = '
            <tr >
                <td>' . $i . '</td>
                <td>' . $gmt . '</td>
                <td>' . $ip . '</td>
                <td>' . $state . '</td>
                <td>' . $page . '</td>
                <td>' . $country . '</td>
            </tr>
        ';


    return $html;
}

function xb_nmc_render_page() {
    $active_tab = isset($_GET ['tab']) ? $_GET ['tab'] : 'dashboard';
    ?>
    <div class="wrap">

        <h2 class="nav-tab-wrapper">
            <a href="?page=xb_nmc_config&tab=dashboard"
               class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>
            <a href="?page=xb_nmc_config&tab=settings_page"
               class="nav-tab <?php echo $active_tab == 'settings_page' ? 'nav-tab-active' : ''; ?>">Settings
                Page</a> <a href="?page=xb_nmc_config&tab=logging_page"
                        class="nav-tab <?php echo $active_tab == 'logging_page' ? 'nav-tab-active' : ''; ?>">Log
                Options</a>
        </h2>
        <?php if ($active_tab == 'logging_page') {
            add_action('wp_enqueue_scripts', 'add_date_picker');
            loggingPage();
            ?>       
           <!-- <form method="post" action="options-general.php?page=xb_nmc_config&tab=logging_page" onclick="enableDisableLogging()"> -->
            <form action="" method="POST" onclick="" class="form_logging" >    
                <h2><?php print 'Enable or Disable Event Logs' ?></h2>
                <?php
                settings_fields('plugin:xb_nmc_option_group_log');
                do_settings_sections('xb_nmc_slug_log');
                submit_button('Confirm', 'primary', 'confirmLog');
                ?>
            </form>
            <form action="" method="POST" onclick=""class="form_logfiles">     
                <?php
                settings_fields('plugin:xb_nmc_option_log_file');
                do_settings_sections('xb_nmc_slug_logfile');
                submit_button('Delete old log file(s)', 'primary');
                ?>
            </form>
           
           
<!--            <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
            <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> -->
            
			<script type="text/javascript">
                
                /*
                 * Delete all or custom logs
                
                function updateCkboxByDate(sd, ed) {
                    sd = sd.replace(/\-/g, '');
                    ed = ed.replace(/\-/g, '');
                    jQuery.each(jQuery('.delete_ckbox'), function(k, v) {
                        var id = jQuery(v).attr('data-id');
                        console.log(id);
                        if(id >= sd && id <= ed) {
                            jQuery(v).prop('checked', true);
                        }
                    });
                }
                
                var all_selected = false;
                jQuery('#master_delete_ckbox').on('change', function() {
                    all_selected = jQuery('#master_delete_ckbox').prop('checked');
                    jQuery('.delete_ckbox').prop('checked', all_selected);
                });
                
                
                jQuery('#delete_from_date').datepicker({
                    dateFormat: "yy-mm-dd",
                    maxDate: '0',
                });
                jQuery('#delete_to_date').datepicker({
                    dateFormat: "yy-mm-dd",
                    maxDate: 0,
                });
                
                jQuery('#delete_from_date').on('change', function() {
                    jQuery('.delete_ckbox').prop('checked', false);
                    var d = jQuery('#delete_from_date').val();
                    jQuery('#delete_to_date').datepicker('option', 'minDate', d);
                    var to = jQuery('#delete_to_date').val();
                    if(to && to >= d) {
                        updateCkboxByDate(d, to);
                    }
                    
                });
                
                jQuery('#delete_to_date').on('change', function() {
                    jQuery('.delete_ckbox').prop('checked', false);
                    var d = jQuery('#delete_to_date').val();
                    jQuery('#delete_from_date').datepicker('option', 'maxDate', d);
                    var from = jQuery('#delete_from_date').val();
                    if(from && from <= d) {
                        updateCkboxByDate(from, d);
                    }
                })
                
                /*
                 * End delete all or custom logs
                 */
                
				document.getElementsByTagName("table")[0].setAttribute("id","table_logging");
				document.getElementsByTagName("table")[1].setAttribute("id","table_logfiles");        
				jQuery("table:eq(1) tr:eq(0) > td").addClass('first_td');
				jQuery('form.form_logfiles').on('submit',function(e){
                    
                    /*
                    if(!jQuery('.delete_ckbox').length) {
                        return false;
                    }
                    */
                    
                    if(!confirm('This will delete all the log files older than 60 days. Are you sure you want to continue?')) {
                        return false;
                    }
                    
                    var s = document.getElementById('formname');
                    s.value = 'textfiles';
					e.preventDefault();
                    
                    jQuery(this).append('<input type="hidden" name="delete_log_files" value="true"/>')
                    
					jQuery.ajax({
						type     : "POST",
						cache    : false,
						url      : jQuery(this).attr('action'),
						data     : jQuery(this).serialize(),
						success  : function(data) {
                            var match = data.match(/nmc_deleted_logs_no::([0-9]+)::/);
                            alert(match ? match[1] + ' log file(s) have been deleted!' : 'No deletable files found!');
                                
						}
					});
					jQuery('#table_logfiles tr').has('input[type="checkbox"]:checked').remove();
				});
                
                /*
				jQuery('form.form_logging').on('submit',function(e){
                                  var s= document.getElementById('formname');
                                      s.value = '';
					e.preventDefault();
					jQuery.ajax({
						type     : "POST",
						cache    : false,
						url      : jQuery(this).attr('action'),
						data     : jQuery(this).serialize(),
						success  : function(data) {
						}
					});
				});   
                */

            </script>
            <?php
        } elseif ($active_tab == 'settings_page') {
            ?>
            <form action="options.php" method="POST">
                <h2><?php print $GLOBALS['title']; ?></h2>
                <?php
                settings_fields('plugin:xb_nmc_option_group');
                do_settings_sections('xb_nmc_slug');

                submit_button('Validate Licence Key', 'primary');
                ?>
            </form>
            <div style="padding: 10px;">
                <input type="checkbox" id="nmc_apps_switcher"
                    <?php echo isset($GLOBALS['3rd_party_apps_options']) && $GLOBALS['3rd_party_apps_options']->allow_3rd_party_apps_login ? 'checked' : ''; ?>
                >
                <label for="nmc_apps_switcher">
                    Allow login with third-party apps, such as the WordPress app or IFTTT.<br>
                    <small>
                        <i>
                            Note, this will allow you to login without being verified by NoMoreCaptchas. 
                            It may also allow bots using these app services to avoid detection. 
                            While no such exploits are currently known, by checking this box, you acknowledge this issue could arise.
                        </i>
                    </small>
                </label>
            </div>
            <div style="padding: 10px; display: none; color: red;" id="nmc_apps_switcher_state">
                Something went wrong. Please try again!
            </div>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
            <script>
                $(document).ready(function() {
                    if($('#nmc_apps_switcher').length) {
                        $('#nmc_apps_switcher').on('click', function() {
                            $.ajax({
                                method: 'POST',
                                url: '<?php echo plugin_dir_url(__FILE__); ?>3rd_party_apps/change_3rd_party_apps_options.php',
                                data: {
                                    action: 'change_nmc_options', 
                                    value: $('#nmc_apps_switcher').prop('checked') ? 1 : 0
                                },
                            })
                            .done(function (msg) {
                                !msg || !msg['status'] ? $('#nmc_apps_switcher_state').show() : $('#nmc_apps_switcher_state').hide();
                            });
                        })
                    }
                });
            </script>
            <?php
        } else {
            ?>
            <form action="" method="POST"><?php
                settings_fields('plugin:xb_nmc_dashboard');
                do_settings_sections('xb_nmc_slug_dashboard');
                // get_total_authentication_by_month();
                // $total_authentications= "64";

                $files = nmc_get_current_month_files_list();
                $complete_log = array();
                $log_data_array = array();
                $log_details = array();
                for ($j = (sizeof($files) - 1); $j >= 0; $j --) {
                    unset($log_data_array);
                    unset($log_details);
                    $filename = $files [$j];
                    $log_data = read_file_by_filename($filename);
                    $log_data_array = explode("\n", $log_data);
                    foreach ($log_data_array as $key => $val) {
                        if (empty($val)) {
                            unset($log_data_array [$key]);
                        }
                    }
                    for ($i = 0; $i < sizeof($log_data_array); $i ++) {
                        $log_details[$i] = explode("|", $log_data_array[$i]);
                        $time = $log_details[$i][0];
                        $ip = $log_details[$i][1];
                        $screen = $log_details[$i][2];
                        $country = $log_details[$i][3];
                        $humanOrBot = $log_details[$i][4];

                        $searchCriteria = getSearchCriteria();
                        if (!empty($searchCriteria)) {
                            if (strpos(strtolower($time), $searchCriteria) !== FALSE || strpos(strtolower($ip), $searchCriteria) !== FALSE || strpos(strtolower($screen), $searchCriteria) !== FALSE || strpos(strtolower($humanOrBot), $searchCriteria) !== FALSE || strpos(strtolower($country), $searchCriteria) !== FALSE) {
                                
                            } else {
                                unset($log_details[$i]);
                                $log_details = array_values($log_details);
                            }
                        }
                    }
                    $complete_log = array_merge($complete_log, $log_details);
                }

                // Get posted parametes and do sorting according
                $col = isset($_GET ['col']) ? $_GET ['col'] : '0';
                $ord = isset($_GET ['ord']) ? $_GET ['ord'] : 'SORT_DESC';
                if ($ord == 'SORT_DESC') {
                    $ord = SORT_DESC;
                } else if ($ord == 'SORT_ASC') {
                    $ord = SORT_ASC;
                }

                $tmp = Array();
                foreach ($complete_log as &$ma) {
                    $tmp [] = &$ma [$col];
                }
                array_multisort($tmp, $ord, $complete_log);

                // to do the pagination magic here //

                $per_page = 400; // number of results to show per page
                $total_results = sizeof($complete_log);
                $total_pages = ceil($total_results / $per_page); // total pages we going to have
                $show_page = 1; // default
                // -------------if page is setcheck------------------//
                if (filter_input(INPUT_GET, 'curpage')) {
                    $show_page = filter_input(INPUT_GET, 'curpage'); // current page
                    if (($show_page > 0) && ($show_page <= $total_pages)) {
                        $start = ($show_page - 1) * $per_page;
                        $end = $start + $per_page;
                    } else {
                        // error - show first set of results
                        $start = 0;
                        $end = $per_page;
                    }
                } else {
                    // if page isn't set, show first set of results
                    $start = 0;
                    $end = $per_page;
                }
                // display pagination
                $page = (isset($_GET ['curpage'])) ? intval($_GET ['curpage']) : 1;
                $tpages = $total_pages;
                if ($page <= 0)
                    $page = 1;
                if(isset($searchCriteria) && $searchCriteria!=='')
                $reload = $_SERVER['PHP_SELF'] . "?page=xb_nmc_config&searchCriteria=$searchCriteria&tpages=" . $tpages;
                else
                $reload = $_SERVER['PHP_SELF'] . "?page=xb_nmc_config&tpages=" . $tpages;
                    
                // echo '<div class="pagination"><ul>';
                if ($total_pages >= 1) {
                    $html_pagination = paginate($reload, $show_page, $total_pages);

                    $html = dashboard_head_output($html_pagination,$searchCriteria);
                    print ($html);
                    // echo "</ul></div>";
                    // display data in table
                    // loop through results of database query, displaying them in the table
                    for ($i = $start; $i < $end; $i ++) {
                        // make sure that PHP doesn't try to show results that don't exist
                        if ($i == $total_results) {
                            break;
                        }

                        // Data printing in table
                        // for ($i = 0; $i <= sizeof($complete_log) - 1; $i++) {
                        $html = dashboard_values_output($i + 1, $complete_log [$i] [0], $complete_log [$i] [1], $complete_log [$i] [4], $complete_log [$i] [2], $complete_log [$i] [3]);
                        print ($html);
                        // }
                    }
                    $html_table = '</tbody></table></div>';
                    print ($html_table);
                } else {
                    $html = dashboard_head_output();
                    print ($html);
                }
                // pagination magic ends here
                // echo substr(plugin_dir_path(__FILE__),0,-34);
            } // end if/else
            ?>
        </form>
    </div>
    <?php
}

add_filter('registration_errors', 'xb_nmc_wp_registration_errors_entry_point', 10, 3);
add_filter('wp_authenticate_user', 'xb_nmc_wp_authenticate_user_errors_entry_point', 10, 2);
add_filter('bp_core_validate_user_signup', 'xb_nmc_bp_registration_errors_entry_point', 10, 1);
add_filter('preprocess_comment', 'xb_nmc_wp_comment_form_point', 10, 1);
add_filter('vc_user_access_check-shortcode_all', 'rcumc_nomorecaptchas_user_access_check', 10, 2);
add_action('dhvc_form_before_processor', 'rcumc_dhvc_form_before_processor');

/**
 * Action Function.
 * When DHVC Form detects that the HTTP request is
 * the action from one of its forms, it calls its processor() function.
 * processor() calls all the hooks for its Before Processor Action.
 * This is the time where we can examine the form data.
 *
 * The hidden input field added to the content within the No More Captchas
 * container is used to determine if we need to check the No More Captchas
 * results in order to determine if we are dealing with a human or a bot.
 */
function rcumc_dhvc_form_before_processor($form_id) {
    if (isset($_REQUEST [RCUMC_NOMORECAPTCHS_IN_USE])) {
        $is_human = xb_validate($_REQUEST);

        $form_name = 'dhvc-form-' . $form_id;

        if ($is_human) {
            // Human Detected
            xb_nmc_wp_bot_log_file('human', $form_name, false);
        } else {
            // BOT DETECTED
            xb_nmc_wp_bot_log_file('bot', $form_name, true);

            // Terminate processing of this form.
            // Since we got to this point in the code because a bot attempted
            // to submit this form, we can simply stop all processing.
            wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
        }
    }
}

/**
 * Filter Function that causes the "Form NoMoreCaptchas" item to
 * appear in the DHVC Form "Add Element" popup.
 *
 * This function allows us to by-pass Visual Composer's normal methods
 * for determining whether the user has permission to add the
 * No More Captchas as a form control element.
 *
 * Return a non-null value causes no additional access checking to be
 * performed on this element.
 */
function rcumc_nomorecaptchas_user_access_check($value, $tag) {
    if ($tag == RCUMC_NOMORECAPTCHAS_SHORTCODE) {
        // This is our Form Control, make sure it is visible to the user.
        return true;
    }

    // This is nto our Form Control, return the original value
    return $value;
}

function xb_nmc_wp_bot_log_file($user_type, $page_name, $isbot) {
    global $current_user;
    $user_id = $current_user->ID;
    $home_path = get_user_meta('099999', 'home_path');

    $ip = '';
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    } else {
        $ip = $_SERVER ['REMOTE_ADDR'];
    }
    $date = current_time('mysql');

    $gi = nmc_geoip_open(plugin_dir_path(__FILE__) . '/geoip-api/src/GeoIP.dat', NMC_GEOIP_STANDARD);
    // to get country name
    $log_location = nmc_geoip_country_name_by_addr($gi, $ip);
    // close the database
    nmc_geoip_close($gi);

    // ///////////////// Update Service log files to maintain weekly and monthly no. of authentications
    $auth_code = get_authentication_code();
    $domain_name = get_domain_name();

    $total_week5 = $total_week4 = $total_week3 = $total_week2 = $total_week1 = 0;
    $month = current_time('m');

    $file_name = current_time('Y-m') . '.txt';
    $com_filename = $home_path [0] . 'nmclogs/servicelog/servicelog-' . $file_name;
    $dirname = dirname($com_filename);
    if (!is_dir($dirname)) {
        mkdir($dirname, 0755, true);
        $dirname = dirname($com_filename);
    }
    if (!is_dir('nmclogs/errorLog')) {
        mkdir('nmclogs/errorLog', 0755, true);
    }
    $GLOBALS['DEFAULT_LOG'] = $home_path[0] . 'nmclogs/errorLog/nmceventlog.log';
//    if ($GLOBALS['LOGENABLE'] == 0)
//        setLogEnable();
    if (is_dir($dirname)) {
        if (($servicelogfile = @fopen($home_path [0] . 'nmclogs/servicelog/servicelog-' . $file_name, "r")) == FALSE) {
            $servicelogfile = fopen($home_path [0] . 'nmclogs/servicelog/servicelog-' . $file_name, "w+") or die("Unable to open file!");
            $servicelogVar = $auth_code . '|' . substr($auth_code, 0, 10) . '|' . $domain_name . '|0|0|0|0|0|0|0|0|0|0|0|0|' . current_time("Y-m-d") . '|0';
            fwrite($servicelogfile, $servicelogVar);
            fclose($servicelogfile);
        }
        $servicelogfile = fopen($home_path [0] . 'nmclogs/servicelog/servicelog-' . $file_name, "r+") or die("Unable to open file!");
        $line = fgets($servicelogfile);
        fclose($servicelogfile);
        $data = explode("|", $line);

        $week_number = ceil(current_time('d') / 7);

        if (sizeof($data) == 17) {
            switch ($week_number) {
                case 1 :
                    $total_week1 = $data [7] + 1;

                    break;
                case 2 :
                    $total_week1 = $data [7];
                    $total_week2 = $data [6] + 1;

                    break;
                case 3 :
                    $total_week1 = $data [7];
                    $total_week2 = $data [6];
                    $total_week3 = $data [5] + 1;

                    break;
                case 4 :
                    $total_week1 = $data [7];
                    $total_week2 = $data [6];
                    $total_week3 = $data [5];
                    $total_week4 = $data [4] + 1;
                    break;
                case 5 :
                    $total_week1 = $data [7];
                    $total_week2 = $data [6];
                    $total_week3 = $data [5];
                    $total_week4 = $data [4];
                    $total_week5 = $data [3] + 1;
                    break;
            }
        } else {
            switch ($week_number) {
                case 1 :
                    $total_week1 = 1;
                    break;
                case 2 :
                    $total_week2 = 1;
                    break;
                case 3 :
                    $total_week3 = 1;
                    break;
                case 4 :
                    $total_week4 = 1;
                    break;
                case 5 :
                    $total_week5 = 1;
                    break;
            }
        }
        $cur_month_total_auth = $total_week5 + $total_week1 + $total_week2 + $total_week3 + $total_week4;

        $todayBots = $data [9];
        $todayHumans = $data [10];
        $thisWeekBots = $data [11];
        $thisWeekHumans = $data [12];
        $thisMonthBots = $data [13];
        $thisMonthHumans = $data [14];

        $previousdate = strtotime($data [15]);
        $newformat = date('Y-m-d', $previousdate);

        $interval = date_diff(date_create($newformat), date_create(current_time("Y-m-d")));
        if (intval($interval->format("%d")) == 0) {
            if ($isbot) {
                $todayBots ++;
            } else if (!$isbot) {
                $todayHumans ++;
            }
        } else {
            if ($isbot) {
                $todayBots = 1;
                $todayHumans = 0;
            } else if (!$isbot) {
                $todayHumans = 1;
                $todayBots = 0;
            }
        }
        if ($week_number == intval($data [16])) {
            if ($isbot) {
                $thisWeekBots ++;
            } else if (!$isbot) {
                $thisWeekHumans ++;
            }
        } else {
            if ($isbot) {
                $thisWeekBots = 1;
                $thisWeekHumans = 0;
            } else if (!$isbot) {
                $thisWeekHumans = 1;
                $thisWeekBots = 0;
            }
        }
        if ($isbot) {
            $thisMonthBots ++;
        } else if (!$isbot) {
            $thisMonthHumans ++;
        }

        $servicelogfile = fopen($home_path [0] . 'nmclogs/servicelog/servicelog-' . $file_name, "w") or die("Unable to open file!");
        $servicelogVar = $auth_code . '|' . substr($auth_code, 0, 10) . '|' . $domain_name . '|' . $total_week5 . '|' . $total_week4 . '|' . $total_week3 . '|' . $total_week2 . '|' . $total_week1 . '|' . $cur_month_total_auth;
        $servicelogVar .= '|' . $todayBots . '|' . $todayHumans . '|' . $thisWeekBots . '|' . $thisWeekHumans . '|' . $thisMonthBots . '|' . $thisMonthHumans;
        $servicelogVar .= '|' . current_time("Y-m-d") . '|' . $week_number;

        fwrite($servicelogfile, $servicelogVar);
        fclose($servicelogfile);

        // ///////////////////////////////////////////////////////////

        $log_detail = $date . '|' . $ip . '|/' . $page_name . '/|' . $log_location . '|' . $user_type . "\r\n";
        $file_name = current_time('Y-m-d') . '.txt';

        $logfile = fopen($home_path [0] . 'nmclogs/' . $file_name, "a+") or die("Unable to open file!");
        fwrite($logfile, $log_detail);
        fclose($logfile);
    }
}

function xb_nmc_wp_authenticate_user_errors_entry_point($user) {
    
    // useful for debugging
    
//    $test_filename = 'xtest.txt';
//    $file = fopen(dirname(__FILE__)."/$test_filename", "w");
//    fwrite($file, json_encode($_SERVER, true));
//    fclose($file);
    
    
    /* 
     * on Mobile WordPress App since the NMC JavaScript <nmc-script.js> is not loaded the verification 
     * of the hidden inputs added by the script 'xbk0', 'xbt0', 'xbz0' fails, resulting the user is marked as a bot.
     * Fixing it, requires skipping verification while using WordPress App or any other service that requires login
     */
    if($GLOBALS['3rd_party_apps_options']->allow_3rd_party_apps_login === true) {
        if(in_array("nomorecaptchas/no-more-captchas.php", get_option('active_plugins')) && !isset($_POST['xbt0']) || !isset($_REQUEST['xbt0'])) {
            
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $allowed_user_agents = array(
                'wp-iphone',
                'wp-android',
                'ifttt',
            );
            foreach($allowed_user_agents as $agent) {
                if(strpos($user_agent, $agent) !== false) {
                    return $user;
                }
            }
            unset($user_agent, $allowed_user_agents);   
        }
    }
    
    $tempPath=getRequestMethod();

    if ($tempPath === "POST") {
        $tempPost = filter_input_array(INPUT_POST);
		
        if (!xb_validate($tempPost)) {
            xb_nmc_wp_bot_log_file("bot", "login", True);
            // Since we got to this point in the code because a bot attempted
            // to submit this form, we can simply stop all processing.
            sendException("BOT DETECTED - KILLING THE PROCESS- WHILE ENTERING COMMENT FORM ");
            wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
        } else {
            xb_nmc_wp_bot_log_file("human", "login", False);
        }
    }

    return $user;
}

function xb_nmc_wp_registration_errors_entry_point($errors) {
    $tempPath=getRequestMethod();
    if ($tempPath === "POST") {
        $tempPost = filter_input_array(INPUT_POST);
        if (!xb_validate($tempPost)) {
            xb_nmc_wp_bot_log_file("bot", "register", True);
            // Since we got to this point in the code because a bot attempted
            // to submit this form, we can simply stop all processing.
            sendException("BOT DETECTED - KILLING THE PROCESS- WHILE ENTERING COMMENT FORM ");
            wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
        } else {
            xb_nmc_wp_bot_log_file("human", "register", False);
        }
    }

    return $errors;
}

function xb_nmc_bp_registration_errors_entry_point($result = array()) {
    $tempPath=getRequestMethod();
    if ($tempPath === "POST") {
        $tempPost = filter_input_array(INPUT_POST);
        if (!xb_validate($tempPost)) {
            xb_nmc_wp_bot_log_file("bot", "register-buddypress", True);
            // Terminate processing of this form.
            // Since we got to this point in the code because a bot attempted
            // to submit this form, we can simply stop all processing.
            sendException("BOT DETECTED - KILLING THE PROCESS- WHILE ENTERING COMMENT FORM ");
            wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
        } else {
            xb_nmc_wp_bot_log_file("human", "register-buddypress", False);
        }
    }
    return $result;
}

/* * ******************************************************************************************* */

function xb_nmc_wp_comment_form_point($comment_data) {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') === false){
        $tempPath=getRequestMethod();
        if ($tempPath === "POST") {
            $tempPost = filter_input_array(INPUT_POST);
            if (!xb_validate($tempPost)) {
                xb_nmc_wp_bot_log_file("bot", "comment-form", True);
                // Since we got to this point in the code because a bot attempted
                // to submit this form, we can simply stop all processing.
                sendException("BOT DETECTED - KILLING THE PROCESS- WHILE ENTERING COMMENT FORM ");
                wp_die('<p><strong>Insecure connection detected</strong> We strongly believe in and support a secure internet. Please check that your connection is encrypted (read more on the subject <a href="https://en.wikipedia.org/wiki/HTTPS#Difference_from_HTTP" target="_blank">here</a> or <a href="https://www.techopedia.com/definition/13266/secure-connection" target="_blank">here</a>). Alternatively, if no certificate is available for you, you can delete the cookies your website stores on your browser (<a href="https://www.digitaltrends.com/computing/how-to-delete-cookies/" target="_blank">here\'s how to do it</a>) or you can use a different username to login.</p><hr /><p style="text-align:center;">If you can\'t read this, it means <strong>you\'re a bot and we caught you</strong> in the act.</p><hr />', 'Anti-Bot Protection');
            } else {
                xb_nmc_wp_bot_log_file("human", "comment-form", False);
            }
        }
    }
    return $comment_data;
}

function sendException($exceptionMsg) {
    try {
        throw new Exception($exceptionMsg);
    } catch (Exception $e) {
        error_log("Caught $e");
    }
}


/**
 * Handling fatal error
 *
 * @return void
 */
function fatalErrorHandler() {
    // Getting last error
    $error = error_get_last();

    // Checking if last error is a fatal error
    if ((($error ['type'] === E_ERROR) || ($error ['type'] === E_USER_ERROR)) && strpos($error['file'], 'nomorecaptchas') !== false) {
        // Here we handle the error, displaying HTML, logging, ...
		echo 'Uh oh, something happened! Please contact the site administrator to report the problem.';
    }
}

// Registering shutdown function
register_shutdown_function('fatalErrorHandler');

//trigger_error("Fatal error", E_USER_ERROR);
//exit();

function getSearchCriteria() {
    $RequestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
//    echo $RequestMethod;
    $searchCriteria = "";

    if ($RequestMethod === 'POST') {
        $srchctr = array_key_exists('searchCriteria', $_POST) ? trim($_POST ['searchCriteria']) : null;
        $searchCriteria = strtolower($srchctr);
    } else {
        $srchctr = array_key_exists('searchCriteria', $_GET) ? trim($_GET ['searchCriteria']) : null;
        $searchCriteria = strtolower($srchctr);
    }
//    var_dump($searchCriteria);
    return $searchCriteria;
}
/*Return Request menthod*/
function getRequestMethod() {
    if (filter_has_var(INPUT_SERVER, "REQUEST_METHOD")) {
        $tempPath = filter_input(INPUT_SERVER, "REQUEST_METHOD", FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
    } else {
        if (isset($_SERVER["REQUEST_METHOD"]))
            $tempPath = filter_var($_SERVER["REQUEST_METHOD"], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        else
            $tempPath = null;
    }
    return $tempPath;
}
function xb_nmc_set_error_handling_level(){
      
    $logfile = $GLOBALS['home_path'].'nmclogs/errorLog/nmceventlog.log';
    $level=$GLOBALS['nmc_error_level'];
    setErrorReporting($level,$logfile);
}
?>