<?php

if (!function_exists('to_utf8')) {
    function to_utf8($data = '')
    {
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = to_utf8($value);
                }
                return $data;
            } else {
                $fileType = mb_detect_encoding($data, ['UTF-8', 'GBK', 'LATIN1', 'BIG5']);
                if ($fileType != 'UTF-8') {
                    $data = mb_convert_encoding($data, 'utf-8', $fileType);
                }
            }
        }
        return $data;
    }
} 

if (!function_exists('chttp')) {
    function chttp($d = [])
    {
        $mrd = ['url' => '', 'do' => '', 'tz' => '', 'data' => '', 'ref' => '', 'llq' => '', 'qt' => '', 'cookie' => '', 'time' => '', 'daili' => [], 'headon' => '', 'code' => '', 'nossl' => '', 'to_utf8' => '', 'gzip' => '', 'port' => ''];
        $d = array_merge($mrd, $d);

        $url = $d['url'];
        if ($url == "") {
            exit("URL cannot be empty!");
        }
        $header = [];

        if ($d['llq']) {
            $header[] = "User-Agent:" . $d['agent'];
        } else {
            $header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64)AppleWebKit/537.36 (KHTML, like Gecko)Chrome/63.0.3239.26 Safari/537.36';
        }
        if ($d['ref']) {
            $header[] = "Referer:" . $d['ref'];
        }

        $ch = curl_init($url);
        if ($d['port'] != '') {
            curl_setopt($ch, CURLOPT_PORT, intval($d['port']));
        }

        // Cookie file/text
        if ($d['cookie'] != "") {
            if (substr($d['cookie'], -4) == ".txt") {
                // Create file if it doesn't exist
                if (!wjif($d['cookie'])) {
                    wjxie($d['cookie'], '');
                }
                $d['cookie'] = realpath($d['cookie']);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $d['cookie']);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $d['cookie']);
            } else {
                $cookie = 'cookie: ' . $d['cookie'];
                $header[] = $cookie;
            }
        }

        // Additional header information
        if ($d['qt']) {
            foreach ($d['qt'] as $v) {
                $header[] = $v;
            }
        }

        // Proxy settings
        if (count($d['daili']) == 2) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXY, $d['daili'][0]);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $d['daili'][1]);
        }

        $postData = $d['data'];
        $timeout = $d['time'] == "" ? 10 : ints($d['time'], 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Gzip settings
        if ($d['gzip'] != "0") {
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        }

        // Redirect following
        if ($d['tz'] == "0") {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        // SSL settings
        if (substr($url, 0, 8) === 'https://' || $d['nossl'] == "1") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Request method
        if (in_array(strtoupper($d['do']), ['DELETE', 'PUT'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($d['do']));
        } else {
            // POST data
            if (!empty($postData)) {
                if (is_array($postData)) {
                    $postData = http_build_query($postData);
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            } // POST empty content
            elseif (strtoupper($d['do']) == "POST") {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
        }

        // Return headers
        if ($d['headon'] == "1") {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Timeout settings
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

        // Execute
        $content = curl_exec($ch);

        // Convert to UTF-8
        if ($d['to_utf8'] != "0") {
            $content = to_utf8($content);
        }

        // Whether to return status code
        if ($d['code'] == "1") {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content = [$httpCode, $content];
        }

        curl_close($ch);
        return $content;
    }
}



if (!function_exists('dd')) {
    function dd(...$v){
        echo '<pre>';
        var_dump($v);
        echo '<pre/>';
        die;
    }
}

if (!function_exists('writeLog')) {
    function writeLog($testMode,$msg,$data){
        $data['message1'] = $msg;
        $api_url = $testMode === 'no' ? 'https://pgpay.weroam.xyz':'https://pgpay-stage.weroam.xyz';
        $d = [
            'do'=>'POST',
            'url'=>$api_url.'/wlog',
            'data'=>json_encode($data),
            'qt'=>[
                'Content-type: application/json;charset=UTF-8'
            ]
        ];
        chttp($d);
    }
}

if (!function_exists('removeEmptyValues')) {
    function removeEmptyValues($value) {
        return !empty($value) || ($value === 0 || $value === '0');
    }
}

if (!function_exists('encodeSHA256withRSA')) {
    function encodeSHA256withRSA($content,$privateKey0=''){
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey0, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($content, $sign, $privateKey, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);
        return $sign;
    }
}

if (!function_exists('wPost')) {
    function wPost($url = '',$post_data = []){
        $ch = curl_init(); // Initialize cURL

        curl_setopt($ch,CURLOPT_URL,$url); // Set target URL
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); // Return result as string
        curl_setopt($ch,CURLOPT_POST,1); // POST request method
    //        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data); // POST data

        $output = curl_exec($ch); // Execute and get HTML content
        curl_close($ch); // Release cURL handle
        return $output;
    }
}


if (!function_exists('wGet')) {
    function wGet($url = '', $params = [], $headers = []) {
        // Add parameters to URL if present
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        $ch = curl_init();

        // Basic cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        // Set default headers
        $default_headers = [
            'Accept: */*',
            'Connection: keep-alive'
        ];

        // Merge custom headers with default headers
        $header = array_merge($default_headers, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Additional options for better handling
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $output = curl_exec($ch);

        // Error handling
        if (curl_errno($ch)) {
            $output = curl_error($ch);
        }

        curl_close($ch);
        return $output;
        // 使用示例：
    // $result = wGet('https://api.example.com', ['id' => 123, 'name' => 'test']);
    // 这会请求 https://api.example.com?id=123&name=test
    }
}


if (!function_exists('arr2SignStr')) {
    function arr2SignStr(array $d=[],string $separator=''):string{
        // Remove empty values and sort for signature
        $d = array_filter($d, "removeEmptyValues");
        ksort($d);
        $sData = implode($separator,$d);
        return $sData;
    }
}



if (!function_exists('verifySHA256withRSA')) {
    /**
     * RSA-SHA256 signature verification function
     * @param string $content Original content
     * @param string $signature Base64 encoded signature
     * @param string $publicKey0 Public key string (without header/footer)
     * @return bool Verification result, true for success, false for failure
     */
    function verifySHA256withRSA($content, $signature, $publicKey0 = '') {
        if (empty($content) || empty($signature) || empty($publicKey0)) {
            return false;
        }

        try {
            // 格式化公钥
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey0, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";

            // Decode base64 signature
            $decodedSignature = base64_decode($signature);
            if ($decodedSignature === false) {
                return false;
            }

            // Verify signature
            $result = openssl_verify($content, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

            return $result === 1;
        } catch (Exception $e) {
            // Log error if needed
            // error_log("RSA verify error: " . $e->getMessage());
            return false;
        }
    }
}