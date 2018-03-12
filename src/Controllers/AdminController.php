<?php
/** Freesewing\Data\Controllers\AdminController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\User as User;
use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * Holds admin methods.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class AdminController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }


    private function getUserHandle($args)
    {
        return filter_var($args['handle'], FILTER_SANITIZE_STRING);
    }

    /** Set password (by admin) */
    public function userSetPassword($request, $response, $args) 
    {
        
        // Handle request
        $in = new \stdClass();
        $in->password = Utilities::scrub($request,'password');
        $in->userHandle = $this->getUserHandle($args);
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set password: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPassword($in->password);
        $user->save();
        $logger->info("Password for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return Utilities::prepResponse($response, [ 'result' => 'ok', ], 200, $this->container['settings']['app']['origin']);

    }

    /** Set address (by admin) */
    public function setAddress($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->address = Utilities::scrub($request,'address');
        $in->userHandle = Utilities::scrub($request,'user');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set address: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPatronAddress($in->address);
        $user->save();
        $logger->info("Address for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
        ], 200, $this->container['settings']['app']['origin']);

    }

    /** Set birthday (by admin) */
    public function setBirthday($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->day = Utilities::scrub($request,'day');
        $in->month = Utilities::scrub($request,'month');
        $in->userHandle = Utilities::scrub($request,'user');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set birthday: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPatronBirthday($in->day, $in->month);
        $user->save();
        $logger->info("Birthday for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
        ], 200, $this->container['settings']['app']['origin']);

    }

    /** Removes badge from user profile */
    public function userRemoveBadge($request, $response, $args) 
    {
        return $this->userAddBadge($request, $response, $args, true);
    }

    /** Add badge to user profile */
    public function userAddBadge($request, $response, $args, $remove=false) 
    {
        if($remove) $verb = 'remove';
        else $verb = 'add';

        // Handle request
        $in = new \stdClass();
        $in->userHandle = $this->getUserHandle($args);
        $in->badge = filter_var($args['badge'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to $verb badge: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);

        // Add badge and save
        if($remove) $user->removeBadge($in->badge);
        else $user->addBadge($in->badge);
        $user->save();
        $logger->info("Badge ".$in->badge." $verb"."ed to user ".$user->getId());

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'badges' => $user->getBadges(),
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Make a user a Patron */
    public function userSetPatronTier($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->userHandle = $this->getUserHandle($args);
        $in->tier = filter_var($args['tier'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed set Patron status: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);

        // Set patron status and save
        $user->makePatron($in->tier);
        $user->save();
        $logger->info("Patron status set to ".$in->tier." for user ".$user->getId());

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'patron' => $user->getPatron(),
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Send patron email */
    public function userSendPatronEmail($request, $response, $args)
    {
        // Handle request data 
        $handle = $this->getUserHandle($args);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed set Patron status: User $id is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Load user
        $user = clone $this->container->get('User');
        $user->loadFromHandle($handle);

        // Is this a patron?
        if($user->getPatronTier() < 2) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not-a-patron'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->patron($user);
        
        $logger->info("Sent patron email to: ".$user->getEmail()." (user ".$user->getId().")");

        return Utilities::prepResponse($response, ['result' => 'ok'], 200, $this->container['settings']['app']['origin']); 
    }

    /** Load user account */
    public function userLoad($request, $response, $args) 
    {
        // Handle request data 
        $handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container and load user data
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromHandle($handle);
        
        // Is user an admin?
        if($admin->getRole() != 'admin') {
            // Get a logger instance from the container
            $logger = $this->container->get('logger');
            $logger->info("Failed to load user data: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return Utilities::prepResponse($response, [
            'account' => [
                'id' => $user->getId(), 
                'email' => $user->getEmail(), 
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'login' => $user->getLogin(), 
                'picture' => $user->getPicture(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
                'data' => $user->getData(), 
            ],
            'models' => $user->getModels(),
            'drafts' => $user->getDrafts(),
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Find user accounts */
    public function userFind($request, $response, $args) 
    {
        // Handle request data 
        $filter = filter_var($args['filter'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container and load user data
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            // Get a logger instance from the container
            $logger = $this->container->get('logger');
            $logger->info("Failed to find users: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        $db = $this->container->get('db');

        $sql = "SELECT `users`.`id` from `users` WHERE 
            `users`.`uhash` = ".$db->quote(hash('sha256',strtolower(trim($filter))))."
            OR `users`.`ehash` = ".$db->quote(hash('sha256',strtolower(trim($filter))))."
            OR `users`.`id` = ".$db->quote(trim($filter))."
            OR `users`.`handle` LIKE ".$db->quote("%$filter%")."
            ORDER BY `CREATED` DESC
            LIMIT 50";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) {
            return Utilities::prepResponse($response, [ 
                'result' => 'error',
                'reason' => 'no_users_found'
            ], 404, $this->container['settings']['app']['origin']);
        } else {
            $json = [];
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            // Load user account data
            $user = clone $this->container->get('User');
            foreach($result as $hit) {
                $user->loadFromId($hit['id']);
                $json[$user->getHandle()] = [
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'picture' => '/static'.$avatarKit->getDir($user->getHandle()).'/'.$user->getPicture(),
                    'created' => $user->getCreated(),
                    'login' => $user->getLogin(),
                    'handle' => $user->getHandle(),
                    'patron' => $user->getPatronTier(),
                    'patronSince' => $user->getPatronSince(),
                    'badges' => $user->getBadges()
                ];
            } 
        } 

        return Utilities::prepResponse($response, [ 
            'users' => $json,
            'filter' => $filter
        ], 200, $this->container['settings']['app']['origin']);
    }
    /** Update error group */
    public function errorUpdateGroup($request, $response, $args) 
    {
        // Request data
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);
        $status = Utilities::scrub($request,'status');

        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger = $this->container->get('logger');
            $logger->info("Failed to update error group status: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                'role' => $admin->getRole(), 
                'id' => $id, 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Does the group exist?
        $errorController = $this->container->get('ErrorController');
        if($errorController->getGroupInfo($hash) === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_group', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Update status
        $errorController->setGroupStatus($status, $hash);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok' 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** 
     * Updates the status for (all) entries in a group
     *
     * @param string $status The new status
     */
    private function setGroupStatus($status, $hash)
    {
        $db = $this->container->get('db');
        if($status == 'open' || $status == 'closed') {
            $sql = "UPDATE `errors` 
                SET `status` = ".$db->quote($status)." 
                WHERE `errors`.`hash` = ".$db->quote($hash); 
        }
        else if ($status == 'muted') {
            $sql = "UPDATE `errors` 
                SET `status` = ".$db->quote($status)." 
                WHERE `errors`.`hash` = ".$db->quote($hash)." 
                AND (
                    `errors`.`status` = 'new' OR
                    `errors`.`status` = 'open'
                )";
        }
        
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }

}
