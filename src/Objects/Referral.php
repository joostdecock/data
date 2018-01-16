<?php
/** Freesewing\Data\Objects\Referral class */
namespace Freesewing\Data\Objects;

/**
 * The regerral class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Referral
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the referral */
    private $id;

    /** @var string $host Hostname of the referral */
    private $host;

    /** @var string $path Path of the referral */
    private $path;

    /** @var string $url Full referral url */
    private $url;

    /** @var int $site Name to group referrals under */
    private $site;
    
    /** @var string $time The time the referral was logged */
    private $time;

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

    public function getTime() 
    {
        return $this->time;
    } 

    public function getHost() 
    {
        return $this->host;
    } 

    public function getPath() 
    {
        return $this->path;
    } 

    public function getUrl() 
    {
        return $this->url;
    } 

    public function getSite() 
    {
        return $this->site;
    } 

    // Setters
    private function setHost($host) 
    {
        $this->host = $host;
    } 

    private function setPath($path) 
    {
        $this->path = $path;
    } 

    private function setUrl($url) 
    {
        $this->url = $url;
    } 

    public function setSite($site) 
    {
        $this->site = $site;
    } 

    /**
     * Loads a referral based on its id
     *
     */
    public function load($id) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `referrals` WHERE `id` =".$db->quote($id);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else foreach($result as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Creates a new referral and stores it in the database
     *
     * @return int The id of the newly created referral
     */
    public function create($host, $path, $url) 
    {
        // Set basic info    
        $this->setHost($host);
        $this->setPath($path);
        $this->setUrl($url);

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `referrals`(
            `host`,
            `path`,
            `url`,
            `time`
             ) VALUES (
            ".$db->quote($this->getHost()).",
            ".$db->quote($this->getPath()).",
            ".$db->quote($this->getUrl()).",
            NOW()
            );";

        $db->exec($sql);

        return $db->lastInsertId();
    }

    /** Saves the referral to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `referrals` set 
            `site` = ".$db->quote($this->getSite())."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";
        
        return $db->exec($sql);
    }
    
    public function group()
    {
        $groups =  $this->container['settings']['referrals'];
        $matched = false;
        foreach($groups as $gid => $group) {
            if (isset($group['host'])) {
                if(stripos($this->getHost(),$group['host']) !== FALSE) $this->setSite($gid); 
            } elseif (isset($group['hosts'])) {
                foreach($group['hosts'] as $host) {
                    if(stripos($this->getHost(),$host) !== FALSE)  $this->setSite($gid); 
                }
            }
            if (isset($group['url'])) {
                if(stripos($this->getUrl(),$group['url']) !== FALSE)  $this->setSite($gid); 
            } elseif (isset($group['urls'])) {
                foreach($group['urls'] as $url) {
                    if(stripos($this->getUrl(),$url) !== FALSE) $this->setSite($gid); 
                }
            }

        }
        
        if ($this->getSite() === null) return false;
        else return true; 
    }
}
