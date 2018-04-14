<?php
/** Freesewing\Data\Objects\Task class */
namespace Freesewing\Data\Objects;

use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * The task class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Task 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the task */
    private $id;

    /** @var string $type The type of task this is */
    private $type;

    /** @var JsonStore $data Other task data stored as JSON */
    public $data;

    /** @var string $notBefore Datetime before which the task should not run */
    private $notBefore;

    // constructor receives container instance    
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
        $this->data = clone $this->container->get('JsonStore');
    }

    // Ensure a deep clone when cloning a task    
    public function __clone() 
    {
        $this->data = clone $this->data;
    }

    // Getters
    public function getId() 
    {
        return $this->id;
    } 

    public function getType() 
    {
        return $this->type;
    } 

    public function getNotBefore() 
    {
        return $this->notBefore;
    } 

    public function getDataAsJson() 
    {
        return (string) $this->data;
    } 

    public function getData() 
    {
        return $this->data;
    } 

    // Setters
    public function setData(\Freesewing\Data\Objects\JsonStore $data) 
    {
        $this->data = ($data);
    }

    public function setType($type) 
    {
        $this->type = $type;
    } 

    public function setNotBefore($notBefore) 
    {
        $this->notBefore = $notBefore;
    } 

    /**
     * Loads a task based on its id
     *
     * @param string $id The id of the task. 
     */
    public function load($id) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `tasks` WHERE `id` =".$db->quote($id);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $db = null;
        if(!$result) return false;
        
        foreach($result as $key => $value) {
            if($key === 'data') $this->data->import($value);
            else $this->{$key} = $value;
        } 
        
    }
    
    /** alias for load() */
    public function loadFromId($id)
    {
        $this->load($id);
    } 
   
    /**
     * Creates a new task and stores it in database
     *
     * @param string $type The type of task
     * @param Object $data The data for the task
     * @param int $notBefore The time (in seconds) that the task should be held 
     *
     * @return int The id of the newly created task
     */
    public function create($type, $data, $notBefore=0) 
    {
        $this->data->import(json_encode($data));

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `tasks`(
            `type`,
            `data`,
            `notBefore`
             ) VALUES (
            ".$db->quote($type).",
            ".$db->quote($this->getDataAsJson()).",
             DATE_ADD(NOW(), INTERVAL $notBefore SECOND)
            );";
        $db->exec($sql);

        // Update instance from database
        $this->load($db->lastInsertId());
    }

    /** Saves a task to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `tasks` set 
              `type` = ".$db->quote($this->getType()).",
              `data` = ".$db->quote($this->getDataAsJson()).",
           `notBefore` = ".$db->quote($this->getNotBefore())."
            WHERE 
                `id` = ".$db->quote($this->getId());
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
    
    /** Removes the task */
    public function remove() 
    {
        // Remove from database
        $db = $this->container->get('db');
        $sql = "DELETE from `tasks` WHERE `id` = ".$db->quote($this->getId()).";";

        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
}
