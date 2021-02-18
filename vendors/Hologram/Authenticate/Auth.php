<?php
namespace Hologram\Authenticate;

use ForceField\Core\Configure;
use Hologram\Relations\Model;
use ForceField\Security\Session;
use ForceField\Security\Password;

final class Auth
{

    private static $db;

    private static $user;

    private function __construct()
    {}

    private static function connect()
    {
        if (! Auth::$db)
            Auth::$db = \Hologram\Relations\Model::getDb();
    }

    private static function init()
    {
        Auth::connect();
    }

    private static function authUser($id, $raw_password)
    {
        $table = Configure::readString('auth.users.table', 'users');
        $id_field = Configure::readString('auth.users.fields.id', 'id');
        $pass_field = Configure::readString('auth.users.fields.password', 'password');
        
        $result = Auth::$db->$table();
        
        foreach ($result->where($id_field, $id)->limit(1) as $row) {
            
            $pw = Password::get($row[$pass_field]);
            
            if (! $pw)
                die('Error! The password cypher is unknown. Please check the ForceField configuration for security.');
            
            if ($pw->verify($raw_password))
                return $row;
        }
        
        return null;
    }

    private static function loadUser($id)
    {
        $table = Configure::readString('auth.users.table', 'users');
        $id_field = Configure::readString('auth.users.fields.id', 'id');
        $pass_field = Configure::readString('auth.users.fields.password', 'password');
        
        $result = Auth::$db->$table();
        
        // Select specific fields
        $fields = Configure::readArray('auth.users.select', [
            'id'
        ]);
        
        foreach ($fields as $f) {
            $result = $result->select($f);
        }
        
        foreach ($result->where($id_field, $id)->limit(1) as $row) {
            
            return $row;
        }
        
        return null;
    }

    public static function login($user, $pass)
    {
        if (Auth::exists())
            throw new \Exception('User already exists.');
        
        Auth::init();
        
        $user = trim($user); // Allow whitespace
        $table = Configure::readString('auth.users.table', 'users');
        $id_field = Configure::readString('auth.users.fields.id', 'id');
        $auth_field = Configure::readString('auth.users.fields.auth', 'email');
        $pass_field = Configure::readString('auth.users.fields.password', 'password');
        $loadUser = Configure::readCallable('auth.users.custom.loadUser');
        
        $u = null;
        
        if ($loadUser) {
            // Custom user loader
            $u = $loadUser(Auth::$db, $user);
        } else {
            // Default user loader
            $fields = Configure::readArray('auth.users.select', [
                'id'
            ]);
            
            $result = Auth::$db->$table();
            
            if ($fields) {
                
                foreach ($fields as $f) {
                    $result = $result->select($f);
                }
            }
            
            $result = $result->where($auth_field, $user)->limit(1);
            
            foreach ($result as $row) {
                $u = $row;
                break;
            }
        }
        
        if ($u) {
            // Check password
            $pw = Password::get($u[$pass_field]);
            
            if (! $pw)
                die('Error! The password cypher is unknown. Please check the ForceField configuration for security.');
            
            if ($pw && $pw->verify($pass)) {
                // Success!
                $uid = $u[$id_field];
                
                Session::set([
                    'logged_in' => true,
                    'uid' => $uid,
                    'password' => $pass
                ]);
                
                return Auth::$user = Auth::loadUser($uid);
            }
        }
        
        // Fail
        return false;
    }

    public static function logout()
    {
        Auth::init();
        
        if (Auth::exists()) {
            
            Session::set('logged_in', false);
            Session::set('uid', null);
            Session::set('password', null);
            Auth::$user = null;
            return true;
        }
        
        return false;
    }

    public static function user()
    {
        Auth::init();
        
        if (Auth::$user)
            return Auth::$user;
        else if (Session::get('logged_in') === true) {
            
            $uid = Session::get('uid');
            $pass = Session::get('password');
            $user = Auth::authUser($uid, $pass); // Authenticate
            
            if ($user) {
                
                // Load with limited fields
                $user = Auth::loadUser($uid);
                
                if ($user) {
                    Auth::$user = $user;
                    return $user;
                }
            }
        }
        
        return false;
    }

    public static function exists()
    {
        return Auth::user() != false;
    }

    public static function remember()
    {}
}

