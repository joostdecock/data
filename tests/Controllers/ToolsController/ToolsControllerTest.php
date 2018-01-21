<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\User;

class ToolsControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** Helper to create an authenticated session */
    private function getSession($rand='')
    {
        $session = new \stdClass();

        $session->user = new User($this->app->getContainer());
        
        $email = $rand.time().debug_backtrace()[1]['function'].'.UserController@freesewing.org';
        $session->user->create($email, 'bananas');
        $session->user->setStatus('active');
        $session->user->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];

        $response = $this->app->call('POST','/login', $data);
        $json = json_decode((string)$response->getBody());

        $session->token = $json->token;
        // Refresh user data
        $session->user->loadFromId($session->user->getId());

        return $session;
    }

    /**
     * @param string $format The format to output
     *
     * @dataProvider providerTile
     */
    public function testTile($format)
    {
        $session = $this->getSession($format);
        $svg = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(__DIR__.'/../../fixtures/brian.svg'));
        $data = [
            'format' => $format,
            'svg' => $svg
        ];
        $response = $this->app->call('POST','/tools/tile', $data, $session->token);
        
        $json = json_decode((string)$response->getBody());
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->link);
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(file_exists($path));
    }

    public function providerTile()
    {
        return [
            ['pdf'],
            ['ps'],
            ['A4'],
        ];
    }

    public function testTileInvalidSvg()
    {
        $session = $this->getSession();
        $data = [
            'format' => 'pdf',
            'svg' => 'invalid'
        ];
        $response = $this->app->call('POST','/tools/tile', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'invalid_svg');
    }

    public function testTileInvalidFormat()
    {
        $session = $this->getSession();
        $svg = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(__DIR__.'/../../fixtures/brian.svg'));
        $data = [
            'format' => 'invalid',
            'svg' => $svg
        ];
        $response = $this->app->call('POST','/tools/tile', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'invalid_format');
    }
}
