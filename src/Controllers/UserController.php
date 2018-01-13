<?php
/** Freesewing\Data\Controllers\UserController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\User as User;

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
    private function prepResponse($response, $data, $status=200)
    {
        return $response
            ->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /** Minimal auth check */
    public function auth($request, $response, $args)
    {
        $return = false;
        
        // Get ID from authentication middleware
        $in = new \stdClass();
        $in->id = $request->getAttribute("jwt")->user;
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);
        if($user->isPatron()) $patron = $user->getPatronTier();
        else $patron = 0;
        
        // Add badge if needed
        if(isset($this->container['settings']['badges']['login'])) {
            if($user->addBadge($this->container['settings']['badges']['login'])) {
                $user->save();
                $return = [
                    'result' => 'ok', 
                    'id' => $user->getId(), 
                    'email' => $user->getEmail(), 
                    'user' => $user->getUsername(), 
                    'patron' => $patron, 
                    'new_badge' => $this->container['settings']['badges']['login'],
                ];
            }
        }
    
        if(!$return) { 
            $return = [
                'result' => 'ok', 
                'id' => $user->getId(), 
                'email' => $user->getEmail(), 
                'user' => $user->getUsername(), 
                'patron' => $patron, 
            ];
        }
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
        $in->address = $this->scrub($request,'address');
        $in->birthmonth = $this->scrub($request,'birthday-month');
        $in->birthday = $this->scrub($request,'birthday-day');
        $in->twitter = $this->scrub($request,'twitter');
        $in->instagram = $this->scrub($request,'instagram');
        $in->github = $this->scrub($request,'github');
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
        if($user->getAccountUnits() != $in->units) $user->setAccountUnits($in->units);
        if($user->getAccountTheme() != $in->theme) $user->setAccountTheme($in->theme);
        
        // Handle 3rd party accounts
        $user->setTwitterHandle($in->twitter);
        $user->setInstagramHandle($in->instagram);
        $user->setGithubHandle($in->github);

        // Patron info 
        if($in->address !== false) $user->setPatronAddress($in->address);
        if($in->birthday !== false) $user->setPatronBirthday($in->birthday, $in->birthmonth);

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
            // Store future email address pending confirmation
            $user->setPendingEmail($in->email);
            
            // Save changes 
            $user->save();

            return $this->prepResponse($response, [
                'result' => 'ok', 
                'message' => 'account/updated',
                'pendingEmail' => $user->getPendingEmail(),
                'data' => $user->getDataAsJson(),
            ]);
        } else {
            // Save changes 
            $user->save();

            return $this->prepResponse($response, [
                'result' => 'ok', 
                'message' => 'account/updated',
                'data' => $user->getDataAsJson(),
            ]);
        }
    }
    
    /** Set password (by admin) */
    public function setPassword($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->password = filter_var($request->getParsedBody()['password'], FILTER_SANITIZE_STRING);
        $in->userHandle = $this->scrub($request,'user');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set password: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPassword($in->password);
        $user->save();
        $logger->info("Password for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return $this->prepResponse($response, [
            'result' => 'ok', 
        ]);

    }

    /** Set address (by admin) */
    public function setAddress($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->address = filter_var($request->getParsedBody()['address'], FILTER_SANITIZE_STRING);
        $in->userHandle = $this->scrub($request,'user');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set address: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPatronAddress($in->address);
        $user->save();
        $logger->info("Address for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return $this->prepResponse($response, [
            'result' => 'ok', 
        ]);

    }

    /** Set birthday (by admin) */
    public function setBirthday($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->month = filter_var($request->getParsedBody()['month'], FILTER_SANITIZE_NUMBER_INT);
        $in->day = filter_var($request->getParsedBody()['day'], FILTER_SANITIZE_NUMBER_INT);
        $in->userHandle = $this->scrub($request,'user');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed to set birthday: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);
        
        $user->setPatronBirthday($in->day, $in->month);
        $user->save();
        $logger->info("Birthday for user ".$in->userHandle." changed by admin ".$admin->getHandle());

        return $this->prepResponse($response, [
            'result' => 'ok', 
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
            'badges' => $user->getBadges(),
        ]);
    }

    /** Make a user a Patron */
    public function makePatron($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->userHandle = $this->scrub($request,'user');
        $in->patron = $this->scrub($request,'patron');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Get a user instance from the container
        $admin = $this->container->get('User');
        $admin->loadFromId($in->id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger->info("Failed set Patron status: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }

        // Load account
        $user = clone $this->container->get('User');
        $user->loadFromHandle($in->userHandle);

        // Set patron status and save
        $user->makePatron($in->patron);
        $user->save();
        $logger->info("Patron status set to ".$in->patron." for user ".$user->getId());

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'patron' => $user->getPatron(),
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
        if($user->isPatron()) $tier = $user->getPatronTier();
        else $tier = 0;
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'password_correct', 
            'message' => 'login/success',
            'token' => $TokenKit->create($user->getId()),
            'userid' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'patron' => $tier,
        ]);
    }

    /** User signup
     *
     * Handles POST requests to /user/signup 
     * Expects email and password in request params
     */
    public function signup($request, $response, $args) 
    {
        // Handle request data 
        $in = new \stdClass();
        $in->email = $this->scrub($request, 'signup-email');
        $in->password = $this->scrub($request, 'signup-password');
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Signup request from: ".$in->email);

        // Don't continue if we don't have the required input
        if($in->email === false || $in->password === false || $in->password == '') {
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'invalid_input', 
                'message' => 'generic/error',
            ], 400);
        }
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromEmail($in->email);

        // Don't continue if this user already exists
        if ($user->getId() != '') { 
            $logger->info("Signup rejected: ".$in->email." already has an account");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_exists', 
                'message' => 'signup/account-exists',
            ], 400);
        } 
        
        // Create new user
        $user->create($in->email, $in->password);

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->signup($user);
        $logger->info("Activation email sent to: ".$in->email);
        $logger->info("Signup: ".$in->email." is user ".$user->getId());

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
            ], 404);
        } 
        
        if($user->getStatus() === 'blocked') {
            $logger->info("Resend blocked: User ".$user->getId()." is blocked");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'resend/account-blocked',
            ], 400);
        }

        if($user->getStatus() === 'active') {
            $logger->info("Resend blocked: User ".$user->getId()." is already active");
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_active', 
                'message' => 'resend/account-active',
            ], 400);
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
        $user->setEmail($user->getPendingEmail());
        $user->unsetPendingEmail();
        $user->save();
        
        $logger->info("Confirmation: User ".$user->getId()." is now confirmed for address ".$user->getEmail());
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'confirm_complete', 
        ]);
    }

    /** Send patron email */
    public function sendPatronEmail($request, $response, $args)
    {
        // Handle request data 
        $handle = $this->scrub($request,'user');
        
        // Get a logger instance from the container
        $logger = $this->container->get('logger');

        // Load user
        $user = $this->container->get('User');
        $user->loadFromHandle($handle);

        // Is this a patron?
        if($user->getPatronTier() < 2) return $this->prepResponse($response, ['result' => 'error', 'reason' => 'not-a-patron']);

        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->patron($user);
        
        $logger->info("Sent patron email to: ".$user->getEmail()." (user ".$user->getId().")");

        return $this->prepResponse($response, ['result' => 'ok']);
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
            ], 404);
        }

        // Is the user blocked? 
        if($user->getStatus() === 'blocked') {
            $logger->info('Activation rejected: User '.$user->getId().' is blocked');
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'account/blocked'
            ], 400);
        }

        // Is there a token mismatch? 
        if($activation_data['token'] != $user->getActivationToken()) {
            $logger->info("Activation rejected: Token mismatch for user ".$user->getId());
            
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'token_mismatch', 
                'message' => 'activation/token-mismatch'
            ], 400);
        }

        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        // Activate user
        $user->setStatus('active');

        // Login user
        $user->setLogin();
        $user->save();
        
        // Log
        $logger->info("Activation: User ".$user->getId()." is now active");
        $logger->info("Login: User ".$user->getId())." auto-login upon activation";
        
        return $this->prepResponse($response, [
            'result' => 'ok',
            'reason' => 'signup_complete', 
            'message' => 'login/success',
            'token' => $TokenKit->create($user->getId()),
            'userid' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
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

    /** Load user account */
    public function adminLoad($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $admin = $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            // Get a logger instance from the container
            $logger = $this->container->get('logger');
            $logger->info("Failed to load user data: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                ]);
        }
        
        // Handle request data 
        $handle = filter_var($args['handle'], FILTER_SANITIZE_STRING);
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromHandle($handle);

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
                if(isset($data->patron)) $val->patron = $data->patron;
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

        $return = [
            'profile' => [
                'username' => $user->getUsername(), 
                'handle' => $user->getHandle(), 
                'status' => $user->getStatus(), 
                'created' => $user->getCreated(), 
                'migrated' => $user->getMigrated(), 
                'pictureSrc' => $avatarKit->getWebDir($user->getHandle(), 'user').'/'.$user->getPicture(), 
            ],
            'drafts' => $user->getDrafts(),
        ];
        $data = $user->getData();
        if(isset($data->badges)) $return['badges'] = $data->badges;
        if(isset($data->social)) $return['social'] = $data->social;
        if(isset($data->patron)) $return['patron'] = $data->patron;

        return $this->prepResponse($response, $return);
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

    /** Export user data */
    public function export($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container and load user data
        $user = $this->container->get('User');
        $user->loadFromId($id);

        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Exporting user data for: ".$user->getId()." (".$user->getEmail().")");
        
        $zip = $user->export();
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'archive' => $zip, 
        ]);
    } 

    /** Patron list */
    public function patronList($request, $response, $args) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT `id` FROM `users` WHERE `data` LIKE '%\"patron\":%'";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $tiers = $this->container['settings']['patrons']['tiers'];
        if(!$result) return false;
        else {
            $patrons = array();
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
                    $patron->since = $timestamp;
                    while(isset($patrons[$timestamp])) $timestamp++;
                    $patrons[$timestamp] = $patron;
                    $keys[] = $timestamp;
                }
            }
        } 

        // Newest patrons at the top
        rsort($keys);
        foreach($keys as $t) $sorted[] = $patrons[$t];
    
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'patrons' => $sorted, 
        ]);
    } 

}