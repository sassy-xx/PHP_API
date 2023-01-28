<?php
    // class file for the api handler
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    class api_handler {
        private string $api_key;
        private string $api_secret_key;
        private string $api_endpoint;
        private string $api_token;
        private string $request_method;
        public array $permissions;

        public function __construct($api_key, $api_secret_key, $api_endpoint, $api_token, $request_method) {
            // Construct the API hanlder class object
            $this->api_key = $api_key;
            $this->api_secret_key = $api_secret_key;
            $this->api_endpoint = $api_endpoint;
            $this->api_token = $api_token;
            $this->request_method = $request_method;
        }

        public function check_request_method() {
            // check the method is not empty
            if(empty(trim($this->request_method))) {
                self::error(403, BAD_REQUEST_METHOD);
            }
            // check the method is valid
            if(!in_array($this->request_method, ALLOWED_REQUEST_METHODS)) {
                self::error(403, BAD_REQUEST_METHOD);
            }
        }

        public function check_key() {
            if(empty($this->api_key)) {
                self::error(403, BAD_API_KEY);
            }
            $mysql = new mysql_('localhost');
            $parray = [$this->api_key];
            $bstring = 's';
            $sql = "SELECT keys.api_key FROM api.api_keys AS `keys` WHERE keys.api_key = ? AND keys.key_enabled = 1";
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                self::error(403, BAD_API_KEY);
            }
        }
        
        public function check_secret_key() {
            if(empty($this->api_key)) {
                self::error(403, BAD_API_KEY);
            }
            if(empty($this->api_secret_key)) {
                self::error(403, BAD_API_SECRET_KEY);
            }
            // Lets make sure that the secret key they provided is valid
            $parray = [$this->api_key, $this->api_secret_key];
            $bstring = 'ss';
            $sql = "SELECT
                        ky.api_key
                    FROM
                        api.api_keys ky
                    WHERE
                        ky.api_key = ?
                        AND ky.secret_key = ?;";
            $mysql = new mysql_('localhost');
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                self::error(403, BAD_API_SECRET_KEY);
            }
        }
        public function check_endpoint() {
            if(empty(trim($this->api_endpoint))) {
                self::error(403, BAD_API_ENDPOINT);
            }
            // check this endpoint is enabled
            $mysql = new mysql_('localhost');
            $parray = [$this->api_endpoint];
            $bstring = 's';
            $sql = "SELECT endpoints.endpoint_name FROM api.api_endpoints AS `endpoints` WHERE endpoints.endpoint_name = ? AND endpoints.endpoint_enabled = 1";
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                self::error(403, BAD_API_ENDPOINT);
            }
        }

        public function check_token() {
            if(empty($this->api_token)) {
                self::error(403, BAD_API_TOKEN);
            }
            // validate the API token
            $api_key = $this->api_key;
            $api_token = $this->api_token;
            $key = self::return_secret_key($api_key);
            if(!$key['success']) {
                self::error('403', BAD_API_KEY);
            }
            $key = $key['data']['secret_key'];
            // try to decode the JWT
            try {
                $payload = JWT::decode($api_token, new Key($key, 'HS512'));
            } catch (Exception $e){
                if($e->getMessage() == "Expired token"){
                    self::error(403, API_TOKEN_EXPIRED);
                } else {
                    self::error(403, UNKNOWN_ERROR);
                }
            }
            // TODO create rotatable constant keys which are pulled from a secure location (encryption store?)
            if($payload->iss !== 'api_internal_022444' || $payload->aud !== 'api_internal_022445') {
                self::error(403, BAD_API_TOKEN);
            }
        }


        private static function return_secret_key($api_key) {
            $parray = [$api_key];
            $bstring = 's';
            $sql = "SELECT keys.secret_key FROM api.api_keys AS `keys` WHERE keys.api_key = ? AND keys.key_enabled = 1";
            $mysql = new mysql_('localhost');
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => BAD_API_KEY
                    ]
                ];
            }
            foreach($mysql->result as $k => $v) {
                // Retur the secret key
                return [
                    'success' => true,
                    'data' => [
                        'secret_key' => $v['secret_key'],
                    ],
                    'error' => false
                ];
            }
        }

        public function init_permissions() {
            if(empty($this->api_key) || empty($this->api_endpoint) || empty($this->api_secret_key)) {
                self::error(403, BAD_API_REQUEST);
            }
            // Return this keys permissions
            $parray = [$this->api_key];
            $bstring = 's';
            $sql = "SELECT
                        enp.endpoint_id,
                        enp.endpoint_name
                    FROM
                        api.api_permissions pm
                    JOIN api.api_endpoints enp ON
                        pm.endpoint_id = enp.endpoint_id
                    WHERE
                        pm.api_key = ?";
            $mysql_ = new mysql_('localhost');
            $mysql_->sql_select($sql, $bstring, $parray);
            if($mysql_->result->num_rows > 0) {
                foreach($mysql_->result as $k => $v) {
                    if(!isset($this->permissions[$v['endpoint_id']])) {
                        $this->permissions[$v['endpoint_id']] = [];
                    }
                    $this->permissions[$v['endpoint_id']] = xss::xss($v['endpoint_name']);
                }
            } else {
                $this->permissions = [];
            }
        }

        public function execute() {
            if(empty($this->api_key) || empty($this->api_endpoint)) {
                // print_r($this);
                self::error(403, UNKNOWN_ERROR);
            }
            // search the root endpoint directory for the specified endpoint
            $endpoints = new file_finder(DOCUMENT_ROOT.'/api/');
            foreach($endpoints->files as $v) {
                if(str_contains($v, $this->api_endpoint.'.php')) {
                    // Require the API endpoint file
                    require_once($v);
                    // Initialise the endpoint
                    if($this->api_endpoint == 'get_token') {
                        $api_endpoint_response = new get_token($this->api_key);
                    } else {
                        $api_endpoint_response = new $this->api_endpoint;
                    }
                    self::return_endpoint_response($api_endpoint_response);
                }
            }
        }

        public static function return_endpoint_response($api_endpoint_response) {
            if(!empty($api_endpoint_response->http_code)) {
                http_response_code($api_endpoint_response->http_code);
            }
            if(!isset($api_endpoint_response->success) || !isset($api_endpoint_response->data) || !isset($api_endpoint_response->error)) {
                // The API endpoint returned without setting the expected public variables
                trigger_error('The API endpoint returned without setting the expected public variables!', E_USER_NOTICE);
            }
            echo json_encode([
                'success' => $api_endpoint_response->success,
                'data' => $api_endpoint_response->data,
                'error' => $api_endpoint_response->error
            ]);
            die();
        }

        public static function error($http_code, $error) {
            http_response_code($http_code);
            echo json_encode([
                'success' => false,
                'data' => null,
                'error' => $error
            ]);
            die();
        }
    }
?>