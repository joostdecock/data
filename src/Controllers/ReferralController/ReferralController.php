<?php
/** App\Controllers\ReferralController class */
namespace App\Controllers;

/**
 * Logs referrals
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class ReferralController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** Log referral */
    public function log($request, $response, $args) {
        // Handle request data 
        $data = $request->getParsedBody();

        $ref_data = [
            'host' => filter_var($data['host'], FILTER_SANITIZE_STRING),
            'path' => filter_var($data['path'], FILTER_SANITIZE_STRING),
            'url' => filter_var($data['url'], FILTER_SANITIZE_STRING),
        ];
        
        // Get a referral instance from the container
        $ref = $this->container->get('Referral');
        $ref->create($ref_data['host'],$ref_data['path'],$ref_data['url']);
    }        
}
