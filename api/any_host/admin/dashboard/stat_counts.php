<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');

    class stat_counts {
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
            $sql = "SELECT
                        COUNT(o.orderid) total_pending_orders,
                        cancellations.total_cancellations,
                        tickets.total_tickets,
                        module_errors.module_errors
                    FROM
                        `any_host`.`order` o
                    LEFT JOIN (
                        SELECT
                            COUNT(o.orderid) total_cancellations
                        FROM
                            `any_host`.`order` o
                        WHERE
                            o.order_statusid = 2) AS cancellations ON
                        1 = 1
                    LEFT JOIN (
                        SELECT
                            COUNT(t.ticketid) total_tickets
                        FROM
                            `any_host`.`ticket` t
                        WHERE
                            t.ticket_statusid = 2) AS tickets ON
                        1 = 1
                    LEFT JOIN (
                        SELECT
                            COUNT(am.auditid) module_errors
                        FROM
                            `any_host`.`audit_module` am
                        WHERE
                            am.audit_statusid = 1
                            AND am.seen = 0) AS module_errors ON
                        1 = 1
                    WHERE
                        o.order_statusid = 1";
            $mysql = new mysql_(DEFAULT_DB_CONN);
            $mysql->sql_select($sql);
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
                        'message' => 'Something went wrong returning the stat counts.'
                    ]
                ];
            }
            foreach($mysql->result as $k => $v) {
                $total_pending_orders = $v['total_pending_orders'];
                $total_cancellations = $v['total_cancellations'];
                $total_tickets = $v['total_tickets'];
                $module_errors = $v['module_errors'];
            }
            return [
                'success' => true,
                'data' => [
                    'total_pending_orders' => $total_pending_orders,
                    'total_cancellations' => $total_cancellations,
                    'total_tickets' => $total_tickets,
                    'module_errors' => $module_errors
                ],
                'error' => false
            ];
        }
    }
?>