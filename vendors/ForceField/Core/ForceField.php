<?php
namespace ForceField\Core;

use ForceField\App\iApplication;
use ForceField\Network\Navigate;
use ForceField\Network\Url;
use ForceField\Routing\Router;
use ForceField\Web\Html;
use ForceField\Filesystem\File;
use ForceField\Security\Security;

class ForceField
{

    private static $config;

    private static $limit_ip;

    private static $config_path;

    private function __construct()
    {}

    public static function setup(array $settings = NULL)
    {
        if (ForceField::$config)
            die('Cannot run setup more than once.');
        
        ForceField::$config = [];
        
        // Init Settings
        if ($settings) {
            
            foreach ($settings as $key => $val) {
                switch ($key) {
                    case 'config_path':
                        $val = realpath($val);
                        ForceField::$config_path = $val;
                        break;
                    case 'limit_ip':
                        ForceField::$limit_ip = is_array($val) ? $val : null;
                        break;
                    case 'allowed_domains':
                        if (is_string($val))
                            Security::allowDomain($val);
                        else if (is_array($val)) {
                            foreach ($val as $domain) {
                                Security::allowDomain($domain);
                            }
                        }
                        break;
                    default:
                }
            }
        }
        
        // Run security check
        Security::init();
        
        // Validate Settings
        if (! ForceField::$config_path)
            ForceField::$config_path = realpath(APPPATH . '/config'); // TODO Get path from settings
        if (! is_dir(ForceField::$config_path))
            die('The specified configuration path is not a directory.');
        
        $current = (string) Url::current();
        $base = (string) Url::base();
        
        // Initialize configuration settings
        $default_subdomain = Configure::readString('app.defaultSubdomain', 'www');
        $domain_levels = Configure::readInt('app.domainLevels', 3); // When set to two causes all subdomains to be redirected to main domain
        
        if (! is_int($domain_levels) || $domain_levels < 2)
            throw new \Exception('The "domain_levels" setting must be an integer no greater than one.');
        
        $mode = Configure::readString('app.mode', 'development');
        
        switch ($mode) {
            case 'development':
                ini_set('display_errors', '1');
                error_reporting(E_ALL);
                break;
            case 'production':
            default:
                ini_set('display_errors', '0');
        }
        
        // Script time limit helper
        $time_limit = Configure::readInt('app.timeLimit', 0);
        
        if ($time_limit > 0)
            set_time_limit($time_limit);
        
        $url = Url::current(); // Current URL
                               
        // Enforce domain redirection policies
        if ($default_subdomain && $url->domainLevels() == 2 && $domain_levels > 2) {
            // The page was visited without a subdomain so redirect to default subdomain
            Output::clear();
            $url->subdomain($default_subdomain);
            Navigate::redirect($url);
        } else if ($url->domainLevels() > $domain_levels) {
            // The page was visited with additional subdomain levels
            $a = [];
            
            for ($i = 1; $i <= $domain_levels; $i ++) {
                $a[] = $i;
            }
            
            rsort($a);
            $url->clearSubdomains();
            
            $base = Url::get($url->scheme() . '://' . call_user_func_array([
                $url,
                'domain'
            ], $a));
            
            Navigate::redirect($base);
        }
        
        // Load Routes
        include APPPATH . '/resources/routes.php';
    }

    public static function exec()
    {
        if (TRUE) {
            if (is_array(ForceField::$limit_ip) && ! ForceField::authorizedIp())
                exit(0);
            
            Router::resolve(LOCALHOST && isset($_GET['ffurl']) ? $_GET['ffurl'] : Url::current());
        } else
            die('Engine never initialized.');
    }

    public static function authorizedIp($ip = REMOTE_ADDRESS)
    {
        return ! is_array(ForceField::$limit_ip) || in_array($ip, array_merge([
            // Allow localhost
            '127.0.0.1',
            '::1'
        ], is_array(ForceField::$limit_ip) ? ForceField::$limit_ip : []));
    }
}

