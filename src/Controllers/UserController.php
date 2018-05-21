<?php
/** Freesewing\Data\Controllers\UserController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\User as User;
use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * Holds data for a user.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class UserController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    // MIGRATION CALL 
    /** Migrate user accounts to encrypted DB scheme */
    public function migrate($request, $response, $args) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT `id`, `email`, `initial`, `username`, `pepper`, `data` FROM `users` WHERE `ehash` IS NULL OR `ehash` = '' LIMIT 1000";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if(!$result) return "All users migrated\n";
        else {
            // Cycle through users
            $count = 0; 
            foreach($result as $i => $val) {
                // Merge serialized data into atomic fields
                $d = json_decode($val['data']);
                // Units & theme 
                if(isset($d->account->units)) $units = $d->account->units;
                else $units = 'metric';
                if(isset($d->account->theme)) $theme = $d->account->theme;
                else $theme = 'basic';
                // Social accounts 
                foreach(['twitter','instagram','github'] as $s) {
                    if(isset($d->social->{$s}) && $d->social->{$s} != '') ${$s} = $d->social->{$s}; 
                    else ${$s} = NULL;
                }
                // Patron status 
                if(isset($d->patron) && isset($d->patron->tier)) { 
                    $patronSince = NULL;
                    $patron = $d->patron->tier;
                    $patronSince = date("Y-m-d H:i:s",$d->patron->since);
                } else {
                    $patron = 0;
                    $patronSince = NULL;
                }
                // Badges
                $data = new \stdClass();
                if(isset($d->badges)) $data->badges = $d->badges;

                // Format username
                $username = $this->migrateUsername($val['username']);

                // Encrypt data at rest
                $nonce = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)); 
                $email = Utilities::encrypt($val['email'], $nonce);
                $initial = Utilities::encrypt($val['initial'], $nonce);
                $json = JSON_encode($data, JSON_UNESCAPED_SLASHES);
                $data = Utilities::encrypt($json, $nonce);
                $twitter = Utilities::encrypt($twitter, $nonce);
                $instagram = Utilities::encrypt($instagram, $nonce);
                $github = Utilities::encrypt($github, $nonce);
                $ehash = hash('sha256', strtolower(trim($val['email'])));
                $sql = "UPDATE `users` SET 
                    `units` = ".$db->quote($units).",
                    `theme` = ".$db->quote($theme).",
                    `locale` = 'en',
                    `twitter` = ".$db->quote($twitter).",
                    `instagram` = ".$db->quote($instagram).",
                    `github` = ".$db->quote($github).",
                    `patron` = ".$db->quote($patron).",
                    `patronSince` = ".$db->quote($patronSince).",
                    `ehash` = ".$db->quote($ehash).",
                    `pepper` = ".$db->quote($nonce).",
                    `email` = ".$db->quote($email).",
                    `initial` = ".$db->quote($initial).",
                    `username` = ".$db->quote($username).",
                    `data` = ".$db->quote($data)."
                    WHERE `id` = ".$val['id'];
                if($db->exec($sql)) $count++;
                else die("Failed to run query: $sql"); 
            }
        }
        $db = null;
        return "Migrated $count users.\n";
    }
    
    private function migrateUsername($username) {
        $initial = str_replace([' ','@','#'], '', $username);
        // Some users don't need a change
        if($initial == $username) return $username;

        $count=2;
        $proposal = $initial;
        while(!$this->usernameIsFree($proposal)) {
            $proposal = $initial.$count;
            $count++;
        }

        return $proposal;
    }



    // Anonymous calls

    /** User signup
     *
     * Handles POST requests to /signup 
     * Expects email and password in request params
     */
    public function signup($request, $response, $args) 
    {
        // Handle request data 
        $in = new \stdClass();
        $in->email = strtolower(trim(Utilities::scrub($request, 'email')));
        $in->password = Utilities::scrub($request, 'password');
        $in->locale = Utilities::scrub($request, 'locale');
        
        // Don't continue if we don't have the required input
        if($in->email === false || $in->password === false || $in->password == '') {
            return Utilities::prepResponse($response, [
                'result' => 'error',
                'reason' => 'invalid_input'
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        // Do we already have a user with this email address?
        $user = clone $this->container->get('User');
        $user->loadFromEmail($in->email);
        if($user->getId()) {
            return Utilities::prepResponse($response, [
                'result' => 'error',
                'reason' => 'user_exists'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Seems like we'll at least be creating a task to send out an email
        $in->hash = Utilities::getToken('signup'.$in->email);
        $taskData = new \stdClass();
        $taskData->email = $in->email;
        $taskData->hash = $in->hash;
        $taskData->locale = $in->locale;

        // Do we already have a pending signup for this email address?
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->loadFromHash($in->hash);
        if($confirmation->getId()) {
            // Create task to send signup email
            $task = clone $this->container->get('Task');
            $task->create('emailSignup', $taskData);
            return Utilities::prepResponse($response, [
                'result' => 'error',
                'reason' => 'signup_pending'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Create signup confirmation
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->create($in);

        // Queue signup email
        $task = clone $this->container->get('Task');
        $task->create('emailSignup', $taskData);

        return Utilities::prepResponse($response, [
            'result' => 'ok' 
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Confirm email address at signup*/
    public function confirmEmailAddress($request, $response, $args) {

        // Get hash from API endpoint 
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);
        // Do we have a pending confirmation for this hash?
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->loadFromHash($hash);
        if ($confirmation->getId() == '') { 
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_pending_confirmation', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        // Set the email as confirmed 
        $confirmation->data->setNode('emailConfirmed', true);
        $confirmation->save();
        return Utilities::prepResponse($response, [
            'result' => 'ok'
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Confirm email address change after signup */
    public function confirmEmailChange($request, $response, $args) {

        // Get hash from API endpoint 
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);
        // Do we have a pending confirmation for this hash?
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->loadFromHash($hash);
        if ($confirmation->getId() == '') { 
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_pending_confirmation', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        // Load user object
        $user = clone $this->container->get('User');
        $user->loadFromId($confirmation->getData()->userid);
        if(!is_numeric($user->getId())) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_user', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        $user->setEmail($confirmation->getData()->newemail);
        $user->save();
        $confirmation->remove();

        return Utilities::prepResponse($response, [
            'result' => 'ok'
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Remove confirmation, consent for data processing not given */
    public function removeConfirmation($request, $response, $args) 
    {
        // Get hash from API endpoint 
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);

        // Remove from database
        $db = $this->container->get('db');
        $sql = "DELETE from `confirmations` WHERE `hash` = ".$db->quote($hash).";";

        $result = $db->exec($sql);
        $db = null;
        
        return Utilities::prepResponse($response, [
            'result' => 'ok'
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Create account from confirmation, consent for data processing given */
    public function createAccount($request, $response, $args) 
    {
        // Get hash from POST data 
        $hash = Utilities::scrub($request, 'hash');
        // Do we have a pending confirmation for this hash?
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->loadFromHash($hash);
        if ($confirmation->getId() == '') { 
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_pending_confirmation', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        // Load user object
        $user = clone $this->container->get('User');
        // Do we already have a user with this email address?
        $user->loadFromEmail($confirmation->data->getNode('email'));
        if(is_numeric($user->getId())) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'user_exists', 
            ], 404, $this->container['settings']['app']['origin']);
        }
        // Create user
        $user->create(
            $confirmation->data->getNode('email'),
            $confirmation->data->getNode('password'),
            $confirmation->data->getNode('locale')
        ); 
        // Create username from email address
        $user->setUsername($this->suggestUsername($confirmation->data->getNode('email')));
        $user->save();

        // Remove confirmation
        $confirmation->remove();

        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        return Utilities::prepResponse($response, [
            'result' => 'ok',
            'token' => $TokenKit->create($user->getId())
        ], 200, $this->container['settings']['app']['origin']);
    }

    private function suggestUsername($email) {
        $initial = array_shift(explode('@', $email));
        $count=2;
        $proposal = $initial;
        while(!$this->usernameIsFree($proposal)) {
            $proposal = $initial.$count;
            $count++;
        }

        return $proposal;
    }

    private function usernameIsFree($username) 
    {
        $db = $this->container->get('db');
        $sql = 'SELECT `id` FROM `users` WHERE  `username` = '.$db->quote($username).' LIMIT 1';
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;
    
        if ($result) return false;
        else return true;
    }

    /** Resend actication email
     *
     * Handles POST requests to /user/resend 
     * Expects email in request params
     */
    public function resend($request, $response, $args) {
        // Handle request data 
        $resend_data = ['email' => Utilities::scrub($request,'resend-email'), 'email'];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromEmail($resend_data['email']);

        // Does this user already exist?
        if ($user->getId() == '') { 
            $logger->info("Signup rejected: No account for ".$resend_data['email']);
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'resend/no-such-account',
            ], 404, $this->container['settings']['app']['origin']);
        } 
        
        if($user->getStatus() === 'blocked') {
            $logger->info("Resend blocked: User ".$user->getId()." is blocked");
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'resend/account-blocked',
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'active') {
            $logger->info("Resend blocked: User ".$user->getId()." is already active");
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_active', 
                'message' => 'resend/account-active',
            ], 400, $this->container['settings']['app']['origin']);
        }
        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->signup($user);
        
        $logger->info("Resend: ".$resend_data['email']." is user ".$user->getId());
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'signup_complete', 
            'message' => 'signup/success',
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Email change confirmation */
    public function confirm($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        $in->token = filter_var($args['token'], FILTER_SANITIZE_STRING);

        // Get a logger instance from the container
        $logger = $this->container->get('logger');

        // Load user
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->handle);

        // Does the user exist?
        if ($user->getId() == '') { 
            $logger->info("Confirmation rejected: User handle ".$in->handle." does not exist");
        
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'activation/no-such-account'
            ], 404, $this->container['settings']['app']['origin']);
        }

        // Is the user blocked? 
        if($user->getStatus() === 'blocked') {
            $logger->info('Confirmation rejected: User '.$user->getId().' is blocked');
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'account/blocked'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Is there a token mismatch? 
        if($in->token != $user->getActivationToken()) {
            $logger->info("Confirmation rejected: Token mismatch for user ".$user->getId());
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'token_mismatch', 
                'message' => 'activation/token-mismatch'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        // Confirm address
        $user->setEmail($user->getPendingEmail());
        $user->unsetPendingEmail();
        $user->save();
        
        $logger->info("Confirmation: User ".$user->getId()." is now confirmed for address ".$user->getEmail());
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'confirm_complete', 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** User login */
    public function login($request, $response, $args) {
        // Handle request data 
        $username =  Utilities::scrub($request, 'username'); 
        $password =  Utilities::scrub($request, 'password'); 
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromUsername($username);
        
        // We used to use email for logins, so don't punish people who still have that habit
        if($user->getId() == '') $user->loadFromEmail($username);

        if($user->getId() == '') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_user', 
                'username' => $username,
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'blocked') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'inactive') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
                'message' => 'login/account-inactive',
            ], 400, $this->container['settings']['app']['origin']);
        }

        if(!$user->checkPassword($password)) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'login_failed', 
                'message' => 'login/failed',
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Log login
        $user->setLogin();
        $user->save();
        
        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'token' => $TokenKit->create($user->getId()),
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** User password reset */
    public function reset($request, $response, $args) 
    {
        // Get hash from API endpoint 
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);
        // Do we have a pending confirmation for this hash?
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->loadFromHash($hash);
        if ($confirmation->getId() == '') { 
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_pending_confirmation', 
            ], 404, $this->container['settings']['app']['origin']);
        }

        // Load the user from the container
        $user = clone $this->container->get('User');
        $user->loadFromUsername($confirmation->data->getNode('username'));
        if($user->getId() === null) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_user', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'blocked') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'inactive') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Remove confirmation
        $confirmation->remove();

        // Log login
        $user->setLogin();
        $user->save();
        
        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'token' => $TokenKit->create($user->getId()),
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** User password recovery */
    public function recover($request, $response, $args) 
    {
        $input = Utilities::scrub($request, 'username');
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromUsername($input);
        if($user->getId() == '') $user->loadFromEmail($input);
        
        if($user->getId() == '') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_user', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'blocked') {
            
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        if($user->getStatus() === 'inactive') {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Create task to send out an email
        $hash = Utilities::getToken('recoverPassword'.$user->getEmail());
        $taskData = new \stdClass();
        $taskData->email = $user->getEmail();
        $taskData->username = $user->getUsername();
        $taskData->hash = $hash;
        $taskData->user = $user->getId();
        $taskData->locale = $user->getLocale();
        
        // Queue signup email
        $task = clone $this->container->get('Task');
        $task->create('recoverPassword', $taskData);

        // Create confirmation
        $confirmation = clone $this->container->get('Confirmation');
        $confirmation->create($taskData);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'recover_initiated', 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Patron list */
    public function patronList($request, $response, $args) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT `id` FROM `users` WHERE `patron` != 0 LIMIT 100";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;
        $tiers = $this->container['settings']['patrons']['tiers'];
        if(!$result) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_patrons', 
            ], 400, $this->container['settings']['app']['origin']);
        } else {
            // Cycle through users
            foreach($result as $id) {
                // Load account
                $user = clone $this->container->get('User');
                $user->loadFromId($id['id']);
                if($user->getStatus() === 'active' && in_array($user->getPatronTier(), $tiers)) {
                    $patron = new \stdClass();
                    $patron->username = $user->getUsername();
                    $patron->handle = $user->getHandle();
                    $patron->tier = $user->getPatronTier();
                    $patron->picture = $user->getPictureUrl();
                    $patron->badges = $user->getBadges();
                    $patron->social = $user->getSocial();
                    $timestamp = $user->getPatronSince();
                    if($timestamp === false) $timestamp = time();
                    $patron->since = $timestamp;
                    while(isset($patrons[$timestamp])) $timestamp++;
                    $patrons[$timestamp] = $patron;
                    $keys[] = $timestamp;
                } else {
                    echo "\nUser ".$user->getId()." is not a patron";
                }
            }
        } 

        // Newest patrons at the top
        rsort($keys);
        foreach($keys as $t) $sorted[] = $patrons[$t];
    
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'patrons' => $sorted, 
        ], 200, $this->container['settings']['app']['origin']);
    } 

    // Authenticated calls
   
    /** Minimal auth check */
    public function auth($request, $response, $args)
    {
        $return = false;
        // Get ID from authentication middleware
        $in = new \stdClass();
        $in->id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($in->id);
        if($user->isPatron()) $patron = $user->getPatronTier();
        else $patron = 0;
        
        // Prepare return JSON
        $return = [
            'result' => 'ok', 
            'id' => $user->getId(), 
            'email' => $user->getEmail(), 
            'user' => $user->getUsername(), 
            'patron' => $patron, 
            'role' => $user->getRole(), 
        ];

        // Add badge if needed
        $badge = $this->container['settings']['badges']['login'];
        if($user->addBadge($badge)) {
            $user->save();
            $return['new_badge'] = $badge;
        }
    
        $response->getBody()->write(json_encode($return));

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin']);
    }

    /** Load user account */
    public function load($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return Utilities::prepResponse($response, [
            'account' => [
                'id' => $user->getId(), 
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'patron' => $user->getPatron(), 
                'role' => $user->getRole(), 
                'units' => $user->getUnits(), 
                'theme' => $user->getTheme(), 
                'locale' => $user->getLocale(), 
                'consent' => [
                    'profile' => $user->getProfileConsent(), 
                    'model' => $user->getModelConsent(),
                    'objectsToOpenData' => $user->getObjectsToOpenData() 
                ],
                'social' => $user->getSocial(),
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'login' => $user->getLogin(), 
                'picture' => $user->getPicture(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
                'data' => $user->getData(), 
                'email' => $user->getEmail(), 
            ],
            'models' => $user->getModels(),
            'drafts' => $user->getDrafts(),
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Update account */
    public function update($request, $response, $args) 
    {
        $update = false; 
        
        // Handle request
        $in = new \stdClass();
        $in->email = Utilities::scrub($request,'email','email');
        $in->username = Utilities::scrub($request,'username', 'username');
        $in->address = Utilities::scrub($request,'address');
        $in->birthmonth = Utilities::scrub($request,'birthday-month');
        $in->birthday = Utilities::scrub($request,'birthday-day');
        $in->twitter = Utilities::scrub($request,'twitter');
        $in->instagram = Utilities::scrub($request,'instagram');
        $in->github = Utilities::scrub($request,'github');
        $in->locale = Utilities::scrub($request,'locale');
        $in->profileConsent = Utilities::scrub($request,'profileConsent', 'bool');
        $in->modelConsent = Utilities::scrub($request,'modelConsent', 'bool');
        $in->objectsToOpenData = Utilities::scrub($request,'objectsToOpenData', 'bool');
        $in->currentPassword = Utilities::scrub($request,'currentPassword');
        $in->newPassword = Utilities::scrub($request,'newPassword');
        $in->bio = Utilities::scrub($request,'bio');
        (Utilities::scrub($request,'units') == 'imperial') ? $in->units = 'imperial' : $in->units = 'metric';
        (Utilities::scrub($request,'theme') == 'paperless') ? $in->theme = 'paperless' : $in->theme = 'classic';

        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = clone $this->container->get('User');
        $user->loadFromId($in->id);

        // Handle picture
        if($in->picture != '') {
            // Get the AvatarKit to create the avatar
            $avatarKit = $this->container->get('AvatarKit');
            $user->setPicture($avatarKit->createFromDataString($in->picture, $user->getHandle()));
            $update = true;
        }

        // Handle preferences
        foreach(['units', 'theme', 'locale'] as $field) {
            if($in->{$field} !== false && $user->{'get'.ucfirst($field)}() != $in->{$field}) {
                $user->{'set'.ucfirst($field)}($in->{$field});
                $update = true;
            }
        }

        // Handle 3rd party accounts
        foreach(['twitter', 'instagram', 'github'] as $field) {
            if($in->{$field} !== false && $user->{'get'.ucfirst($field).'Handle'}() != $in->{$field}) {
               $user->{'set'.ucfirst($field).'Handle'}($in->{$field});
               $update = true;
            }
        }

        // Handle username change
        if($in->username !== false && $user->getUsername() != $in->username) {
            if($user->usernameTaken($in->username)) {
                return Utilities::prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'username_taken', 
                ], 200, $this->container['settings']['app']['origin']);
            }
            $user->setUsername($in->username);
            $update = true;
        }

        // Handle email change
        if($in->email !== false && $user->getEmail() != $in->email) {
            if($user->emailTaken($in->email)) {
                return Utilities::prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'email_taken', 
                ], 400, $this->container['settings']['app']['origin']);
            }

            // Queue confirmation email 
            $taskData = new \stdClass();
            $taskData->oldemail = $user->getEmail();
            $taskData->newemail = $in->email;
            $taskData->username = $user->getUsername();
            $taskData->userid = $user->getId();
            $taskData->locale = $user->getLocale();
            $taskData->hash = Utilities::getToken('emailChange'.$in->email.$user->getId());
            $task = clone $this->container->get('Task');
            $task->create('emailChange', $taskData);
        
            // Create confirmation
            $confirmation = clone $this->container->get('Confirmation');
            $confirmation->create($taskData);
            $update = true;
        } 

        // Handle password change
        if($in->currentPassword !== false && $in->newPassword != $in->github && $in->newPassword != '') {
            if(!$user->checkPassword($in->currentPassword)) {
                return Utilities::prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'password_incorrect', 
                ], 400, $this->container['settings']['app']['origin']);
            }
            $user->setPassword($in->newPassword); 
            $update = true;
        }

        // Handle avatar upload
        if(
            $request->getContentType() === 'image/jpeg' ||
            $request->getContentType() === 'image/png' ||
            $request->getContentType() === 'image/gif' 
        ) {
            // Get the AvatarKit to create the avatar
            $avatarKit = $this->container->get('AvatarKit');
            $user->setPicture($avatarKit->createFromData($request->getBody()->getContents(), $user->getHandle()));
            $update = true;
        }

        // Handle consent
        foreach(['profileConsent', 'modelConsent', 'objectsToOpenData'] as $field) {
            if(($in->{$field} === false || $in->{$field} === true) && $user->{'get'.ucfirst($field)}() != $in->{$field}) {
                if($field === 'profileConsent' && $in->{$field} === false) {
                    // No profile consent, remove all data
                    $user->remove();
                    return Utilities::prepResponse($response, [
                        'result' => 'ok', 
                        'reason' => 'account_removed',
                    ], 200, $this->container['settings']['app']['origin']);
                } else if($field === 'modelConsent' && $in->{$field} === false) {
                    // No mode consent, remove relevant data
                    $user->removeModelData();
                } 
                $user->{'set'.ucfirst($field)}($in->{$field});
                $update = true;
            }
        }

        // Handle bio change
        if($in->bio !== false && $user->data->getNode('bio') != $in->bio) {
            $user->data->setNode('bio', $in->bio); 
            $update = true;
        }

        // Save changes 
        if($update) {
            $user->save();
        
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'reason' => 'account_updated',
            ], 200, $this->container['settings']['app']['origin']);

        }
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'no_changes_made',
        ], 200, $this->container['settings']['app']['origin']);
    }
    
    /** Load user profile */
    public function profile($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromUsername(filter_var($args['username'], FILTER_SANITIZE_STRING));

        if($user->getId() === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error',
                'reason' => 'no_such_user'
            ], 400, $this->container['settings']['app']['origin']);
        }

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        $bio = $user->data->getNode('bio');
        if($bio === false) $bio = '';
        $return = [
            'profile' => [
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'bio' => $bio, 
                'social' => $user->getSocial(),
                'badges' => $user->data->getNode('badges'),
                'patron' => $user->getPatronTier(),
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'migrated' => $user->getMigrated(),
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
            ],
            'drafts' => $user->getSharedDrafts(),
            'comments' => $user->getComments(),
        ];
        $data = $user->getData();
        if(isset($data->badges)) $return['badges'] = $data->badges;
        if(isset($data->social)) $return['social'] = $data->social;
        if(isset($data->patron)) $return['patron'] = $data->patron;

        return Utilities::prepResponse($response, $return, 200, $this->container['settings']['app']['origin']);
    } 

    /** Remove account */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->goodbye($user);
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        $logger->info("User removed: ".$user->getId()." (".$user->getEmail().")is no more");
        
        $user->remove();
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'user_removed', 
        ], 200, $this->container['settings']['app']['origin']);
    } 

    /** Export user data */
    public function export($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Exporting user data for: ".$user->getId()." (".$user->getEmail().")");
        
        $zip = $user->export();
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'archive' => $zip, 
        ], 200, $this->container['settings']['app']['origin']);
    } 
    
    public function role($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($id);

        return Utilities::prepResponse(
            $response, 
            ['role' => $user->getRole()],
            200,
            $this->container['settings']['app']['origin']
        );
    } 

}
