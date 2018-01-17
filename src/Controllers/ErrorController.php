<?php
/** Freesewing\Data\Controllers\ErrorController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Error as Error;

/**
 * Holds errors
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class ErrorController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /**
     * Helper function to format response and send CORS headers
     *
     * @param $data The data to return
     */
    private function prepResponse($response, $data, $status=200)
    {
        return $response
            ->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
   
    private function scrub($request, $key)
    {
        if(isset($request->getParsedBody()[$key])) return filter_var($request->getParsedBody()[$key], FILTER_SANITIZE_STRING);
        else return false;
    }

    /** log error */
    public function log($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->type = $this->scrub($request,'type');
        $in->level = $this->scrub($request,'level');
        $in->message = $this->scrub($request,'message');
        $in->origin = $this->scrub($request,'origin');
        if($this->hasRequiredInput($in) === false) {
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'missing_input',
            ], 400);
        }
        
        $in->file = $this->scrub($request,'file');
        $in->line = $this->scrub($request,'line');
        $in->user = $this->scrub($request,'user');
        $in->raw = $this->scrub($request,'raw');

        // Get an error instance from the container
        $error = $this->container->get('Error');
        $error->setLevel($in->level);
        $error->setMessage($in->message);
        $error->setFile($in->file);
        $error->setLine($in->line);
        $error->setOrigin($in->origin);

        // That's all we need for a hash. Is this a familiar error?
        if($error->isFamiliar() === false) {
            $error->setUser($in->user);
            $error->setType($in->type);
            if(isset($_SERVER['REMOTE_ADDR'])) $error->setIp($_SERVER['REMOTE_ADDR']);
            $error->setRaw($in->raw);
            $id = $error->create();
            
            return $this->prepResponse($response, [
                'result' => 'ok', 
                'id' => $id,
            ]);
        } else {
            return $this->prepResponse($response, [
                'result' => 'ignored', 
                'reason' => 'error_is_familiar',
            ]);

        }
    }

    private function hasRequiredInput($obj)
    {
        if(
            isset($obj->type)    && $obj->type    !== false &&
            isset($obj->level)   && $obj->level   !== false &&
            isset($obj->message) && $obj->message !== false &&
            isset($obj->origin)  && $obj->origin  !== false
        ) {
            return true;
        } else {
            return false;
        }
    }
}
