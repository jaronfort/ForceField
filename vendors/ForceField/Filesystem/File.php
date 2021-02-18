<?php
namespace ForceField\Filesystem;

use ForceField\Core\Output;
use ForceField\Display\Image;

class File
{

    /**
     * Stores a path that does not exist.
     */
    private $falsepath;

    /**
     * Used to store the temporary name of an uploaded file.
     */
    private $tmp_name;

    /**
     */
    private $orig_name;

    /**
     */
    private $type;

    /**
     * Stores the absolute path of the file in the filesystem.
     */
    private $path;

    /**
     * Stores the parent directory of the stored file.
     */
    private $parent;

    /**
     * Stores the full relative filename to the file.
     */
    private $name;

    /**
     * Stores the resource handler supplied <code>fopen()</code> called when opening the file resource.
     */
    private $resource;

    /**
     * Specifies whether or not the file was uploaded to the server.
     */
    private $is_upload;

    public function __construct($path)
    {
        $this->init($path);
        $this->is_upload = $this->path ? is_uploaded_file($this->path) : FALSE;
    }

    public function __destruct()
    {
        $this->close();
    }

    private static function cleanFileName($name)
    {
        $len = strlen($name);
        $new_name = '';
        for ($i = 0; $i < $len; $i ++) {
            if (preg_match('/^[a-zA-Z0-9_\-\.]$/', $name[$i]))
                $new_name .= $name[$i];
        }
        return $new_name;
    }

    private static function getFileName($name)
    {
        $a = explode('.', $name);
        return count($a) > 1 ? implode('.', array_slice($a, 0, count($a) - 1)) : $name;
    }

    private static function getFileExt($name)
    {
        $a = explode('.', $name);
        return count($a) > 1 ? $a[count($a) - 1] : '';
    }

    private function init($path)
    {
        $this->path = is_string($path) ? Path::resolve($path) : $path;
        if ($this->path instanceof Path) {
            $this->falsepath = NULL; // Not needed
            $this->parent = Path::resolve(dirname($this->path));
            $this->name = basename($this->path);
        } else {
            $this->falsepath = $path;
            $this->parent = NULL;
            $this->name = NULL;
        }
    }

    public static function resolvePath($path)
    {
        $path = Path::resolve($path);
        if (file_exists($path))
            return new File($path);
        else
            return FALSE;
    }

    public static function getFileMimeType($filename)
    {
        $mime_type = FALSE;
        if (version_compare(phpversion(), '5.3', '>=')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $filename);
        } else
            $filename = mime_content_type($filename);
        return $mime_type;
    }

    /**
     * 
     * @param string $field
     * @param string $destination
     * @param mixed $error
     * @param string $file_class
     * @return File|Image
     */
    public static function accept($field = 'userfile', $destination = 'library/uploads', &$error = NULL, $file_class = 'ForceField\Filesystem\File')
    {
        if (isset($_FILES[$field])) {
            if (is_uploaded_file($_FILES[$field]['tmp_name'])) {
                if ($_FILES[$field]['error'] == UPLOAD_ERR_OK) {
                    if (!file_exists($destination)) {
                        if(!mkdir($destination, 0755)) {
                            // TODO Fail safe
                        }
                    }
                    $tmp_name = $_FILES[$field]['tmp_name'];
                    $name = File::cleanFileName($_FILES[$field]['name']);
                    $type = $_FILES[$field]['type'];
                    $destination .= '/' . $name;
                    if ($destination = realpath($destination) && move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                        $file = load($file_class, 'ForceField\Filesystem\File');
                        $file->tmp_name = $tmp_name;
                        $file->name = $destination;
                        $file->type = $type;
                        return $file;
                    } else {
                        // TODO Handle error
                    }
                } else if ($error)
                    $error = $_FILE[$field]['error'];
            } else {
                // TODO Handle error
            }
        }
        return FALSE; // Fail
    }

    public function load(&$var = null)
    {
        if ($var) {
            $var = $this->path ? file_get_contents($this->path) : false;
            return $this;
        } else
            return $this->path ? file_get_contents($this->path) : false;
    }

    public function create()
    {
        if ($this->falsepath) {
            if (! $this->resource)
                $this->open(); // Creates file
            $this->init($this->falsepath);
        }
        return $this;
    }

    public function open()
    {
        if (! $this->resource) {
            if ($this->falsepath) {
                $this->resource = fopen($this->falsepath, 'w');
                $this->init($this->falsepath);
            } else if ($this->path)
                $this->resource = fopen($this->falsepath);
            else {
                // TODO Handle Error
            }
        }
        return $this;
    }

    public function close()
    {
        if ($this->resource) {
            fclose($this->resource);
            $this->resource = NULL;
        }
        return $this;
    }

    public function clear()
    {
        if ($this->path)
            file_put_contents($this->path, '');
        return $this;
    }

    public function write($data, $auto_create = TRUE)
    {
        // Create if not exists
        if (! $this->path && $auto_create)
            $this->create();
        // Put contents
        if ($this->path) {
            // Override file
            // TODO Use fwrite when applicable
            file_put_contents($this->path, $data);
        }
        return $this;
    }

    public function read()
    {
        return Output::read($this->path);
    }

    public function delete()
    {
        if ($this->exists()) {
            if (is_dir($this->path)) {} else if (is_file($this->path)) {}
        }
        return $this;
    }

    public function move($destination, $overwrite = FALSE)
    {
        if ($this->path && $destination = Path::resolve($destination . '/' . $this->name)) {
            if (is_uploaded_file($this->path)) {
                if (move_uploaded_file($this->path, $destination))
                    $this->init($destination);
            } else {
                if (rename($this->path, $destination))
                    $this->init($destination);
            }
        }
        
        if ($this->path) {
            if ($destination == $this->path)
                return $this; // Skip (Already in location)
            $this->path = $destination;
        } else {
            if (move_uploaded_file($this->tmp_name, $destination . '/' . $this->name))
                $this->path = $destination;
            else {
                // TODO Handle error
            }
        }
        return $this;
    }

    public function rename($new_name, $overwrite = FALSE, $keep_ext = TRUE)
    {
        if ($this->path) {
            $ext = File::getFileExt($this->name);
            if ($keep_ext && $ext)
                $name = $new_name . '.' . $ext;
            else
                $name = $new_name;
            if (is_uploaded_file($this->path)) {
                // TODO Handle differently
            } else {
                $falsepath = $this->parent . '/' . $name;
                if (file_exists($falsepath) && ! $overwrite) {
                    $i = 0;
                    while (file_exists($falsepath)) {
                        $i ++;
                        if ($keep_ext && $ext)
                            $name = $name . '(' . $i . ').' . $ext;
                        else
                            $name = $new_name . '(' . $i . ')';
                        $falsepath = $this->parent . '/' . $name;
                    }
                }
                if (rename($this->path, $falsepath))
                    $this->init($falsepath);
            }
            return $this;
        }
        return $this;
    }

    public function copy($directory, $new_name, $overwrite = FALSE, $keep_ext = TRUE)
    {}

    public function ext($ext = NULL)
    {
        // if (! $this->tmp_name)
        // return FALSE;
        if (func_num_args() > 0) {
            // TODO Check valid extension
            $name = File::getFileName($this->name) . '.' . $ext;
            if (rename($this->path, $this->parent . '/' . $name))
                $this->init($this->parent . '/' . $name);
            return $this;
        } else
            return File::getFileExt($this->name);
    }

    public function extToLower()
    {
        // if (! $this->tmp_name)
        // return FALSE;
        $name = File::getFileName($this->name);
        $ext = File::getFileExt($this->name);
        $this->name = $name . '.' . strtolower($ext);
        return $this;
    }

    public function extToUpper()
    {
        // if (! $this->tmp_name)
        // return FALSE;
        $name = File::getFileName($this->name);
        $ext = File::getFileExt($this->name);
        $this->name = $name . '.' . strtoupper($ext);
        return $this;
    }

    public function base64(&$var = NULL)
    {
        if ($var != NULL) {
            $this->load($var);
            $var = base64_encode($this->load());
            return $this;
        } else
            return base64_encode($this->load());
    }

    public function name()
    {
        return $this->name ? $this->name : FALSE;
    }

    public function nameNoExt()
    {
        return $this->name ? File::getFileName($this->name) : FALSE;
    }

    public function tmpName()
    {
        return is_string($this->tmp_name) ? $this->tmp_name : FALSE;
    }

    public function exists()
    {
        return $this->path ? file_exists($this->path) : FALSE;
    }

    public function isDir()
    {
        return $this->path ? is_dir($this->path) : FALSE;
    }

    public function isFile()
    {
        return $this->path ? is_file($this->path) : FALSE;
    }

    public function isImage()
    {
        if ($this->path) {
            $mime_type = File::getFileMimeType($this->path);
            return $mime_type && preg_match('/image\//', $mime_type);
        }
        return FALSE;
    }

    public function isOpen()
    {
        return $this->resource != NULL;
    }

    public function isUpload()
    {
        return $this->is_upload;
    }

    public function fullPath()
    {
        return $this->path;
    }

    public function parent()
    {
        return $this->parent;
    }

    public function size($format = NULL)
    {
        return $this->path ? filesize($this->path) : FALSE;
    }

    public function mime()
    {
        return $this->path ? File::getFileMimeType($this->path) : FALSE;
    }

    public function __invoke($content_type = NULL)
    {
        return $this->path ? \Output::file($this->path, $content_type) : FALSE;
    }

    public function __toString()
    {
        return $this->path . '';
    }
    
}