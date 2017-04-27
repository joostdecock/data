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

        // Does this user already exist?
        $user->loadFromEmail($signup_data['email']);

        if ($user->getId() != '') { // Yes, user is already signed-up 
            $logger->info("Signup rejected: ".$signup_data['email']." already has an account");
            // FIXME: Handle return better
            echo json_encode([
                'result' => 'error', 
                'reason' => 'account_exists', 
                'message' => 'We already have a user account with address '.$signup_data['email']
            ]);
        } else { // No, creating new user
            $user->create($signup_data['email'], $signup_data['password']);
            $logger->info("Signup: ".$signup_data['email']." is user ".$user->getId());
            echo json_encode([
                'result' => 'sucess', 
                'reason' => 'signup_complete', 
                'message' => 'Thank you for signing up. Please check your '.$signup_data['email'].' mailbox for the activation email',
            ]);
        }
        
        return $response;
    }
}
