<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class generate_key {
        public bool $success;
        public mixed $data;
        public mixed $error;
        public int $http_code;
        public string $api_key;
        public string $api_secret_key;
        public int $api_key_enabled;
        public function __construct($data) {
            foreach($data as $k => $v) {
                $$k = $v;
            }
            if(!isset($x_api_key_enabled)) {
                api_handler::error(400, INCORRECT_PARAMETERS);
            }
            if(!isset($permissions)) {
                api_handler::error(400, INCORRECT_PARAMETERS);
            }
            // initialise the endpoint;
            $init = self::init($x_api_key_enabled, $permissions);
            if((isset($init['success']) || $init['success'] === false) && (isset($init['data']) || $init['data'] === null) && isset($init['error'])) {
                $this->success = $init['success'];
                $this->data = $init['data'];
                $this->error = $init['error'];
            } else {
                $this->success = false;
                $this->data = null;
                $this->error = 'Bad endpoint structure. Missing basic array return.';
            }
        }
        
        private static function init($x_api_key_enabled, $permissions) {
            // generate an API key
            $api_key = bin2hex(random_bytes(64));

            // Generate a secret key
            $api_secret_key = bin2hex(random_bytes(256));

            // Store in database.
            $parray = [$api_key, $api_secret_key, $x_api_key_enabled];
            $bstring = 'ssi';
            $sql = "INSERT INTO api.api_keys VALUES (?, ?, CURRENT_TIMESTAMP(), NULL, ?)";
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_insert($sql, $bstring, $parray);
            if($mysql->result->affected_rows !== 1) {
                // Close the connection
                mysql_::close($mysql->result);
                return [
                    'success' => false,
                    'data' => null,
                    'error' => INSERT_DB_ERROR
                ];
            }
            // Close the connection
            mysql_::close($mysql->result);
            // set the permissions
            foreach($permissions as $k => $v) {
                $parray = [$api_key, $v];
                $bstring = 'si';
                $sql = "INSERT INTO api.api_permissions (api_key, endpoint_id) VALUES (?, ?)";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_insert($sql, $bstring, $parray);
                if($mysql->result->affected_rows !== 1) {
                    // Close the connection
                    mysql_::close($mysql->result);
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => INSERT_DB_ERROR
                    ];
                }
            }
            return [
                'success' => true,
                'data' => [
                    'api_key' => $api_key,
                    'api_secret_key' => $api_secret_key
                ],
                'error' => false
            ];
        }
    }
?>