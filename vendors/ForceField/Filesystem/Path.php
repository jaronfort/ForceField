<?php
namespace ForceField\Filesystem;

class Path
{

    private $value;

    private function __construct($path)
    {
        $this->value = $path;
    }

    public static function valid($path)
    {
        // TODO Use a more leniant regexp
        return preg_match('/^\/?(\w+\/)+\w+(\.\w+)?\/?$/', $path);
    }
    
    public static function exists($path)
    {
        return file_exists($path);
    }
    
    public static function prep($path)
    {
        $path = trim($path);
        if ($path && Path::valid($path)) {
            $len = strlen($path);
            return $path;
        }
        return $path;
    }

    public static function resolve($path)
    {
        $src = $path;
        if (is_string($path) && $path = realpath($path)) {
            $path = Path::prep($path);
            return new Path($path);
        } else
            return FALSE;
    }
    
    public function __toString()
    {
        return $this->value ? $this->value : '';
    }
}