<?php
/** Freesewing\Data\Objects\Error class */
namespace Freesewing\Data\Objects;

/**
 * The error class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Error
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the error */
    private $id;

    /** @var int $type The error type */
    private $type;

    /** @var int $level The error level */
    private $level;

    /** @var string $message The error message */
    private $message;

    /** @var string $file The file/script in which the error occured */
    private $file;

    /** @var int $line The line number at which the error occured */
    private $line;

    /** @var string $origin The origin/host where the error occured */
    private $origin;

    /** @var string $user The user, if known */
    private $user;

    /** @var string $ip The ip address, for front-end errors */
    private $ip;

    /** @var string $time The time at which the error occured */
    private $time;

    /** @var string $status The error status. One of [new|open|muted|closed] */
    private $status;

    /** @var string $hash A hash of the error message, to group identical errors */
    private $hash;

    /** @var string $raw Additional raw error data */
    private $raw;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
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

    public function getLevel() 
    {
        return $this->level;
    } 

    public function getMessage() 
    {
        return $this->message;
    } 

    public function getFile() 
    {
        return $this->file;
    } 
    
    public function getLine() 
    {
        return $this->line;
    } 

    public function getOrigin() 
    {
        return $this->origin;
    } 

    public function getUser() 
    {
        return $this->user;
    } 

    public function getIp() 
    {
        return $this->ip;
    } 

    public function getTime() 
    {
        return $this->time;
    } 

    public function getStatus() 
    {
        return $this->status;
    } 

    public function getHash() 
    {
        return $this->hash;
    } 

    public function getRaw() 
    {
        return $this->raw;
    } 

    // Setters
    public function setLevel($level) 
    {
        $this->level = $level;
    } 

    public function setType($type) 
    {
        $this->type = $type;
    } 

    public function setMessage($message) 
    {
        $this->message = $message;
    } 

    public function setFile($file) 
    {
        $this->file = $file;
    } 
    
    public function setLine($line) 
    {
        $this->line = $line;
    } 

    public function setOrigin($origin) 
    {
        $this->origin = $origin;
    } 

    public function setUser($user) 
    {
        $this->user = $user;
    } 

    public function setIp($ip) 
    {
        $this->ip = $ip;
    } 

    public function setStatus($status) 
    {
        $this->status = $status;
    } 

    public function setHash($hash) 
    {
        $this->hash = $hash;
    } 

    public function setRaw($raw) 
    {
        $this->raw = $raw;
    } 

    // Set status
    public function open() 
    {
        $this->status = 'open';
    } 

    public function mute() 
    {
        $this->status = 'muted';
    } 

    public function close() 
    {
        $this->status = 'closed';
    } 

    // Set hash
    public function hash() 
    {
        $this->hash = sha1($this->level.$this->message.$this->file.$this->line.$this->origin);
    } 
    
    /**
     * Creates a new error and stores it in database
     *
     * @return int The id of the newly created error
     */
    public function create() 
    {
        // Set basic info    
        $this->hash();
        
        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `errors`(
            `type`,
            `level`,
            `message`,
            `file`,
            `line`,
            `origin`,
            `user`,
            `ip`,
            `time`,
            `status`,
            `hash`,
            `raw`
             ) VALUES (
            ".$db->quote($this->getType()).",
            ".$db->quote($this->getLevel()).",
            ".$db->quote($this->getMessage()).",
            ".$db->quote($this->getFile()).",
            ".$db->quote($this->getLine()).",
            ".$db->quote($this->getOrigin()).",
            ".$db->quote($this->getUser()).",
            ".$db->quote($this->getIp()).",
            NOW(),
            'new',
            ".$db->quote($this->getHash()).",
            ".$db->quote($this->getRaw())."
            );";
        $db->exec($sql);

        // Retrieve error ID
        $id = $db->lastInsertId();
        $db = null;
        
        // Update instance from database
        $this->load($id);

        return $id;
    }

    /**
     * Loads an error based on the id
     *
     * @param string $id   The comment id
     *
     * @return object|false Updates the error object or returns false if the error does not exist
     */
    public function load($id) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `errors` WHERE `errors`.`id` =".$db->quote($id);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;

        if(!$result) return false;
        else foreach($result as $key => $val)  $this->$key = $val;
    }
   
    /** Saves the error to the database */
    public function save() 
    {
        $this->hash();
        $db = $this->container->get('db');
        $sql = "UPDATE `errors` set 
            `level`   = ".$db->quote($this->getLevel()).",
            `message` = ".$db->quote($this->getMessage()).",
            `file`    = ".$db->quote($this->getFile()).",
            `line`    = ".$db->quote($this->getLine()).",
            `origin`  = ".$db->quote($this->getOrigin()).",
            `user`    = ".$db->quote($this->getUser()).",
            `ip`      = ".$db->quote($this->getIp()).",
            `time`    = ".$db->quote($this->getTime()).",
            `status`  = ".$db->quote($this->getStatus()).",
            `hash`    = ".$db->quote($this->getHash()).",
            `raw`     = ".$db->quote($this->getRaw())."
            WHERE 
            `id`      = ".$db->quote($this->getId()).";";

        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
    
    /** Remove an error */
    public function remove() 
    {
        $db = $this->container->get('db');
        $sql = "DELETE from `errors` WHERE `id` = ".$db->quote($this->getId()).";";
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }

    /** 
     * Determine whether an error is 'familiar'
     *
     * This method stops logging of the same errors when 
     * they happen too frequently.
     *
     * Specifically: max 30 entries over the last 30 minutes 
     */
    public function isFamiliar()
    {
        if($this->hash === null) $this->hash();
        if($this->countRecentPerHash($this->hash) >= 30) return true;
        else return false;
    }

    /**
     * Returns the number of recent entries of the same error hash
     *
     * @param string $hash The error hash to count
     * @param int $minutes The number of minutes to look back
     *
     * @return in $count The number of counted errors
     */
    private function countRecentPerHash($hash, $minutes=30) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT COUNT(`id`) as 'count' FROM `errors` WHERE 
            `hash` = ".$db->quote($hash)." 
            AND `time` > NOW() - INTERVAL 30 MINUTE;";

        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;

        if(!$result) return 0;
        else return $result->count;
    }

}
