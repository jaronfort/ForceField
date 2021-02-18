<?php
namespace ForceField\Security;

use ForceField\Core\Configure;

class Encrypt
{

    private $cypher;

    private $options;

    public function __construct($cypher = NULL, $options = NULL)
    {
        $this->cypher = $cypher ? $cypher : Configure::readString('security.encryption.cypher', 'AES-256-CBC');
        $this->options = is_long($options) ? $options : Configure::readString('security.encryption.options', 0);
        
        if (! Encrypt::isSupported($this->cypher))
            throw new \Exception('Unsupported cypher "' . $this->cypher . '" provided.');
    }

    private function key()
    {
        return Configure::readString('security.encryption.key', '[this-is-an-encryption-key]');
    }

    public static function isSupported($cypher)
    {
        return function_exists('openssl_get_cipher_methods') && in_array($cypher, openssl_get_cipher_methods());
    }

    public function encrypt($data, $key = NULL)
    {
        if ($this->willEncrypt()) {
            $key = $key ? $key : $this->key();
            
            if ($key && function_exists('openssl_encrypt')) {
                $secret_iv = '';
                $secret_key = hash('sha256', $key);
                $iv = substr(hash('sha256', $secret_iv), 0, 16);
                $result = openssl_encrypt($data, $this->cypher, $secret_key, 0, $iv);
                return base64_encode($result);
            }
        }
        return FALSE;
    }

    public function decrypt($data, $key = NULL)
    {
        if ($this->willEncrypt()) {
            $key = $key ? $key : $this->key();
            if ($key && function_exists('openssl_decrypt')) {
                $secret_iv = '';
                $secret_key = hash('sha256', $key);
                $iv = substr(hash('sha256', $secret_iv), 0, 16);
                return openssl_decrypt(base64_decode($data), $this->cypher, $secret_key, 0, $iv);
            }
        }
        return FALSE;
    }

    public function willEncrypt()
    {
        return function_exists('openssl_encrypt') && Encrypt::isSupported($this->cypher);
    }

    public function cypher()
    {
        return $this->cypher;
    }

    public function hasKey()
    {
        return ! is_null($this->key());
    }
}