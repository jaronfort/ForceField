<?php
namespace ForceField\Security;

use ForceField\Utility\ArrayUtil;
use ForceField\Core\Configure;
use ForceField\Network\Url;

class Session
{

    private static $use_db;

    private static $table;

    private static $session_name;

    private static $encrypt;

    private static $verified;

    private static $data;

    private static function init()
    {
        if (! Session::$encrypt)
            Session::$encrypt = new Encrypt();
        
        switch (session_status()) {
            case PHP_SESSION_ACTIVE:
                return Session::verifySession();
            case PHP_SESSION_NONE:
                Session::$session_name = Configure::readString('security.session.name', 'ff_session');
                session_name(Session::$session_name);
                
                $session_domain = Configure::readString('security.session.domain', '*');
                
                if ($session_domain == '*') {
                    // Session will work across subdomains
                    $domain = Url::current()->domain(2, 1);
                    $session_domain = '.' . $domain;
                }
                
                if ($session_domain) {
                    ini_set('session.cookie_domain', $session_domain);
                    session_set_cookie_params(0, '/', $session_domain);
                }
                
                header('Access-Control-Allow-Credentials: true');
                
                return session_start() && Session::verifySession();
            case PHP_SESSION_DISABLED:
            default:
                return false;
        }
    }

    private static function verifySession()
    {
        if (Session::$verified)
            return true;
        
        Session::$data = [];
        Session::$use_db = Configure::readBool('security.session.database.enabled', false);
        
        foreach ($_SESSION as $name => $val) {
            if (Session::decrypt($name) == Session::$session_name) {
                $json = json_decode(Session::decrypt($val));
                if (is_array($json))
                    Session::$data = $json;
                else if (is_a($json, 'stdclass'))
                    Session::$data = ArrayUtil::fromStd($json);
            }
        }
        
        Session::$verified = true;
        
        return true;
    }

    private static function encrypt($val)
    {
        return Session::$encrypt->encrypt($val);
    }

    private static function decrypt($val)
    {
        return Session::$encrypt->decrypt($val);
    }

    private static function update()
    {
        $_SESSION[Session::encrypt(Session::$session_name)] = Session::encrypt(json_encode(Session::$data));
    }

    public static function exists($name)
    {
        if (Session::init())
            return array_key_exists($name, Session::$data);
        else
            return FALSE;
    }

    public static function get($name = NULL)
    {
        if (Session::init()) {
            if (is_string($name) && $name)
                return array_key_exists($name, Session::$data) ? Session::$data[$name] : NULL;
            else {
                
                $a = [];
                foreach (Session::$data as $name => $val) {
                    $a[$name] = $val;
                }
                return $a;
            }
        }
        
        return null;
    }

    public static function set($name, $val = null)
    {
        if (Session::init()) {
            
            if (is_string($name) && $name) {
                
                Session::$data[$name] = $val;
                Session::update();
                return true;
            } else if (is_array($name)) {
                
                foreach ($name as $key => $val) {
                    
                    Session::$data[$key] = $val;
                }
                
                Session::update();
                return true;
            }
        }
        
        return false;
    }

    public static function delete($name)
    {
        $args = func_get_args();
        
        foreach ($args as $name) {
            if (Session::init() && isset(Session::$data[$name])) {
                unset(Session::$data[$name]);
                Session::update();
            }
        }
    }

    public static function destroy()
    {
        if (Session::init()) {
            
            if (isset($_SESSION[Session::$session_name]))
                unset($_SESSION[Session::$session_name]);
        }
    }

    /**
     */
    public static function share()
    {}

    /**
     *
     * @param string $passkey
     */
    public static function import($passkey)
    {}

    public static function active()
    {
        return session_status() == PHP_SESSION_ACTIVE;
    }

    public static function disabled()
    {
        return session_status() == PHP_SESSION_DISABLED;
    }

    public static function usesDatabase()
    {
        if (Session::init())
            return Session::$use_db;
        return FALSE;
    }
}
