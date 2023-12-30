<?php
    // Main config file

    // Load the .env variables and create constants from them
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'../../../config');
        $dotenv->load();
        $dotenv->required(['ENVIRONMENT_TYPE']);
    } catch(\Exception $e) {
        die('Failed to load the required environment configuration file. Cannot continue.');
    }

    // Create constants from the loaded environment variables
    foreach($_ENV as $k => $v) {
        // Ensure this constant has not already been defined
        if(!defined($k)) {
            // Check if this is the NO_TOKEN_ENDPOINTS env var as this will need json_decoding
            if($k === "NO_TOKEN_ENDPOINTS") {
                define("NO_TOKEN_ENDPOINTS", json_decode($_ENV['NO_TOKEN_ENDPOINTS'], true));
            } else if($k === "ALLOWED_REQUEST_METHODS") {
                define("ALLOWED_REQUEST_METHODS", json_decode($_ENV['ALLOWED_REQUEST_METHODS'], true));
            } else {
                define($k, $v);
            }
        }
    }

    if(FORCE_JSON) {
        header('Content-type: application/json');
    }

    // require the main functions file (global functions etc)
    require_once('./php/includes/functions.php');
    
    // require the main class list file (refreences all main class files)
    require_once('./php/includes/class_loader.php');

    // Require everything in the php/includes/auto_load direcotry
    $auto_load = new file_finder('./php/includes/auto_loader/');
    foreach($auto_load->files as $v) {
        require_once($v);
    }

    // ENVIRONMENT_TYPE check (stops dangerous combos of live and setup vars)
    if(ENVIRONMENT_TYPE === 'local') {
        // Enable all debugging of MySQL errors
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        if(FIRST_TIME_SETUP) {
            $first_time_setup = api_handler::first_time_setup();
            if(!$first_time_setup['success']) {
                trigger_error($first_time_setup['error'], E_USER_WARNING);
            }
            die();
        }
    }
?>