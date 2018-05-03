<?php
/** Freesewing\Data\Objects\Confirmation class */
namespace Freesewing\Data\Objects;

use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * The confirmation class. Used for email-based confirmation.
 *
 * When creating an account or changing email, or password resets. These kinda things.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Confirmation 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the confirmation */
    private $id;

    /** @var JsonStore $data Confirmation data stored as JSON */
    public $data;

    /** @var string $time Datetime of when the confirmation was created */
    private $time;

    /** @var string $expires Datetime of when the confirmation expires */
    private $expires;

    /** @var string $nonce Nonce used for encryption */
    private $nonce;

    /** @var string $hash A sha-1 hash used as confirmation token */
    private $hash;

    /** Fields that are stored as plain text in the database */
    CONST CLEARTEXT_FIELDS = [
        'id',
        'time',
        'expires',
        'nonce',
        'hash'
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

    public function getNonce() 
    {
        return $this->nonce;
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

    public function getHash() 
    {
        return $this->hash;
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

    public function setHash($hash) 
    {
        $this->hash = $hash;
    } 

    /**
     * Loads a confirmation based on a unique identifier
     *
     * @param string $key   The id identifying the confirmation. 
     * @param string $value The value to look for in the key column
     */
    private function load($value, $key='id') 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `confirmations` WHERE `$key` =".$db->quote($value);
        
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
     * Loads a confirmation based on its hash
     *
     * @param int $id
     */
    public function loadFromHash($hash) 
    {
        $this->load($hash, 'hash');
    }
   
    /**
     * Loads a confirmation based on its id
     *
     * @param int $id
     */
    public function loadFromId($id) 
    {
        $this->load($id, 'id');
    }
   
    /**
     * Creates a new confirmation and stores it in database
     *
     * @param Object $data The data for the confirmation
     * @param int $expiresIn The time (in seconds) when the confirmation expires 
     * after it was created. Default is 604800 (1 week)
     *
     * @return int The id of the newly created confirmation
     */
    public function create($data, $expiresIn=604800) 
    {
        $nonce = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)); 
        $this->data->import(json_encode($data));

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `confirmations`(
            `data`,
            `time`,
            `hash`,
            `expires`,
            `nonce`
             ) VALUES (
            ".$db->quote(Utilities::encrypt($this->getDataAsJson(), $nonce)).",
            NOW(),
            ".$db->quote($data->hash).",
             DATE_ADD(NOW(), INTERVAL $expiresIn SECOND),
            ".$db->quote($nonce)."
            );";
        $db->exec($sql);

        // Update instance from database
        $this->loadFromId($db->lastInsertId());
    }

    /** Saves a confirmation to the database */
    public function save() 
    {
        $nonce = $this->getNonce();
        $db = $this->container->get('db');
        $sql = "UPDATE `confirmations` set 
              `data` = ".$db->quote(Utilities::encrypt(json_encode($this->getData()), $nonce)).",
           `expires` = ".$db->quote($this->getExpires()).",
           `hash` = ".$db->quote($this->getHash())."
            WHERE 
                `id` = ".$db->quote($this->getId());
        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
    
    /** Removes the confirmation */
    public function remove() 
    {
        // Remove from database
        $db = $this->container->get('db');
        $sql = "DELETE from `confirmations` WHERE `id` = ".$db->quote($this->getId()).";";

        $result = $db->exec($sql);
        $db = null;

        return $result;
    }
}
