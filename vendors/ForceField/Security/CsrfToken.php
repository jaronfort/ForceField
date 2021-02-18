<?php
namespace ForceField\Security;

class CsrfToken
{
    
    private $name;
    
    private $value;
    
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
    
    public function name()
    {}
    
    public function value()
    {}
    
    public function __toString()
    {
        return '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '" />';
    }
}

