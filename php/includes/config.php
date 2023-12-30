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

    // main language section
        $bad_request_method = 'The request method is not valid!';
        $bad_api_request = 'The API request is not valid!';
        $bad_api_key = 'API key is missing or invalid!';
        $bad_api_endpoint = 'API endpoint is missing or invalid!';
        $bad_api_secret_key = 'API secret key is missing or invalid!';
        $bad_api_method = 'API method is missing or invalid!';
        $bad_api_token = 'API token is missing or invalid!';
        $api_token_expired = 'API token has expired!';
        $bad_permissions ='Your API key is not authorized to use this endpoint!';
        $db_exists = 'The database already exists, it would be unwise to proceed. Stopping!';
        $db_insert_err = 'Something went wrong inserting data into the database.';
        $db_select_err = 'Something went wrong selecting data from the database.';
        $db_delete_err = 'Something went wrong deleting data from the database.';
        $db_udpate_err = 'Something went wrong updating data in the database.';
        $bad_params = 'Incorrect parameters passed.';
        $unknown_error = 'An unkown error has occored!';

    // definitions of global constants
        define('BAD_REQUEST_METHOD', $bad_request_method);
        define('BAD_API_REQUEST', $bad_api_request);
        define('BAD_API_KEY', $bad_api_key);
        define('BAD_API_ENDPOINT', $bad_api_endpoint);
        define('BAD_API_METHOD', $bad_api_method);
        define('BAD_API_SECRET_KEY', $bad_api_secret_key);
        define('BAD_API_TOKEN', $bad_api_token);
        define('BAD_PERMISSIONS', $bad_permissions);
        define('UNKNOWN_ERROR', $unknown_error);
        define('API_TOKEN_EXPIRED', $api_token_expired);
        define('DB_ALREADY_EXISTS', $db_exists);
        define('INSERT_DB_ERROR', $db_insert_err);
        define('SELECT_DB_ERROR', $db_select_err);
        define('DELETE_DB_ERROR', $db_delete_err);
        define('UPDATE_DB_ERROR', $db_udpate_err);
        define('INCORRECT_PARAMETERS', $bad_params);
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
        }
    }
?>