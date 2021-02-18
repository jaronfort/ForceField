<?php
namespace ForceField\Utility;

class PhoneNumber
{

    private $value;

    private $type;

    private $country_code;

    private $area_code;

    private $office;

    private $line;

    private $ext;

    public function __construct($phone)
    {
        $this->parsePhone($phone);
    }

    private function parsePhone($phone)
    {
        if ($phone = PhoneNumber::validate($phone)) {
            $this->value = $phone;
            $a = PhoneNumber::parse($phone);
            $this->type = $a['type'];
            $this->country_code = isset($a['country_code']) ? $a['country_code'] : NULL;
            $this->area_code = isset($a['area_code']) ? $a['area_code'] : NULL;
        }
    }

    public static function validate($phone)
    {
        $phone = trim($phone);
        
        return $phone;
    }

    public static function parse($phone)
    {
        if (PhoneNumber::validate($phone)) {
            return array(
                'type' => NULL,
                'country_code' => NULL,
                'area_code' => NULL,
                'office' => NULL,
                'line' => NULL,
                'extension' => NULL
            );
        } else
            return FALSE;
    }

    public static function format($phone)
    {
        // TODO Support country codes
        $val = '';
        $len = strlen($phone);
        for ($i = 0; $i < $len; $i ++) {
            if (is_numeric($phone[$i]))
                $val .= $phone[$i];
        }
        $val = str_split($val);
        $count = count($val);
        $has_area_code = $count == 10;
        if ($count == 10 || $count == 7) {
            $r = array();
            for ($i = 0; $i < count($val); $i ++) {
                if ($i == 0 && $has_area_code)
                    $r[] = '(';
                $r[] = $val[$i];
                if ($has_area_code) {
                    if ($i == 2)
                        $r[] = ') ';
                    else if ($i == 5)
                        $r[] = '-';
                } else if ($i == 2)
                    $r[] = '-';
            }
            return implode($r, '');
        } else
            return $phone; // Fail
    }

    public static function mask($phone, $mask_character = '*')
    {
        return $phone;
    }

    public function type()
    {
        return $this->type;
    }

    public function countryCode()
    {
        return $this->country_code;
    }

    public function areaCode()
    {
        return $this->area_code;
    }

    public function office()
    {
        return $this->office;
    }

    public function line()
    {
        return $this->line;
    }

    public function ext()
    {
        return $this->ext;
    }

    public function masked($mask_character = '*')
    {
        return PhoneNumber::mask($this->value, $mask_character);
    }
    
    public function valid() {
        return $this->value != NULL;
    }

    public function __toString()
    {
        return $this->value;
    }
}

