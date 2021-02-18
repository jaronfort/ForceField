<?php
namespace ForceField\Utility;

class CreditCard
{

    private $value;

    private $exp_date;

    private $crv;

    public function __construct($credit)
    {
        $this->parseCredit($credit);
    }

    private function parseCredit($credit)
    {}

    public static function validate($credit)
    {
        $credit = trim($credit);
        return FALSE;
    }

    public static function mask($credit, $mask_character = '*')
    {
        if ($credit = CreditCard::validate($credit)) {
            $credit = str_replace(' ', '', $credit); // Remove spaces
            $mask = '';
            $end = substr($credit, 4); // Last four digits
            $len = strlen($credit) - 4;
            for ($i = 0; $i < $len; $i ++) {
                $mask .= $mask_character;
                if ($i % 4 == 0)
                    $mask .= ' ';
            }
            return $mask . ' ' . $end;
        }
        return FALSE;
    }

    public function valid()
    {
        return $this->value != NULL;
    }

    public function masked($mask_character = '*')
    {
        return CreditCard::mask($this->value, $mask_character);
    }

    public function __toString()
    {
        return $this->value;
    }
}