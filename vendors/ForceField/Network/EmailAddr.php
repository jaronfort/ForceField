<?php
namespace ForceField\Network;

class EmailAddr
{

    private $value;

    private $user;

    private $domain;

    public function __construct($email)
    {
        $this->parseEmail($email);
    }

    private function parseEmail($email)
    {
        if ($a = EmailAddr::parse($email)) {
            $this->value = $email;
            $this->user = $a['user'];
            $this->domain = $a['domain'];
        }
    }

    public static function mask($email, $mask_character = '*', $threshold = 1)
    {
        if ($a = EmailAddr::parse($email)) {
            $user = $a['user'];
            $domain = $a['domain'];
            $len = strlen($user);
            if ($len > 1) {
                $mask = substr($user, 0, $threshold);
                for ($i = $threshold; $i < $len - $threshold; $i ++) {
                    $mask .= $mask_character;
                }
                $mask .= substr($user, - $threshold);
                return $mask . '@' . $domain;
            } else
                return $email;
        }
        return FALSE;
    }

    public static function validate($email)
    {
        $email = trim($email);
        return preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email) ? $email : FALSE;
    }

    public static function parse($email)
    {
        if ($email = EmailAddr::validate($email)) {
            $a = [
                'user' => ''
            ];
            $len = strlen($email);
            for ($i = 0; $i < $len; $i ++) {
                $c = $email[$i];
                if (isset($a['domain']))
                    $a['domain'] .= $c;
                else if ($c == '@')
                    $a['domain'] = '';
                else
                    $a['user'] .= $c;
            }
            return $a;
        }
        return FALSE;
    }
    
    public function valid()
    {
        return $this->value != NULL;
    }

    public function masked($mask_character = '*', $threshold = 1)
    {
        return EmailAddr::mask($this->value, $mask_character, $threshold);
    }

    public function user()
    {
        return $this->user;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function mailto()
    {
        return $this->value ? 'mailto://' . $this->value : '';
    }
    
    public function __toString()
    {
        return $this->value != NULL ? $this->value : '';
    }

    public function __clone()
    {
        return new EmailAddr($this->value);
    }
}

