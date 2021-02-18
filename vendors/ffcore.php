<?php
use ForceField\Core\ForceField;
use ForceField\Core\Output;
use ForceField\Network\Url;
use ForceField\Web\Html;
use ForceField\Filesystem\Path;
use ForceField\Filesystem\File;
use Hologram\Web\WebForm;
use Hologram\Manifest\View;
use ForceField\Core\Configure;
use Hologram\Authenticate\Auth;
use Hologram\Relations\Model;
use JShrink\Minifier;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Gibber jabber and magic
 */

// Process/clean the request URI and parse into URI segment tokens.
$request_uri = strlen($_SERVER['REQUEST_URI']) > 0 ? substr($_SERVER['REQUEST_URI'], 1) : $_SERVER['REQUEST_URI'];
if ($request_uri && $request_uri[strlen($request_uri) - 1] == '/')
    $request_uri = substr($request_uri, 0, strlen($request_uri) - 1);
$uri_segments = explode('/', trim(strpos($request_uri, '?') !== FALSE ? substr($request_uri, 0, strpos($request_uri, '?')) : $request_uri, '/'));

// Initialize base path
$basepath = realpath($_SERVER['DOCUMENT_ROOT'] . '/' . dirname($_SERVER['SCRIPT_NAME']));

// Detect local host
$is_local = isset($_SERVER['REMOTE_ADDR']) ? in_array($_SERVER['REMOTE_ADDR'], [
    '127.0.0.1',
    '::1'
]) : null;

// Detect request protocol (HTTP or HTTPS)
$is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || ! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on');
$protocol = $is_secure ? 'https' : 'http';

// Determine server host name
$server_host = $_SERVER['HTTP_HOST'];

// Validate remote address
$remote_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) or '0.0.0.0';

/**
 * The "base" path for the <code>index.php</code> script.
 */
define('BASEPATH', $basepath);

/**
 * The path to the source folder containing the application's PHP classes and interfaces.
 */
defined('SRCPATH') or define('SRCPATH', __DIR__);

/**
 * The path to the application directory containing the controllers, views, models, and application logic.
 */
defined('APPPATH') or define('APPPATH', BASEPATH . '/app');

/**
 * The path to the library directory containing the application's assets, configuration files, etc.
 */
defined('LIBPATH') or define('LIBPATH', BASEPATH . '/library');

/**
 * The path to the public directory in which all publically accessible files exists.
 * All contents of this directory may be accessed automatically without the need for attaching a route.
 */
defined('PUBPATH') or define('PUBPATH', BASEPATH . '/public');

/**
 * The host name of the server.
 * This is usually the domain name of the requested page.
 */
define('SERVER_HOST', $server_host);

/**
 */
define('LOCALHOST', $is_local ? $server_host : FALSE);

/**
 */
define('URI_SEGMENTS', implode('/', $uri_segments));

/**
 */
define('URI_PATH', '/' . URI_SEGMENTS);

/**
 */
define('QUERY_STRING', $_SERVER['QUERY_STRING']);

/**
 */
define('REMOTE_ADDRESS', $remote_address);

/**
 */
define('SERVER_ADDRESS', $_SERVER['SERVER_ADDR']);

/**
 */
define('SERVER_PORT', $_SERVER['SERVER_PORT']);

/**
 */
define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);

/**
 * The protocol used when making the HTTP request.
 * Will be 'http' or 'https'.
 */
define('SERVER_PROTOCOL', $protocol);

/**
 * The HTTP Origin of the request.
 * 
 * @var unknown
 */
define('HTTP_ORIGIN', isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '');

/**
 */
define('AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));

/**
 * The version of the current ForceField deployment.
 */
define('FF_VERSION', '1.0.0');

/**
 * A linefeed character.
 */
define('LN', "\n");


// Essential autoloading of classes and interfaces that exist within the source directory
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $path = '/' . $class . '.php';
    if (is_file(SRCPATH . $path)) {
        include_once SRCPATH . $path;
        return true;
    } else if (is_file(APPPATH . $path)) {
        include_once APPPATH . $path;
        return true;
    }
    
    $tokens = explode('/', $class);
    if (strtolower($tokens[0]) == 'app') {
        $count = count($tokens);
        $classname = $tokens[$count - 1];
        $path = '/' . strtolower(implode('/', array_slice($tokens, 1, $count - 2))) . '/' . $classname . '.php';
        
        if (is_file(APPPATH . $path)) {
            include_once APPPATH . $path;
            return true;
        }
    }
    
    return false;
});

// Load Core
$dir = scandir(SRCPATH . '/ForceField/Core/');
foreach ($dir as $file) {
    if (strrpos($file, '.php') == strlen($file) - 4) {
        // Load if ends file matches *.php
        require_once SRCPATH . '/ForceField/Core/' . $file;
    }
}

// Start output buffering
Output::set('');

function ff_setup($settings = null)
{
    ForceField::setup($settings);
}

function ff_exec()
{
    ForceField::exec();
}

/**
 *
 * @param string $key
 * @param mixed $default_val
 * @return mixed
 */
function ff_config($key = null, $default_val = null)
{
    return call_user_func_array([
        'ForceField\Core\Configure',
        'read'
    ], func_get_args());
}

function ff_mode()
{
    $mode = ff_config('app.mode', 'development');
    
    // TODO Check development on IP
    
    return $mode;
}

function srcpath($load)
{
    if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*\:\:[a-zA-Z0-9][a-zA-Z0-9_]*$/', trim($load)))
        $path = SRCPATH . '/constants/' . str_replace('::', '/', $load) . '.php';
    else {
        $path = SRCPATH . '/' . str_replace('\\', '/', $load);
        if (is_dir($path))
            return $path;
        $path .= '.php';
    }
    return is_file($path) ? $path : FALSE;
}

function basepath($load)
{
    if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*\:\:[a-zA-Z0-9][a-zA-Z0-9_]*$/', trim($load)))
        $path = BASEPATH . '/constants/' . str_replace('::', '/', $load) . '.php';
    else {
        $path = BASEPATH . '/' . str_replace('\\', '/', $load);
        if (is_dir($path))
            return $path;
        $path .= '.php';
    }
    return is_file($path) ? $path : FALSE;
}

function load($load, $type = null)
{
    $args = array_slice(func_get_args(), 2);
    
    if (! $load)
        throw new Exception('Load must not be a string that represents a class or interface within the source path.');
    
    if (class_exists($load)) {
        $r = new ReflectionClass($load);
        if ($r->isInstantiable() && (! $type || $load == $type || $r->isSubclassOf($type))) {
            // Success
            return $r->newInstanceArgs($args);
        }
    } else if (interface_exists($load))
        return TRUE;
    else if ($path = srcpath($load)) {
        include_once $path;
        if (class_exists($load))
            return load($load, $type);
    }
    
    return FALSE; // Fail
}

function load_array($load, array $args, $type = null)
{
    return call_user_func_array('load', array_merge([
        $load,
        $type
    ], $args));
}

function load_result($src)
{
    return include $src;
}

/**
 * Loads a global variable stored inside a particular file.
 *
 * @param string $src
 * @param string $name
 *            The variable in which to load. Loads all variables if value is left null.
 * @return mixed|boolean
 */
function load_var($src, $name = null)
{
    include $src;
    $vars = get_defined_vars();
    if (is_string($name)) {
        if (isset($vars[$name]))
            return $vars[$name];
    } else if (func_num_args() == 1)
        return $vars;
    return FALSE;
}

function capture($src, array $data = null)
{
    unset($src);
    unset($vars);
    if (func_num_args() > 1 && is_array(func_get_arg(1))) {
        foreach (func_get_arg(1) as $name => $val) {
            $$name = $val;
        }
    }
    ob_start();
    include func_get_arg(0); // PHP will be executed producing an output
    $result = ob_get_contents();
    ob_end_clean(); // Clear included output
    return $result ? $result : '';
}

$__dwoo;

function dwoo()
{
    global $__dwoo;
    
    if (! $__dwoo)
        $__dwoo = new Dwoo\Core();
    
    return $__dwoo;
}

function load_template($template_path, array $data = null)
{
    $core = dwoo();
    $tpl = new Dwoo\Template\File($template_path);
    $dwoo_data = new Dwoo\Data();
    
    if ($data) {
        
        // Populate
        foreach ($data as $key => $val) {
            $dwoo_data->assign($key, $val);
        }
    }
    
    return $core->get($tpl, $dwoo_data);
}

function parse_template($template_string, array $data = null)
{
    $core = dwoo();
    $tpl = new \Dwoo\Template\Str($template_string);
    $dwoo_data = new Dwoo\Data();
    
    if ($data) {
        
        // Populate
        foreach ($data as $key => $val) {
            $dwoo_data->assign($key, $val);
        }
    }
    
    return $core->get($tpl, $dwoo_data);
}

function PluginStyle(Dwoo\Core $core, $value)
{
    $prod = LIBPATH . '/css/' . $value . '.css';
    $min = LIBPATH . '/css/' . $value . '.min.css';
    $css = '';
    
    if (is_string($value) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_\-]*(\/[a-zA-Z_][a-zA-Z0-9_\-]*)*$/', $value)) {
        
        if (file_newer($prod, $min) == $prod) {
		// If development CSS has been modified, update the minified version
		$css = file_get_contents(LIBPATH . '/css/' . $value . '.css');
		$css = \Hologram\Web\Css::parse($css);
		//$css->minified((ff_mode() == 'production'));
		//@file_put_contents($min, $css);
        } else if (is_file($min)) {
            // Production
            $css = file_get_contents($min);
        } else if (ff_mode() == 'development') {
            // Error
            $css = '/* Template Error!: "' . $prod . '" does not exist. */';
        }
    } else if (ff_mode() != 'production') {
        // Error
        $css = '/* Template Error!: Invalid CSS "' . $value . '" name. */';
    }
    
    // Fail safe
    return '<style type="text/css">' . $css . '</style>';
}

function PluginScript(Dwoo\Core $core, $value)
{
    $prod = LIBPATH . '/js/' . $value . '.js';
    $min = LIBPATH . '/js/' . $value . '.min.js';
    $js = '';
    
    if (is_string($value) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_\-]*(\/[a-zA-Z_][a-zA-Z0-9_\-]*)*$/', $value)) {
        
        if (file_newer($prod, $min) == $prod) {
            // If development JS has been modified, update the minified version
            $js = file_get_contents($prod);
            /*$minified = $js = \Hologram\Web\JavaScript::minify($js);
            @file_put_contents($min, $minified);*/
        } else if (is_file($min)) {
            // Production
            $js = file_get_contents($min);
        } else if (ff_mode() != 'production') {
            // Error
            $js = '/* Template Error!: "' . $prod . '" does not exist. */';
        }
    } else if (ff_mode() != 'production') {
        // Error
        $js = '/* Template Error!: Invalid JavaScript "' . $value . '" name. */';
    }
    
    // Fail safe
    return '<script>' . $js . '</script>';
}

function PluginSvg(Dwoo\Core $core, $value)
{
    $svg = '';
    $path = LIBPATH . '/images/' . $value . '.svg';
    
    if (is_file($path)) {
        // Load SVG
        $svg = file_get_contents($path);
    }
    
    return $svg;
}

function PluginForm(Dwoo\Core $core, $value)
{
    $form = '';
    $path = APPPATH . '/forms/' . $value . '.php';
    
    if (is_file($path)) {
        // Load Form
        $form = load_template($path);
    }
    
    return (string) $form;
}

function PluginLang(Dwoo\Core $core, $value)
{
    return lang($value);
}

function view($view = null, $data = null)
{
    $core = dwoo();
    
    if (is_string($view))
        return new View(load_template(APPPATH . '/views/' . $view . '.php', $data));
    else if (is_null($view))
        return new View();
    
    return false;
}

function form($form, $data = null)
{
    return new \Hologram\Web\Form(new \Hologram\Web\Html(load_template(APPPATH . '/forms/' . $form . '.php', $data)));
}

/**
 * Generates a route.
 *
 * @param unknown $value
 * @throws \Exception
 * @return unknown
 */
function route($value)
{
    $route = Router::resolve($value, true);
    
    if ($route == null)
        throw new \Exception('Failed to resolve route \" . $value . ".');
    
    return $route->url();
}

/**
 * Generates a network route.
 *
 * @param unknown $domain
 * @param unknown $value
 */
function nroute($domain, $value)
{}

function lang($key, $default = '', array $data = null, $ext = '*')
{
    $val = \ForceField\l10n\Lang::read($key, $ext);
    
    if ($val == '')
        $val = $default;
    
    return parse_template($val, $data);
}

/**
 * Helpers
 */
function domain()
{
    $current_url = Url::current();
    return func_num_args() == 0 ? $current_url->domainName() : call_user_func_array($current_url->domain, func_get_args());
}

function subdomain()
{
    $current_url = Url::current();
    return $current_url->subdomain();
}

function site()
{
    return call_user_func_array([
        Url::class,
        'site'
    ], func_get_args());
}

function url()
{
    if(func_num_args() > 0 && $url = filter_var(func_get_arg(0), FILTER_VALIDATE_URL))
    {
        if(LOCALHOST && isset($_GET['ffurl']) && $_GET['ffurl'])
        {
            // Generate localhost URL
            return Url::real()->query('ffurl=' . $url);
        }
        return $url;
    }

    $url = call_user_func_array('site', func_get_args());

    if(LOCALHOST && isset($_GET['ffurl']) && $_GET['ffurl'])
    {
        // Generate localhost URL
        return Url::real()->query('ffurl=' . $url);
    }

    return $url;
}

function redirect($url, $response_code = 301)
{
    \ForceField\Network\Navigate::redirect($url, $response_code);
}

function realUrl()
{
    return call_user_func_array([
        Url::class,
        'real'
    ], func_get_args());
}

function file_newer($path_a, $path_b)
{
    // Check if primary file exists
    if (is_file($path_a)) {
        
        // Check if secondary file exists
        if (is_file($path_b)) {
            
            // Get modification times
            $a = @filemtime($path_a);
            $b = @filemtime($path_b);
            
            // Return true if equal
            if ($a == $b)
                return true;
            else // Otherwise, return the newer path
                return $a > $b ? $path_a : $path_b;
        }
        
        return $path_a;
    }
    
    // Fail
    return false;
}

function getfile($path)
{
    $path = Path::resolve($path);
    
    if ($path && file_exists((string) $path))
        return new File($path);
    
    return false; // Fail
}

function lib($segments)
{}

function css($css)
{
    $min = LIBPATH . '/css/' . $css . '.min.css';
    $prod = LIBPATH . '/css/' . $css . '.css';
    
    if (file_newer($prod, $min) == $min)
        return getfile($min);
    
    $file = getfile($prod);
    
    if ($file && Configure::readBool('minify.css', false)) {
        // Saved minified version for later
        $minified = \Hologram\Web\Css::minify(file_get_contents($prod)); // Minified

        if (file_put_contents($min, $minified))
            return getfile($min);
    } else if (is_file($min))
        return getfile($min);
    
    return $file;
}

function js($js)
{
    $min = LIBPATH . '/js/' . $js . '.min.js';
    $prod = LIBPATH . '/js/' . $js . '.js';
    
    if (file_newer($prod, $min) == $min)
        return getfile($min);
    
    $file = getfile($prod);
    
    if ($file && Configure::readBool('minify.js', false)) {
        // Saved minified version for later
        $minified = \Hologram\Web\JavaScript::minify(file_get_contents($prod));

        if (file_put_contents($min, $minified))
            return getfile($min);
    } else if (is_file($min))
        return getfile($min);
    
    return $file;
}

function image($img)
{
    $path = LIBPATH . '/images/' . $img;
    
    if(is_file($path))
        return Image::make($path);
    
    return false;
}

function font($font)
{
    return getfile(LIBPATH . '/fonts/' . $font);
}

/**
 * Gets the signed in user or returns <code>false</code> if the current user is not logged in.
 *
 * @return mixed
 */
function user()
{
    return Auth::user();
}

/**
 * Specifies whether or not the current user is anonymous.
 * Returns <code>false</code> the current user is logged in.
 *
 * @return boolean
 */
function is_anonymous()
{
    return ! Auth::exists();
}

/**
 * Authenticates the current user using the <code>ForceField\Authenticate\Auth</code> class.
 *
 * @param unknown $user
 * @param unknown $pass
 * @return boolean|unknown
 */
function login($user, $pass)
{
    return Auth::login($user, $pass);
}

/**
 * Terminates the session of the current user.
 *
 * @return boolean
 */
function logout()
{
    return Auth::logout();
}

function db($table = null)
{
    $db = Model::getDb();
    
    return $table ? $db->$table() : $db;
}

function row($table, array $data = null) {
    $db = Model::getDb();
    $target = $db->$table();
    return $target->createRow($data ? $data : []);
}

function isDigit($val)
{
    return preg_match('/^[0-9]$/', val);
}

function isLetter($val)
{
    return preg_match('/^[a-z]$/i', val);
}

function isWhiteSpace($val)
{
    return preg_match('/^[\s\n]$/', $val);
}

function trimWords($val)
{
    return implode(' ', preg_split('/[\s\n]+/', trim($val)));
}

function upper($val)
{
    return strtoupper($val);
}

function lower($val)
{
    return strtolower($val);
}

function alpha($val)
{
    $s = '';
    $len = val . length;
    $char;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isLetter($char))
            $s .= $char;
    }
    
    return s;
}

function alphanum($val)
{
    $s = '';
    $len = strlen($val);
    $char;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isLetter($char) || isDigit($char))
            $s .= $char;
    }
    
    return $s;
}

function alphanumSpace($val)
{
    $s = '';
    $len = strlen($val);
    $char;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isLetter($char) || isDigit($char) || $char == ' ')
            $s .= $char;
    }
    
    return $s;
}

function alphanumUnderscore($val)
{
    $s = '';
    $len = strlen($val);
    $char;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isLetter($char) || isDigit($char) || $char == '_')
            $s .= $char;
    }
    
    return s;
}

function hydraateID($val)
{
    // Append @ if value exists
    $result = '@' + alphanumUnderscore(trim($val, '@'));
    
    if ($val == '')
        return '';
    else
        return $result;
}

function integer($val)
{
    $s = '';
    $len = strlen($val);
    $char;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isDigit($char))
            $s .= $char;
    }
    
    return $val;
}

function number($val)
{
    return $val;
}

function fullName($val)
{
    $val = ucwords($val);
    
    $len = strlen($val);
    $a = [];
    $buffer = '';
    $char;
    $ws = 0;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isWhiteSpace($char)) {
            
            if ($buffer) {
                array_push($a, $buffer);
                $buffer = '';
                
                if ($ws < 1) {
                    
                    array_push($a, ' ');
                    $ws ++;
                }
            }
        } else if (isLetter($char))
            $buffer .= $char;
    }
    
    if ($buffer)
        array_push($a, $buffer);
    
    $val = implode('', $a);
    
    return $val;
}

function fullNameWithMiddle($val)
{
    $val = ucwords($val);
    
    $len = strlen($val);
    $a = [];
    $buffer = '';
    $char;
    $ws = 0;
    
    for ($i = 0; $i < $len; $i ++) {
        $char = $val[$i];
        
        if (isWhiteSpace($char)) {
            
            if ($buffer) {
                array_push($a, $buffer);
                $buffer = '';
                
                if ($ws < 2) {
                    
                    array_push($a, ' ');
                    $ws ++;
                }
            }
        } else if (isLetter($char))
            $buffer .= $char;
    }
    
    if (buffer)
        a . push(buffer);
    
    $val = implode('', $a);
    
    return $val;
}

function parseFullName($value)
{
    $name = explode(' ', fullName($value));
    $c = count($name);
    
    switch ($c) {
        case 3:
            return [
                'first' => $name[0],
                'middle' => $name[1],
                'last' => $name[2]
            ];
        case 2:
            return [
                'first' => $name[0],
                'last' => $name[1]
            ];
        case 1:
        default:
            return [
                'first' => $name[0]
            ];
    }
    
    return [];
}