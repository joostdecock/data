<?php
/** Freesewing\Data\Objects\Comment class */
namespace Freesewing\Data\Objects;

/**
 * The comment class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Comment
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the comment */
    private $id;

    /** @var int $user ID of the user owning the comment */
    private $user;

    /** @var string $comment The comment content */
    private $comment;

    /** @var string $page The page on which the comment was made */
    private $page;

    /** @var datetime $time The time/date of the comment */
    private $time;

    /** @var string $status The comment status. One of [active|removed|restricted]
     * active is the default
     * removed means removed by the owner
     * restricted means removed by a moderator
    */
    private $status;

    /** @var int $parent The id of the comment this is a reply to (or null) */
    private $parent;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }

    // Getters
    public function getComment() 
    {
        return $this->comment;
    } 

    public function getId() 
    {
        return $this->id;
    } 

    public function getPage() 
    {
        return $this->page;
    } 

    public function getParent() 
    {
        return $this->parent;
    } 
    
    public function getStatus() 
    {
        return $this->status;
    } 

    public function getUser() 
    {
        return $this->user;
    } 


    // Setters
    public function setComment($comment) 
    {
        $this->comment = $comment;
    } 

    public function setPage($page) 
    {
        $this->page = $page;
    } 

    public function setParent($parent) 
    {
        $this->parent = $parent;
    } 

    public function setStatusActive() 
    {
        $this->status = 'active';
    } 

    public function setStatusRemoved() 
    {
        $this->status = 'removed';
    } 

    public function setStatusRestricted() 
    {
        $this->status = 'restricted';
    } 

    public function setUser($user) 
    {
        $this->user = $user;
    } 

    private function hasChildren()
    {
        $db = $this->container->get('db');
        $sql = "SELECT `comments`.`id` FROM `comments` WHERE `parent` =  ".$db->quote($this->getId())." LIMIT 1;";
       
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else return true;
    }

    /**
     * Creates a new comment and stores it in database
     *
     * @param User $user The user object     
     * 
     * @return int The id of the newly created comment
     */
    public function create($user) 
    {
        // Set basic info    
        $this->setUser($user->getId());
        
        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `comments`(
            `user`,
            `comment`,
            `page`,
            `time`,
            `status`,
            `parent`
             ) VALUES (
            ".$db->quote($this->getUser()).",
            ".$db->quote($this->getComment()).",
            ".$db->quote($this->getPage()).",
            NOW(),
            'active',
            ".$db->quote($this->getParent())."
            );";
        $db->exec($sql);

        // Retrieve comment ID
        $id = $db->lastInsertId();
        
        // Update instance from database
        $this->load($id);
    }

    /**
     * Loads a comment based on the id
     *
     * @param string $id   The comment id
     *
     * @return object|false A comment object or false if the comment does not exist
     */
    public function load($id) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT 
            `comments`.`id`,
            `comments`.`user`,
            `comments`.`comment`,
            `comments`.`page`,
            `comments`.`time`,
            `comments`.`status`,
            `comments`.`parent`,
            `users`.`username`,
            `users`.`handle` as userhandle
            from `comments`,`users` 
            WHERE `comments`.`user` = `users`.`id` AND
            `comments`.`id` =".$db->quote($id);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else foreach($result as $key => $val)  $this->$key = $val;
    }
   
    /** Saves the comment to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `comments` set 
            `comment` = ".$db->quote($this->getComment()).",
            `status` = ".$db->quote($this->getStatus())."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";

        return $db->exec($sql);
    }
    

    /** Remove a comment */
    public function remove() 
    {
        // Only remove comments that don't have children, we don't want orphans
        if($this->hasChildren()) {
            $this->setStatusRemoved();
            return $this->save();
        } else { 
            // Remove from database
            $db = $this->container->get('db');
            $sql = "DELETE from `comments` WHERE `id` = ".$db->quote($this->getId()).";";
            return $db->exec($sql);
        }
    }
}
