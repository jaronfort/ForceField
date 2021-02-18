<?php
namespace ForceField\Utility;

class Color
{

    private $val;

    private $alp;

    public function __construct($value = 0, $alpha = 1)
    {
        if ($value instanceof Color)
            $value = $value->color_value;
        if (! is_numeric($value))
            $value = 0;
        $this->val = (int) $value;
        $this->alp = is_numeric($alpha) ? (float) $alpha : 1;
    }
    
    public static function hex( $hex, $alpha = 1 ) {
        return new Color( $hex, $alpha );
    }
    
    public static function rgba( $r, $g, $b, $a = 1 ) {
        return new Color( ( int ) hexdec( sprintf( '%02x%02x%02x', $r, $g, $b ) ), $a );
    }
    
    public static function rgb( $r, $g, $b, $a = 1 ) {
        return rgba( $r, $g, $b, $a ); // Alias
    }
    
    public static function cssrgb( $r, $g, $b  ) {
        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    public static function hexToRgb($hex)
    {
        // TODO Perfect this function (validate with regular expression)
        if (is_string($hex) && strlen($hex) > 0) {
            $hex = trim($hex);
            if ($hex[0] == '#')
                $hex = substr($hex, 1); // Remove #
            else if (strlen($hex) > 1 && $hex[0] == '0' && strtolower($hex[1]) == 'x')
                $hex = substr($hex, 2); // Remove 0x or 0X
            if (strlen($hex) == 6)
                list ($r, $g, $b) = array(
                    $hex[0] . $hex[1],
                    $hex[2] . $hex[3],
                    $hex[4] . $hex[5]
                );
            else if (strlen($hex) == 3)
                list ($r, $g, $b) = array(
                    $hex[0] . $hex[0],
                    $hex[1] . $hex[1],
                    $hex[2] . $hex[2]
                );
            else
                return FALSE; // Fail
            return array(
                hexdec($r),
                hexdec($g),
                hexdec($b)
            );
        } else
            return FALSE; // Fail
    }

    public function toHex()
    {
        return dechex($this->val);
    }

    public function toCSS()
    {
        if ($this->alp == 1)
            return '#' . dechex($this->val);
        else {
            $val = dechex($this->val);
            $val = $rbg = hextorgb($val);
            $r = $val[0];
            $g = $val[1];
            $b = $val[2];
            return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $this->alp . ')';
        }
    }

    public function value()
    {
        return $this->val;
    }

    public function alpha()
    {
        return $this->alpha;
    }

    public function __toString()
    {
        return $this->toCSS();
    }
}
