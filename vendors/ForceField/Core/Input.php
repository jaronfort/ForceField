<?php
namespace ForceField\Core;

use ForceField\Security\Session;

class Input
{

    private static $default_xss_filter = true;

    private static $ip_address;

    private static $user_agent;

    private static function xssFunc($value, $apply_filter = NULL)
    {
        if (is_null($apply_filter))
            $apply_filter = Input::$default_xss_filter;
        
        switch ($apply_filter) {
            case true:
                return Input::xssFilter($value);
            case false:
            default:
                return $value;
        }
    }

    private static function xssFilter($value)
    {
        if (is_array($value)) {
            $a = array();
            foreach ($value as $v) {
                $a[] = $v;
            }
            return $a;
        }
        return strip_tags($value);
    }

    public static function verb()
    {
        return 'GET';
    }

    public static function get($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_GET[$index]))
                return Input::xssFunc($_GET[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = [];
            foreach (array_keys($_GET) as $index) {
                $a[$index] = Input::xssFunc($_GET[$index], $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function post($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_POST[$index]))
                return Input::xssFunc($_POST[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = [];
            foreach ($_POST as $index => $val) {
                $a[$index] = Input::xssFunc($val, $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function getPost($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_GET[$index]))
                return Input::xssFunc($_GET[$index], $xss_filter);
            else if (isset($_POST[$index]))
                return Input::xssFunc($_POST[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = array();
            foreach (array_keys($_GET) as $index) {
                $a[$index] = Input::xssFunc($_GET[$index], $xss_filter);
            }
            foreach (array_keys($_POST) as $index) {
                if (! array_key_exists($index, $a))
                    $a[$index] = Input::xssFunc($_POST[$index], $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function postGet($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_POST[$index]))
                return Input::xssFunc($_POST[$index], $xss_filter);
            else if (isset($_GET[$index]))
                return Input::xssFunc($_GET[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = array();
            foreach (array_keys($_POST) as $index) {
                $a[$index] = Input::xssFunc($_POST[$index], $xss_filter);
            }
            foreach (array_keys($_GET) as $index) {
                if (! array_key_exists($index, $a))
                    $a[$index] = Input::xssFunc($_GET[$index], $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function cookie($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_COOKIE[$index]))
                return Input::xssFunc($_COOKIE[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = array();
            foreach (array_keys($_COOKIE) as $index) {
                $a[$index] = Input::xssFunc($_COOKIE[$index], $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function setCookie($name, $value)
    {
        setcookie($name, $value);
    }

    public static function session($name = NULL, $val = NULL)
    {
        if (func_num_args() > 1)
            return Session::set($name, $val);
        else
            return Session::get($name);
    }

    public static function server($index = NULL, $xss_filter = NULL)
    {
        if (is_string($index)) {
            if (isset($_SERVER[$index]))
                return Input::xssFunc($_SERVER[$index], $xss_filter);
            else
                return NULL;
        } else if (is_null($index)) {
            $a = array();
            foreach (array_keys($_SERVER) as $index) {
                $a[$index] = Input::xssFunc($_SERVER[$index], $xss_filter);
            }
            return $a;
        }
        return NULL;
    }

    public static function ipMatch($ip_address)
    {
        if (is_string($ip_address))
            return REMOTE_ADDRESS == $ip_address;
        else if (is_array($ip_address)) {
            foreach ($ip_address as $ip) {
                if (REMOTE_ADDRESS == $ip)
                    return TRUE;
            }
        }
        return FALSE;
    }

    public static function isCli()
    {
        return php_sapi_name() == 'cli';
    }
}
