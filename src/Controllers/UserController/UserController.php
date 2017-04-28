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
            echo json_encode([
                'result' => 'error', 
                'reason' => 'account_exists', 
                'message' => 'signup/account-exists',
            ]);

            return $response->withHeader('Access-Control-Allow-Origin', '*');
        } 
        
        // Create new user
        $user->create($signup_data['email'], $signup_data['password']);
        $logger->info("Signup: ".$signup_data['email']." is user ".$user->getId());
        echo json_encode([
            'result' => 'ok', 
            'reason' => 'signup_complete', 
            'message' => 'signup/success',
        ]);

        $mailKit = $this->container->get('MailKit');
        $mailKit->signup($user);

        return $response->withHeader('Access-Control-Allow-Origin', '*');
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
            echo json_encode([
                'result' => 'error', 
                'reason' => 'no_such_account', 
                'message' => 'activation/no-such-account'
            ]);
            
            return $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Is the user blocked? 
        if($user->getStatus() === 'blocked') {
            $logger->info('Activation rejected: User '.$user->getId().' is blocked');
            echo json_encode([
                'result' => 'error', 
                'reason' => 'account_blocked', 
                'message' => 'account/blocked'
            ]);
            
            return $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Is there a token mismatch? 
        if($activation_data['token'] != $user->getActivationToken()) {
            $logger->info("Activation rejected: Token mismatch for user ".$user->getId());
            echo json_encode([
                'result' => 'error', 
                'reason' => 'token_mismatch', 
                'message' => 'activation/token-mismatch'
            ]);
            
            return $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Activate user
        $user->setStatus('active');
        $user->save();
        $logger->info("Activation: User ".$user->getId()." is now active");
        echo json_encode([
            'result' => 'ok', 
            'reason' => 'signup_complete', 
        ]);
        
        return $response->withHeader('Access-Control-Allow-Origin', '*');
    }
}
