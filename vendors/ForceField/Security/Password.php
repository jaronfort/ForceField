<?php
namespace ForceField\Security;

use ForceField\Core\Configure;

class Password
{

    private static $salt;
    
    private $value;

    private $cost;

    private $cypher;

    private $cypher_name;

    private function __construct($hash)
    {
        $this->value = $hash;
        $this->initInfo();
    }
    
    private static function salt()
    {
        if(!is_string(Password::$salt))
            Password::$salt = Configure::readString('security.password.salt', '');
        
        return Password::$salt;
    }
    
    public static function get($hashed_password)
    {
        $pw = new Password($hashed_password);
        
        if ($pw->cypher_name != 'unknown')
            return $pw;
        
        return false;
    }

    public static function hash($raw_password, array $options = NULL)
    {
        $cypher = isset($options['cypher']) ? $options['cypher'] : Configure::readInt('security.password.cypher', PASSWORD_BCRYPT);
        $cost = isset($options['cost']) ? $options['cost'] : Configure::readInt('security.password.cost', 10);
        $pw = new Password(password_hash($raw_password . Password::salt(), $cypher, [
            'cost' => $cost
        ]));
        
        return $pw;
    }

    private function initInfo()
    {
        $info = password_get_info($this->value);
        
        if ($info) {
            $this->cypher = $info['algo'];
            $this->cypher_name = $info['algoName'];
            $this->cost = isset($info['cost']) ? $info['cost'] : FALSE;
            return true;
        }
        
        return false;
    }

    public function verify($raw_password)
    {
        return password_verify($raw_password . Password::salt(), $this->value);
    }

    public function needsRehash()
    {
        return password_needs_rehash($this->value . Password::salt(), $this->cypher, [
            'cost' => $this->cost
        ]);
    }

    public function cost()
    {
        return $this->cost;
    }

    public function cypher()
    {
        return $this->cypher;
    }

    public function cypherName()
    {
        return $this->cypher_name;
    }

    public function __toString()
    {
        return $this->value;
    }
}