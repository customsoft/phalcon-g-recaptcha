<?php
/*
 * FORK OF https://github.com/kahurangitama/phalcon-recaptcha/blob/master/Recaptcha.php
 * Update 2018-05-03 by Zordan Marco
 *
 * -----------------------------------------------------
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *   Pavlo Sadovyi (made this wrapper for Phalcon Framework)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace csTelegram;

class Recaptcha extends \Phalcon\DI\Injectable
{
    /**
     * The reCAPTCHA server URL's
     */
    const RECAPTCHA_HOST = 'www.google.com';
    const RECAPTCHA_PATH = '/recaptcha/api/siteverify';
    const RECAPTCHA_API_SECURE_SERVER = 'https://www.google.com/recaptcha/api.js';

    /**
     * The reCAPTCHA error messages
     */
    const RECAPTCHA_ERROR_KEY = 'To use reCAPTCHA you must get an API key from <a href="https://www.google.com/recaptcha/admin/create">https://www.google.com/recaptcha/admin/create</a>';
    const RECAPTCHA_ERROR_REMOTE_IP = 'For security reasons, you must pass the remote IP address to reCAPTCHA';

    public static $error = 'incorrect-captcha-sol';
    public static $is_valid = false;

    /**
     * Gets the challenge HTML (javascript and non-javascript version).
     * This is called from the browser, and the resulting reCAPTCHA HTML widget
     * is embedded within the HTML form it was called from.
     *
     * @param string $publicKey A public key for reCAPTCHA (optional, default is false)
     * @param string $error The error given by reCAPTCHA (optional, default is '')
     * @return string - The HTML to be embedded in the user's form.
     */
    public static function get($publicKey, $error = '')
    {
        // Merging method arguments with class fileds
        $publicKey = $publicKey or die(self::RECAPTCHA_ERROR_KEY);

        // Append an error
        if ($error)
            $error = "&amp;error=" . $error;

        // Return HTML
        return "<script src='" . self::RECAPTCHA_API_SECURE_SERVER . "'></script>" .
            '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';
    }

    /**
     * Calls an HTTP POST function to verify if the user's guess was correct
     *
     * @param string $privateKey
     * @param string $remoteip
     * @param string $response
     * @param array $extra_params An array of extra variables to post to the server
     * @return boolean $this->is_valid property
     */
    public static function check($privateKey, $remoteIP, $response, $extra_params = array())
    {
        $privateKey = $privateKey or die(self::RECAPTCHA_ERROR_KEY);
        $remoteIP = $remoteIP or die(self::RECAPTCHA_ERROR_REMOTE_IP);

        // Discard spam submissions
        if (!$response)
            return self::$is_valid;

        $response = self::httpPost(self::RECAPTCHA_HOST, self::RECAPTCHA_PATH, array(
                'secret' => $privateKey,
                'remoteip' => $remoteIP,
                'response' => $response
                ) + $extra_params);

        if (json_decode($response[0])->success) {
            self::$is_valid = true;
        } else {
            self::$error = json_decode($response[0])->{'error-codes'};
        }
        return self::$is_valid;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server
     *
     * @param string $host
     * @param string $path
     * @param array $data
     * @return array response
     */
    private static function httpPost($host, $path, $data)
    {
        $req = self::qsEncode($data);
        $http_request = "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $url = 'https://' . $host . $path;

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => $http_request,
                'method' => 'POST',
            )
        );
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        $response = explode("\r\n\r\n", $response, 2);

        return $response;
    }

    /**
     * Encodes the given data into a query string format
     *
     * @param array $data Array of string elements to be encoded
     * @return string $req Encoded request
     */
    private static function qsEncode($data)
    {
        $req = '';
        foreach ($data as $key => $value)
            $req .= $key . '=' . urlencode(stripslashes($value)) . '&';

        // Cut the last '&'
        $req = substr($req, 0, strlen($req) - 1);

        return $req;
    }
}
