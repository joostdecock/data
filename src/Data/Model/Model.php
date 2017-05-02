<?php
/** App\Data\Model class */
namespace App\Data;

/**
 * The model class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Model
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the model */
    private $id;

    /** @var int $user ID of the user owning the model */
    private $user;

    /** @var string $name Name of the model */
    private $name;

    /** @var string $handle Unique handle of the model */
    private $handle;

    /** @var string $body The body type of the model. One of female/male/other 
     * Note: This is not about gender, but about curves
     */
    private $body;

    /** @var string $picture File name of the user's avatar */
    private $picture;

    /** @var string $data Other app data stored as JSON */
    private $data;

    /** @var string $created The time the model was created */
    private $created;

    /** @var bool $migrated Whether the model was migrated (from MMP) */
    private $migrated;


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

    public function setBody($body) 
    {
        $this->body = $body;
        return true;
    } 

    public function getBody() 
    {
        return $this->body;
    } 

    public function setMigrated($migrated) 
    {
        $this->migrated = $migrated;
        return true;
    } 

    public function getMigrated() 
    {
        return $this->migrated;
    } 

    public function getCreated() 
    {
        return $this->created;
    } 

    public function setPicture($picture) 
    {
        $this->picture = $picture;
        return true;
    } 

    public function getPicture() 
    {
        return $this->picture;
    } 

    public function setUnits($units) 
    {
        if($units === 'metric' || $units === 'imperial') $this->units = $units;
        else return false;

        return true;
    } 

    public function getUnits() 
    {
        return $this->units;
    } 

    public function getData() 
    {
        return json_decode($this->data);
    } 

    public function setData($data) 
    {
        if(is_object($data)) {
            $this->data = json_encode($data);
            
            return true;
        }

        return false;
    } 


    /**
     * Loads a model based on a unique identifier
     *
     * @param string $key   The unique column identifying the user. 
     *                      One of id/handle.
     * @param string $value The value to look for in the key column
     *
     * @return object|false A model object or false if model does not exist
     */
    private function load($value, $key='id') 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `models` WHERE `$key` =".$db->quote($value);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else foreach($result as $key => $val) $this->$key = $val;
    }
   
    /**
     * Loads a model based on their id
     *
     * @param int $id
     *
     * @return object|false A model object or false if user does not exist
     */
    public function loadFromId($id) 
    {
        return $this->load($id, 'id');
    }
   
    /**
     * Loads a model based on their handle
     *
     * @param string $handle
     *
     * @return object|false A model object or false if user does not exist
     */
    public function loadFromHandle($handle) 
    {
        return $this->load($handle, 'handle');
    }
   
    /**
     * Creates a new model and stores it in database
     *
     * @param User $user The user object     
     * 
     * @return int The id of the newly created model
     */
    public function create($user) 
    {
        // Set basic info    
        $this->setUser($user->getId());
        
        // Get the HandleKit to create the handle
        $handleKit = $this->container->get('HandleKit');
        $this->setHandle($handleKit->create('model'));

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');
        $this->setPicture($avatarKit->create($user->getHandle(), 'model', $this->getHandle()));
        
        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `models`(
            `user`,
            `handle`,
            `picture`,
            `created`
             ) VALUES (
            ".$db->quote($this->getUser()).",
            ".$db->quote($this->getHandle()).",
            ".$db->quote($this->getPicture()).",
            NOW()
            );";
        $db->exec($sql);

        // Retrieve model ID
        $id = $db->lastInsertId();

        // Set modelname to #ID to encourage people to change it
        $sql = "UPDATE `models` SET `name` = '#$id' WHERE `models`.`id` = '$id';";
        $db->exec($sql);

        // Update instance from database
        $this->loadFromId($id);
    }

    /** Saves the model to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `models` set 
            `user`    = ".$db->quote($this->getUser()).",
            `name` = ".$db->quote($this->getName()).",
            `body`   = ".$db->quote($this->getBody()).",
            `picture`  = ".$db->quote($this->getPicture()).",
            `data`     = ".$db->quote($this->data)."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";

        return $db->exec($sql);
    }
}
