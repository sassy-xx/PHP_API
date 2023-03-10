<?php // Main configuration file

    // debug settings
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Force appication/json returns from the server
        header('Content-type: application/json');

    // Force the application to try to create the DB structure before it looks at any requests (FIRST TIME SETUP ONLY!)
        $first_time_setup = false;
    
    // file paths
        $root = $_SERVER['DOCUMENT_ROOT'].'/';
    
    // SQL detials
    
        // localhost db connection
        $local_hostname = 'localhost';
        $local_username = 'root';
        $local_password = '10584066';

        // live db connection
        $live_hostname = '';
        $live_username = '';
        $live_password = '';

        // read replica db connection
        $readrep_hostname = '';
        $readrep_username = '';
        $readrep_password = '';
    
    // main API configuration section

        // Token expiration time
        $token_expiry_time = 1800; // seconds to token expiry

        // Endpoints which will not require a token
        $no_token_endpoints = [
            'get_token'
        ];

        // Methods of request which are allowed
        $allowed_request_methods = [
            'GET',
            'POST'
        ];

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
        $bad_params = 'Incorrect parameters passed.';
        $unknown_error = 'An unkown error has occored!';

    // definitions of global constants
        define('DOCUMENT_ROOT', $root);
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
        define('API_TOKEN_EXEMPT', $no_token_endpoints);
        define('ALLOWED_REQUEST_METHODS', $allowed_request_methods);
        define('TOKEN_EXPIRE_TIME', $token_expiry_time);
        define('DB_LOCAL_HOSTNAME', $local_hostname);
        define('DB_LOCAL_USERNAME', $local_username);
        define('DB_LOCAL_PASSWORD', $local_password);
        define('DB_LIVE_HOSTNAME', $live_hostname);
        define('DB_LIVE_USERNAME', $live_username);
        define('DB_LIVE_PASSWORD', $live_password);
        define('DB_READREP_HOSTNAME', $readrep_hostname);
        define('DB_READREP_USERNAME', $readrep_username);
        define('DB_READREP_PASSWORD', $readrep_password);
        define('DB_ALREADY_EXISTS', $db_exists);
        define('INSERT_DB_ERROR', $db_insert_err);
        define('INCORRECT_PARAMETERS', $bad_params);
    // require the main functions file (global functions etc)
    require_once(DOCUMENT_ROOT.'/php/includes/functions.php');
    
    // require the main class list file (refreences all main class files)
    require_once(DOCUMENT_ROOT.'/php/includes/class_loader.php');

    // Require everything in the php/includes/auto_load direcotry
    $auto_load = new file_finder(DOCUMENT_ROOT.'/php/includes/auto_loader/');
    foreach($auto_load->files as $v) {
        require_once($v);
    }
    if($first_time_setup === true) {
        $first_time_setup = api_handler::first_time_setup();
        if(!$first_time_setup['success']) {
            trigger_error($first_time_setup['error'], E_USER_WARNING);
        }
    }
?>