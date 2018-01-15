<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Symfony\Component\Yaml\Yaml;

class InfoControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testAsYaml()
    {
        $response = $this->app->call('GET','/info/yaml');
        $yamlr = new Yaml();
        $yaml = $yamlr::parse((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertTrue(isset($yaml['version']['core']));
        $this->assertTrue(isset($yaml['patterns']['BrianBodyBlock']['options']['chestEase']['type']));
        $this->assertTrue(isset($yaml['namespaces']['Core']));
        $this->assertTrue(isset($yaml['mapping']['handleToPatternTitle']['brian']));
        $this->assertTrue(isset($yaml['mapping']['handleToPattern']['brian']));
        $this->assertTrue(isset($yaml['mapping']['patternToHandle']['BrianBodyBlock']));
        $this->assertTrue(isset($yaml['mapping']['measurementToTitle']['acrossBack']));
        $this->assertTrue(isset($yaml['measurements']['acrossBack']));
    }

    public function testAsJson()
    {
        $response = $this->app->call('GET','/info/json');
        $json = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertTrue(isset($json['version']['core']));
        $this->assertTrue(isset($json['patterns']['BrianBodyBlock']['options']['chestEase']['type']));
        $this->assertTrue(isset($json['namespaces']['Core']));
        $this->assertTrue(isset($json['mapping']['handleToPatternTitle']['brian']));
        $this->assertTrue(isset($json['mapping']['handleToPattern']['brian']));
        $this->assertTrue(isset($json['mapping']['patternToHandle']['BrianBodyBlock']));
        $this->assertTrue(isset($json['mapping']['measurementToTitle']['acrossBack']));
        $this->assertTrue(isset($json['measurements']['acrossBack']));
    }

    public function testStatus()
    {
        $response = $this->app->call('GET','/status');
        $json = json_decode((string)$response->getBody(), true);

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertTrue(isset($json['system']['memory']['free']));
        $this->assertTrue(isset($json['system']['cpu']));
        $this->assertTrue(isset($json['system']['uptime']));
        $this->assertTrue(isset($json['data']['users']));
        $this->assertTrue(isset($json['data']['drafts']));
        $this->assertTrue(isset($json['data']['comments']));
        $this->assertTrue(isset($json['data']['models']));
        $this->assertTrue(isset($json['referrals']));
    }
}
