<?php
/** App\Data\Draft class */
namespace App\Data;

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

    /** @var string $data Other app data stored as JSON */
    private $data;

    /** @var string $created The time the draft was created */
    private $created;

    /** @var bool $shared Whether the draft is shared */
    private $shared;

    /** @var string $notes the draft notes */
    private $notes;


    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }

    public function getId() 
    {
        return $this->id;
    } 

    public function getUser() 
    {
        return $this->user;
    } 

    private function setUser($user) 
    {
        $this->user = $user;
        return true;
    } 

    public function getModel() 
    {
        return $this->model;
    } 

    private function setModel($model) 
    {
        $this->model = $model;
        return true;
    } 

    public function getPattern() 
    {
        return $this->pattern;
    } 

    private function setPattern($pattern) 
    {
        $this->pattern = $pattern;
        return true;
    } 

    private function setHandle($handle) 
    {
        $this->handle = $handle;
        return true;
    } 

    public function getHandle() 
    {
        return $this->handle;
    } 

    public function setName($name) 
    {
        $this->name = $name;
        return true;
    } 

    public function getName() 
    {
        return $this->name;
    } 

    public function setSvg($svg) 
    {
        $this->svg = $svg;
        return true;
    } 

    public function getSvg() 
    {
        return $this->svg;
    } 

    public function setCompared($compared) 
    {
        $this->compared = $compared;
        return true;
    } 

    public function getCompared() 
    {
        return $this->compared;
    } 

    public function setNotes($notes) 
    {
        $this->notes = $notes;
        return true;
    } 

    public function getNotes() 
    {
        return $this->notes;
    } 

    public function setShared($shared) 
    {
        $this->shared = $shared;
        return true;
    } 

    public function getShared() 
    {
        return $this->shared;
    } 

    public function getCreated() 
    {
        return $this->created;
    } 

    public function getData() 
    {
        return $this->data;
    } 

    public function setData($data) 
    {
        $this->data = $data;
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

        if(!$result) return false;
        else foreach($result as $key => $val) {
            if($key == 'data' && $val != '') $this->$key = json_decode($val);
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
     * @param array $in The pattern options
     * @param Model $model The model object     
     * @param User $user The user object     
     * 
     * @return int The id of the newly created model
     */
    public function create($in, $user, $model) 
    {
        // Passing model measurements to core
        foreach($model->getData()->measurements as $key => $val) {
            if(strtolower($model->getUnits()) != strtolower($in['userUnits'])) {
                // Measurements need to be converted
                if(strtolower($model->getUnits() == 'imperial')) $val = $val * 2.54;
                else $val = $val / 2.54;
            }
            $in[$key] = $val;
            $data['measurements'][$key] = $val;
        }

        // Get the HandleKit to create the handle
        $handleKit = $this->container->get('HandleKit');
        $this->setHandle($handleKit->create('draft'));
        
        // Pass units, handle and model name to core
        $in['unitsIn'] = strtolower($in['userUnits']);
        $in['unitsOut'] = strtolower($in['userUnits']);
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
        
        // Prep data
        $data['version'] = $json->version;
        $data['options'] = $in;
        $data['units'] = strtolower($in['userUnits']);
        $data['coreUrl'] = $this->container['settings']['app']['core_api']."/index.php?".http_build_query($in);
        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Getting compare from core
        $in['service'] = 'compare';
        $in['theme'] = 'Compare'; // Overriding theme
        $this->setCompared($this->getDraft($in));
        
        // Set basic info    
        $this->setUser($user->getId());
        $this->setModel($model->getId());
        $this->setPattern($in['pattern']);

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `drafts`(
            `user`,
            `pattern`,
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
            ".$db->quote($this->getModel()).",
            ".$db->quote($this->getHandle()).",
            ".$db->quote($data).",
            ".$db->quote($this->getSvg()).",
            ".$db->quote($this->getCompared()).",
            ".$db->quote($this->container['settings']['app']['motd']).",
            NOW()
            );";
        $db->exec($sql);

        // Retrieve draft ID
        $id = $db->lastInsertId();
        
        // Set draft name to 'pattern for model'
        $sql = "UPDATE `drafts` SET `name` = ".$db->quote('Draft '.$this->getHandle())." WHERE `drafts`.`id` = '$id';";
        $db->exec($sql);

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
            $data['measurements'][$key] = $val;
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
        
        // Prep data
        $data['version'] = $json->version;
        $data['options'] = $in;
        $data['units'] = $model->getUnits();
        $data['coreUrl'] = $this->container['settings']['app']['core_api']."/index.php?".http_build_query($in);
        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Getting compare from core
        $in['service'] = 'compare';
        $in['theme'] = 'Compare';
        $this->setCompared($this->getDraft($in));
        
        // Update info    
        $this->setData($data);

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
    }

    private function getDraft($args)
    {
        $url = $this->container['settings']['app']['core_api']."/index.php";
        
        $config = [
            'connect_timeout' => 5,
            'timeout' => 35,
            'query' => $args,
        ];

        $guzzle = new GuzzleClient($config);
        $response = $guzzle->request('GET', $url);
        
        return $response->getBody();
    }

    /** Saves the draft to the database */
    public function save() 
    {
        // Don't double encode
        $data = $this->getData();
        if(!is_string($data)) $data = json_encode($this->getData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $db = $this->container->get('db');
        $sql = "UPDATE `drafts` set 
            `name` = ".$db->quote($this->getName()).",
            `svg` = ".$db->quote($this->getSvg()).",
            `compared` = ".$db->quote($this->getCompared()).",
            `data` = ".$db->quote($data).",
            `shared`   = ".$db->quote($this->getShared()).",
            `notes`     = ".$db->quote($this->getNotes())."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";
        
        return $db->exec($sql);
    }
    
    /**
     * Loads all drafts for a model
     *
     * @param int $id
     *
     * @return array|false An array of drafts or false
     */
    public function getDrafts() 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `drafts` WHERE `model` =".$db->quote($this->getId());
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                $drafts[$val->id] = $val;
            }
        } 
        return $drafts;
    }

    /** Remove a draft */
    public function remove($user) 
    {
        // Remove from storage
        shell_exec("rm -rf ".$this->container['settings']['storage']['static_path']."/users/".substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$this->getHandle());
        
        // Remove from database
        $db = $this->container->get('db');
        $sql = "DELETE from `drafts` WHERE `id` = ".$db->quote($this->getId()).";";

        return $db->exec($sql);
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
            else $pf = ucfirst(array_shift(explode('.',$format))); // Turn a4.pdf into A4

            // Get Postscript file
            $ps = $this->getExportPath($user, 'ps'); // Postscript file
            $this->svgToPs($user); 

            // Tile postscript to required format
            $cmd = "/usr/local/bin/tile -a -m$pf -s1 -t\"$patternName\" -h\"$patternHandle\" $ps > ".$this->getExportPath($user, $format.'.ps');
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
