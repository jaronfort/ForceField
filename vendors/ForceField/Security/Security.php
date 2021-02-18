<?php
namespace ForceField\Security;

use Exception;
use ForceField\Core\Output;
use ForceField\Core\Configure;
use ForceField\Network\Url;

class Security
{

    private static $initialized;

    private static $allowed_domains = [];

    private static $allow_all;

    private static $ssl_detected;

    private static $is_local;

    public static function init()
    {
        if (Security::$initialized)
            return;
        
        Security::$initialized = TRUE;
        Security::$ssl_detected = SERVER_PROTOCOL == 'https';
        
        // Initialize allowed domains from config
        Security::$is_local = in_array(REMOTE_ADDRESS, [
            '127.0.0.1',
            '::1'
        ]);
        
        if (HTTP_ORIGIN) {
            
            $allowed = ff_config('security.allowedOrigins', '*');
            
            if (is_array($allowed) && in_array(HTTP_ORIGIN, $allowed)) {
                // Allow origin
                $allowed = HTTP_ORIGIN;
            } else if ($allowed === false) {
                // Allow nothing
            } else if ($allowed === true || $allowed == '*') {
                // Allow everything
                if (HTTP_ORIGIN) {
                    $url = new Url(HTTP_ORIGIN);
                    $allowed = $url->scheme() . '://' . $url->domain(3, 2, 1);
                } else
                    $allowed = '*';
            } else
                $allowed = false;
            
            if ($allowed !== false)
                header('Access-Control-Allow-Origin: ' . $allowed);
        }
        
        if (! Security::safeScript()) {
            Output::clear(); // Clear output for security
                             // http_response_code(500);
            die('Security Violation! "' . Url::current()->domainName() . '" is not an authorized domain.');
        }
        
        if (Configure::readbool('security.ssl.enabled', false)) {
            
            if (Configure::readbool('security.ssl.force', false) && ! Security::$ssl_detected) {
                //
                header('Location: https://' . $url->domainName());
                exit(0);
            }
        }
        
        return true;
    }

    public static function allowDomain($domain)
    {
        if (is_string($domain)) {
            
            $domain = strtolower($domain);
            
            if ($domain == '*') {
                Security::$allow_all = true;
                return true;
            } else if ($domain == 'localhost' || $domain == '127.0.0.1') {
                // Fail
                die('Never allow localhost.');
            } else if (preg_match('/^(\*\.)?([a-z0-9\-]+)(\.[a-z0-9\-]+)*(\.[a-z]+)$/', $domain) && ! in_array($domain, Security::$allowed_domains)) {
                Security::$allowed_domains[] = $domain;
                return true;
            }
        }
        
        return false;
    }

    public static function validDomains()
    {
        if (Security::$allow_all)
            return Security::$allow_all;
        
        $a = [];
        
        foreach (Security::$allowed_domains as $domain) {
            $a[] = $domain;
        }
        
        return $a;
    }

    public static function isValidDomain($domain)
    {
        if (Security::$allow_all)
            return true;
        else
            return Security::$allowed_domains && in_array($domain, Security::$allowed_domains);
    }

    public static function safeScript()
    {
        $url = Url::current();
        return Security::$is_local || Security::isValidDomain($url->domain(2, 1));
    }

    public static function isSecureConnection()
    {
        return Security::$ssl_detected;
    }
}