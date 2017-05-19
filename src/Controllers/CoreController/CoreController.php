<?php
/** App\Controllers\CoreController class */
namespace App\Controllers;

/**
 * Pulls data from freesewing core and bundles is as JSON
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class CoreController 
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
   
    /** Pattern handles */
    public function handles($request, $response, $args) 
    {
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Fetching patterns handles from core");

        $handles = [];

        foreach ($this->getPatterns() as $namespace => $list) {
            foreach ($list as $handle => $data) {
                $pattern = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                $handles[$pattern->info->handle] = ['namespace' => $namespace, 'pattern' => $handle];
            }
        }

        return $this->prepResponse($response, $handles);
    }

    /** Patterns */
    public function patterns($request, $response, $args) 
    {
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Fetching patterns from core");

        $patterns = [];

        foreach ($this->getPatterns() as $namespace => $list) {
            $ns = [];
            foreach ($list as $handle => $title) {
                //$pattern = [];
                $pattern = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                //unset($pattern['measurements']);
                foreach ($pattern->measurements as $name => $default) {
                    $pattern->measurements->$name = strtolower($name);
                }
                unset($pattern->models);
                //$pattern['info'] = $patternInfo;
                $ns[$handle] = $pattern;
            }
            $patterns[$namespace] = $ns;
        }

        return $this->prepResponse($response, $patterns);
    }

    /** Measurements */
    public function measurements($request, $response, $args) 
    {
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        $logger->info("Fetching measurements");

        return $this->prepResponse($response, $this->getMeasurements());
    }

    private function getPatterns()
    {
        return json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info'))->patterns;
    }

    private function getMeasurements()
    {
        $measurements = [];
        $female = $this->container['settings']['app']['female_measurements'];
        //$male = $this->container['settings']['app']['female_measurements'];
        
        foreach ($this->getPatterns() as $namespace => $list) {
            foreach ($list as $handle => $title) {
                $patternInfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                foreach ($patternInfo->measurements as $name => $default) {
                    if(in_array($name, $female)) $measurements[$name] = 'female';
                    // else if (in_array($name, $male)) $measurements[$name] = 'male';
                    else  $measurements[$name] = 'all';
                }
            }
        }
        
        return $measurements;
    }

}
