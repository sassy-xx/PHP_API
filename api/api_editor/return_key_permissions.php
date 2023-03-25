<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class return_key_permissions {
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
                $sql = "SELECT
                            e.endpoint_id,
                            e.endpoint_name,
                            CASE
                                WHEN p.api_key IS NOT NULL THEN 1
                                ELSE 0
                            END AS permission_granted
                        FROM
                            api.api_endpoints e
                        LEFT JOIN api.api_permissions p ON
                            e.endpoint_id = p.endpoint_id
                            AND p.api_key = ?
                        WHERE
                            e.endpoint_id != 1";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_select($sql, $bstring, $parray);
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
                        if(!isset($return_array[$k][$kk])) {
                            $return_array[$k][$kk] = [];
                        }
                        $return_array[$k][$kk] = $vv;
                    }
                }
                return [
                    'success' => true,
                    'data' => $return_array,
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