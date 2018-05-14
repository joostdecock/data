<?php
/** Freesewing\Data\Objects\Draft class */
namespace Freesewing\Data\Objects;

use GuzzleHttp\Client as GuzzleClient;

/**
 * The model class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Draft
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the draft */
    private $id;

    /** @var int $user ID of the user owning the draft */
    private $user;

    /** @var string $pattern Pattern of which this is a draft */
    private $pattern;

    /** @var int $model ID of the model for which this was drafted */
    private $model;

    /** @var string $name Name of the draft */
    private $name;

    /** @var string $handle Unique handle of the draft */
    private $handle;

    /** @var string $svg The drafted SVG */
    private $svg;

    /** @var string $compared The compared SVG */
    private $compared;

    /** @var string $created The time the draft was created */
    private $created;

    /** @var bool $shared Whether the draft is shared */
    private $shared;

    /** @var string $notes the draft notes */
    private $notes;

    /** @var JsonStore $data Other app data stored as JSON */
    public $data;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
        $this->data = clone $this->container->get('JsonStore');
    }

    // Getters
    public function getCompared() 
    {
        return $this->compared;
    } 

    public function getCoreUrl()
    {
        return $this->data->getNode('coreUrl');
    }

    public function getGist()
    {
        return $this->data->getNode('gist');
    }

    public function getCreated() 
    {
        return $this->created;
    } 

    public function getData() 
    {
        return $this->data;
    } 

    public function getDataAsJson() 
    {
        return (string) $this->data;
    } 

    public function getHandle() 
    {
        return $this->handle;
    } 

    public function getId() 
    {
        return $this->id;
    } 

    public function getMeasurement($key)
    {
        return $this->data->getNode("measurements.$key");
    }

    public function getMeasurements()
    {
        return $this->data->getNode('measurements');
    }

    public function getModel() 
    {
        return $this->model;
    } 

    public function getName() 
    {
        return $this->name;
    } 

    public function getNotes() 
    {
        return $this->notes;
    } 

    public function getOption($option)
    {
        return $this->data->getNode("options.$option");
    }

    public function getOptions()
    {
        return $this->data->getNode('options');
    }

    public function getPattern() 
    {
        return $this->pattern;
    } 

    public function getShared() 
    {
        return $this->shared;
    } 

    public function getSvg() 
    {
        return $this->svg;
    } 

    public function getUnits()
    {
        return $this->data->getNode('units');
    }

    public function getUser() 
    {
        return $this->user;
    } 

    public function getVersion()
    {
        return $this->data->getNode('version');
    }

    // Setters
    public function setUser($user) 
    {
        $this->user = $user;
        return true;
    } 

    public function setModel($model) 
    {
        $this->model = $model;
        return true;
    } 

    public function setPattern($pattern) 
    {
        $this->pattern = $pattern;
        return true;
    } 

    public function setHandle($handle) 
    {
        $this->handle = $handle;
        return true;
    } 

    public function setName($name) 
    {
        $this->name = $name;
        return true;
    } 

    public function setSvg($svg) 
    {
        $this->svg = $svg;
        return true;
    } 

    public function setCompared($compared) 
    {
        $this->compared = $compared;
        return true;
    } 

    public function setNotes($notes) 
    {
        $this->notes = $notes;
        return true;
    } 

    public function setShared($shared) 
    {
        $this->shared = $shared;
        return true;
    } 

    public function setData(\Freesewing\Data\Objects\JsonStore $data) 
    {
        $this->data = ($data);
    } 

    public function setMeasurement($key, $val)
    {
        $this->data->setNode("measurements.$key", $val);
    }

    public function setCoreUrl($url)
    {
        $this->data->setNode('coreUrl', $url);
    }

    public function setGist($gist)
    {
        $this->data->setNode('gist', $gist);
    }

    public function setUnits($units)
    {
        $this->data->setNode('units', strtolower($units));
    }

    public function setVersion($version)
    {
        $this->data->setNode('version', $version);
    }

    public function setOption($key, $val)
    {
        $this->data->setNode("options.$key", $val);
    }
    public function setOptions($options)
    {
        $this->data->setNode('options', $options);
    }

    /**
     * Loads a draft based on a unique identifier
     *
     * @param string $key   The unique column identifying the draft. 
     *                      One of id/handle.
     * @param string $value The value to look for in the key column
     */
    private function load($value, $key='id') 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `drafts` WHERE `$key` =".$db->quote($value);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;

        if(!$result) return false;
        else foreach($result as $key => $val) {
            if($key == 'data' && $val != '') $this->data->import($val);
            else $this->$key = $val;
        }
    }
   
    /**
     * Loads a draft based on their id
     *
     * @param int $id
     */
    public function loadFromId($id) 
    {
        return $this->load($id, 'id');
    }
   
    /**
     * Loads a draft based on their handle
     *
     * @param string $handle
     */
    public function loadFromHandle($handle) 
    {
        return $this->load($handle, 'handle');
    }
   
    /**
     * Creates a new draft and stores it in the database
     *
     * @param array $gist The gist submitted from the frontend
     * @param User $user The user object     
     * @param Model $model The model object     
     * 
     * @return int The id of the newly created draft
     */
    public function create($gist, $user, $model) 
    {
        $data = [];
        // Passing model measurements to core
        $measurements = $gist['model']['measurements'];
        foreach($gist['model']['measurements'] as $key => $val) {
            if(strtolower($gist['model']['units']) != strtolower($user->getUnits())) {
                // Measurements need to be converted to user's units
                if(strtolower($gist['model']['units'] == 'imperial')) $val = $val * 2.54;
                else $val = $val / 2.54;
            }
            $data[$key] = $val;
            $this->setMeasurement($key, $val);
        }

        // Passing pattern options to core
        $measurements = $gist['model']['measurements'];
        foreach($gist['patternOptions'] as $key => $val) {
            $data[$key] = $val;
        }

        // Get the HandleKit to create the handle
        $handleKit = $this->container->get('HandleKit');
        $this->setHandle($handleKit->create('draft'));
        
        // Pass units, handle and model name to core
        $u = strtolower($gist['units']);
        $data['userUnits'] = $u;
        $data['unitsIn'] = $u;
        $data['unitsOut'] = $u;
        $data['draftHandle'] = $this->getHandle();
        $data['modelName'] = $gist['model']['name'];

        // Switch theme to its JSON variant
        $originalTheme = $gist['draftOptions']['theme'];
        $data['theme'] = $originalTheme.'Json';
        
        // Set SA for core
        $data['sa'] = $gist['draftOptions']['sa']['value'];

        // Set scope for core, if needed
        if($gist['draftOptions']['scope']['type'] === 'parts') {
            $data['parts'] = implode(',', array_keys($gist['draftOptions']['scope']['included']));
        }

        // Set pattern to class name
        $data['pattern'] = $gist['patternClass'];
        $data['inpattern'] = $gist['pattern'];

        // Getting draft from core
        $data['service'] = 'draft';

        $json = json_decode($this->getDraft($data));
        $this->setSvg($json->svg);
        
        // Prep data
        $this->setVersion($json->version);
        $this->setGist($gist);
        $this->setUnits($user->getUnits());
        $data['theme'] = $originalTheme;
        $this->setCoreUrl($this->container['settings']['app']['core_api']."/index.php?".http_build_query($data));
        
        // Getting compare from core
        $data['service'] = 'compare';
        $data['theme'] = 'Compare'; // Overriding theme
        $this->setCompared($this->getDraft($data));
        
        // Set basic info    
        $this->setUser($user->getId());
        $this->setModel($model->getId());
        $this->setPattern($gist['pattern']);

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `drafts`(
            `user`,
            `pattern`,
            `name`,
            `model`,
            `handle`,
            `data`,
            `svg`,
            `compared`,
            `notes`,
            `created`
             ) VALUES (
            ".$db->quote($this->getUser()).",
            ".$db->quote($this->getPattern()).",
            ".$db->quote('Draft '.$this->getHandle()).",
            ".$db->quote($this->getModel()).",
            ".$db->quote($this->getHandle()).",
            ".$db->quote($this->getDataAsJson()).",
            ".$db->quote($this->getSvg()).",
            ".$db->quote($this->getCompared()).",
            ".$db->quote($this->container['settings']['app']['motd']).",
            '".date('Y-m-d H:i:s')."'
            );";
        $db->exec($sql);
        // Retrieve draft ID
        $id = $db->lastInsertId();
        
        // Set draft name to 'pattern for model'
        $sql = "UPDATE `drafts` SET `name` = ".$db->quote('Draft '.$this->getHandle())." WHERE `drafts`.`id` = '$id';";
        $db->exec($sql);
        $db = null;

        // Update instance from database
        $this->loadFromId($id);

        // Store on disk
        $dir = $this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle();
        mkdir($dir, 0755, true);
        $handle = fopen($dir.'/'.$this->getHandle().'.svg', 'w');
        fwrite($handle, $this->getSvg());
        fclose($handle);
        $handle = fopen($dir.'/'.$this->getHandle().'.compared.svg', 'w');
        fwrite($handle, $this->getCompared());
        fclose($handle);

        return $id;
    }

    /**
     * Recreates a draft and stores it in the database
     *
     * @param array $in The pattern options
     * @param Model $model The model object     
     * @param User $user The user object     
     * 
     * @return int The id of the newly created model
     */
    public function recreate($in, $user, $model) 
    {
        // Passing model measurements to core
        foreach($model->getData()->measurements as $key => $val) {
            if(strtolower($model->getUnits()) != strtolower($in['userUnits'])) {
                // Measurements need to be converted
                if(strtolower($model->getUnits() == 'imperial')) $val = $val * 2.54;
                else $val = $val / 2.54;
            }
            $in[$key] = $val;
            $this->setMeasurement($key, $val);
        }
            
        // Pass units and handle to core
        $in['unitsIn'] = strtolower($in['userUnits']);
        $in['unitsOut'] = strtolower($in['userUnits']);
        $in['reference'] = $this->getHandle();
        $in['draftHandle'] = $this->getHandle();
        $in['modelName'] = $model->getName();
        
        // Switch theme to its JSON variant
        $originalTheme = $in['theme'];
        $in['theme'] = $in['theme'].'Json';
        
        // Getting draft from core
        $in['service'] = 'draft';
        $json = json_decode($this->getDraft($in));
        $this->setSvg($json->svg);
        
        // Restoring original theme
        $in['theme'] = $originalTheme;
        
        // Storing data
        $this->setVersion($json->version);
        $this->setOptions((object)$in);
        $this->setUnits($model->getUnits());
        $this->setCoreUrl($this->container['settings']['app']['core_api']."/index.php?".http_build_query($in));
        
        // Getting compare from core
        $in['service'] = 'compare';
        $in['theme'] = 'Compare';
        $this->setCompared($this->getDraft($in));
        
        // Save draft to database
        $this->save();

        // Store on disk
        $dir = $this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle();
        $handle = fopen($dir.'/'.$this->getHandle().'.svg', 'w');
        fwrite($handle, $this->getSvg());
        fclose($handle);
        $handle = fopen($dir.'/'.$this->getHandle().'.compared.svg', 'w');
        fwrite($handle, $this->getCompared());
        fclose($handle);

        return $this->getId();
    }

    /**
     * Upgrades a draft in-place stores it in the database
     *
     */
    public function upgrade($user) 
    {
        // Passing model measurements to core
        foreach($this->getData()->measurements as $key => $val) {
            $in[$key] = $val;
            $this->setMeasurement($key, $val);
        }
        foreach($this->getData()->options as $key => $val) {
            $in[$key] = $val;
        }
        
        // Switch theme to its JSON variant
        $originalTheme = $this->getData()->options->theme;
        $in['theme'] = $originalTheme.'Json';

        // Getting draft from core
        $in['service'] = 'draft';
        $json = json_decode($this->getDraft($in));
        $this->setSvg($json->svg);
        
        // Restoring original theme
        $in['theme'] = $originalTheme;
        
        // Storing data
        $this->setVersion($json->version);
        $this->setOptions((object)$in);
        $this->setUnits($this->getData()->model->units);
        $this->setCoreUrl($this->container['settings']['app']['core_api']."/index.php?".http_build_query($in));
        
        // Getting compare from core
        $in['service'] = 'compare';
        $in['theme'] = 'Compare';
        $this->setCompared($this->getDraft($in));
        
        // Save draft to database
        $this->save();

        // Store on disk
        $dir = $this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle();
        $handle = fopen($dir.'/'.$this->getHandle().'.svg', 'w');
        fwrite($handle, $this->getSvg());
        fclose($handle);
        $handle = fopen($dir.'/'.$this->getHandle().'.compared.svg', 'w');
        fwrite($handle, $this->getCompared());
        fclose($handle);

        return $this->getId();
    }

    private function getDraft($args)
    {
        $url = $this->container['settings']['app']['core_api']."/index.php?".http_build_query($args);
        $guzzle = $this->container->get('GuzzleClient');
        $response = $guzzle->request('GET', $url);

        return $response->getBody();
    }

    /** Saves the draft to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `drafts` set 
            `name` = ".$db->quote($this->getName()).",
            `svg` = ".$db->quote($this->getSvg()).",
            `compared` = ".$db->quote($this->getCompared()).",
            `data` = ".$db->quote($this->getDataAsJson()).",
            `shared`   = ".$db->quote($this->getShared()).",
            `notes`     = ".$db->quote($this->getNotes())."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";
        
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
    
    /** Remove a draft */
    public function remove($user) 
    {
        // Remove from storage
        shell_exec("rm -rf ".$this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle());
        
        // Remove from database
        $db = $this->container->get('db');
        $sql = "DELETE from `drafts` WHERE `id` = ".$db->quote($this->getId()).";";
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }

    private function getExportPath($user, $format)
    {
        return $this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle().'/'.$this->getHandle().'.'.$format;

    }

    /** Exports SVG as a format of choice 
     *
     * Note that since users can re-draft, we can't be sure the exiting PDF/PS files
     * are up-to-date with the SVG. So we always regenerate them, even if they exist
     * */
    public function export($user, $format, $patternName, $patternHandle) 
    {
        // SVG is already on disk
        if($format == 'svg') return $this->getExportPath($user, 'svg');

        // Full-size PDF is just a simple Inkscape export
        if($format == 'pdf') $cmd = "/usr/bin/inkscape --export-pdf=".$this->getExportPath($user, 'pdf').' '.$this->getExportPath($user, 'svg');
        else {
            // Other formats require more work
            if($format == 'letter.pdf') $pf = 'Let';
            elseif($format == 'tabloid.pdf') $pf = 'Tab';
            else {
                // Turn a4.pdf into A4
                $array = explode('.',$format);
                $first = array_shift($array);
                $pf = ucfirst($first);
            }
            // Get Postscript file
            $ps = $this->getExportPath($user, 'ps'); // Postscript file
            $this->svgToPs($user); 

            // Tile postscript to required format
            $tile = $this->container['settings']['tile'];
            $cmd = "$tile -a -m$pf -s1 -t\"$patternName\" -h\"$patternHandle\" $ps > ".$this->getExportPath($user, $format.'.ps');
            // Convert to PDF
            $cmd .= " ; /usr/bin/ps2pdf14 ".$this->getExportPath($user, $format.'.ps').' '.$this->getExportPath($user, $format);
        }

        shell_exec($cmd);
        
        return $this->getExportPath($user, $format);
    }

    private function svgToPs($user) 
    {
        $cmd = "/usr/bin/inkscape --export-ps=".$this->getExportPath($user, 'ps').' '.$this->getExportPath($user, 'svg');
        return shell_exec($cmd);
    } 

}
