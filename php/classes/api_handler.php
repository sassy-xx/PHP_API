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
        private $data;
        public array $permissions;

        public function __construct($api_key, $api_secret_key, $api_endpoint, $api_token, $request_method, $data) {
            // Construct the API hanlder class object
            $this->api_key = $api_key;
            $this->api_secret_key = $api_secret_key;
            $this->api_endpoint = $api_endpoint;
            $this->api_token = $api_token;
            $this->request_method = $request_method;
            $this->data = $data;
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
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $parray = [$this->api_key];
            $bstring = 's';
            $sql = "SELECT keys.api_key FROM api.api_keys AS `keys` WHERE keys.api_key = ? AND keys.key_enabled = 1";
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                mysql_::close($mysql->result);
                self::error(403, BAD_API_KEY);
            }
            mysql_::close($mysql->result);
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
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                mysql_::close($mysql->result);
                self::error(403, BAD_API_SECRET_KEY);
            }
            mysql_::close($mysql->result);
        }
        public function check_endpoint() {
            if(empty(trim($this->api_endpoint))) {
                self::error(403, BAD_API_ENDPOINT);
            }
            // check this endpoint is enabled
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $parray = [$this->api_endpoint];
            $bstring = 's';
            $sql = "SELECT endpoints.endpoint_name FROM api.api_endpoints AS `endpoints` WHERE endpoints.endpoint_name = ? AND endpoints.endpoint_enabled = 1";
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                mysql_::close($mysql->result);
                self::error(403, BAD_API_ENDPOINT);
            }
            mysql_::close($mysql->result);
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
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows !== 1) {
                mysql_::close($mysql->result);
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
                mysql_::close($mysql->result);
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
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_select($sql, $bstring, $parray);
            if($mysql->result->num_rows > 0) {
                foreach($mysql->result as $k => $v) {
                    if(!isset($this->permissions[$v['endpoint_id']])) {
                        $this->permissions[$v['endpoint_id']] = [];
                    }
                    $this->permissions[$v['endpoint_id']] = xss::xss($v['endpoint_name']);
                }
                mysql_::close($mysql->result);
            } else {
                $this->permissions = [];
                mysql_::close($mysql->result);
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
                        $api_endpoint_response = new $this->api_endpoint($this->data);
                    }
                    self::return_endpoint_response($api_endpoint_response);
                }
            }
        }

        public static function return_endpoint_response($api_endpoint_response) {
            if(!empty($api_endpoint_response->http_code)) {
                http_response_code($api_endpoint_response->http_code);
            }
            if(!isset($api_endpoint_response->success) || !isset($api_endpoint_response->error)) {
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

        public static function first_time_setup() {
            // Create the API backend structure
            echo 'FIRST TIME SETUP IS ON!';
            $sql[] = <<<EOM
                CREATE DATABASE `api`;
            EOM;
            $sql[] = <<<EOM
                DROP TABLE IF EXISTS `api_endpoints`;
            EOM;
            $sql[] = <<<EOM
                CREATE TABLE `api_endpoints` (
                    `endpoint_id` int NOT NULL AUTO_INCREMENT,
                    `endpoint_name` varchar(100) NOT NULL,
                    `endpoint_enabled` int NOT NULL DEFAULT '1',
                    `created_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_timestamp` datetime NOT NULL,
                    PRIMARY KEY (`endpoint_id`)
                ) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
            EOM;
            $sql[] = <<<EOM
                INSERT INTO `api_endpoints` VALUES (1,'get_token',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(2,'create_endpoint',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(3,'delete_api_key',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(4,'delete_endpoint',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(5,'edit_endpoint',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(6,'edit_key',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(7,'generate_key',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(8,'return_endpoints',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(9,'return_key_permissions',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(10,'return_keys',1,'2023-04-09 00:59:51','0000-00-00 00:00:00'),(11,'return_permission_options',1,'2023-04-09 00:59:51','0000-00-00 00:00:00');
            EOM;
            $sql[] = <<<EOM
                DROP TABLE IF EXISTS `api_keys`;
            EOM;
            $sql[] = <<<EOM
                CREATE TABLE `api_keys` (
                    `api_key` varchar(128) NOT NULL,
                    `secret_key` varchar(512) DEFAULT NULL,
                    `created_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_timestamp` datetime DEFAULT NULL,
                    `key_enabled` int NOT NULL DEFAULT '1',
                    PRIMARY KEY (`api_key`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
            EOM;
            $sql[] = <<<EOM
                INSERT INTO `api_keys` VALUES ('1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742','a5804571e86143987985ca1c9d5d6789005725128a2aa620c7cef0dc46cd9a4701ae34345180e01cebcfcb3e3ff7153b89fd849c05221755afae6548fa7e8ee9c53220c3de2120aec6ff7e2bcc96c252d0959414f61b1ae8fbbcad695c26443a586f6e8cb4ce353306332589ab8d0459d1545e29b2e7185fd5e6d5d94e86ebe59fc8352f5006af853d84750e8c02c57c6b5499cd72243090555ed8744a1e038ea073afbe8241542267d4b67204b683db35fbc35353f6ea04cb409ea32f0453f826b5c5aa305751ff781869dff60cf35deddf05b49078fde398b9e4f517c1945a6e4ff90e6303212f206345bd14c934d2f947e0400476dfb6eed730f76e5cd5fd','2023-01-09 16:02:22',NULL,1);
            EOM;
            $sql[] = <<<EOM
                DROP TABLE IF EXISTS `api_permissions`;
            EOM;
            $sql[] = <<<EOM
                CREATE TABLE `api_permissions` (
                    `permission_id` int NOT NULL AUTO_INCREMENT,
                    `api_key` varchar(128) NOT NULL,
                    `endpoint_id` int NOT NULL,
                    PRIMARY KEY (`permission_id`)
                ) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
            EOM;
            $sql[] = <<<EOM
                INSERT INTO `api_permissions` VALUES (1,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',1),(2,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',2),(3,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',3),(4,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',4),(5,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',5),(6,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',6),(7,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',7),(8,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',8),(9,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',9),(10,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',10),(11,'1460dc644f545ea518fbbe37732d97079c692456d9bad3058ed9b4bcea9c2ee662d5063bd6c336a908c6fc6349ba26a4ecb891bc226184dc223320e2df8fa742',11);
            EOM;
            $mysql = new mysql_(DEFAULT_DB_CONN);
            // check if the db exists
            if(SAFE_FIRST_TIME !== false) {
                $check_sql = "SELECT
                            SCHEMA_NAME
                        FROM
                            INFORMATION_SCHEMA.SCHEMATA
                        WHERE
                            SCHEMA_NAME = 'api'";
                $mysql->sql_select($check_sql);
                if($mysql->result->num_rows == 1) {
                    // The databse already exists
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => DB_ALREADY_EXISTS
                    ];
                }
            } else {
                $mysql->sql_exec('DROP DATABASE api', '');
            }
            
            for($i = 0; $i < count($sql); $i++) {
                if($i === 0) {
                    $mysql->sql_exec($sql[$i], '');
                } else {
                    $mysql->sql_exec($sql[$i], 'api');
                }
            }
            return [
                'success' => true,
                'data' => null,
                'erorr' => false
            ];
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