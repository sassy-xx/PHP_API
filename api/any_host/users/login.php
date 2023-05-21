<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class login {
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
            if(isset($user) && !empty($user) && isset($password) && !empty($password)) {
                $parray = [$user];
                $bstring = 's';
                $sql = "SELECT usr.userid, usr.user_firstname, usr.password, usr.groupid FROM any_host.users usr WHERE usr.email_address = ?";
                $mysql = new mysql_(DEFAULT_DB_CONN);
                $mysql->sql_select($sql, $bstring, $parray);
                if($mysql->result->num_rows == -1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => SELECT_DB_ERROR
                        ]
                    ];
                } elseif($mysql->result->num_rows < 1) {
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => [
                            'message' => 'Invalid credentials'
                        ]
                    ];
                }
                foreach($mysql->result as $k => $v) {
                    $hash = $v['password'];
                    $firstname = $v['user_firstname'];
                    $userid = $v['userid'];
                    $groupid = $v['groupid'];
                }
                if(password_verify($password, $hash)) {
                    return [
                        'success' => true,
                        'data' => [
                            'firstname' => $firstname,
                            'userid' => $userid,
                            'groupid' => $groupid
                        ],
                        'error' => false
                    ];
                }
                return [
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Invalid credentials'
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Incorrect parameters passed.'
                ];
            }
        }
    }
?>