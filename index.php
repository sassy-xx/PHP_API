<?php
    session_start();
    // require the main configuration file (constants/globals/requires etc)
    require_once($_SERVER['DOCUMENT_ROOT'].'/php/includes/config.php');
    
    // init empty defaults

    // mapped test vals for now
    $request_method = $_SERVER['REQUEST_METHOD'];
    $api_key = '';
    $api_secret_key = '';
    $api_endpoint = '';
    $api_token = '';
    $data = [];
    // set easy vars
    foreach($_REQUEST as $k => $v) {
        $$k = $v;
        $data[$k] = $v;
    }
    
    if(isset($api_endpoint) && !empty(trim($api_endpoint))) {
    } else {
        if($_SERVER['PHP_SELF'] !== 'index.php') {
            // API endpoint file is were we came from
                $api_endpoint = explode('?', str_replace('.php', '', basename($_SERVER['PHP_SELF'])))[0];
        } else {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'data' => null,
                'error' => BAD_API_REQUEST
            ]);
            die();
        }
    }

    // inittialise the API handler class
    $api = new api_handler($api_key, $api_secret_key, $api_endpoint, $api_token, $request_method, $data);
    // Check the request method is valid
    $api->check_request_method();
    // Check the API key is valid
    $api->check_key();
    // Check the secret key is valid
    $api->check_secret_key();
    // Check the API endpoint is valid
    $api->check_endpoint();
    // Check the API key has permission to access the requested endpoint
    $api->init_permissions();
    // Check if the API endpoint being executed requires a token
    if(!in_array($api_endpoint, API_TOKEN_EXEMPT)) {
        $api->check_token();
        if(!in_array($api_endpoint, $api->permissions)) {
            api_handler::error(403, BAD_PERMISSIONS);
        }
    }
    // Execute the API request
    $api->execute();
?>