<?php
/** App\Data\Referral class */
namespace App\Data;

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

    private function setHost($host) 
    {
        $this->host = $host;
    } 

    public function getPath() 
    {
        return $this->path;
    } 

    private function setPath($path) 
    {
        $this->path = $path;
    } 

    public function getUrl() 
    {
        return $this->url;
    } 

    private function setUrl($url) 
    {
        $this->url = $url;
    } 

    public function getSite() 
    {
        return $this->site;
    } 

    private function setSite($site) 
    {
        $this->site = $site;
    } 


    /**
     * Loads a draft based on its id
     *
     */
    private function load($id) 
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
        $this->setSite($this->site($host));

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `referrals`(
            `host`,
            `path`,
            `url`,
            `site`,
            `time`
             ) VALUES (
            ".$db->quote($this->getHost()).",
            ".$db->quote($this->getPath()).",
            ".$db->quote($this->getUrl()).",
            ".$db->quote($this->getSite()).",
            NOW()
            );";

        return $db->exec($sql);
    }

    private function site($host)
    {
        return null;
    }
}
