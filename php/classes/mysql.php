<?php
class mysql_ {
    // private string $conn_file;
    private string $hostname;
    private string $username;
    private string $password;
    public $result;
        public function __construct($db_conn) {
            switch($db_conn) {
                case 'localhost':
                    $this->hostname = DB_LOCAL_HOSTNAME;
                    $this->username = DB_LOCAL_USERNAME;
                    $this->password = DB_LOCAL_PASSWORD;
                break;
                case 'live_conn':
                    $this->hostname = DB_LIVE_HOSTNAME;
                    $this->username = DB_LIVE_USERNAME;
                    $this->password = DB_LIVE_PASSWORD;
                break;
                case 'readrep':
                    $this->hostname = DB_READREP_HOSTNAME;
                    $this->username = DB_READREP_USERNAME;
                    $this->password = DB_READREP_PASSWORD;
                break;
                default:
                    trigger_error('The sql connection for: "'.$db_conn.'" was not found. Please check your $db_conn parameter!', E_USER_NOTICE);
                    die();
                break;
            }
        }

        /**
            * @param string $sql The SQL statment to prepare/execute.
            * @param string $bstring The bind string to use with binding variables to the prepared statment.
            * @param array $parray The parameter array to bind to the prepared statment.
        **/
        public function sql_select($sql, $bstring = '', $parray = '') {
            // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            // initial sanity checks
            if(!empty($bstring) && empty($parray) || !empty($parray) && empty($bstring)) {
                // only one of the two binder variables is set correctly.
                trigger_error('Warning, missing a binder variable. Stopping execution.');
                return false;
            }
            // prepare the SQL statment.
            $conn = new mysqli($this->hostname, $this->username, $this->password);
            $sql_obj = $conn->prepare($sql);
            // check if we need to bind any variables to the statement.
            if(!empty($bstring)) {
                $sql_obj->bind_param($bstring, ...$parray);
            }
            // execute the statment.
            $sql_obj->execute();
            // return the result set.
            $this->result = $sql_obj->get_result();
            $sql_obj->close();
        }

        /**
            * @param string $sql The SQL statment to prepare/execute.
            * @param string $bstring The bind string to use with binding variables to the prepared statment.
            * @param array $parray The parameter array to bind to the prepared statment.
            * @param bool $force If true, the funciton will NOT check for WHERE in the SQL statement.
        **/
        public function sql_update($sql, $bstring = '', $parray = '', $force = false) {
            // initial sanity checks
            if(!empty($bstring) && empty($parray) || !empty($parray) && empty($bstring)) {
                // only one of the two binder variables is set correctly.
                trigger_error('Warning, missing a binder variable. Stopping execution.');
                return false;
            }
            // prepare the SQL statment.
            $conn = new mysqli($this->hostname, $this->username, $this->password);
            $sql_obj = $conn->prepare($sql);
            // check if we need to bind any variables to the statement.
            if(!empty($bstring) && !empty($parray)) {
                $sql_obj->bind_param($bstring, ...$parray);
            }
            // sanity check to ensure there is where on UPDATE statment.
            if(!str_contains($sql, 'WHERE') && !$force) {
                trigger_error('Warning, there is no WHERE in the UPDATE statment. $force === FALSE, stopping execution.');
                return false;
            }
            // execute the statment.
            $sql_obj->execute();
            $this->result = $sql_obj->affected_rows;
            $sql_obj->close();
        }

        /**
            * @param string $sql The SQL statment to prepare/execute.
            * @param string $bstring The bind string to use with binding variables to the prepared statment.
            * @param array $parray The parameter array to bind to the prepared statment.
            * @param bool $force If true, the funciton will NOT check for WHERE in the SQL statement.
        **/
        public function sql_insert($sql, $bstring = '', $parray = '', $force = false) {
            // initial sanity checks
            if(!empty($bstring) && empty($parray) || !empty($parray) && empty($bstring)) {
                // only one of the two binder variables is set correctly.
                trigger_error('Warning, missing a binder variable. Stopping execution.');
                return false;
            }
            $conn = new mysqli($this->hostname, $this->username, $this->password);
            // prepare the SQL statment.
            $sql_obj = $conn->prepare($sql);
            // check if we need to bind any variables to the statement.
            if(!empty($bstring) && !empty($parray)) {
                $sql_obj->bind_param($bstring, ...$parray);
            }
            // execute the statment.
            $sql_obj->execute();
            $this->result = $sql_obj;
        }

        /**
            * @param string $sql The SQL statment to prepare/execute.
            * @param string $bstring The bind string to use with binding variables to the prepared statment.
            * @param array $parray The parameter array to bind to the prepared statment.
            * @param bool $force If true, the funciton will NOT check for WHERE in the SQL statement.
        **/
        public function sql_replace_into($sql, $bstring = '', $parray = '', $force = false) {
            // initial sanity checks
            if(!empty($bstring) && empty($parray) || !empty($parray) && empty($bstring)) {
                // only one of the two binder variables is set correctly.
                trigger_error('Warning, missing a binder variable. Stopping execution.');
                return false;
            }
            $conn = new mysqli($this->hostname, $this->username, $this->password);
            // prepare the SQL statment.
            $sql_obj = $conn->prepare($sql);
            // check if we need to bind any variables to the statement.
            if(!empty($bstring) && !empty($parray)) {
                $sql_obj->bind_param($bstring, ...$parray);
            }
            // sanity check to ensure there is where on UPDATE statment.
            if(!str_contains($sql, 'WHERE') && !$force) {
                trigger_error('Warning, there is no WHERE in the REPLACE INTO statment. $force === FALSE, stopping execution...');
                return false;
            }
            // execute the statment.
            $sql_obj->execute();
            $this->result = $sql_obj->affected_rows;
            $sql_obj->close();
        }

        /**
            * @param string $sql The SQL statment to prepare/execute.
            * @param string $bstring The bind string to use with binding variables to the prepared statment.
            * @param array $parray The parameter array to bind to the prepared statment.
            * @param bool $force If true, the funciton will NOT check for WHERE in the SQL statement.
        **/
        public function sql_delete($sql, $bstring = '', $parray = '', $force = false) {
            // initial sanity checks
            if(!empty($bstring) && empty($parray) || !empty($parray) && empty($bstring)) {
                // only one of the two binder variables is set correctly.
                trigger_error('Warning, missing a binder variable. Stopping execution.');
                return false;
            }
            $conn = new mysqli($this->hostname, $this->username, $this->password);
            // prepare the SQL statment.
            $sql_obj = $conn->prepare($sql);
            // check if we need to bind any variables to the statement.
            if(!empty($bstring) && !empty($parray)) {
                $sql_obj->bind_param($bstring, ...$parray);
            }
            // sanity check to ensure there is where on UPDATE statment.
            if(!str_contains($sql, 'WHERE') && !$force) {
                trigger_error('Warning, there is no WHERE in the DELETE FROM statment. $force === FALSE, stopping execution...');
                return false;
            }
            // execute the statment.
            $sql_obj->execute();
            $this->result = $sql_obj->affected_rows;
            $sql_obj->close();
        }

        public static function close($connection) {
            $connection->close();
        }

        public function sql_exec($sql, $db) {
            $mysqli = mysqli_connect($this->hostname, $this->username, $this->password, $db);
            $result = mysqli_query($mysqli, $sql);
            $this->result = $result;
        }
    }
?>