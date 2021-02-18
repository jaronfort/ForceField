<?php
namespace Hologram\Halo;

class Template
{

    public function __construct()
    {}
    
    public function load($view)
    {
        $result = '';
        $contents = file_get_contents(APPPATH . '/views/' . $view . '.halo.php');
        $len = strlen($contents);
        
        die($result);
    }

    public function render()
    {
        echo (string) $this;
    }

    public function __toString()
    {
        $s = '[todo: render templates]';
        
        return $s;
    }
}

