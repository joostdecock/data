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

    private function getUsername($args)
    {
        return filter_var($args['username'], FILTER_SANITIZE_STRING);
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
        $in->username = $this->getUsername($args);
        $in->badge = filter_var($args['badge'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container
        $admin = clone $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromUsername($in->username);
        // Add badge and save
        if($remove) $user->removeBadge($in->badge);
        else $user->addBadge($in->badge);
        $user->save();

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
        $username = filter_var($args['username'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container and load user data
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromUsername($username);
        
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

    /** Latest user accounts */
    public function recentUsers($request, $response, $args) 
    {
        return $this->userFind($request, $response, $args, TRUE);
    }

    /** Find user accounts */
    public function userFind($request, $response, $args, $showLatestUsers=false) 
    {
        $db = $this->container->get('db');
        if($showLatestUsers) {
            $sql = "SELECT `users`.`id` from `users` WHERE 1
                ORDER BY `CREATED` DESC
                LIMIT 100";
        } else {
            // Handle request data 
            $filter = filter_var($args['filter'], FILTER_SANITIZE_STRING);

            $sql = "SELECT `users`.`id` from `users` WHERE 
                `users`.`username` LIKE ".$db->quote('%'.$filter.'%')."
                OR `users`.`ehash` = ".$db->quote(hash('sha256',strtolower(trim($filter))))."
                OR `users`.`id` = ".$db->quote(trim($filter))."
                OR `users`.`handle` LIKE ".$db->quote("%$filter%")."
                ORDER BY `CREATED` DESC
                LIMIT 50";
        }
        
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
        if($errorController->errorsGroupInfo($hash) === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_group', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Update status
        $errorController->errorsSetGroupStatus($status, $hash);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok' 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** 
     * Updates the status for (all) entries in a group
     *
     * @param string $status The new status
     */
    private function errorsSetGroupStatus($status, $hash)
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

    /** List recent referrals */
    public function recentReferrals($request, $response, $args, $host=FALSE) 
    {
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
        $sql = "SELECT COUNT(`id`) as `hits`, `host`, `url` FROM `referrals` 
            WHERE `time` > DATE('".date('Y-m-d',strtotime('-1 month'))."') ";
        if($host === FALSE) $sql .= "GROUP BY `host` ORDER BY `hits` DESC";
        else $sql .= "AND `host` = ".$db->quote($host)." GROUP BY `url` ORDER BY `hits` DESC";
        
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) {
            return Utilities::prepResponse($response, [ 
                'result' => 'error',
                'reason' => 'no_referrals_found'
            ], 404, $this->container['settings']['app']['origin']);
        } else {
            return Utilities::prepResponse($response, [ 
                'result' => 'ok',
                'referrals' => $result
            ], 200, $this->container['settings']['app']['origin']);
        } 
    }
    
    /** List recent referrals for a host*/
    public function recentReferralsForHost($request, $response, $args) 
    {
        // Handle request data 
        $host = filter_var($args['host'], FILTER_SANITIZE_STRING);
        
        return $this->recentReferrals($request, $response, $args, $host);
    }

    /** List recent error groups */
    public function errorsRecent($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container and load user data
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            // Get a logger instance from the container
            $logger = $this->container->get('logger');
            $logger->info("Failed to load recent errors: User ".$admin->getId()." is not an admin");

            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ], 400, $this->container['settings']['app']['origin']);
        }

        $errors = $this->errorsActiveGroups();
        if($errors === false) {
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ], 200, $this->container['settings']['app']['origin']);
        }

            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ], 200, $this->container['settings']['app']['origin']);

    }

    /** List all error groups */
    public function errorsAll($request, $response, $args) 
    {
        $errors = $this->errorsAllGroups();
        if($errors === false) {
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ], 200, $this->container['settings']['app']['origin']);
        }

            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ], 200, $this->container['settings']['app']['origin']);

    }

    /** Load error group */
    public function errorsGroup($request, $response, $args) 
    {
        // Request data
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);

        $group = $this->errorsGroupInfo($hash);
        if($group === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'failed_to_load_group',
            ], 400, $this->container['settings']['app']['origin']);
        }
        $group['count'] = $this->errorsGroupCount($hash);
        $group['status'] = $this->errorsGroupStatus($group['count']);
        $group['errors'] = $this->errorsGroupErrors($hash);

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'group' => $group
        ], 200, $this->container['settings']['app']['origin']);
    }

    private function errorsGroupStatus($counters) {
        if($counters['new'] > 0) return 'new';
        if($counters['open'] > 0) return 'open';
        if($counters['muted'] > 0) return 'muted';
        return 'closed';
    }


    /**
     * Returns the common info for an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    public function errorsGroupInfo($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT 
            `hash`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `type`,
            `raw`,
            `time`
            FROM `errors` WHERE 
            `hash` = ".$db->quote($hash)."
            ORDER BY `time` DESC LIMIT 1";
        $result = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $db = null;
        
        if($result === false) return false;
        
        $result['last_seen'] = $result['time'];
        unset($result['time']);
        
        return $result;
    }

    /**
     * Returns list of errors for an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    private function errorsGroupErrors($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT 
            `id`,
            `ip`,
            `time`,
            `status`
            FROM `errors` WHERE 
            `hash` = ".$db->quote($hash)."
            ORDER BY `time` DESC LIMIT 50";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;
        
        if(!$result) return false;
        else return $result;
    }

    /**
     * Returns the error count by statusfor an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    private function errorsGroupCount($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT COUNT(`id`) as 'count' FROM `errors` 
            WHERE hash=".$db->quote($hash);
        $result = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);

        $count['total'] = (int)$result['count'];
        $sql = "SELECT COUNT(`id`) as 'count', `status` FROM `errors` 
            WHERE hash=".$db->quote($hash)." GROUP BY `status`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        $db = null;
        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $count[$val['status']] = $val['count'];
        }
        // Set missing status types to zero
        foreach(['new','open','muted','closed'] as $status) {
            if(!isset($count[$status])) {
                $count[$status] = 0;
            } else {
                $count[$status] = (int)$count[$status];
            }
        }

        return $count;
    }

    /**
     * Returns an overview of new/open groups in the last 24 hours
     */
    private function errorsActiveGroups() 
    {
        $db = $this->container->get('db');

        $time = date('Y-m-d H:i:s', time() - 7*24*60*60);
        $sql = "SELECT 
            COUNT(`id`) as 'count', 
            `type`,
            `time`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `hash`
            FROM `errors` WHERE 
            (`status` = 'new' OR `status` = 'open') 
            AND `time` > '$time' 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $result[$key]['status'] = $this->errorsGroupStatus($this->errorsGroupCount($val['hash']));
        }
        return $result;
    }

    /**
     * Returns an overview of all groups in the last 28 days
     */
    private function errorsAllGroups() 
    {
        $db = $this->container->get('db');

        $time = date('Y-m-d H:i:s', time() - 28*24*60*60);
        $sql = "SELECT 
            COUNT(`id`) as 'count', 
            `type`,
            `time`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `hash`
            FROM `errors` WHERE 
            `time` > '$time' 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC
            LIMIT 100";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $result[$key]['status'] = $this->errorsGroupStatus($this->errorsGroupCount($val['hash']));
        }
        return $result;
    }
}
