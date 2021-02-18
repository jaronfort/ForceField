<?php
namespace Hologram\Api;

use ForceField\Core\Configure;

class REST implements \JsonSerializable
{

    private $json;

    private $status;

    private function __construct(array $json, $http_status_code = null)
    {
        $this->json = $json;
        $this->status = $http_status_code;
    }

    private static function successCodeFromRequestMethod()
    {
        switch (REQUEST_METHOD) {
            case 'GET':
                // Resource retrieved
                
                break;
            case 'POST':
                // Resource created
                
                break;
            case 'PUT':
                // Resource updated
                
                break;
            case 'DELETE':
                // Resource removed
                break;
            default:
            // Do nothing
        }
        
        return 0;
    }

    public static function data($result)
    {
        return new REST([
            'data' => $result
        ]);
    }

    public static function success($type, array $data = null)
    {
        $code = Configure::readInt('api.success.codes.' . $type, 0);
        $message = lang('api.success.' . $type, $data, '');
        
        return new REST([
            'code' => $code,
            'message' => $message
        ], REST::successCodeFromRequestMethod());
    }

    public static function error($type, $error_target = null, array $data = null)
    {
        $json = [];
        $code = Configure::readInt('api.errors.codes.' . $type, 0);
        $message = lang('api.errors.' . $type . (is_string($error_target) && preg_match('/^[a-zA-Z0-9]+$/', $error_target) ? '.' . $error_target : ''), '', $data);
        
        return new REST([
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
    }

    public function append()
    {
        return $this;
    }

    public function status()
    {
        return $this->status;
    }

    public function jsonSerialize()
    {
        return $this->json;
    }
}

