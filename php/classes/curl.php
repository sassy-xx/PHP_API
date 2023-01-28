<?php

    // class file for curl requests

    class curlreq {
        /**
            * @param string $url URL to call.
            * @param string $method The method to use with the CURL request (GET, POST etc.)
            * @param string $return_type The return type expected from the CURL request (JSON, PLAIN).
            * @param array $headers The headers to apply to the CURL request.
            * @param array $body The body to apply to the CURL request.
            * @param bool $local This will toggle CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST.
        **/
        public function request($url, $method, $return_type, $headers, $body, $local) {
            // initialise curl object.
            $curl = curl_init();
            // setup curl object with options.
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_FAILONERROR => false,
                CURLOPT_POSTFIELDS => json_encode($body, true),
                CURLOPT_HTTPHEADER => $headers
            ];
            // check if this is a local curl request
            if($local) {
                $options['CURLOPT_SSL_VERIFYPEER'] = false;
                $options['CURLOPT_SSL_VERIFYHOST'] = false;
            }
            // apply the curl options
            curl_setopt_array($curl, $options);
            // execute the curl request.
            $response = curl_exec($curl);
            // close curl connection.
            curl_close($curl);
            // check what data type is expected to be returned.
            if($return_type == 'json') {
                // convert the plain response into an array.
                $response = json_decode($response, true);
                if(!is_array($response)) {
                    // the response could not be json decoded correctly, something is wrong.
                    trigger_error('Error, could not json decode the response.');
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Could not json_decode the response.'
                    ];
                }
                // return the response array.
                return $response;
            } else {
                // plain response/non json response
                return $response;
            }
        }
    }

?>