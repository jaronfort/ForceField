<?php
namespace ForceField\Utility;

class DataURI
{

    private static function load($file, $max_size = NULL)
    {
        $file = realpath($file);
        return (file_exists($file) && (is_int($max_size) && filesize($file) <= $max_size)) ? base64_encode(file_get_contents($file)) : NULL;
    }

    public static function image($image, $max_size = NULL)
    {
        $data = DataURI::load($image, $max_size);
        if ($data) {
            $type = exif_imagetype($image);
            if ($type !== FALSE)
                $mime = image_type_to_mime_type($type);
            else
                return NULL;
            return 'data:' . $mime . ';base64,' . $data;
        } else
            return NULL;
    }
    
}
