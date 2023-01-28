<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class return_date {
        public bool $success;
        public array $data;
        public bool $error;
        public int $http_code;
        public function __construct() {
            // initialise the endpoint
            $init = self::init();
            $this->success = $init['success'];
            $this->data = $init['data'];
            $this->error = $init['error'];
        }
        
        private static function init() {
            // this endpoint is very basic, and does not need any other internal calss functions really.
            $date_time = new DateTime();
            return [
                'success' => true,
                'data' => [
                    'timestamp' => date_format($date_time, 'Y-m-d H:i:s'),
                    'date_uk' => date_format($date_time, 'd-m-Y'),
                    'date_us' => date_format($date_time, 'm-d-Y'),
                    'time_12' => date_format($date_time, 'h:i:s'),
                    'time_24' => date_format($date_time, 'H:i:s')
                ],
                'error' => false
            ];
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
    }
?>