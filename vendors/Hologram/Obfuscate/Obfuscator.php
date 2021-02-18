<?php
namespace Hologram\Obfuscate;

use ForceField\Utility\RandUtil;
use ForceField\Core\Configure;

class Obfuscator
{

    private static $map;
    
    private static function exists($hash)
    {
        foreach(Obfuscator::$map as $key => $val)
        {
            if($val == $hash)
                return true;
        }
        
        return false;
    }
    
    private static function generate($raw_value)
    {
        $result = RandUtil::id(rand(4, 5), RandUtil::get('f', 'a', 'u', 'd'));
        
        while(Obfuscator::exists($result))
        {
            $result = RandUtil::id(rand(4, 5), RandUtil::get('f', 'a', 'u', 'd'));
        }
        
        Obfuscator::$map[$raw_value] = $result;
        
        return $result;
    }

    private static function init()
    {
        Obfuscator::$map = [];
        $path = APPPATH . '/bin/.obfmap';
        //$file = new File($path);
    }
    
    public static function obscure($raw_value)
    {
        if(!Configure::readBool('security.obfuscate.enabled', false))
            return $raw_value;
        
        if (! is_array(Obfuscator::$map))
            Obfuscator::init();
        
        if (preg_match('/^(#|\.)[a-zA-Z0-9_\-:]+$/', $raw_value)) {
            $prefix = $raw_value[0];
            $raw_value = substr($raw_value, 1);
        } else {
            // All other values
            $prefix = '';
        }
        
        if (array_key_exists($raw_value, Obfuscator::$map))
            $obscure_value = Obfuscator::$map[$raw_value];
        else
            $obscure_value = Obfuscator::generate($raw_value);
        
        return $prefix . $obscure_value;
    }
}

