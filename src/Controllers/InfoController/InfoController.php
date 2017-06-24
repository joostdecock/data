<?php
/** App\Controllers\CoreController class */
namespace App\Controllers;

use Symfony\Component\Yaml\Yaml;

/**
 * Pulls data from freesewing core info service and bundles it 
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class InfoController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** Info bundle as YAML */
    public function asYaml($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($this->infoBundle(),5));
    }

    /** Info bundle as JSON */
    public function asJson($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(json_encode($this->infoBundle(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /** Info bundle */
    private function infoBundle() 
    {
        // Get patterns from info service
        $patternlist = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info'))->patterns;

        // Iterate over patterns to get remaining info
        $female = $this->container['settings']['app']['female_measurements'];
        //$male = $this->container['settings']['app']['female_measurements'];
        
        foreach ($patternlist as $namespace => $list) {
            foreach ($list as $handle => $title) {
                $patternInfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                $info['patterns'][$handle] = $this->patternToArray($patternInfo);
                $info['namespaces'][$namespace][] = $patternInfo->info->handle;
                $info['mapping']['handleToPatternTitle'][$patternInfo->info->handle] = $patternInfo->info->name;
                $info['mapping']['handleToPattern'][$patternInfo->info->handle] = $handle;
                $info['mapping']['patternToHandle'][$handle] = $patternInfo->info->handle;
                $info['mapping']['handleToNamespace'][$patternInfo->info->handle] = $namespace;
                foreach ($patternInfo->measurements as $name => $default) {
                    if(in_array($name, $female)) $info['measurements'][$name] = 'female';
                    // else if (in_array($name, $male)) $info['measurements'][$name] = 'male';
                    else  $info['measurements'][$name] = 'all';
                }
            }
        }

        return $info;
    }

    private function patternToArray($pattern)
    {
        unset($pattern->models);
        unset($pattern->inMemoryOf);
        unset($pattern->pattern);
        foreach($pattern as $key => $val) {
            if($key == 'options') {
                foreach($val as $okey => $oval) {
                    $options[$okey] = (array) $oval;
                    $ogroups[$oval->group][] = $okey;
                }
                $p['options'] = $options;
                $p['optiongroups'] = $ogroups;
                unset($options);
                unset($ogroups);
            }
            else $p[$key] = (array) $val;
        }
        
        return $p;
    }
}
