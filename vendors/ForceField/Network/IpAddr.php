<?php
namespace ForceField\Network;

class IpAddr
{

    private $value;

    public function __construct($ip)
    {
        $this->parseIp($ip);
    }

    private function parseIp($ip)
    {
        if ($ip = IpAddr::validate($ip))
            $this->value = $ip;
    }

    public static function get($ip)
    {
        if ($ip instanceof IpAddr)
            $instance = $ip;
        else
            $instance = new IpAddr($ip);
        return $instance->valid() ? $instance : FALSE;
    }

    public static function validate($ip)
    {
        return filter_var(trim($ip), FILTER_VALIDATE_IP);
    }

    public function valid()
    {
        return $this->value != NULL;
    }

    public function __toString()
    {
        return $this->value;
    }
}

