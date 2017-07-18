<?php
/** App\Data\User class */
namespace App\Data;

/**
 * The user class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class User 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    /** @var int $id Unique id of the user */
    private $id;

    /** @var string $email Unique email address of the user */
    private $email;

    /** @var string $username Unique username of the user */
    private $username;

    /** @var string $handle Unique handle of the user */
    private $handle;

    /** @var string $status Status of the user. One of inactive,active,blocked */
    private $status;

    /** @var string $created Time when the accout was created */
    private $created;

    /** @var string $migrated Time when the migrated mmp account was created */
    private $migrated;

    /** @var string $login Time of the last login */
    private $login;

    /** @var string $role Role of the user. One of user,moderator,admin */
    private $role;

    /** @var string $picture File name of the user's avatar */
    private $picture;

    /** @var string $data Other app data stored as JSON */
    private $data;

    /** @var string $password Password hash/salt/algo combo */
    private $password;

    /** @var string $initial The email address the (migrated) account was created with */
    private $initial;


    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;

    }

    public function getId() 
    {
        return $this->id;
    } 

    public function getPepper() 
    {
        return $this->pepper;
    } 

    public function getInitial() 
    {
        return $this->initial;
    } 

    public function getCreated() 
    {
        return $this->created;
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

    private function setLogin($login) 
    {
        $this->email = $login;
        return true;
    } 

    public function getLogin() 
    {
        return $this->login;
    } 

    public function setEmail($email) 
    {
        $this->email = $email;
        return true;
    } 

    public function getEmail() 
    {
        return $this->email;
    } 

    public function setUsername($username) 
    {
        $this->username = $username;
        return true;
    } 

    public function getUsername() 
    {
        return $this->username;
    } 

    public function setStatus($status) 
    {
        if(in_array($status, $this->container['settings']['app']['user_status'])) {
            $this->status = $status;

            return true;
        } 
    
        return false;
    } 

    public function getStatus() 
    {
        return $this->status;
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

    public function setRole($role) 
    {
        if(in_array($role, $this->container['settings']['app']['user_role'])) {
            $this->role = $role;

            return true;
        } 
    
        return false;
    } 

    public function getRole() 
    {
        return $this->role;
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

    public function setData($data) 
    {
        $this->data = $data;
        return true;
    } 

    public function getData() 
    {
        if(!is_object($this->data)) {
            $this->data = new \stdClass();
            $this->data->account = new \stdClass();
            $this->data->account->units = 'metric';
            $this->data->account->theme = 'classic';
            return $this->data;
        }

        return $this->data;
    } 

    public function setPassword($password) 
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    private function getPassword() 
    {
        return $this->password;
    } 


    /**
     * Loads a user based on a unique identifier
     *
     * @param string $key   The unique column identifying the user. 
     *                      One of id/email/handle. Defaults to id
     * @param string $value The value to look for in the key column
     *
     * @return object|false A plain user object or false if user does not exist
     */
    private function load($value, $key='id') 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `users` WHERE `$key` =".$db->quote($value);
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);

        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                if($key == 'data' && $val != '') $this->$key = json_decode($val);
                else $this->$key = $val;
            }
        }
    }
   
    /**
     * Loads a user based on their id
     *
     * @param int $id
     *
     * @return object|false A plain user object or false if user does not exist
     */
    public function loadFromId($id) 
    {
        return $this->load($id, 'id');
    }
   
    /**
     * Loads a user based on their handle
     *
     * @param string $handle
     *
     * @return object|false A plain user object or false if user does not exist
     */
    public function loadFromHandle($handle) 
    {
        return $this->load($handle, 'handle');
    }
   
    /**
     * Loads a user based on their email address
     *
     * @param string $email
     *
     * @return object|false A plain user object or false if user does not exist
     */
    public function loadFromEmail($email) 
    {
        return $this->load($email, 'email');
    }
   
    /**
     * Creates a new user and stores it in database
     *
     * Also auto-magically handles migration from MMP if needed
     *
     * @param string $email The email of the new user
     * @param string $password The password of the new user
     *
     * @return int The id of the newly created user
     */
    public function create($email, $password) 
    {
        // Get a logger instance from the container
        $logger = $this->container->get('logger');
        
        // Set basic info    
        $this->setPassword($password);
        $this->setEmail($email);
        
        // Get the HandleKit to create the handle
        $handleKit = $this->container->get('HandleKit');
        $this->setHandle($handleKit->create('user'));
        $logger->info("Got handle ".$this->getHandle()." for: ".$email);

        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');
        $this->setPicture($avatarKit->create($this->getHandle(), 'user'));
        
        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `users`(
            `email`,
            `username`,
            `handle`,
            `status`,
            `created`,
            `role`,
            `picture`,
            `password`,
            `initial`,
            `pepper`
             ) VALUES (
            ".$db->quote($this->getEmail()).",
            ".$db->quote($this->getHandle()).",
            ".$db->quote($this->getHandle()).",
            'inactive',
            NOW(),
            'user',
            ".$db->quote($this->getPicture()).",
            ".$db->quote($this->getPassword()).",
            ".$db->quote($this->getEmail()).",
            ".$db->quote(hash('sha256', random_bytes(256)))."
            );";
        if($db->exec($sql)) $logger->info("User ".$this->getHandle()." created in database.");
        else $logger->info("Could not create user ".$this->getHandle()." in database. Query that failed was: $sql");

        // Retrieve user ID
        $id = $db->lastInsertId();

        // Set username to 'user ID' to encourage people to change it
        $sql = "UPDATE `users` SET `username` = 'user $id' WHERE `users`.`id` = '$id';";
        $db->exec($sql);

        // Update instance from database
        $this->loadFromId($id);

        // Migrate user data from MMP
        $migrationKit = $this->container->get('MigrationKit');
        $migrationKit->migrate($this);
    }

    /** 
     * Checks whether a email address is used by a user
     *
     * @return bool true if it's free, false if not
     */
    public function emailTaken($email) 
    {
        $db = $this->container->get('db');
        $sql = 'SELECT `email` FROM `users` WHERE  `email` = '.$db->quote($email).' LIMIT 1';
        
        if($db->query($sql)->fetch(\PDO::FETCH_OBJ)) return true;
        else return false;
    }

    /** 
     * Checks whether a username address is used by a user
     *
     * @return bool true if it's free, false if not
     */
    public function usernameTaken($email) 
    {
        $db = $this->container->get('db');
        $sql = 'SELECT `username` FROM `users` WHERE  `username` = '.$db->quote($email).' LIMIT 1';
        
        if($db->query($sql)->fetch(\PDO::FETCH_OBJ)) return true;
        else return false;
    }

    /** 
     * Generates an activation token for a user account
     *
     * Note that actication tokens never expire
     *
     * @return string The activation token
     */
    public function getActivationToken() 
    {
        return hash('sha256',$this->getId().$this->getUsername().$this->getHandle().$this->getCreated().$this->getPepper());
    }

    /** 
     * Generates an password reset token for a user account
     *
     * Note that reset tokens do expire
     *
     * @return string The activation token
     */
    public function getResetToken() 
    {
        // Days since epoch
        $dse = round(time()/(24*3600));
        
        return hash('sha256',$dse.$this->getId().$this->getUsername().$this->getHandle().$this->getCreated().$this->getPepper());
    }

    /** Verifies the user's password */
    public function checkPassword($password)
    {
        return(password_verify($password, $this->password));
    }

   
    /** Saves the user to the database */
    public function save() 
    {
        $db = $this->container->get('db');
        $sql = "UPDATE `users` set 
            `email`    = ".$db->quote($this->getEmail()).",
            `username` = ".$db->quote($this->getUsername()).",
            `status`   = ".$db->quote($this->getStatus()).",
            `migrated` = ".$db->quote($this->getMigrated()).",
            `role`     = ".$db->quote($this->getRole()).",
            `login`    = ".$db->quote($this->getLogin()).",
            `role`     = ".$db->quote($this->getEmail()).",
            `picture`  = ".$db->quote($this->getPicture()).",
            `data`     = ".$db->quote(json_encode($this->getData())).",
            `password` = ".$db->quote($this->getPassword())."
            WHERE 
            `id`       = ".$db->quote($this->getId()).";";

        return $db->exec($sql);
    }
    
    /** Removes the user */
    public function remove() 
    {
        // Remove from storage
        shell_exec("rm -rf ".$this->container['settings']['storage']['static_path']."/users/".substr($this->getHandle(),0,1).'/'.$this->getHandle());
        
        // Remove from database
        $db = $this->container->get('db');
        $sql = "
            DELETE from `models` WHERE `user` = ".$db->quote($this->getId()).";
            DELETE from `users` WHERE `id` = ".$db->quote($this->getId()).";
        ";

        return $db->exec($sql);
    }
    
    /**
     * Loads all models for a given user id
     */
    public function getModels() 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `models` WHERE `user` =".$db->quote($this->getId());
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        // Get the AvatarKit to create the avatar
        $avatarKit = $this->container->get('AvatarKit');

        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                $models[$val->handle] = $val;
                $models[$val->handle]->pictureSrc = $avatarKit->getWebDir($this->getHandle(), 'model', $val->handle).'/'.$val->picture;
                $models[$val->handle]->data = json_decode($val->data);

            }
        } 
        return $models;
    }
   
    /**
     * Loads all drafts for a given user id
     * but not the SVG to limit the reponse size
     */
    public function getDrafts() 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `drafts` WHERE `user` =".$db->quote($this->getId());
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                unset($val->svg);
                $drafts[$val->id] = $val;
            }
        } 
        return $drafts;
    }
}
