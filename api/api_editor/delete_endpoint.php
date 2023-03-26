<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class delete_endpoint {
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
            if(isset($x_endpoint_id) && !empty($x_endpoint_id)) {
                $parray = [$x_endpoint_id];
                $bstring = 'i';
                $sql = "DELETE FROM api.api_endpoints e WHERE e.endpoint_id = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_delete($sql, $bstring, $parray);
                if($mysql->result !== 1) {
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
                    'error' => 'Endpoint ID not passed to endpoint.'
                ];
            }
        }
    }
?>