<?php
/** Freesewing\Data\Controllers\ReferralController class */
namespace Freesewing\Data\Controllers;

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
    public function __construct(\Slim\Container $container) 
    {
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

    /** Log referral */
    public function log($request, $response, $args) 
    {
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


    /** Group referrals - typically called from cron */
    public function group($request, $response, $args) 
    {
        $matched = array();
        $unmatched = array();
        // Load all ungrouped referrals
        $db = $this->container->get('db');
        $sql = "SELECT `id` FROM `referrals` WHERE `site` = '' OR `site` IS NULL";

        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;
        
        if(!$result) return false;
        else {
            foreach($result as $key => $referral) {
                // Get a referral instance from the container
                $ref = clone $this->container->get('Referral');
                $ref->load($referral->id);
                
                if(!$ref->group()) {
                    $unmatched[] = $ref->getUrl();
                } else {
                    $ref->save();
                    $matched[] = $ref->getUrl();
                }
            }
        } 
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'unmatched' => $unmatched,
            'matched' => $matched,
        ]);
    }        
}
