<?php
/** Freesewing\Data\Controllers\DraftController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Model as Model;
use \Freesewing\Data\Tools\Utilities as Utilities;

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

    /** Create draft */
    public function create($request, $response, $args, $recreate=false) 
    {
        switch(Utilities::scrub($request,'type')) {
            case 'draftFromModel':
            case 'forkFromModel':
                return $this->createFromModel($request, $response, $args);
                break;
            case 'redraftFromModel':
                return $this->recreateFromModel($request, $response, $args);
                break;
            default: 
                // Not a supported way to create draft
                return Utilities::prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'unsupported_draft_type', 
                ], 400, $this->container['settings']['app']['origin']);
                break;
        }
    } 

    public function createFromModel($request, $response, $args, $handle='') 
    {
        // New draft or in-place update/redraft ?
        if ($handle !== '') $redraft = true;
        else $redraft = false;

        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        // Load the gist
        $gist = Utilities::loadGist($request);
        // Get a model instance from the container and load the model
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($gist['model']['handle']);
        if($model->getUser() != $user->getId() && !$model->getShared()) {
            // Not a model that belongs to the user, or shared model
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
         
        // Get a draft instance from the container
        $draft = $this->container->get('Draft');

        if($redraft) {
            $draft->loadFromHandle($gist['draftOptions']['redraft']);
            if ($draft->getUser() != $user->getId()) {
                return Utilities::prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'draft_not_yours', 
                ], 400, $this->container['settings']['app']['origin']);
            }
            // Recreate draft
            $draft->create($gist, $user, $model, $draft->getHandle());
        } else {
            // Create draft
            $draft->create($gist, $user, $model);
            // Add badge if needed
            if(isset($gist['draftOptions']['fork'])) $badge = 'fork';
            else $badge = 'draft';
            if($user->addBadge($badge)) $user->save();
        }

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'handle' => $draft->getHandle()
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Recreate draft */
    public function REMOVEMErecreateFromModel($request, $response, $args) 
    {
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($request->getAttribute("jwt")->user);

        // Load the gist
        $gist = Utilities::loadGist($request);
        // Get a model instance from the container and load the model
        $model = clone $this->container->get('Model');
        $model->loadFromHandle($gist['model']['handle']);
        if($model->getUser() != $user->getId() && !$model->getShared()) {
            // Not a model that belongs to the user, or shared model
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'model_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Get a draft instance from the container and load data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($gist['draftOptions']['redraft']);
         
        if ($draft->getUser() != $user->getId()) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        return $this->create($request, $response, $args, true); 
    }

    /** Load draft data */
    public function load($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;

        // Logged in user could be an admin
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a draft instance from the container and load its data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);

        // Does draft even exist?
        if(!is_numeric($draft->getId())) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_draft', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        if($draft->getUser() != $id && !$draft->getShared() && $admin->getRole() != 'admin') {
            // Not a draft that belongs to the user, nor is it shared
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_yours_and_not_shared', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Get a user instance from the container and load its data
        $user = clone $this->container->get('User');
        // It's important to load the user owning the draft.
        // This may or may not be the logged-in user (if it's a shared draft)
        $user->loadFromId($draft->getUser());
        
        // Get a model instance from the container and load its data
        $model = $this->container->get('Model');
        $model->loadFromId($draft->getModel());
        
        // Get the AvatarKit to get the avatar location
        $avatarKit = $this->container->get('AvatarKit');

        if($admin->getRole() == 'admin') $asAdmin = true;
        else $asAdmin = false;

        // Add caching token based on draft data
        $optionData = $draft->getData();
        $cacheToken = sha1(serialize($optionData));

        return Utilities::prepResponse($response, [
            'draft' => [
                'id' => $draft->getId(), 
                'asAdmin' => $asAdmin,
                'user' => $draft->getUser(), 
                'userHandle' => $user->getHandle(), 
                'userName' => $user->getUsername(), 
                'pattern' => $draft->getPattern(), 
                'model' => [
                    'handle' => $model->getHandle(),
                    'name' => $model->getName(),
                    'breasts' => $model->getBreasts(), 
                    'picture' => $model->getPicture(), 
                    'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'model',$model->getHandle()).'/'.$model->getPicture(), 
                    'units' => $model->getUnits(), 
                    'created' => $model->getCreated(), 
                    'shared' => $model->getShared(), 
                ],
                'name' => $draft->getName(), 
                'handle' => $draft->getHandle(), 
                'data' => $optionData,
                'cache' => $cacheToken,
                'created' => $draft->getCreated(), 
                'shared' => $draft->getShared(), 
                'notes' => $draft->getNotes(), 
                'dlroot' => $this->container['settings']['app']['data_api'].$this->container['settings']['app']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$draft->getHandle().'/',
            ]
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Load a shared draft */
    public function loadShared($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a draft instance from the container and load its data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);

        if(!$draft->getShared()) {
            // Not a shared draft
            $logger->info("Load blocked: ".$in->handle." is not a shared draft");
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_shared', 
            ], 200, $this->container['settings']['app']['origin']);
        }
        
        // Get a user instance from the container and load its data
        $user = $this->container->get('User');
        $user->loadFromId($draft->getUser());
        
        // Get a model instance from the container and load its data
        $model = $this->container->get('Model');
        $model->loadFromId($draft->getModel());
        
        // Add caching token based on draft data
        $optionData = $draft->getData();
        $cacheToken = sha1(serialize($optionData));

        return Utilities::prepResponse($response, [
            'draft' => [
                'id' => $draft->getId(), 
                'user' => $draft->getUser(), 
                'userHandle' => $user->getHandle(), 
                'pattern' => $draft->getPattern(), 
                'model' => [
                    'breasts' => $model->getBreasts(), 
                    'units' => $model->getUnits(), 
                ],
                'name' => $draft->getName(), 
                'handle' => $draft->getHandle(), 
                'svg' => $draft->getSvg(), 
                'compared' => $draft->getCompared(), 
                'data' => $optionData,
                'cache' => $cacheToken,
                'created' => $draft->getCreated(), 
                'shared' => $draft->getShared(), 
                'notes' => $draft->getNotes(), 
                'dlroot' => $this->container['settings']['app']['data_api'].$this->container['settings']['app']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$draft->getHandle().'/',
            ]
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Upgrade draft */
    public function upgrade($request, $response, $args) 
    {
        $handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a draft instance from the container and load its data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($handle);

        // Does this user own this draft?
        if($draft->getUser() != $id) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($id);

        $draft->upgrade($user);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'handle' => $draft->getHandle(),
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Update draft */
    public function update($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->name = Utilities::scrub($request,'name');
        $in->notes = Utilities::scrub($request,'notes');
        (Utilities::scrub($request,'shared') == '1') ? $in->shared = 1 : $in->shared = 0;
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
     
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a draft instance from the container and load its data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);

        // Does this user own this draft?
        if($draft->getUser() != $in->id) {
            $logger->info("Draft edit blocked: User id ".$in->id." is not the owner of draft ".$draft->getHandle());
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'draft_not_yours', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Handle name change
        if($in->name && $draft->getName() != $in->name) $draft->setName($in->name);

        // Handle shared
        if($draft->getShared() != $in->shared) $draft->setShared($in->shared);

        // Handle notes
        if($in->notes && $draft->getNotes() != $in->notes) $draft->setNotes($in->notes);
        
        // Save changes 
        $draft->save();

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'handle' => $draft->getHandle(),
            'name' => $draft->getName(),
            'shared' => $draft->getShared(),
            'notes' => $draft->getNotes(),
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Remove draft */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get a draft instance from the container and load draft data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);

        // Does this draft belong to the user?
        if($draft->getUser() != $id) {
            $logger = $this->container->get('logger');
            $logger->info("Access blocked: Attempt to remove draft ".$draft->getId()." by user: ".$user->getId());
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not_your_draft', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        $draft->remove($user);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'draft_removed', 
        ], 200, $this->container['settings']['app']['origin']);
    } 
    
    /** Download draft */
    public function download($request, $response, $args) 
    {
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        $in->format = filter_var($args['format'], FILTER_SANITIZE_STRING);

        // Get a draft instance from the container and load draft data
        $draft = $this->container->get('Draft');
        $draft->loadFromHandle($in->handle);

        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($draft->getUser());

        // Get location of file on disk
        $path = $draft->export($user, $in->format, $draft->getPattern(), $draft->getHandle());

        if($in->format == 'svg') $contentType = 'image/svg+xml';
        else $contentType = 'application/pdf';

        return $response
            ->withHeader("Content-Type", $contentType)
            ->withHeader("Content-Disposition", 'attachment; filename="freesewing.'.basename($path).'"')
            ->write(file_get_contents($path));
    }
}
