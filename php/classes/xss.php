<?php

    // class file for xss protection

    class xss {
        /**
            * @param string $input the input to apply XXS mitigation to.
        **/
        public static function xss($input) {
            if(is_array($input)) {
                // return a new array.
                $result = (array)[];
                // apply xss on every value in the array.
                foreach($input as $key => $val){
                    $result[$key] = xss::xss($val);
                }
            } else if(is_object($input)){
                // return a new object.
                $result = new stdClass();
                // apply xss on every value in the object.
                foreach($input as $key => $val){
                    $result->$key = xss::xss($val);
                }
            } else {
                if($input == null) {
                    return $input;
                } else {
                    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                }
            }
            return $result;
        }
    }
        
?>