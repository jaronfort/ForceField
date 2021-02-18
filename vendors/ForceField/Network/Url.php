<?php
namespace ForceField\Network;

use ForceField\Core\Configure;
use ForceField\Utility\ArrayUtil;
use ForceField\Core\Input;

class Url
{

    private static $request_uri;

    private static $is_secure;

    private static $is_local;

    private static $base;

    private static $current;

    private static $real;

    private $scheme;

    private $user;

    private $password;

    private $host;

    private $port;

    private $path;

    private $query;

    private $fragment;

    private $auto_lowercase;

    private $is_valid;

    public function __construct($url = NULL, $auto_lowercase = TRUE)
    {
        $this->auto_lowercase = $auto_lowercase;
        if (is_string($url) || $url instanceof Url)
            $this->parseUrl((string) $url);
        else
            throw new \Exception('Expecting a string or Url instance.');
    }

    private function parseUrl($url)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! $scheme) // Prep url
            $url = 'http://' . $url;
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            if ($url = filter_var($url, FILTER_SANITIZE_URL)) {
                $this->host = preg_replace('/[a-zA-Z]+\:\/\//', '', $url);
                $a = parse_url($url);
                $this->scheme = $a['scheme'];
                if (isset($a['user']))
                    $this->user = $a['user'];
                if (isset($a['pass']))
                    $this->password = $a['pass'];
                $this->host = $a['host'];
                $this->port = array_key_exists('port', $a) ? $a['port'] : NULL;
                if (isset($a['path']) && $a['path'] != '/') {
                    $a['path'] = substr($a['path'], 1); // Remove first /
                    $segments = [];
                    $path = explode('/', trim($a['path'], '/'));
                    foreach ($path as $seg) {
                        $segments[] = urldecode($seg);
                    }
                    $this->path = $segments;
                } else
                    $this->path = [];
                $this->query = [];
                if (array_key_exists('query', $a)) {
                    $parts = [];
                    parse_str($a['query'], $parts);
                    foreach ($parts as $name => $val) {
                        $this->query[urldecode($name)] = urldecode($val);
                    }
                }
                $this->fragment = isset($a['fragment']) ? $a['fragment'] : NULL;
                $this->is_valid = TRUE;
                if ($this->auto_lowercase) {
                    $this->scheme = strtolower($this->scheme);
                    $this->host = strtolower($this->host);
                }
            }
        } else
            $this->is_valid = FALSE;
    }

    public static function get($url)
    {
        if ($url instanceof Url)
            $instance = $url;
        else
            $instance = new Url($url);
        return $instance->is_valid ? $instance : FALSE;
    }

    public static function redirect($url, $status_code = 301)
    {
        if ($url = Url::get($url)) {
            switch ($status_code) {
                default:
                // Do nothing
            }
            header('Location: ' . $url);
            exit(0);
        } else
            return FALSE;
    }

    public static function validate($url)
    {
        return filter_var(trim($url), FILTER_VALIDATE_URL);
    }

    public static function parse($url)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! $scheme) // Prep url
            $url = 'http://' . $url;
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            if ($url = filter_var($url, FILTER_SANITIZE_URL)) {
                return parse_url($url);
            }
        } else
            return FALSE;
    }

    public static function prep($url, $scheme = 'http')
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $s = parse_url($url, PHP_URL_SCHEME);
            $scheme = is_string($scheme) ? $scheme : 'http';
            if ($s)
                return $url;
            else if ($scheme) {
                if (preg_match('/^[a-zA-Z0-9]+\:\/\/$/', $scheme))
                    return $scheme . $url;
                else if (preg_match('/^[a-zA-Z0-9]+$/', $scheme))
                    return $scheme . '://' . $url;
            }
        }
        return $url;
    }

    public static function real()
    {
        if(Url::$real)
            return clone Url::$real;
        
        $scheme = SERVER_PROTOCOL;
        $domain = SERVER_HOST;
        $port = ':' . SERVER_PORT;
        
        if ((SERVER_PROTOCOL == 'http' && SERVER_PORT == 80) || (SERVER_PROTOCOL == 443)) // Omit default port
            $port = '';
        
        $path = '/' . URI_SEGMENTS;
        $query = QUERY_STRING ? '?' . QUERY_STRING : '';
        $index = ''; // TODO Get index file from config
        Url::$real = new Url($scheme . '://' . $domain . $port . $index . $path . $query);

        return Url::$real;
    }

    public static function base()
    {
        if (Url::$base)
            return clone Url::$base;
        
        $options = func_get_args();
        $scheme = SERVER_PROTOCOL;
        $domain = SERVER_HOST;
        $port = ':' . SERVER_PORT;
        if ((SERVER_PROTOCOL == 'http' && SERVER_PORT == 80) || (SERVER_PROTOCOL == 443)) // Omit default port
            $port = '';
        $index = ''; // TODO Get index file from config
        Url::$base = new Url($scheme . '://' . $domain . $port . $index);
        
        return Url::base(); // Get copy
    }

    /**
     *
     * @return Url
     */
    public static function current()
    {
        if (Url::$current)
            return clone Url::$current;
        
        if (LOCALHOST && $val = Input::get('ffurl'))
        {
            $url = new Url($val);
            Url::$current = $url;
            return $url;
        }
        
        $options = func_get_args();
        $scheme = SERVER_PROTOCOL;
        $domain = SERVER_HOST;
        $port = ':' . SERVER_PORT;
        
        if ((SERVER_PROTOCOL == 'http' && SERVER_PORT == 80) || (SERVER_PROTOCOL == 443)) // Omit default port
            $port = '';
        
        $path = '/' . URI_SEGMENTS;
        $query = QUERY_STRING ? '?' . QUERY_STRING : '';
        $index = ''; // TODO Get index file from config
        Url::$current = new Url($scheme . '://' . $domain . $port . $index . $path . $query);
        return Url::current(); // Get copy
    }

    public static function site($segments = NULL, $base = NULL)
    {
        // Use current url or supplied base url
        if(is_a('\ForceField\Network\Url', $base)) { /* Do nothing */ }
        else if(is_string($base))
            $url = new Url($base);
        else if($base)
            throw new \Exception('Argument two is expected to be a string or Url instance.');

        $url = Url::current();

        $result = implode('/', func_get_args());
        $a = explode('/', $result);
        $result = [];

        foreach ($a as $seg) {
            if ($seg) // Skip double slashes
                $result[] = urlencode($seg);
        }

        // Set path
        $url->path(implode('/', $result));

        return $url;
    }

    public function domain($domain_level = NULL)
    {
        if (! $this->valid())
            return FALSE;
        if (func_num_args() > 1 && is_string($new_value = func_get_arg(1))) {
            if (! is_null($new_value)) {
                if (! is_string($new_value) && ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\_]*[a-zA-Z0-9]*$/', $new_value) && $new_value != '')
                    throw new \Exception('Expecting argument two to be a valid string.');
                $levels = preg_match('/^[a-z0-9\-]+(\.[a-z0-9\-]+)*(\.[a-z]+)$/', $this->host) ? explode('.', $this->host) : [
                    $this->host
                ];
                $num_levels = count($levels);
                if (is_int($domain_level) && $domain_level <= $num_levels) {
                    for ($k = 1; $k <= $num_levels; $k ++) {
                        $i = $num_levels - $k;
                        if ($k == $domain_level || $domain_level == $levels[$i])
                            $levels[$i] = $new_value;
                    }
                    $levels = ArrayUtil::remove('', $levels);
                    $this->host = implode('.', $levels);
                } else {
                    $new = [];
                    for ($k = $num_levels + 1; $k <= $domain_level; $k ++) {
                        $new[] = $new_value;
                    }
                    $levels = array_merge($new, $levels);
                    $levels = ArrayUtil::remove('', $levels);
                    $this->host = implode('.', $levels);
                }
                $this->parseUrl((string) $this); // Revalidate
                return $this;
            }
        } else if (func_num_args() > 0) {
            if (preg_match('/^[a-z0-9\-]+(\.[a-z0-9\-]+)*(\.[a-z]+)$/', $this->host)) {
                $levels = explode('.', $this->host);
                $num_levels = count($levels);
                $result = [];
                $args = func_get_args();
                foreach ($args as $i) {
                    $i = $num_levels > 0 ? $num_levels - $i : - 1;
                    if ($i >= 0 && $i < $num_levels)
                        $result[] = $levels[$i];
                }
                return implode('.', $result);
            } else
                return FALSE;
        } else
            return $this->host;
        
        return NULL;
    }

    public function subdomain($k = 0, $new_value = NULL)
    {
        if (is_string($k))
            return $this->domain(3, $k);
        else if (! is_int($k))
            throw new \Exception('Expecting argument one to be an integer.');
        if (! $this->is_valid)
            return FALSE;
        return $this->domain(3 + $k, $new_value); // Default behavior
    }

    public function clearSubdomains()
    {
        $this->host = $this->domain(2, 1);
        $this->parseUrl((string) $this); // Revalidate
        return $this;
    }

    public function topLevelDomain()
    {
        if (! $this->is_valid)
            return FALSE;
        return $this->domain(1);
    }

    public function domainName()
    {
        if (! $this->is_valid)
            return false;
        
        return $this->domain(2, 1); // Returns 2nd level domain and top level domain
    }

    public function domainLevels()
    {
        if (! $this->is_valid)
            return FALSE;
        return count(explode('.', $this->host));
    }

    public function scheme($scheme = NULL)
    {
        if (func_num_args() > 0) {
            if (is_string($scheme) || is_null($scheme)) {
                if (! $scheme || preg_match('/^[a-zA-Z][a-zA-Z0-9+-.]*[a-zA-Z0-9]*(\:\/\/)?$/', $scheme)) {
                    $this->scheme = $scheme ? str_replace('://', '', $scheme) : NULL;
                    return $this;
                }
            }
            throw new \Exception('Scheme value must be a valid string or null.');
        }
        return $this->scheme;
    }

    public function user($user = NULL)
    {
        if (func_get_args() > 0) {
            if (is_string($user) || is_null($port)) {
                $this->user = urlencode($user);
                return $this;
            } else
                throw new \Exception('User value must be a string or null.');
        }
        return $this->user;
    }

    public function password($password = NULL)
    {
        if (func_get_args() > 0) {
            if (is_string($password) || is_null($password)) {
                $this->password = urlencode($password);
                return $this;
            } else
                throw new \Exception('Password value must be a string or null.');
        }
        return $this->password;
    }

    public function host($host = NULL)
    {
        if (func_get_args() > 0) {
            if (is_string($host) || is_null($host)) {
                if (! $host || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]+(\.[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])*$/', $host)) {
                    $this->host = $host ? $host : NULL;
                    $this->is_valid = ! is_null($this->host);
                    return $this;
                } else
                    throw new \Exception('Host value must be a valid domain string.');
            } else
                throw new \Exception('Host value must be an string or null.');
        }
        return $this->host;
    }

    public function port($port = NULL)
    {
        if (func_get_args() > 0) {
            if (is_int($port) || is_null($port)) {
                $this->port = $port;
                return $this;
            } else
                throw new \Exception('Port value must be an integer or null.');
        }
        return $this->port;
    }

    /**
     * Reports the default port the URL's protocol.
     * For example if the scheme is set to <code>ftp</code>, then the method will return <code>21</code>.
     *
     * @return int|boolean
     */
    public function defaultPort()
    {
        return getservbyname($this->scheme ? $this->scheme : 'http', 'tcp');
    }

    public function path($path = NULL)
    {
        if (func_num_args() > 0) {
            
            // Trim (if needed)
            $args = func_get_args();
            $segments = [];
            foreach ($args as $seg) {
                $seg = trim((string) $seg, '/');
                $segments[] = $seg;
            }
            
            $path = $path == '' || $path == '/' ? [] : explode('/', implode('/', $segments));
            $segments = [];
            foreach ($path as $seg) {
                $segments[] = urldecode(trim($seg, '/'));
            }
            $this->path = $segments;
            return $this;
        } else
            return implode('/', $this->path);
    }

    public function numSegments()
    {
        return count($this->path);
    }

    public function segment($index = NULL)
    {
        if (func_num_args() == 0) {
            $a = [];
            foreach ($this->path as $seg) {
                $a[] = $seg;
            }
            return $a;
        } else
            return $index > - 1 && $index < count($this->path) ? $this->path[$index] : NULL;
    }

    public function query($query = NULL)
    {
        if (func_num_args() > 0) {
            if (is_array($query))
                $this->query = $query;
            else if (is_string($query)) {
                $a = [];
                parse_str($query, $a);
                $this->query = $a;
            }
            else if($query == null)
                $this->query = [];
            else
                throw new \Exception('Query value must be a string or an array.');
            return $this;
        } else {
            $q = '';
            if ($this->query) {
                foreach ($this->query as $name => $val) {
                    if ($q)
                        $q .= '&';
                    $q .= urlencode($name) . '=' . urlencode($val);
                }
            }
        }
        return $q;
    }

    public function fragment($fragment = NULL)
    {
        if (func_num_args() > 0) {
            $this->fragment = $fragment;
            return $this;
        }
        return $this->fragment;
    }

    public function valid()
    {
        return $this->is_valid;
    }

    public function value()
    {
        if (! $this->is_valid)
            return '';
        
        $url = $this->scheme ? $this->scheme . '://' : 'http://';
        $user = $this->user ? $this->user : '';
        $pass = $this->password ? ':' . $this->password : '';
        if ($user && $pass)
            $user .= $pass . '@';
        else if ($user)
            $user .= '@';
        else if ($pass)
            $user = $pass . '@';
        $url .= $user;
        $url .= $this->host; // Host
        if ($this->port)
            $url .= ':' . $this->port;
        $url .= '/';
        if (count($this->path))
            $url .= implode('/', $this->path); // path Segments
        $query = $this->query();
        if ($query)
            $url .= '?' . $query;
        if ($this->fragment)
            $url .= '#' . $this->fragment;
        return $url;
    }

    public function toLower()
    {
        return strtolower($this);
    }

    public function __invoke($url = NULL)
    {
        if (func_num_args() > 0)
            $this->parseUrl($url); // Re-parse
        else
            Navigate::redirect($this);
    }

    public function __toString()
    {
        return $this->value();
    }

    public function __clone()
    {
        return new Url($this, $this->auto_lowercase);
    }
}

