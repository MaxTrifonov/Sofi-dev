<?php

class CRequest {
    /**
     * Scheme
     */
    const SCHEME_HTTP = 'http';
    const SCHEME_HTTPS = 'https';

    protected $_url;
    protected $_uri;
    protected $_params;

    public function sapi() {
        return php_sapi_name();
    }

    public function scheme() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    public function url() {
        return $this->_url;
    }

    public function uri() {
        return $this->_uri;
    }

    public function params() {
        return $this->_params;
    }

    function __construct() {

        $uri = $_SERVER['REQUEST_URI'];
        $this->_url = $_SERVER['HTTP_HOST'];

        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->_params = $_GET;
        } else {
            $this->_params = $_POST;
        }

        $this->_url = urldecode($this->_url);
        $this->_uri = urldecode($uri);

        foreach ($this->_params as $key => $val) {
            $this->_params[$key] = urldecode($val);
        }
    }

    function getSegments() {
        if ($this->_segments !== null)
            return $this->_segments;

        $this->_segments = explode('/', substr($this->_request_uri, 1));
        if ($this->_segments[count($this->_segments) - 1] == '') {
            unset($this->_segments[count($this->_segments) - 1]);
        }
        foreach ($this->_segments as $key => $val) {
            $this->_segments[$key] = urldecode($val);
        }

        return $this->_segments;
    }

    function __get($name) {
        if (isset($this->_params[$name]))
            return $this->_params[$name];
        else
            return null;
    }

    function getParam($num, $default = null) {
        if (isset($this->_params[$num]))
            return $this->_params[$num];
        else
            return $default;
    }

    public function getCookie($key = null, $default = null) {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    public function getServer($key = null, $default = null) {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    public function getEnv($key = null, $default = null) {
        if (null === $key) {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }

    public function getMethod() {
        return $this->getServer('REQUEST_METHOD');
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost() {
        if ('POST' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet() {
        if ('GET' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut() {
        if ('PUT' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete() {
        if ('DELETE' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead() {
        if ('HEAD' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions() {
        if ('OPTIONS' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws Zend_Controller_Request_Exception
     */
    public function getHeader($header) {
        if (empty($header)) {
            //TODO Check error new TError(__CLASS__, -1);
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (isset($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers[$header])) {
                return $headers[$header];
            }
            $header = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) == $header) {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return boolean
     */
    public function isXmlHttpRequest() {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * Is this a Flash request?
     *
     * @return boolean
     */
    public function isFlashRequest() {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure() {
        return ($this->scheme() === self::SCHEME_HTTPS);
    }

}

?>
