<?php
/** App\Controllers\UserController class */
namespace App\Controllers;

use \App\Data\User as User;

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

    /** Minimal auth check */
    public function auth($request, $response, $args)
    {
        $return = false;
        // Add badge if needed
        if(isset($this->container['settings']['badges']['login'])) {
            // Get ID from authentication middleware
            $in = new \stdClass();
            $in->id = $request->getAttribute("jwt")->user;
            // Get a user instance from the container
            $user = $this->container->get('User');
            $user->loadFromId($in->id);
            if($user->addBadge($this->container['settings']['badges']['login'])) {
                $user->save();
                $return = ['result' => 'ok', 'new_badge' => $this->container['settings']['badges']['login']];
            }
        }
    
        if(!$return) $return = ['result' => 'ok'];
        $response->getBody()->write(json_encode($return));

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin']);
    }

    /** User password reset */
    public function reset($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $reset_data = [
            'password' => filter_var($data['reset-password'], FILTER_SANITIZE_STRING),
            'handle' => filter_var($data['reset-handle'], FILTER_SANITIZE_STRING),
            'token' => filter_var($data['reset-token'], FILTER_SANITIZE_STRING),
        ];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromHandle($reset_data['handle']);

        if($user->getId() == '') {
            $logger->info("Reset blocked: No user with address ".$reset_data['email']);

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'reset_failed_FIXME_AddedToKnowDifferenceBetweenWrongPasswordWhileDeveloping', 
                'message' => 'reset/failed',
            ]);
        }

        if($user->getStatus() === 'blocked') {
            $logger->info("Reset blocked: User ".$user->getId()." is blocked");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'reset/blocked',
            ]);
        }

        if($user->getStatus() === 'inactive') {
            $logger->info("Reset blocked: User ".$user->getId()." is inactive");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
                'message' => 'reset/inactive',
            ]);
        }

        $user->setPassword($reset_data['password']);
        $user->save();

        $logger->info("Reset: Password reset for user ".$user->getId());

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'password_reset', 
            'message' => 'reset/success',
        ]);
    }

    /** User password recovery */
    public function recover($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $recover_data = [
            'email' => filter_var($data['recover-email'], FILTER_SANITIZE_EMAIL),
        ];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromEmail($recover_data['email']);

        if($user->getId() == '') {
            $logger->info("Recover blocked: No user with address ".$recover_data['email']);

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'recover_failed_FIXME_AddedToKnowDifferenceBetweenWrongPasswordWhileDeveloping', 
                'message' => 'recover/failed',
            ]);
        }

        if($user->getStatus() === 'blocked') {
            $logger->info("Recover blocked: User ".$user->getId()." is blocked");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'recover/blocked',
            ]);
        }

        if($user->getStatus() === 'inactive') {
            $logger->info("Recover blocked: User ".$user->getId()." is inactive");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
                'message' => 'recover/inactive',
            ]);
        }

        $logger->info("Recover: User ".$user->getId());

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->recover($user);
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'recover_initiated', 
            'message' => 'recover/sent',
        ]);
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

    /** Update account */
    public function update($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->email = $this->scrub($request,'email','email');
        $in->username = $this->scrub($request,'username');
        $in->picture = $this->scrub($request,'picture');
        ($this->scrub($request,'units') == 'imperial') ? $in->units = 'imperial' : $in->units = 'metric';
        ($this->scrub($request,'theme') == 'paperless') ? $in->theme = 'paperless' : $in->theme = 'classic';
     
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Handle picture
        if($in->picture != '') {
            // Get the AvatarKit to create the avatar
            $avatarKit = $this->container->get('AvatarKit');
            $user->setPicture($avatarKit->createFromDataString($in->picture, $user->getHandle()));
        }

        // Handle username change
        if($user->getUsername() != $in->username) {
            if($user->usernameTaken($in->username)) {
                $logger->info("Failed to update profile for user ".$user->getId().": Username ".$in->username." is taken");

                return $this->prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'username_taken', 
                    'message' => 'account/username-taken',
                ]);
            }
            $user->setUsername($in->username);
        }

        // Handle toggles
        $data = $user->getData();
        if($data->account->units != $in->units) $data->account->units = $in->units;
        if($data->account->theme != $in->theme) $data->account->theme = $in->theme;

        // Handle email change
        $pendingEmail = false;
        if($user->getEmail() != $in->email) {
            if($user->emailTaken($in->email)) {
                $logger->info("Failed to update profile for user ".$user->getId().": Email ".$in->email." is taken");

                return $this->prepResponse($response, [
                    'result' => 'error', 
                    'reason' => 'email_taken', 
                    'message' => 'account/email-taken',
                ]);
            }
            // Send email 
            $mailKit = $this->container->get('MailKit');
            $mailKit->emailChange($user, $in->email);
            $logger->info("Email change requested for user ".$user->getId().": From ".$user->getEmail()." to ".$in->email);
            $pendingEmail = $in->email;
            // Store future email address pending confirmation
            $data->account->pendingEmail = $in->email;
        }

        // Save changes 
        $user->setData($data);
        $user->save();

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'message' => 'account/updated',
            'pendingEmail' => $pendingEmail,
            'data' => json_encode($data),
        ]);
    }
    
    /** Removes badge from user profile */
    public function removeBadge($request, $response, $args) 
    {
        return $this->addBadge($request, $response, $args, true);
    }

    /** Add badge to user profile */
    public function addBadge($request, $response, $args, $remove=false) 
    {
        if($remove) $verb = 'remove';
        else $verb = 'add';

        // Handle request
        $in = new \stdClass();
        $in->userHandle = $this->scrub($request,'user');
        $in->badge = $this->scrub($request,'badge');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to $verb badge: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);

        // Add badge and save
        if($remove) $user->removeBadge($in->badge);
        else $user->addBadge($in->badge);
        $user->save();
        $logger->info("Badge ".$in->badge." $verb"."ed to user ".$user->getId());

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'badges' => $user->getData()->badges
        ]);
    }

    /** User login */
    public function login($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $login_data = [
            'email' => filter_var($data['login-email'], FILTER_SANITIZE_EMAIL),
            'password' => filter_var($data['login-password'], FILTER_SANITIZE_STRING),
        ];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromEmail($login_data['email']);

        if($user->getId() == '') {
            $logger->info("Login blocked: No user with address ".$login_data['email']);

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'login_failed_FIXME_AddedToKnowDifferenceBetweenWrongPasswordWhileDeveloping', 
                'message' => 'login/failed',
            ]);
        }

        if($user->getStatus() === 'blocked') {
            $logger->info("Login blocked: User ".$user->getId()." is blocked");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'login/account-blocked',
            ]);
        }

        if($user->getStatus() === 'inactive') {
            $logger->info("Login blocked: User ".$user->getId()." is inactive");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_inactive', 
                'message' => 'login/account-inactive',
            ]);
        }

        if(!$user->checkPassword($login_data['password'])) {
            $logger->info("Login failed: Incorrect password for user ".$user->getId());
            
            return $this->prepResponse($response, [
                'user' => print_r($user,1),
                'result' => 'error', 
                'reason' => 'login_failed', 
                'message' => 'login/failed',
            ]);
        }

        // Log login
        $user->setLogin();
        $user->save();
        $logger->info("Login: User ".$user->getId());
        
        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'password_correct', 
            'message' => 'login/success',
            'token' => $TokenKit->create($user->getId()),
            'userid' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
        ]);
    }

    /** User signup
     *
     * Handles POST requests to /user/signup 
     * Expects email and password in request params
     */
    public function signup($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $signup_data = [
            'email' => filter_var($data['signup-email'], FILTER_SANITIZE_EMAIL),
            'password' => filter_var($data['signup-password'], FILTER_SANITIZE_STRING),
        ];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Signup request from: ".$signup_data['email']);
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromEmail($signup_data['email']);

        // Does this user already exist?
        if ($user->getId() != '') { 
            $logger->info("Signup rejected: ".$signup_data['email']." already has an account");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_exists', 
                'message' => 'signup/account-exists',
            ]);
        } 
        
        // Create new user
        $user->create($signup_data['email'], $signup_data['password']);

        // Send email 
        $mailKit = $this->container->get('MailKit');
        if($mailKit->signup($user)) $logger->info("Activation email sent to: ".$signup_data['email']);
        else $logger->info("Could not send activation email to: ".$signup_data['email']);
        
        $logger->info("Signup: ".$signup_data['email']." is user ".$user->getId());
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'signup_complete', 
            'message' => 'signup/success',
        ]);
    }
    
    /** Resend actication email
     *
     * Handles POST requests to /user/resend 
     * Expects email in request params
     */
    public function resend($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $resend_data = [
            'email' => filter_var($data['resend-email'], FILTER_SANITIZE_EMAIL),
        ];
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromEmail($resend_data['email']);

        // Does this user already exist?
        if ($user->getId() == '') { 
            $logger->info("Signup rejected: No account for ".$resend_data['email']);
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'resend/no-such-account',
            ]);
        } 
        
        if($user->getStatus() === 'blocked') {
            $logger->info("Resend blocked: User ".$user->getId()." is blocked");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'resend/account-blocked',
            ]);
        }

        if($user->getStatus() === 'active') {
            $logger->info("Resend blocked: User ".$user->getId()." is already active");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_active', 
                'message' => 'resend/account-active',
            ]);
        }
        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->signup($user);
        
        $logger->info("Resend: ".$resend_data['email']." is user ".$user->getId());
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'signup_complete', 
            'message' => 'signup/success',
        ]);
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
        $user = $this->container->get('User');
        $user->loadFromHandle($in->handle);

        // Does the user exist?
        if ($user->getId() == '') { 
            $logger->info("Confirmation rejected: User handle ".$in->handle." does not exist");
        
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'activation/no-such-account'
            ]);
        }

        // Is the user blocked? 
        if($user->getStatus() === 'blocked') {
            $logger->info('Confirmation rejected: User '.$user->getId().' is blocked');
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'account/blocked'
            ]);
        }

        // Is there a token mismatch? 
        if($in->token != $user->getActivationToken()) {
            $logger->info("Confirmation rejected: Token mismatch for user ".$user->getId());
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'token_mismatch', 
                'message' => 'activation/token-mismatch'
            ]);
        }

        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        // Confirm address
        $user->setEmail($user->getData()->account->pendingEmail);
        $data = $user->getData();
        unset($data->account->pendingEmail);
        $user->save();
        
        $logger->info("Confirmation: User ".$user->getId()." is now confirmed for address ".$user->getEmail());
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'confirm_complete', 
        ]);
    }
    
    /** User activation */
    public function activate($request, $response, $args) {

        // Handle request data 
        $activation_data = [
            'handle' => filter_var($args['handle'], FILTER_SANITIZE_STRING),
            'token' => filter_var($args['token'], FILTER_SANITIZE_STRING),
        ];

        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        $user = $this->container->get('User');
        $user->loadFromHandle($activation_data['handle']);

        // Does the user exist?
        if ($user->getId() == '') { 
            $logger->info("Activation rejected: User handle ".$activation_data['handle']." does not exist");
        
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'activation/no-such-account'
            ]);
        }

        // Is the user blocked? 
        if($user->getStatus() === 'blocked') {
            $logger->info('Activation rejected: User '.$user->getId().' is blocked');
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'account/blocked'
            ]);
        }

        // Is there a token mismatch? 
        if($activation_data['token'] != $user->getActivationToken()) {
            $logger->info("Activation rejected: Token mismatch for user ".$user->getId());
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'token_mismatch', 
                'message' => 'activation/token-mismatch'
            ]);
        }

        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        // Activate user
        $user->setStatus('active');
        $user->save();
        
        $logger->info("Activation: User ".$user->getId()." is now active");
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'signup_complete', 
        ]);
    }
    
    /** Load user account */
    public function load($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return $this->prepResponse($response, [
            'account' => [
                'id' => $user->getId(), 
                'email' => $user->getEmail(), 
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'migrated' => $user->getMigrated(), 
                'login' => $user->getLogin(), 
                'picture' => $user->getPicture(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
                'data' => $user->getData(), 
            ],
            'models' => $user->getModels(),
            'drafts' => $user->getDrafts(),
        ]);
    } 

    /** List user accounts */
    public function userlist($request, $response, $args) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT 
            `users`.`username`,
            `users`.`picture`,
            `users`.`data`,
            `users`.`created`,
            `users`.`handle` as userhandle
            from `users` 
            WHERE `users`.`status` = 'active'
            ORDER BY `CREATED` DESC
            LIMIT 50";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else {
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            foreach($result as $key => $val) {
                $val->picture = '/static'.$avatarKit->getDir($val->userhandle).'/'.$val->picture;
                $data = json_decode($val->data);
                if(isset($data->badges)) $val->badges = $data->badges;
                unset($val->data);
                $users[$val->userhandle] = $val;
            }
        } 

        return $this->prepResponse($response, [ 'users' => $users ]);
    }

    /** Find user accounts */
    public function find($request, $response, $args) 
    {
        // Handle request data 
        $filter = filter_var($args['filter'], FILTER_SANITIZE_STRING);
        
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $admin = $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            // Get a logger instance from the container
            $logger = $this->container->get('logger');
            $logger->info("Failed to find users: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        $db = $this->container->get('db');

        $sql = "SELECT 
            `users`.`email`,
            `users`.`username`,
            `users`.`picture`,
            `users`.`data`,
            `users`.`created`,
            `users`.`handle` as userhandle
            from `users` 
            WHERE `users`.`username` LIKE ".$db->quote("%$filter%")."
            OR `users`.`email` LIKE ".$db->quote("%$filter%")."
            OR `users`.`handle` LIKE ".$db->quote("%$filter%")."
            ORDER BY `CREATED` DESC
            LIMIT 50";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else {
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            foreach($result as $key => $val) {
                $val->picture = '/static'.$avatarKit->getDir($val->userhandle).'/'.$val->picture;
                $data = json_decode($val->data);
                if(isset($data->badges)) $val->badges = $data->badges;
                unset($val->data);
                $users[$val->userhandle] = $val;
            }
        } 

        return $this->prepResponse($response, [ 'users' => $users, 'filter' => $filter ]);
    }

    /** Load user profile */
    public function profile($request, $response, $args) 
    {
        // Request data
        $in = new \stdClass();
        $in->handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromHandle($in->handle);

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        return $this->prepResponse($response, [
            'profile' => [
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'migrated' => $user->getMigrated(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
            ],
            'badges' => $user->getData()->badges,
            'drafts' => $user->getDrafts(),
        ]);
    } 

    public function role($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        return $this->prepResponse($response, ['role' => $user->getRole()]);
    } 

    /** Remove account */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->goodbye($user);
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        $logger->info("User removed: ".$user->getId()." (".$user->getEmail().")is no more");
        
        $user->remove();
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'user_removed', 
        ]);
    } 

}
