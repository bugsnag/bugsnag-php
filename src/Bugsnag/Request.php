<?php

class BugsnagRequest {
    public static function isRequest() {
        return isset($_SERVER['REQUEST_METHOD']);
    }

    public static function getRequestMetaData() {
        $requestData = array();

        // Request Tab
        $requestData['request'] = array();
        $requestData['request']['url'] = self::getCurrentUrl();
        if(isset($_SERVER['REQUEST_METHOD'])) {
            $requestData['request']['httpMethod'] = $_SERVER['REQUEST_METHOD'];
        }

        if(!empty($_POST)) {
            $requestData['request']['params'] = $_POST;
        } else {
            if(isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
                $requestData['request']['params'] = json_decode(file_get_contents('php://input'));
            }
        }

        $requestData['request']['ip'] = self::getRequestIp();
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $requestData['request']['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        if(function_exists("getallheaders")) {
            $headers = getallheaders();
            if(!empty($headers)) {
                $requestData['request']['headers'] = $headers;
            }
        }

        // Session Tab
        if(!empty($_SESSION)) {
            $requestData['session'] = $_SESSION;
        }

        // Cookies Tab
        if(!empty($_COOKIE)) {
            $requestData['cookies'] = $_COOKIE;
        }

        return $requestData;
    }

    public static function getContext() {
        if(self::isRequest() && isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER["REQUEST_URI"])) {
            return $_SERVER['REQUEST_METHOD'] . ' ' . strtok($_SERVER["REQUEST_URI"], '?');
        } else {
            return null;
        }
    }

    public static function getUserId() {
        if(self::isRequest()) {
            return self::getRequestIp();
        } else {
            return null;
        }
    }

    private static function getCurrentUrl() {
        $schema = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';

        return $schema.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    private static function getRequestIp() {
        return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    }
}

?>