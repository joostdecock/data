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

    /** @var string $time Datetime of when the task was created */
    private $time;

    /** @var string $expires Datetime of when the task expires */
    private $expires;

    /** @var string $nonce Nonce used for encryption */
    private $nonce;

    /** Fields that are stored as plain text in the database */
    CONST CLEARTEXT_FIELDS = [
        'id',
        'type',
        'time',
        'expires',
        'nonce'
    ];

    /** Fields that are encrypted in the database */
    CONST ENCRYPTED_FIELDS = [
        'data'
    ];

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
        $this->data = clone $this->container->get('JsonStore');
    }

    // Getters
    public function getId() 
    {
        return $this->id;
    } 

    public function getNonce() 
    {
        return $this->nonce;
    } 

    public function getType() 
    {
        return $this->type;
    } 

    public function getTime() 
    {
        return $this->time;
    } 

    public function getExpires() 
    {
        return $this->expires;
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

    public function setExpires($expires) 
    {
        $this->expires = $expires;
    } 

    public function setNonce($nonce) 
    {
        $this->nonce = $nonce;
    } 

    /**
     * Loads a task based on a unique identifier
     *
     * @param string $key   The id identifying the task. 
     * @param string $value The value to look for in the key column
     *
     * @return object|false A plain task object or false if the task does not exist
     */
    private function load($value, $key='id') 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `tasks` WHERE `$key` =".$db->quote($value);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;
        if(!$result) return false;
        else {
            foreach(self::CLEARTEXT_FIELDS as $f) {
                $this->{$f} = $result->{$f};
            } 
            foreach(self::ENCRYPTED_FIELDS as $f) {
                if($f == 'data') {
                    $this->data->import(Utilities::decrypt($result->{$f}, $result->nonce));
                } else $this->{$f} = Utilities::decrypt($result->{$f}, $result->nonce);
            }
        }
    }
   
    /**
     * Loads a task based on its id
     *
     * @param int $id
     *
     * @return object|false A plain task object or false if the task does not exist
     */
    public function loadFromId($id) 
    {
        return $this->load($id, 'id');
    }
   
    /**
     * Creates a new task and stores it in database
     *
     * @param string $type The type of task
     * @param JsonStore $data The data for the task
     * @param int $expiresIn The time (in seconds) when the task exires 
     * after it was created. Default is 604800 (1 week)
     *
     * @return int The id of the newly created user
     */
    public function create($type, $data, $expiresIn=604800) 
    {
        $nonce = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)); 

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `users`(
            `type`,
            `data`,
            `expires`,
            `nonce`
             ) VALUES (
            ".$db->quote($type).",
            ".$db->quote(Utilities::encrypt($this->getDataAsJson(), $nonce).",
            ".$db->quote(time()+$expiresIn).",
            ".$db->quote($nonce)."
            );";
        $db->exec($sql);

        // Update instance from database
        $this->loadFromId($db->lastInsertId());
    }

    /** Saves a task to the database */
    public function save() 
    {
        $nonce = $this->getNonce();
        $db = $this->container->get('db');
        $sql = "UPDATE `tasks` set 
              `type` = ".$db->quote($this->getType()).",
              `data` = ".$db->quote(Utilities::encrypt(json_encode($this->getData()), $nonce)).",
           `expires` = ".$db->quote($this->getExpires())."
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
