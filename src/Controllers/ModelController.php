<?php
/** Freesewing\Data\Controllers\ModelController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Model as Model;
use \Freesewing\Data\Tools\Utilities as Utilities;

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

    /** Create model */
    public function create($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->name = Utilities::scrub($request,'name');
        (Utilities::scrub($request,'body') == 'female') ? $in->body = 'female' : $in->body = 'male';
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and create a new model
        $model = clone $this->container->get('Model');
        $model->create($user);

        // Update model with user input and save
        $model->setBody($in->body);
        $model->setName($in->name);
        $model->setUnits($user->getAccountUnits());
        $model->save();

        // Add badge if needed
        if($user->addBadge('model')) $user->save();

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'message' => 'model/created',
            'handle' => $model->getHandle(),
            'units' => $model->getUnits(),
            'picture' => $model->getPicture(),
            'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Update model */
    public function update($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();

        // Measurements are stored in data argument
        if(isset($request->getParsedBody()['data']) && $request->getParsedBody()['data'] != '') {
            $in->data = json_decode($request->getParsedBody()['data']);
            $settingsUpdate = false;
        } else {
            $in->data = null;
            $in->name = Utilities::scrub($request,'name');
            $in->picture = Utilities::scrub($request,'picture');
            $in->notes = Utilities::scrub($request,'notes');
            (Utilities::scrub($request,'units') == 'imperial') ? $in->units = 'imperial' : $in->units = 'metric';
            (Utilities::scrub($request,'body') == 'female') ? $in->body = 'female' : $in->body = 'male';
            (Utilities::scrub($request,'shared') == '1') ? $in->shared = 1 : $in->shared = 0;
            $settingsUpdate = true;
        }

        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
     
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            // Not a model that belongs to the user
            $logger->info("Model update blocked: User ".$user->getId()." is not the owner of model ".$in->handle);
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        if($settingsUpdate) { // Updating settings

            if($in->picture && $in->picture != $model->getPicture()) {
                $avatarKit = $this->container->get('AvatarKit');
                $model->setPicture($avatarKit->createFromDataString($in->picture, $user->getHandle(), 'model', $model->getHandle()));
            }
            if($in->name && $model->getName() != $in->name) $model->setName($in->name);
            if($model->getUnits() != $in->units) $model->setUnits($in->units);
            if($model->getBody() != $in->body) $model->setBody($in->body);
            if($model->getShared() != $in->shared) $model->setShared($in->shared);
            if($in->notes && $model->getNotes() != $in->notes) $model->setNotes($in->notes);

        } else { // Updating measurements
            
            if(is_object($in->data) && $model->getData() != $in->data) {
                $unitsKit = $this->container->get('UnitsKit');
                foreach($in->data->measurements as $measurement => $value) {
                    if($model->getUnits() == 'imperial') $value = $unitsKit->asFloat($value); // Handle imperial
                    $model->setMeasurement($measurement, $value);
                }
            }
        }

        // Save changes 
        $model->save();

        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'message' => 'model/updated',
            'name' => $model->getName(),
            'units' => $model->getUnits(),
            'picture' => $model->getPicture(),
            'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
            'data' => $model->getData(),
        ], 200, $this->container['settings']['app']['origin']);
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
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            $logger = $this->container->get('logger');
            // Could still be an admin loading this model
            if($user->getRole() == 'admin') {
                $logger->info("Model loaded by admin: User ".$user->getId()." loaded model ".$in->handle);
                // Need to load the real model owner into the user object
                $admin = $user;
                $user = clone $this->container->get('User');
                $user->loadFromId($model->getUser());
            } else {
                // Not a model that belongs to the user and not an admin
                $logger->info("Model load blocked: User ".$user->getId()." is not the owner of model ".$in->handle);
                
                return $this->prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'model_not_yours', 
                ], 400);
            }
        }

        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');

        return Utilities::prepResponse($response, [
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
                'shared' => $model->getShared(), 
                'notes' => $model->getNotes(), 

            ],
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Clone model data 
     *
     * clone is a reserved work, hence why this methos is called klone */
    public function klone($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId() && $user->getRole() != 'admin') {
            // Not a model that belongs to the user, nor an admin
            $logger = $this->container->get('logger');
            $logger->info("Model clone blocked: User ".$user->getId()." is not the owner of model ".$in->handle);
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Get a model instance from the container and create a new model
        $clone = clone $this->container->get('Model');
        $clone->create($user);

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        // Update model with user input and save
        $clone->setName($model->getName().' (cloned from '.$model->getHandle().')');
        $clone->setBody($model->getBody());
        $img = $avatarKit->getDiskDir($user->getHandle(),'model', $model->getHandle().'/'.$model->getPicture());
        $clone->setPicture($avatarKit->createFromUri($img, $user->getHandle(),'model', $clone->getHandle()));
        $clone->setData($model->getData());
        $clone->setUnits($model->getUnits());
        $clone->setShared($model->getShared());
        $clone->setNotes($model->getNotes());
        $clone->save();

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'handle' => $clone->getHandle(),
            'img' => $avatarKit->getDiskDir($user->getHandle(),'model', $model->getHandle().'/'.$model->getPicture()),
            'orig' => [
                'name' => $model->getName(),
                'handle' => $model->getHandle(),
                'body' => $model->getBody(),
                'data' => $model->getData(),
                'units' => $model->getUnits(),
                'shared' => $model->getShared(),
                'notes' => $model->getNotes(),
            ],
            'clone' => [
                'name'   => $clone->getName(),
                'handle' => $clone->getHandle(),
                'body'   => $clone->getBody(),
                'data'   => $clone->getData(),
                'units'  => $clone->getUnits(),
                'shared' => $clone->getShared(),
                'notes'  => $clone->getNotes(),
            ],
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Export model data */
    public function export($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        $dir = $user->export('test');

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            // Not a model that belongs to the user
            $logger = $this->container->get('logger');
            $logger->info("Model export blocked: User ".$user->getId()." is not the owner of model ".$in->handle);
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Export model data to disk
        $dir = $model->export();

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'formats' => [
                'csv'  => $dir.'/'.$model->getHandle().'.csv',
                'json' => $dir.'/'.$model->getHandle().'.json',
                'yaml' => $dir.'/'.$model->getHandle().'.yaml',
            ]
        ], 200, $this->container['settings']['app']['origin']);

    }

    /** Remove model */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Get a model instance from the container and load model data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);

        // Does this model belong to the user?
        if($model->getUser() != $id) {
            $logger = $this->container->get('logger');
            $logger->info("Access blocked: Attempt to remove model ".$model->getId()." by user: ".$user->getId());
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not_your_model', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        $model->remove($user);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'model_removed', 
        ], 200, $this->container['settings']['app']['origin']);
    } 
}
