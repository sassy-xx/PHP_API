<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class get_token {
        public bool $success;
        public mixed $data;
        public mixed $error;
        public int $http_code;
        private string $api_key;
        public function __construct($api_key) {
            // initialise the endpoint
            $this->api_key = $api_key;
            $token_result = self::init($this->api_key);
            if((isset($token_result['success']) || $token_result['success'] === false) && (isset($token_result['data']) || $token_result['data'] === null) && isset($token_result['error'])) {
                $this->success = $token_result['success'];
                $this->data = $token_result['data'];
                $this->error = $token_result['error'];
            } else {
                $this->success = false;
                $this->data = null;
                $this->error = 'Bad endpoint structure. Missing basic array return.';
            }
        }
        
        private static function init($api_key) {
            // retrieve the secret_key for this api_key
            $key = self::return_secret_key($api_key);
            if(!$key['success']) {
                api_handler::error('403', BAD_API_KEY);
            }
            $key = $key['data']['secret_key'];
            $payload = [
                'iss' => 'api_internal_022444',
                'aud' => 'api_internal_022445',
                'exp' => time() + TOKEN_EXPIRE_TIME
            ];
            $jwt = JWT::encode($payload, $key, 'HS512');
            return [
                'success' => true,
                'data' => [
                    'token' => $jwt
                ],
                'error' => false
            ];
        }

        private static function return_secret_key($api_key) {
            $parray = [$api_key];
            $bstring = 's';
            $sql = "SELECT keys.secret_key FROM api.api_keys AS `keys` WHERE keys.api_key = ? AND keys.key_enabled = 1";
            $mysql = new mysql_(DEFAULT_DB_CONN);
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
                // Return the secret key
                return [
                    'success' => true,
                    'data' => [
                        'secret_key' => xss::xss($v['secret_key']),
                    ],
                    'error' => false
                ];
            }
        }
    }
?>