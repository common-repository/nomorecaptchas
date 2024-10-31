<?php

    header('Content-Type: application/json');
    
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'change_nmc_options':
                if(!isset($_POST['value']) || !in_array($_POST['value'], array(0, 1))) {
                    echo json_encode(array('status' => false));
                    exit();
                }
                $file = fopen("options.txt", "w");
                $option = json_encode(array('allow_3rd_party_apps_login' => $_POST['value'] == 1 ? true : false));
                fwrite($file, $option);
                fclose($file);
                
                echo json_encode(array('status' => true));
                break;
        }
    }

