<?php
namespace Hologram\Web;

use MatthiasMullie\Minify\JS;

class JavaScript
{
    
    public static function minify($js)
    {
        $minifier = new JS('');
        $minifier->add($js);
        return $minifier->minify();
    }
}

