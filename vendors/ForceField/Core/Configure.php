<?php
namespace ForceField\Core;

use ForceField\Utility\ArrayUtil;
use ForceField\Network\Url;

class Configure
{

    private static $url;

    private static $data = [
        '*' => [], // global
        '127.0.0.1' => [] // localhost
    ];

    private static $loaded = [];

    private static function tokenize($key)
    {
        if (preg_match('/^(@(\*|127\.0\.0\.1|localhost|[a-zA-Z0-9][a-zA-Z0-9\-]*\.[a-zA-Z0-9][a-zA-Z0-9\-]*):::)?[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $key)) {
            $matches = [];
            preg_match_all('/(@[^:]*|[a-zA-Z_][a-zA-Z0-9_]*)/', $key, $matches, PREG_PATTERN_ORDER);
            return $matches[0];
        }
        
        throw new \Exception('Invalid configuration key "' . $key . '".');
    }

    private static function load($name)
    {
        if (! in_array($name, Configure::$loaded) && file_exists(APPPATH . '/config/' . $name . '.php')) {
            
            $data = load_result(APPPATH . '/config/' . $name . '.php');
            
            if (is_array($data)) {
                
                foreach ($data as $key => $val) {
                    if (substr_count($key, ':::') > 0)
                        $key = str_replace(':::', ':::' . $name . '.', $key);
                    else
                        $key = $name . '.' . $key;
                    
                    Configure::write($key, $val);
                }
            }
            
            Configure::$loaded[] = $name;
        }
    }

    private static function getName(array $tokens)
    {
        $len = count($tokens);
        
        for ($i = 0; $i < $len; $i ++) {
            $tkn = $tokens[$i];
            if ($tkn[0] == '@')
                continue;
            else
                return $tkn;
        }
    }

    public static function write($key, $value)
    {
        $tokens = Configure::tokenize($key);
        $n = count($tokens);
        
        $target = &Configure::$data;
        
        if ($tokens[0][0] == '@') {
            $tkn = substr($tokens[0], 1);
            
            if ($tkn == 'localhost')
                $tkn = '127.0.0.1';
            
            if (! array_key_exists($tkn, $target) || ! is_array($target[$tkn]))
                $target[$tkn] = [];
            
            $i = 1;
            $target = &$target[$tkn];
        } else {
            $i = 0;
            $target = &$target['*'];
        }
        
        for (; $i < $n; $i ++) {
            $tkn = $tokens[$i];
            
            if ($i == $n - 1)
                $target[$tkn] = $value;
            else {
                if (! array_key_exists($tkn, $target) || ! is_array($target[$tkn]))
                    $target[$tkn] = [];
                
                $target = &$target[$tkn];
            }
        }
        
        // print_r(Configure::$data);
    }

    public static function read($key, $default = null, $network_enabled = true)
    {
        $tokens = Configure::tokenize($key);
        $name = Configure::getName($tokens);
        Configure::load($name);
        $n = count($tokens);
        $target = Configure::$data;
        $explicit = FALSE;
        
        if ($tokens[0][0] == '@') {
            // If key is explicitly directed towards a domain specific value or global value
            $explicit = TRUE;
            $tkn = str_replace('@', '', $tokens[0]);
            
            if ($tkn == 'localhost')
                $tkn = '127.0.0.1';
            
            $tokens[0] = $tkn;
        } else {
            // ...Or if key is implicitly directed to a global value or overriden by a domain specific value
            $domain = Url::current()->domainName();
            
            if (! $domain)
                $domain = Url::current()->domain(); // Will be domain
            
            if ($domain == 'localhost')
                $domain = '127.0.0.1';
            
            if (array_key_exists($domain, $target))
                $target = $target[$domain];
            else
                return Configure::read('@*:::' . implode('.', $tokens));
        }
        
        for ($i = 0; $i < $n; $i ++) {
            
            $tkn = $tokens[$i];
            
            if (is_array($target) && array_key_exists($tkn, $target)) {
                
                $target = $target[$tkn];
                
                if ($i == $n - 1) {
                    // Success!
                    return $target;
                }
            } else {
                // Fail
                if (! $explicit) {
                    // If key is not default domain
                    
                    // Read default value explicitly
                    return Configure::read('@*:::' . implode('.', $tokens));
                } else {
                    // ... Or return default value
                    break; // Leave loop
                }
            }
        }
        
        // Fail
        return $default;
    }

    public static function readBool($key, $default = false)
    {
        $val = Configure::read($key);
        
        if (is_bool($val))
            return $val;
        
        return $default;
    }

    public static function readNum($key, $default = 0.0)
    {
        $val = Configure::read($key);
        
        if (is_numeric($val))
            return (float) $val;
        
        return $default;
    }

    public static function readInt($key, $default = 0)
    {
        $val = Configure::read($key);
        
        if (is_int($val) || is_numeric($val))
            return (int) $val;
        
        return is_int($val) || is_numeric($val) ? (int) $default : $default;
    }

    public static function readString($key, $default = '')
    {
        $val = Configure::read($key);
        
        if (is_string($val))
            return $val;
        
        return $default;
    }

    public static function readPath($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && preg_match('/^((\/)|((\/[a-zA-Z0-9\_\-\.]+)+\/?))$/', $val))
            return new Path('/' . trim($val, '/'));
        
        return is_string($default) && preg_match('/^((\/)|((\/[a-zA-Z0-9\_\-\.]+)+\/?))$/', $default) ? new Path('/' . trim($default, '/')) : $default;
    }

    public static function readPathString($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && preg_match('/^((\/)|((\/[a-zA-Z0-9\_\-\.]+)+\/?))$/', $val))
            return '/' . trim($val, '/');
        
        return $default;
    }

    public static function readUrl($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL))
            return new Url($val);
        
        return is_string($default) && filter_var($val, FILTER_VALIDATE_URL) ? new Url($default) : $default;
    }

    public static function readUrlString($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL))
            return $val;
        
        return $default;
    }

    public static function readEmail($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_EMAIL))
            return $val;
        
        return is_string($default) && filter_var($default, FILTER_VALIDATE_EMAIL) ? new EmailAddr($default) : $default;
    }

    public static function readRegex($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_REGEXP))
            return $val;
        
        return $default;
    }

    public static function readPattern($key, $pattern, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($key) && preg_match('/^' . $pattern . '$/', $val))
            return $val;
        
        return $default;
    }

    public static function readIp($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_IP))
            return $val;
        
        return $default;
    }

    public static function readMac($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_string($val) && filter_var($val, FILTER_VALIDATE_MAC))
            return $val;
        
        return $default;
    }

    public static function readArray($key, $default = [])
    {
        $val = Configure::read($key);
        
        if (is_array($val))
            return $val;
        
        return $default;
    }

    public static function readObject($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_a($val, 'stdclass'))
            return $val;
        else if (is_array($val))
            return ArrayUtil::toStd($val);
        
        return $default;
    }

    public static function readCallable($key, $default = null)
    {
        $val = Configure::read($key);
        
        if (is_callable($val))
            return $val;
        
        return $default;
    }
    
    public static function readIn($key, array $values, $default = null)
    {
        $val = Configure::read($key);
        
        if(in_array($val, $values))
            return $val;
        
        return $default;
    }
}

