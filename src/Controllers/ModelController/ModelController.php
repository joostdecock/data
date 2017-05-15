<?php
/** App\Controllers\ModelController class */
namespace App\Controllers;

use \App\Data\Model as Model;

/**
 * Holds data for a model.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class ModelController 
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

    /** Create model */
    public function create($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->name = $this->scrub($request,'name');
        ($this->scrub($request,'body') == 'female') ? $in->body = 'female' : $in->body = 'male';
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and create a new model
        $model = $this->container->get('Model');
        $model->create($user);

        // Update model with user input and save
        $model->setBody($in->body);
        $model->setName($in->name);
        $model->save();

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'message' => 'model/created',
            'handle' => $model->getHandle(),
            'units' => $model->getUnits(),
            'picture' => $model->getPicture(),
            'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
        ]);
    }

    /** Update model */
    public function update($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        if(isset($request->getParsedBody()['data']) && $request->getParsedBody()['data'] != '') $in->data = json_decode($request->getParsedBody()['data']);
        else $in->data = null;
        $in->name = $this->scrub($request,'name');
        $in->picture = $this->scrub($request,'picture');
        $in->notes = $this->scrub($request,'notes');
        ($this->scrub($request,'units') == 'imperial') ? $in->units = 'imperial' : $in->units = 'metric';
        ($this->scrub($request,'body') == 'female') ? $in->body = 'female' : $in->body = 'male';
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
     
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and load its data
        $model = $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Handle picture
        if($in->picture && $in->picture != $model->getPicture()) {
            // Get the AvatarKit to create the avatar
            $avatarKit = $this->container->get('AvatarKit');
            $model->setPicture($avatarKit->createFromDataString($in->picture, $user->getHandle(), 'model', $model->getHandle()));
        }

        // Handle name change
        if($in->name && $model->getName() != $in->name) $model->setName($in->name);

        // Handle units
        if($in->units && $model->getUnits() != $in->units) $model->setUnits($in->units);

        // Handle body
        if($in->body && $model->getBody() != $in->body) $model->setBody($in->body);

        // Handle notes
        if($in->notes && $model->getNotes() != $in->notes) $model->setNotes($in->notes);

        // Handle data
        if($in->data) {
            if($model->getData() != $in->data) $model->setData($in->data);
        }

        // Save changes 
        $model->save();

        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'message' => 'model/updated',
            'name' => $model->getName(),
            'units' => $model->getUnits(),
            'picture' => $model->getPicture(),
            'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
        ]);
    }

    /** Load model data */
    public function load($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load its data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get a model instance from the container and load its data
        $model = $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');

        return $this->prepResponse($response, [
            'model' => [
                'id' => $model->getId(), 
                'user' => $model->getUser(), 
                'userHandle' => $user->getHandle(), 
                'name' => $model->getName(), 
                'handle' => $model->getHandle(), 
                'body' => $model->getBody(), 
                'picture' => $model->getPicture(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
                'data' => $model->getData(), 
                'units' => $model->getUnits(), 
                'created' => $model->getCreated(), 
                'migrated' => $model->getMigrated(), 
                'notes' => $model->getNotes(), 

            ],
            'drafts' => $model->getDrafts(),
        ]);
    } 

    /** Remove model */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get a model instance from the container and load model data
        $model = $this->container->get('Model');
        $model->loadFromHandle($in->handle);

        // Get a logger instance from the container
        $logger = $this->container->get('logger');

        // Does this model belong to the user?
        if($model->getUser() != $id) {
            $logger->info("Access blocked: Attempt to remove model ".$model->getId()." by user: ".$user->getId());
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not_your_model', 
            ]);
        }
        
        $model->remove($user);
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'model_removed', 
        ]);
    } 
}
