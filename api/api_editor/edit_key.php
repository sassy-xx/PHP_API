<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class edit_key {
        public bool $success;
        public mixed $data;
        public mixed $error;
        public int $http_code;
        public function __construct($data) {
            // initialise the endpoint
            $init = self::init($data);
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
        
        private static function init($data) {
            foreach($data as $k => $v) {
                $$k = $v;
            }
            // look up all of the permissions associated with the passed api key
            if(isset($x_api_key) && !empty($x_api_key) && isset($x_api_key_new) && !empty($x_api_key_new) && isset($x_api_secret) && !empty($x_api_secret) && isset($x_key_enabled) && isset($x_permissions)) {
                if($x_key_enabled == 'true') {
                    $x_key_enabled = (int)1;
                } else {
                    $x_key_enabled = (int)0;
                }
                $parray = [$x_api_key_new, $x_api_secret, $x_key_enabled, $x_api_key];
                $bstring = 'ssii';
                $sql = "UPDATE api.api_keys SET api_key = ?, secret_key = ?, key_enabled = ? WHERE api_key = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_update($sql, $bstring, $parray);
                if($mysql->result == -1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => UPDATE_DB_ERROR
                        ]
                    ];
                }
                // remove old permissions from the old key
                $parray = [$x_api_key];
                $bstring = 's';
                $sql = "DELETE FROM api.api_permissions WHERE api_key = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_update($sql, $bstring, $parray);
                if($mysql->result == -1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => UPDATE_DB_ERROR
                        ]
                    ];
                }
                // insert new permissions
                foreach($x_permissions as $k => $v) {
                    $parray = [$x_api_key_new, $v];
                    $bstring = 'si';
                    $sql = "INSERT INTO api.api_permissions (api_key, endpoint_id) VALUES (?, ?)";
                    $mysql = new mysql_(DEFAULT_DB_CONN);
                    $mysql->sql_insert($sql, $bstring, $parray);
                    if($mysql->result->affected_rows == -1) {
                        return [
                            'success' => false,
                            'data' => null,
                            'error' => [
                                'message' => INSERT_DB_ERROR
                            ]
                        ];
                    }
                }
                return [
                    'success' => true,
                    'data' => null,
                    'error' => false
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Incorrect parameters passed to extermal endpoint.'
                ];
            }
        }
    }
?>