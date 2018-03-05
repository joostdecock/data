<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\User;

class ReferralControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testReferral()
    {
        $data = [
            'host' => 'freesewing.org',
            'path' => '/test',
            'url' => 'https://freesewing.org/test',
        ];
        $response = $this->app->call('POST','/referral', $data);
        
        $this->assertEquals($response->getStatusCode(), 200);
    }
    
    public function testReferralUnknown()
    {
        $data = [
            'host' => '--.org',
            'path' => '/test',
            'url' => 'https://--.org/test',
        ];
        $response = $this->app->call('POST','/referral', $data);
        $this->assertEquals($response->getStatusCode(), 200);
    }
    
}
