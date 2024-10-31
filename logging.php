<?php

function loggingPage() {

    $logfile = $GLOBALS['home_path'] . 'nmclogs/errorLog/nmceventlog.log';
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-admin/options-general.php?page=xb_nmc_config&tab=logging_page') !== false) {

        $check_arry = isset($_POST ['xb_nmc_slug_log']) ? $_POST ['xb_nmc_slug_log'] : 'aaa';
        $RequestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
        $selectedFiles = (array) (isset($_POST ['select_files']) ? $_POST ['select_files'] : '');
        $arryasize = sizeof($selectedFiles);
//$check_arry=$_POST['xb_nmc_slug_log'];
// if you want logs uncomment below two lines
        
        if ($check_arry !== 'aaa') {

            if (sizeof($check_arry) > 0) {

                if ($check_arry ['logOn'] == "1") {

                    $level = (isset($check_arry ['notice']) ? $check_arry ['notice'] : '0');
                    $level = $level . (isset($check_arry ['parse']) ? $check_arry ['parse'] : '0');
                    $level = $level . (isset($check_arry ['warn']) ? $check_arry ['warn'] : '0');
                    $level = $level . (isset($check_arry ['error']) ? $check_arry ['error'] : '0');

                    setErrorReporting($level, $logfile);
                }
            } else {
                setErrorReporting('0000', $logfile);
                ini_set('log_errors', 0);
            }
        } elseif ($check_arry === 'aaa' && $RequestMethod == 'POST' && isset($_POST['formname'])==Null ) {
            setErrorReporting('0000', $logfile);
            ini_set('log_errors', 0);
        }
        
        if($RequestMethod == 'POST' && isset($_POST['delete_log_files']) && $_POST['delete_log_files']) {
            
            $home_path = get_user_meta( '099999', 'home_path' );
            $delete_logs_older_than = '60';
            $limit_date = date("Y-m-d", strtotime("$delete_logs_older_than days ago"));

            $dir = opendir( $home_path [0] . 'nmclogs/' );
            $logs_path = $home_path[0] . 'nmclogs/';
            $deleted_logs = 0;
            while(false != ($file = readdir($dir))) {
                if(preg_match('/([0-9]{4}\-[0-9]{2}-[0-9]{2})/', $file, $match)) {
                    if(trim($match['1']) < $limit_date) {
                        unlink("{$logs_path}{$file}");
                        $deleted_logs ++;
                    }
                }
            }
            if($deleted_logs) {
                echo "nmc_deleted_logs_no::{$deleted_logs}::";
            }
        }

       
        /*
        if ($arryasize >= 1 && $RequestMethod == 'POST' && $selectedFiles[0] != null) {
            if (isset($selectedFiles) && is_array($selectedFiles) !== null) {
                foreach ($selectedFiles as $filename) {
                    // To prevent traversal attacks, you need to validate $filename
                    // For example, if it would only be expected to be alphanumeric:
                    //echo $filename;
                    $do = unlink($filename);
                }
                $_POST ['select_files'] = null;
                wp_delete_post('select_files');
            }
        }
         * 
         */
    }
    return;
}

/* function parse through the error-reporting-level and set the error_reporting() accordingly */

function setErrorReporting($level, $logfile) {
    ini_set('log_errors', 1);
    ini_set('error_log', $logfile);

    $GLOBALS['LOGENABLE'] = 1;

    switch ($level) {
        case '0001':
            error_reporting(E_ERROR);
            break;
        case '0010':
            error_reporting(E_WARNING);
            break;
        case '0011':
            error_reporting(E_WARNING | E_ERROR);
            break;
        case '0100':
            error_reporting(E_PARSE);
            break;
        case '0101':
            error_reporting(E_PARSE | E_ERROR);
            break;
        case '0110':
            error_reporting(E_PARSE | E_WARNING);
            break;
        case '0111':
            error_reporting(E_PARSE | E_WARNING | E_ERROR);
            break;
        case '1000':
            error_reporting(E_NOTICE);
            break;
        case '1001':
            error_reporting(E_NOTICE | E_ERROR);
            break;
        case '1010':
            error_reporting(E_NOTICE | E_WARNING);
            break;
        case '1011':
            error_reporting(E_NOTICE | E_WARNING | E_ERROR);
            break;
        case '1100':
            error_reporting(E_NOTICE | E_PARSE);
            break;
        case '1101':
            error_reporting(E_NOTICE | E_PARSE | E_ERROR);
            break;
        case '1110':
            error_reporting(E_NOTICE | E_PARSE | E_WARNING);
            break;
        case '1111':
            error_reporting(E_NOTICE | E_PARSE | E_WARNING | E_ERROR);
            break;
        default:
            error_reporting(0);
    }
    update_user_meta('099999', 'nmc_error_level', $level);
}

?>