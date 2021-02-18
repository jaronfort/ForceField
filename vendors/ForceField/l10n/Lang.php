<?php
namespace ForceField\l10n;

use ForceField\Core\Configure;
use ForceField\Utility\ArrayUtil;

class Lang
{

    private static $current;

    private static $lang = [];

    private $language_code;

    private $data;

    private function __construct($language_code)
    {
        $this->language_code = $language_code;
        $this->data = [];
        Lang::$lang[] = $this;
    }

    private static function get($language_code)
    {
        foreach (Lang::$lang as $l) {
            if ($l->language_code == $language_code)
                return $l;
        }
        
        return new Lang($language_code);
    }

    public static function read($key, $ext = '*')
    {
        if (in_array($ext, [
            '*',
            'php',
            'json'
            // ,'xml'
        ])) {
            if (preg_match('/^([a-zA-Z]{2,3}(\-[a-zA-Z]{2})?::)?@?[a-zA-Z0-9_\-]+(\.@?[a-zA-Z0-9_\-]+)+$/', $key)) {
                
                if (substr_count($key, '::') > 0) {
                    $result = explode('::', $key);
                    $lang = $result[0];
                    $key = $result[1];
                } else {
                    // Default language
                    $lang = Configure::readPattern('lang.default', '[a-zA-Z]{2,3}(\-[a-zA-Z]{2})?', 'en');
                }
                
                $l = Lang::get($lang);
                $tokens = explode('.', $key);
                $name = $tokens[0];
                $target = $tokens[1];
                
                if (! array_key_exists($name, $l->data)) {
                    
                    if ($ext == '*') {
                        $extensions = [
                            'php',
                            'json'
                            // ,'xml'
                        ];
                        
                        foreach ($extensions as $ext) {
                            $path = APPPATH . '/lang/' . $lang . '/' . $name . '.' . $ext;
                            
                            // Test path
                            if (file_exists($path)) {
                                
                                // Success!
                                break;
                            }
                        }
                    } else {
                        // Define path
                        $path = APPPATH . '/lang/' . $lang . '/' . $name . '.' . $ext;
                    }
                    
                    if (is_file($path)) {
                        switch ($ext) {
                            case 'php':
                                $data = load_result($path);
                                break;
                            case 'json':
                                $contents = file_get_contents($path);
                                $data = ArrayUtil::fromStd(json_decode($contents), true);
                                break;
                            case 'xml':
                                $contents = file_get_contents($path);
                                $xml = simplexml_load_file($path);
                                $data = [];
                                foreach ($xml->children() as $child) {
                                    $name = $child->getName();
                                    $data[$name] = $child->__toString();
                                }
                                break;
                            default:
                            // Do nothing
                        }
                        
                        $l->data[$name] = $data;
                    } else
                        $l->data[$name] = []; // Fail
                }
                
                $n = count($tokens);
                $target = $l->data;
                
                for ($i = 0; $i < $n; $i ++) {
                    $tkn = $tokens[$i];
                    
                    if (is_array($target) && array_key_exists($tkn, $target)) {
                        $target = $target[$tkn];
                        
                        if ($i == $n - 1 && is_string($target))
                            return $target;
                    }
                    else
                        break; // Could not resolve
                }
                
                // Fail
                return '';
            } else
                throw new \Exception("The supplied key ,\"{$key}\", is invalid.");
        } else
            throw new \Exception("The supplied extension, \"{$ext}\", is invalid.");
        
        // Fail safe
        return '';
    }
}

