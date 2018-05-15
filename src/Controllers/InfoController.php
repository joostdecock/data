<?php
/** Freesewing\Data\Controllers\CoreController class */
namespace Freesewing\Data\Controllers;

use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client as GuzzleClient;
use \Freesewing\Data\Tools\Utilities as Utilities;

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

    /** Pattern info as YAML for site config */
    public function patternsAsYaml($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/x-yaml")
            ->write(Yaml::dump($this->infoBundle()['patterns'], 5));
    }

    /** Version info as YAML for site config */
    public function versionsAsYaml($request, $response, $args) 
    {
        $versions = $this->infoBundle()['version'];
        $versions['data'] = $this->container['version']; 
        $versions['site'] = $request->getHeaderLine('Freesewing-Site-Version');
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/x-yaml")
            ->write(Yaml::dump($versions,5));
    }

    /** Namespaces info as YAML for site config */
    public function namespacesAsYaml($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/x-yaml")
            ->write(Yaml::dump($this->infoBundle()['namespaces'],5));
    }

    /** Mapping info as YAML for site config */
    public function mappingAsYaml($request, $response, $args) 
    {
        $mapping = $this->infoBundle()['mapping'];
        unset($mapping['measurementToTitle']);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/x-yaml")
            ->write(Yaml::dump($mapping,5));
    }

    /** Measurements info as YAML for site config */
    public function measurementsAsYaml($request, $response, $args) 
    {
        $mapping = $this->infoBundle()['mapping'];
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/x-yaml")
            ->write(Yaml::dump($mapping['measurementToTitle'],5));
    }


    /** Required measurements bundle (YAML) */
    public function dataConfig($request, $response, $args) 
    {
        $info = $this->infoBundle();

        $allMeasurements = [];
        $breastsMeasurements = [];
        $noBreastsMeasurements = [];
        $fbo = $this->container['settings']['forBreastsOnly'];
        $classnames = [];
        foreach($info['patterns'] as $handle => $pattern) {
            // Measurements
            foreach($pattern['measurements'] as $key => $default) {
                $measurements[$handle][] = $key;
                $allMeasurements[$key] = $key;
                if(!in_array($key, $fbo)) $noBreastsMeasurements[$key] = $key;
                else $breastsMeasurements[$key] = $key;
            }
            // Classnames - Core doesn't know what 'simon' is (for now)
            $classnames[$handle] = $pattern['info']['class'];
        } 

        $body = "<?php \n /* This file is auto-generated. Do not edit! */\n\n";
        $body .="function __requiredMeasurements()\n{\n    return ".var_export($measurements, 1).";\n}";
        $body .= "\n\n";
        $body .="function __allMeasurements()\n{\n    return ".var_export($allMeasurements, 1).";\n}";
        $body .= "\n\n";
        $body .="function __breastsMeasurements()\n{\n    return ".var_export($breastsMeasurements, 1).";\n}";
        $body .= "\n\n";
        $body .="function __noBreastsMeasurements()\n{\n    return ".var_export($noBreastsMeasurements, 1).";\n}";
        $body .= "\n\n";
        $body .="function __patternsToClassNames()\n{\n    return ".var_export($classnames, 1).";\n}";
        $body .= "\n\n";
        $body .="function __patterns()\n{\n    return ".var_export($info['patterns'], 1).";\n}";
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write($body);
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

    /** Locale bundle */
    private function asLocale($request, $response, $args) 
    {
        $info = $this->infoBundle();
        $patterns = $info['patterns'];
        $info['options'] = [];
        $info['optiongroups'] = [];
        unset($info['patterns']);
        foreach($patterns as $pattern) {
            foreach($pattern['options'] as $oname => $option) {
                $info['options'][$oname]['title'] = $option['title'];
                $info['options'][$oname]['description'][$pattern['info']['handle']] = $option['description'];
                $info['optiongroups'][$option['group']] = $option['group'];
                if($option['type'] === 'chooseOne') {
                    $info['options'][$oname]['options'][$pattern['info']['handle']] = $option['options'];
                }
            }
            foreach($pattern['measurements'] as $mname => $default) {
                $info['measurements'][$mname] = $info['mapping']['measurementToTitle'][$mname];
            }
            $pinfo = [
                'title' => $pattern['info']['name'],
                'description' => $pattern['info']['description'],
            ];
            $info['patterns'][$pattern['info']['handle']] = $pinfo;
        }
        foreach($info['mapping']['measurementToTitle'] as $handle => $title) {
            $info['measurements'][$handle] = $title;
        }
        foreach($info['options'] as $oname => $oinfo) {
            if(count($oinfo['description']) === 1) {
                $info['options'][$oname]['description'] = array_shift($oinfo['description']); 
            } else {
                arsort($oinfo['description']);
                $count = 0;
                foreach($oinfo['description'] as $phandle => $odesc) {
                    if($count == 0) {
                        $newDesc['_default'] = $odesc;  
                    } else {
                        if($odesc != $newDesc['_default']) {
                            $newDesc[$phandle] = $odesc;
                        }
                    } 
                    $count++;
                }
                if (count($newDesc) == 1) $newDesc = $newDesc['_default'];
                $info['options'][$oname]['description'] = $newDesc;
                unset($newDesc);
                ksort($info['options'][$oname]['description']);
            }
            if(count($oinfo['options']) === 1) {
                $info['options'][$oname]['options'] = array_shift($oinfo['options']); 
            }
        }

        unset($info['version']);
        unset($info['mapping']);
        unset($info['namespaces']);

        return $info; 
    }

    /** Locale bundle of patterns */
    public function patternsAsLocale($request, $response, $args) 
    {
        $info = $this->asLocale();
        ksort($info['patterns']); 
         
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($info['patterns'],5));
    }

    /** Locale bundle of measurements */
    public function measurementsAsLocale($request, $response, $args) 
    {
        $info = $this->asLocale();
        ksort($info['measurements']); 
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($info['measurements'],5));
    }

    /** Locale bundle of options */
    public function optionsAsLocale($request, $response, $args) 
    {
        $info = $this->asLocale();
        ksort($info['options']); 
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($info['options'],5));
    }

    /** Locale bundle of options */
    public function optiongroupsAsLocale($request, $response, $args) 
    {
        $info = $this->asLocale();
        ksort($info['optiongroups']); 
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($info['optiongroups'],5));
    }

    /** Info bundle */
    private function infoBundle() 
    {
        // Get data from info service
        $coreinfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info'));
        $info['version']['core'] = $coreinfo->version;

        // Iterate over patterns to get remaining info
        $patternlist = $coreinfo->patterns;
        $female = $this->container['settings']['app']['female_measurements'];
        $measurementTitles = $this->container['settings']['app']['female_measurements'];
        
        foreach ($patternlist as $namespace => $list) {
            foreach ($list as $handle => $title) {
                $patternInfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                $patternInfo->info->class = $handle;
                $info['patterns'][$patternInfo->info->handle] = $this->patternToArray($patternInfo);
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
        $info['mapping']['measurementToTitle'] =  $this->container['settings']['measurements'];

        // Sort measurements
        ksort($info['measurements']);
        
        return $info;
    }

    private function patternToArray($pattern)
    {
        unset($pattern->models);
        unset($pattern->pattern);

        foreach($pattern as $key => $val) {
            if($key == 'options') {
                foreach($val as $okey => $oval) {
                    $options[$okey] = $this->optionToArray($oval);
                    $ogroups[$oval->group][] = $okey;
                }
                $p['options'] = $options;
                $p['optiongroups'] = $ogroups;
                unset($options);
                unset($ogroups);
            }
            elseif($key == 'info') {
                $p[$key] = (array) $val;
                // Convert inMemoryOf to array
                if(isset($p[$key]['inMemoryOf'])) $p[$key]['inMemoryOf'] = (array) $p[$key]['inMemoryOf'];
            }
            else $p[$key] = (array) $val;
        }
        
        return $p;
    }

    private function optionToArray($option) 
    {
        if($option->type === 'chooseOne') {
            $option->options = (array) $option->options;
        }
        
        return (array) $option;

    }
    
    /** Status info */
    public function status($request, $response, $args) 
    {
        $memory = $this->asScrubbedArray(rtrim(shell_exec("free -m | grep Mem")));
        $status['system']['memory']['used'] = $memory[2];
        $status['system']['memory']['free'] = $memory[3];
        $swap = $this->asScrubbedArray(rtrim(shell_exec("free -m | grep Swap")));
        $status['system']['swap']['used'] = $swap[2];
        $status['system']['swap']['free'] = $swap[3];
        $stats = rtrim(shell_exec("mpstat 1 1 | tail -n 2 | head -n 1"));
        $stats = explode('  ',strrev($stats));
        $idle = strrev(array_shift($stats));
        $status['system']['cpu'] = 100 - $idle;

        $status['system']['uptime'] = rtrim(substr(shell_exec("uptime -p"), 3));
        $status['data']['users'] = $this->countUsers(); 
        $status['data']['drafts'] = $this->countDrafts(); 
        $status['data']['comments'] = $this->countComments(); 
        $status['data']['models'] = $this->countModels(); 

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    private static function asScrubbedArray($data, $separator = ' ')
    {
        $return = false;
        $array = explode($separator, $data);
        foreach ($array as $value) {
            if (rtrim($value) != '') {
                $return[] = rtrim($value);
            }
        }

        return $return;
    }

    private function countUsers()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'users' FROM `users` WHERE `status` = 'active'";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->users;
    }

    private function countDrafts()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'drafts' FROM `drafts`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->drafts;
    }
    
    private function countComments()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'comments' FROM `comments`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->comments;
    }
    
    private function countModels()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'models' FROM `models`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->models;
    }
}
