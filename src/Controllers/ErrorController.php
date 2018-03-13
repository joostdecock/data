<?php
/** Freesewing\Data\Controllers\ErrorController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Error as Error;
use \Freesewing\Data\Tools\Utilities as Utilities;

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

    /** log error */
    public function log($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->type = Utilities::scrub($request,'type');
        $in->level = Utilities::scrub($request,'level');
        $in->message = Utilities::scrub($request,'message');
        $in->origin = Utilities::scrub($request,'origin');
        if($this->hasRequiredInput($in) === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'missing_input',
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        $in->file = Utilities::scrub($request,'file');
        $in->line = Utilities::scrub($request,'line');
        $in->user = Utilities::scrub($request,'user');
        $in->raw = Utilities::scrub($request,'raw');

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
            
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'id' => $id,
                'hash' => $error->getHash(),
            ], 200, $this->container['settings']['app']['origin']);
        } else {
            return Utilities::prepResponse($response, [
                'result' => 'ignored', 
                'reason' => 'error_is_familiar',
            ], 200, $this->container['settings']['app']['origin']);

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
