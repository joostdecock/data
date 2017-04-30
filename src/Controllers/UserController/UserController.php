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
   
    /** User password reset */
    public function reset($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();
        $reset_data = [
            'password' => filter_var($data['reset-password'], FILTER_SANITIZE_EMAIL),
            'handle' => filter_var($data['reset-handle'], FILTER_SANITIZE_EMAIL),
            'token' => filter_var($data['reset-token'], FILTER_SANITIZE_EMAIL),
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
                'message' => 'login/login-failed',
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
                'message' => 'login/login-failed',
            ]);
        }

        $logger->info("Login: User ".$user->getId());
        // Get the token kit from the container
        $TokenKit = $this->container->get('TokenKit');
        
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'password_correct', 
            'message' => 'login/success',
            'token' => $TokenKit->create($user->getId()),
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
        $mailKit->signup($user);
        
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
            'token' => $TokenKit->create($user->getId()),
        ]);
    }
}
