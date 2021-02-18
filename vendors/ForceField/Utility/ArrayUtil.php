<?php
namespace ForceField\Utility;

use stdClass;

final class ArrayUtil
{

    public static function remove($needle, array $haystack)
    {
        $a = [];
        foreach ($haystack as $n) {
            if ($n != $needle)
                $a[] = $n;
        }
        return $a;
    }

    public static function only(array $array, $filter_type)
    {
        $a = [];
        foreach ($array as $e) {
            if (is_callable($filter_type)) {
                $result = $filter_type($e);
                if ($result === TRUE)
                    $a[] = $e; // Push original value
                else if ($result !== FALSE)
                    $a[] = $result; // Push result
            } else {
                if (is_a($e, $filter_type))
                    $a[] = $e;
            }
        }
        return $a;
    }

    public static function fromStd(stdClass $stdclass, $recursive = false)
    {
        $a = [];
        
        foreach ($stdclass as $prop => $val) {
            if ($val instanceof stdClass && $recursive)
                $val = ArrayUtil::fromStd($val, true);
            
            $a[$prop] = $val;
        }
        
        return $a;
    }

    public static function toStd(array $array)
    {
        $std = new stdclass();
        
        foreach ($array as $key => $val) {
            $std->$key = $val;
        }
        
        return $std;
    }
}