<?php
namespace ForceField\Utility;

class RandUtil
{

    private static $letters = 'abcdefghijklmnopqrstuvwxyz';

    public static function element(array $arr)
    {
        $count = count($arr);
        return $count > 0 ? $arr[rand(0, $count - 1)] : FALSE;
    }

    public static function get()
    {
        return RandUtil::element(func_get_args());
    }

    public static function letter($min = 'a', $max = 'z')
    {
        $min = strtolower($min);
        $max = strtolower($max);
        if (StringUtil::isLetter($min))
            $min = strpos(RandUtil::$letters, $min);
        else
            $min = 0;
        if (StringUtil::isLetter($max))
            $max = strpos(RandUtil::$letters, $max);
        else
            $max = 25;
        return RandUtil::$letters[rand($min, $max)];
    }

    public static function cletter($min = 'a', $max = 'z')
    {
        return rand(0, 1) ? strtoupper(RandUtil::letter($min, $max)) : RandUtil::letter($min, $max);
    }

    public static function digit()
    {
        return (string) rand(0, 9);
    }

    public static function boolean()
    {
        return (bool) rand(0, 1);
    }

    public static function id($length = 16, $prefix = NULL)
    {
        $id = $prefix ? $prefix : '';
        for ($i = 0; $i < $length; $i ++) {
            if (rand(0, 1)) {
                // Letter
                $id .= RandUtil::cletter();
            } else {
                // Digit
                $id .= RandUtil::digit();
            }
        }
        return $id;
    }
}

