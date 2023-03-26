<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class create_endpoint {
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
            if(isset($x_endpoint_name) && !empty($x_endpoint_name) && isset($x_endpoint_enabled)) {
                switch($x_endpoint_enabled) {
                    case true:
                        $x_endpoint_enabled = 1;
                    break;
                    case false:
                        $x_endpoint_enabled = 0;
                    break;
                }
                $parray = [$x_endpoint_name, $x_endpoint_enabled];
                $bstring = 'ss';
                $sql = "INSERT INTO api.api_endpoints (endpoint_name, endpoint_enabled) VALUES (?, ?)";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_insert($sql, $bstring, $parray);
                if($mysql->result->affected_rows !== 1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => SELECT_DB_ERROR
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
                    'error' => 'API key not passed to endpoint.'
                ];
            }
        }
    }
?>