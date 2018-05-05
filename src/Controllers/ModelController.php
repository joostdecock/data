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
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        // Get a model instance from the container and create a new model
        $model = clone $this->container->get('Model');
        $model->setName(Utilities::scrub($request,'name'));
        $breasts = (Utilities::scrub($request,'breasts')) ? true : false;
        $model->setBreasts($breasts);
        $model->setUnits($user->getUnits());
        $model->create($user);

        // Add badge if needed
        if($user->addBadge('model')) $user->save();

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'handle' => $model->getHandle(),
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
            $in->units = (Utilities::scrub($request,'units') == 'imperial') ? 'imperial' : 'metric';
            $in->breasts = (Utilities::scrub($request,'breasts')) ? true : false;
            $in->shared = (Utilities::scrub($request,'shared')) ? true : false;
            $settingsUpdate = true;
        }

        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
     
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($in->handle);
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Handle text fields changes
        foreach(['name', 'units', 'notes'] as $field) {
            if($in->{$field} !== false && $model->{'get'.ucfirst($field)}() != $in->{$field}) {
                $model->{'set'.ucfirst($field)}($in->{$field});
                $update = true;
            }
        }

        // Handle boolean fields changes
        foreach(['breasts', 'shared'] as $field) {
            if(($in->{$field} === false || $in->{$field} === true) && $model->{'get'.ucfirst($field)}() != $in->{$field}) {
                $model->{'set'.ucfirst($field)}($in->{$field});
                $update = true;
            }
        }

        // Handle avatar upload
        if(
            $request->getContentType() === 'image/jpeg' ||
            $request->getContentType() === 'image/png' ||
            $request->getContentType() === 'image/gif' 
        ) {
            // Get the AvatarKit to create the avatar
            $avatarKit = $this->container->get('AvatarKit');
            $model->setPicture($avatarKit->createFromData($request->getBody()->getContents(), $user->getHandle(), 'model', $model->getHandle()));
            $update = true;
        }

        // Handle measurement changes 
        foreach($this->container['settings']['measurements']['all'] as $m) {
            $value = Utilities::scrub($request, $m);
            if(is_numeric($value) && $value != $model->getMeasurement($m)) {
                if($model->getUnits() == 'imperial') $value = $unitsKit->asFloat($value); // Handle imperial
                $model->setMeasurement($m, $value);
                $update = true;
            }
        }

        // Save mode if changes were made 
        if($update) {
            $model->save();
        
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'reason' => 'model_updated',
            ], 200, $this->container['settings']['app']['origin']);

        }
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'no_changes_made',
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Load model data */
    public function load($request, $response, $args) 
    {
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle(filter_var($args['handle'], FILTER_SANITIZE_STRING));

        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
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
                'breasts' => $model->getBreasts(), 
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
     * clone is a reserved work, hence why this method is called klone */
    public function klone($request, $response, $args) 
    {
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle(filter_var($args['handle'], FILTER_SANITIZE_STRING));
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            // Not a model that belongs to the user
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
        $clone->setBreasts($model->getBreasts());
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
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Export model data */
    public function export($request, $response, $args) 
    {
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        $dir = $user->export('test');

        // Get a model instance from the container and load its data
        $model = clone $this->container->get('Model');
        $model->loadFromHandle(filter_var($args['handle'], FILTER_SANITIZE_STRING));
        
        // Verify this user owns this model
        if($model->getUser() != $user->getId()) {
            // Not a model that belongs to the user
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
