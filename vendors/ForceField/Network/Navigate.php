<?php
namespace ForceField\Network;

class Navigate
{

    public static function redirect($url, $reponse_code = 301)
    {
        if ($url = URL::get($url)) {
            $reponse_code = (int) $reponse_code;
            http_response_code($reponse_code);
            header('Location: ' . $url);
            exit(0);
        } else
            return FALSE;
    }

    public static function error404($output)
    {
        header('HTTP/1.0 404 Not Found');
        http_response_code(404);
        echo $output;
        exit(0);
    }
}

