<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class return_endpoints {
        public bool $success;
        public mixed $data;
        public mixed $error;
        public int $http_code;
        public function __construct() {
            // initialise the endpoint
            $init = self::init();
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
        
        private static function init() {
            // look up all of the current endpoints and return them in a sesnible format
            $sql = "SELECT
                        *
                    FROM
                        api.api_endpoints e";
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_select($sql);
            if($mysql->result->num_rows < 1) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => SELECT_DB_ERROR
                    ]
                ];
            }
            $return_array = [];
            foreach($mysql->result as $k => $v) {
                foreach($v as $kk => $vv) {
                    if(!isset($return_array[xss::xss($k)][xss::xss($kk)])) {
                        $return_array[xss::xss($k)][xss::xss($kk)] = [];
                    }
                    $return_array[xss::xss($k)][xss::xss($kk)] = xss::xss($vv);
                }
            }
            return [
                'success' => true,
                'data' => $return_array,
                'error' => false
            ];
        }
    }
?>