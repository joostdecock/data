<?php
/** App\Controllers\DraftController class */
namespace App\Controllers;

use \App\Data\Model as Model;

/**
 * Drafts controller
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class DraftController 
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
    private function prepResponse($response, $data)
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
   
    private function scrub($request, $key, $type='string')
    {
        switch($type) {
            case 'email':
                $filter = FILTER_SANITIZE_EMAIL;
            break;
            default:
                $filter = FILTER_SANITIZE_STRING;
        }

        if(isset($request->getParsedBody()[$key])) return filter_var($request->getParsedBody()[$key], $filter);
        else return false;
    }

    /** Create draft */
    public function create($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->model = $this->scrub($request,'model');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and load the model
        $model = $this->container->get('Model');
        $model->loadFromHandle($in->model);

        if($model->getUser() != $user->getId() && !$model->getShared()) {
            // Not a model that belongs to the user, and not shared either
            $logger->info("Draft blocked: User ".$user->getId()." can not generate a draft for model ".$in->model);
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours_nor_shared', 
            ]);
        }

        // Get a draft instance from the container and create the draft
        $draft = $this->container->get('Draft');
        $draft->create($request->getParsedBody(), $user, $model);
        $logger->info("Drafted ".$draft->getHandle()." for user ".$user->getId());
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'handle' => $draft->getHandle(),
        ]);
    }
    
    /** Load draft data */
    public function load($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container and load its data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get a draft instance from the container and load its data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);
        
        if($draft->getUser() != $user->getId() && !$draft->getShared()) {
            // Not a draft that belongs to the user, and not shared either
            $logger->info("Load blocked: User ".$user->getId()." can not load draft ".$in->handle);
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_yours_nor_shared', 
            ]);
        }
        
        // Get a model instance from the container and load its data
        $model = $this->container->get('Model');
        $model->loadFromId($draft->getModel());
        
        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');

        return $this->prepResponse($response, [
            'draft' => [
                'id' => $draft->getId(), 
                'user' => $draft->getUser(), 
                'userHandle' => $user->getHandle(), 
                'pattern' => $draft->getPattern(), 
                'model' => [
                    'handle' => $model->getHandle(),
                    'name' => $model->getHandle(),
                    'body' => $model->getBody(), 
                    'picture' => $model->getPicture(), 
                    'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
                    'units' => $model->getUnits(), 
                    'created' => $model->getCreated(), 
                    'shared' => $model->getShared(), 
                ],
                'name' => $draft->getName(), 
                'handle' => $draft->getHandle(), 
                'svg' => $draft->getSvg(), 
                'data' => $draft->getData(), 
                'created' => $draft->getCreated(), 
                'shared' => $draft->getShared(), 
                'notes' => $draft->getNotes(), 

            ]
        ]);
    } 

}
