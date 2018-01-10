<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;

class UserControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testSignup()
    {
        $data = [
            'signup-email' => time().'.testSignup@freesewing.org',
            'signup-password' => 'boobies'
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->saveFixture('signup',$body);
        $this->assertEquals($body,$this->loadFixture('signup'));
    }

    public function testSignupExistingAddress()
    {
        $data = [
            'signup-email' => time().'.testSignupExisting@freesewing.org',
            'signup-password' => 'boobies'
        ];

        $response = $this->app->call('POST','/signup', $data);
        unset($response);
        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();
        
        $this->assertEquals($response->getStatusCode(), 409);
        $this->saveFixture('signup.existing',$body);
        $this->assertEquals($body,$this->loadFixture('signup.existing'));
    }

    private function loadFixture($fixture)
    {
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/UserController.$fixture.data";
        return file_get_contents($file);
    }

    private function saveFixture($fixture, $data)
    {
        return true;
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/UserController.$fixture.data";
        $f = fopen($file,'w');
        fwrite($f,$data);
        fclose($f);
    }
}
