<?php
namespace ForceField\Routing;

use ForceField\Network\Url;
use Hologram\Web\Html;
use ForceField\Filesystem\File;
use ForceField\Network\Navigate;
use ForceField\Filesystem\Path;
use Hologram\Manifest\View;
use Hologram\Authenticate\Auth;
use Intervention\Image\Image;
use ForceField\Core\Configure;

class Router
{

    private static $routes = [];

    private static $error404 = [];

    private static $network_enabled = FALSE;

    private static $url;

    private static $path;

    private $scheme = '*';

    private $verb = '*';

    private $target;

    private $rule;

    private $name;

    private $host_tokens;

    private $path_tokens;

    private $params;

    private $limits;

    private $expect;

    private function __construct()
    {
        Router::$routes[] = $this;
    }

    private static function parse($route, $target, $verb)
    {
        $route = (string) $route;
        $r = new Router();
        $r->rule = $route;
        $r->verb = $verb;
        $r->target = $target;
        $start = 0;
        
        if (preg_match('/^(([a-zA-Z]+|{[a-zA-Z0-9_]+}):\/\/)?([a-zA-Z0-9\-]+|{[a-zA-Z0-9_]+})(\.([a-zA-Z0-9\-]+|{[a-zA-Z0-9_]+}))*(\/.*)+(\/{\.\.\.})?$/', $route)) {
            Router::$network_enabled = TRUE;
            $matches = [];
            $protocol = '*'; // Any
            
            if (preg_match('/^([a-zA-Z]+|{[a-zA-Z0-9_]+})\:\/\//', $route, $matches)) {
                // Extract protocol
                $protocol = str_replace('://', '', $matches[0]);
                $route = preg_replace('/^([a-zA-Z]+|{[a-zA-Z0-9_]+})\:\/\//', '', $route, 1);
                $r->params[trim(trim($protocol, '{'), '}')] = TRUE;
            }
            
            $host = [];
            // $dl = 1; // Domain levels detected
            $len = strlen($route);
            $buffer = '';
            
            for ($i = 0; $i < $len; $i ++) {
                $ch = $route[$i];
                $terminated = FALSE;
                
                if ($ch == '{') {
                    $param = '';
                    $buffer .= $ch;
                    
                    while (($i + 1 < $len)) {
                        $i ++;
                        $ch = $route[$i];
                        $buffer .= $ch;
                        
                        if ($ch == '}') {
                            if (is_array($r->params) && array_key_exists($param, $r->params))
                                throw new \Exception("Duplicate route parameter '{$param}' encountered.");
                            
                            $terminated = true;
                            $r->params[$param] = TRUE;
                            break;
                        } else
                            $param .= $ch;
                    }
                    
                    if ($terminated) {
                        if ($i == $len - 1) {
                            // If last character
                            $host[] = $buffer;
                            break;
                        } else
                            continue;
                    } else
                        throw new \Exception("Unterminated route parameter '{$param}'.");
                }
                if ($ch == '/') {
                    // If path reached
                    $host[] = $buffer;
                    break;
                } else if ($i == $len - 1) {
                    // If last character
                    $buffer .= $ch;
                    $host[] = $buffer;
                } else if ($ch == '.') {
                    $host[] = $buffer;
                    $buffer = '';
                    // $dl ++; // Count domain levels (for parsing)
                } else
                    $buffer .= $ch;
            }
            
            // $r->protocol = $protocol;
            $data = [
                $protocol
            ];
            
            $len = count($host);
            
            for ($j = 0; $j < $len; $j ++) {
                $data[] = $host[$j];
            }
            
            $r->host_tokens = $data;
            $start = $i;
            $len = strlen($route);
        } else
            $r->host_tokens = NULL;
        
        $path = substr($route, $start);
        
        if (preg_match('/^((\/{[a-zA-Z0-9_]+})|(\/.*))+(\/{\.\.\.})?$/', $path)) {
            $path = trim($path, '/');
            $segments = [];
            $len = strlen($path);
            $buffer = '';
            
            for ($i = 0; $i < $len; $i ++) {
                $ch = $path[$i];
                $terminated = FALSE;
                
                if ($ch == '{') {
                    $param = '';
                    $buffer .= $ch;
                    
                    while (($i + 1 < $len)) {
                        $i ++;
                        $ch = $path[$i];
                        $buffer .= $ch;
                        
                        if ($ch == '}') {
                            if (is_array($r->params) && array_key_exists($param, $r->params))
                                throw new \Exception("Duplicate route parameter '{$param}' encountered.");
                            
                            $terminated = true;
                            $r->params[$param] = TRUE;
                            break;
                        } else
                            $param .= $ch;
                    }
                    
                    if ($terminated) {
                        if ($i == $len - 1) {
                            // If last character
                            $segments[] = $buffer;
                            break;
                        } else
                            continue;
                    } else
                        throw new \Exception("Unterminated route parameter '{$param}'.");
                }
                
                if ($ch == '/') {
                    $segments[] = $buffer;
                    $buffer = '';
                } else
                    $buffer .= $ch;
                
                if ($i == $len - 1) {
                    // If last character
                    $segments[] = $buffer;
                }
            }
            
            $r->path_tokens = $segments;
        } else
            throw new \Exception("Invalid route '{$route}'.");
        
        return $r;
    }

    private function testVerb($verb = REQUEST_METHOD)
    {
        return $this->verb == '*' || $verb == $this->verb;
    }

    private function testHost(Url $url)
    {
        $tokens = $this->host_tokens;
        $protocol = $tokens[0];
        $scheme = $url->scheme();
        $args = [];
        
        // Check protocol as a paramter
        if ($protocol[0] == '{') {
            $protocol = trim(trim($protocol, '{'), '}');
            
            if ($this->testParam($protocol, $scheme))
                $args[] = $scheme; // Success!
            else
                return FALSE; // Fail (invalid protocol)
        } else if ($protocol != '*' && $protocol != $scheme)
            return FALSE; // Route not related to protocol
        
        $tokens = array_slice($tokens, 1);
        $tkn_len = count($tokens);
        $dl = explode('.', $url->domain());
        $expected_length = $tkn_len;
        
        // Check if number of domain levels match
        if (count($dl) != $tkn_len)
            return FALSE; // Fail (non-matching domains)
                          
        // Check each domain level
        for ($i = 0; $i < $tkn_len; $i ++) {
            $tkn = $tokens[$i];
            $seg = $dl[$i];
            
            if ($tkn[0] == '{') {
                $tkn = trim(trim($tkn, '{'), '}');
                $args[] = urldecode($seg);
                
                if ($this->testParam($tkn, $seg))
                    continue; // Success! (proceed)
                else
                    return FALSE; // Fail (parameter mismatch)
            } else if ($tkn != $seg)
                return FALSE; // Fail (not the same domain)
        }
        
        return $args;
    }

    private function testPath(array $segments)
    {
        $tokens = $this->path_tokens;
        $tkn_len = count($tokens);
        $seg_len = count($segments);
        $max = max($tkn_len, $seg_len);
        $args = [];
        $var_args = is_array($this->params) && array_key_exists('...', $this->params);
        
        if ($tkn_len == 0 && $seg_len == 0)
            return $args;
        
        for ($i = 0; $i < $max; $i ++) {
            $tkn = $i < $tkn_len ? $tokens[$i] : NULL;
            $seg = $i < $seg_len ? $segments[$i] : NULL;
            
            if (! is_null($seg) && ! is_null($tkn)) {
                if (strlen($tkn) > 0 && $tkn[0] == '{') {
                    // Test parameters
                    $tkn = trim(trim($tkn, '{'), '}'); // Trim { }
                    $args[] = urldecode($seg); // Push result even if failed
                    
                    if ($tkn == '...')
                        $var_args = TRUE; // Rest encountered
                    
                    if ($this->testParam($tkn, $seg))
                        continue; // Proceed
                    else
                        return FALSE; // Fail (parameter mistmatch)
                } else if ($tkn != $seg)
                    return FALSE; // Fail (segment not matched)
            } else if (is_null($tkn) && ! $var_args) {
                // Segment encountered without matching token
                return FALSE; // Fail
            } else if (! is_null($seg) && $var_args) {
                // Push restful arguments
                $args[] = urldecode($seg);
            } else
                return FALSE; // Fail (unreachable scenario)
        }
        
        return $args;
    }

    private function testParam($param, $value)
    {
        $limit = $this->params[$param];
        
        if ($limit === TRUE)
            return TRUE; // Fixed success!
        else if ($limit === FALSE)
            return FALSE; // Fixed fail
        else if (is_string($limit)) {
            if (preg_match("/^{$limit}$/", $value))
                return TRUE; // Success!
            else
                return FALSE; // Fail (invalid pattern)
        } else if (is_array($limit)) {
            foreach ($limit as $l) {
                if ($l == $value)
                    return TRUE; // Success!
            }
            
            return FALSE; // Fail (match not found)
        }
        
        return FALSE; // Invalid paramter limit
    }

    private function checkExpectations()
    {
        $result = NULL; // Unsatisfied

        if($this->expect)
        {

            foreach ($this->expect as $target => $value) {
                
                switch ($target) {
                    case 'user':
                        if (! Auth::exists())
                            $result = $value;
                        break;
                    case 'anomymous':
                        if (Auth::exists())
                            $result = $value;
                        break;
                    case 'ajax':
                        if (! AJAX) // TODO Confirm this works
                            $result = $value;
                        break;
                    case 'local':
                        if (! LOCALHOST)
                            $result = $value;
                        break;
                    default:
                        // Do nothing
                }
                
                //die($target);
                if(!is_null($result))
                    break;
            }
        }
        
        return is_null($result) ? TRUE : $result;
    }

    public static function attach($route, $target)
    {
        return Router::parse($route, $target, '*');
    }

    public static function get($route, $target)
    {
        return Router::parse($route, $target, 'GET');
    }

    public static function post($route, $target)
    {
        return Router::parse($route, $target, 'POST');
    }

    public static function put($route, $target)
    {
        return Router::parse($route, $target, 'PUT');
    }

    public static function delete($route, $target)
    {
        return Router::parse($route, $target, 'DELETE');
    }

    public static function error404($route, $target)
    {
        return Router::parse($route, $target, 404);
    }

    public static function resolve($value)
    {
        $val = trim((string) $value);
        $verb = func_num_args() > 1 ? func_get_arg(1) : REQUEST_METHOD;
        $route = null;
        $result = false;
        
        if (preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            // Named route
            foreach (Router::$routes as $r) {
                if ($r->name == $value) {
                    $route = $r;
                    break;
                }
            }
            
            if ($route == null)
                throw new \Exception("The '{$value}' route does not exist.");
        } else {
            
            $search_network = Router::$network_enabled;
            
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $url = new Url($value);
            } else {
                // Assume path
                $url = Url::current();
                $url->path($value);
            }
            
            Router::$url = $url;
            Router::$path = $url->path();
            
            $segments = $url->segment();
            $args = [];
            $error404 = false;
            $final = null;
            
            foreach (Router::$routes as $r) {
                
                if ($r->testVerb($verb)) {
                    
                    if ($search_network && $r->host_tokens) {
                        if (is_array($a = $r->testHost($url))) {
                            // Proceed
                            $args = array_merge($args, $a);
                        } else
                            continue; // Fail
                    }
                    
                    if (is_array($a = $r->testPath($segments))) {
                        $args = array_merge($args, $a);
                        $result = call_user_func_array($r, $args); // Invoke route
                        $route = $r;

                        switch (true) {
                            case $result === false:
                                continue; // Not resolved
                            case $result === 404:
                                // Force error 404
                                $result = false;
                                break;
                            // case $result === 500 : // Internal server error
                            case $result === true:
                            case is_string($result):
                            case is_array($result):
                            case $result instanceof File:
                            case ! is_null($result):
                            default: // Success!
                                break;
                        }
                        

                        // Statify expectations
                        $target = $route->checkExpectations();
                        
                        switch(true)
                        {
                            case is_callable($target) :
                                $result = call_user_func_array($target, $args);
                                break 2;
                            case $target === true:
                                break 2 ;
                            case is_string($target):
                            case is_array($target):
                            case $target instanceof File:
                            case ! is_null($target):
                                $result = $target;
                                break 2;
                            case $result === false;
                            default:
                                continue;
                        }
                    }
                }
            }
        }

        if($result === true)
            exit(0); // Force end script

        if(is_a('int', $result) && $result == 404)
        {
            $verb = 404;
        }
        else if($result instanceof Url)
        {
            Navigate::redirect($result);
            exit(0);
        }
        else if ($result instanceof File) {
            if ($result->exists())
                $result->read();
            else
                $verb = 404;
        } 
        else if($result instanceof Image) {
            echo $result->response();
            exit(0);
        }
        else if ($result || is_string($result) || is_array($result)) {
            if (false && is_a($result, '\Hologram\Api\REST')) {
                $rest = $result;
                $status = $rest->status();
                
                if (is_int($status)) {}
            } else if (is_array($result) || is_a($result, 'stdclass') || $result instanceof \JsonSerializable) {
                $pretty = defined('JSON_PRETTY_PRINT') && ff_config('api.json.pretty', true);
                $result = $pretty ? json_encode($result, JSON_PRETTY_PRINT) : json_encode($result);
                header('Content-Type: ' . ($pretty ? 'text' : 'application') . '/json');
            } else {
                $result = (string) $result;
                header('Content-Type: text/html');
            }
            
            echo $result;
            exit(0); // End
        }
        
        // Route not resolved (404)
        
        // Default 404 error
        if ($verb == 404) {
            // Custom 404 not found so use default
            $path = '/' . $url->path();
            $err = new Html();
            $err->title('404 Not Found');
            $err->body()->append("<h1>404 Not Found</h1><p>The requested resource \"{$path}\" does not exist.</p>");
            Navigate::error404($err);
            // End script
        }
        
        // 404 Not Found
        Router::resolve($value, 404);
    }

    public static function url()
    {
        return Router::$url;
    }

    public static function path()
    {
        return Router::$path;
    }

    public function name($name = null)
    {
        if ($name) {
            $this->name = $name;
            return $this->name;
        }
        
        return $this;
    }

    public function limit($param, $limit)
    {
        if (is_array($this->params) && array_key_exists($param, $this->params)) {
            if (is_bool($limit) || is_array($limit) || is_string($limit))
                $this->params[$param] = $limit;
            else
                throw new \Exception('Limit argument is expected to be a boolean, a string representing a regular expression, or an array of strings to compare against the related segment.');
        } else
            throw new \Exception("The specified parameter '{$param}' does not exist.");
        
        return $this;
    }

    /**
     * @param target Specifies a target argument to bind to the route. (user, anonymous, local, ajax, etc.)
     * @param arg Specifies a value to bind to the route. Expected to be a function, string, view, HTML, redirect URL, or JSON array.
     */
    public function expect($target, $value = NULL)
    {
        if (! is_array($this->expect))
            $this->expect = [];

        if(! is_string($target))
            throw new \Exception('The target argument is expected to be a string.');
        
        $this->expect[$target] = $value;
        return $this;
    }

    public function __invoke()
    {
        $args = func_get_args();
        $target = $this->target;
        
        if (is_callable($target))
            $target = call_user_func_array($target, $args);
        else if (is_string($target)) {
            if (is_file($target)) {
                // If path to resource
                return File::resolvePath(Path::resolve($target));
            } else if (preg_match('/[a-zA-Z_][a-zA-Z0-9_]*(\\\\[a-zA-Z_][a-zA-Z0-9_]*)*@[a-zA-Z_][a-zA-Z0-9_]*/', $target)) {
                // If controller method
                $tokens = explode('@', $target);
                $instance = load($tokens[0]);
                $method_name = $tokens[1];
                
                if (method_exists($instance, $method_name))
                    return call_user_func_array([
                        $instance,
                        $method_name
                    ], $args);
                
                // Not found
                return 404;
            } else if (filter_var($target, FILTER_VALIDATE_URL)) {
                // If 301 redirect url
                Navigate::redirect($target, 301);
            } else {
                // Error
                return 404;
            }
        }
        
        return $target;
    }
}

