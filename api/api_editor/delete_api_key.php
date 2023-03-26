<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class delete_api_key {
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
            if(isset($x_api_key) && !empty($x_api_key)) {
                $parray = [$x_api_key];
                $bstring = 's';
                $sql = "DELETE FROM api.api_permissions p WHERE p.api_key = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_delete($sql, $bstring, $parray);
                if($mysql->result == -1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => DELETE_DB_ERROR
                        ]
                    ];
                }
                $parray = [$x_api_key];
                $bstring = 's';
                $sql = "DELETE FROM api.api_keys k WHERE k.api_key = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_delete($sql, $bstring, $parray);
                if($mysql->result == -1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => DELETE_DB_ERROR
                        ]
                    ];
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
                    'error' => 'API Key not passed to endpoint.'
                ];
            }
        }
    }
?>