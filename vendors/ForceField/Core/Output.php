<?php
namespace ForceField\Core;

use ForceField\Filesystem\File;

class Output
{

    private static $initialized = FALSE;

    private static $callbacks = [];

    private static $content_type = 'text/html';

    private static function init()
    {
        if (Output::$initialized)
            return;
        
        ob_start(function ($output) {
            // Set headers
            header('Content-Type: ' . Output::$content_type);
            foreach (Output::$callbacks as $callable) {
                $result = $callable($output);
                if (is_string($result))
                    $output = $result;
            }
        });
    }

    public static function set($data)
    {
        if (Output::$initialized)
            Output::init();
        else if(ob_get_contents())
            ob_clean();
        
        // Convert data to string
        switch (true) {
            case is_array($data):
            case is_object($data):
                $data = json_encode($data);
                break;
            case is_string($data):
            default:
            // Do nothing
        }
        echo $data;
    }

    public static function clear()
    {
	if(ob_get_contents())
        @ob_clean();
    }

    public static function get()
    {
        return ob_get_contents();
    }

    public static function contentType($content_type = NULL)
    {
        if (func_num_args() > 0)
            Output::$content_type = $content_type;
        return Output::$content_type;
    }

    public static function read($path, $content_type = NULL)
    {
        Output::clear();
        // Get content type if applicable
        if (! $content_type) {
            
            $ext = (new File($path))->ext();
            $content_type = null;
            
            switch ($ext) {
                case 'css':
                    $content_type = 'text/css';
                    break;
                case 'xml':
                    $content_type = 'application/xml';
                    break;
                case 'json':
                    $content_type = 'application/json';
                    break;
                case 'js':
                    $content_type = 'application/javascript';
                    break;
                case 'txt':
                    $content_type = 'plain/text';
                    break;
                case 'svg':
                    $content_type = 'image/svg';
                    break;
                default:
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $content_type = finfo_file($finfo, $path);
                    } else if (function_exists('exif_imagetype')) {
                        $content_type = exif_imagetype($path);
                        if ($content_type)
                            $content_type = image_type_to_mime_type($path);
                    }
            }
        }
        
        if ($content_type)
            header('Content-Type:' . $content_type);
        
        header('Content-Length: ' . filesize($path));
        header("X-Sendfile: {$path}");
        
        while (ob_get_level())
            @ob_end_clean();
        
        readfile($path); // Output file
        exit(0); // Skip
    }
}
