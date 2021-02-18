<?php
namespace ForceField\Utility;

final class StringUtil
{

    public static function isLetter($val, $index = 0)
    {
        return $index > 0 && $index < strlen($val) ? preg_match('/^[a-zA-Z]$/', $val[$index]) : FALSE;
    }

    public static function isDigit($val, $index = 0)
    {
        return $index > 0 && $index < strlen($val) ? preg_match('/^[0-9]$/', $val[$index]) : FALSE;
    }

    public static function isWhitespace($val, $index = 0)
    {
        return $index > 0 && $index < strlen($val) ? preg_match('/^[\s\n]$/', $val[$index]) : FALSE;
    }

    public static function startsWith($needle, $haystack)
    {
        return strpos($haystack, $needle) === 0;
    }

    public static function endsWith($needle, $haystack)
    {
        return strrpos($haystack, $needle) == strlen($haystack) - strlen($needle);
    }

    public static function charAt($index, $string, $fail_val = NULL)
    {
        return $index > - 1 && $index < strlen($string) ? $string[$index] : $fail_val;
    }

    public static function firstChar($string)
    {
        return char_at(0, $string, NULL);
    }

    public static function lastChar($string)
    {
        return char_at(strlen($string) - 1, $string, NULL);
    }
    
    public static function match($subject, $pattern)
    {
        preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) > 0) {
            $a = $matches[0];
            return count($a) > 0;
        } else
            return FALSE;
    }
}