<?php
/** Freesewing\Data\Objects\User class */
namespace Freesewing\Data\Objects;

use Symfony\Component\Yaml\Yaml;

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

    /** @var JsonStore $data Other app data stored as JSON */
    public $data;

    /** @var string $password Password hash/salt/algo combo */
    private $password;

    /** @var string $initial The email address the (migrated) account was created with */
    private $initial;


    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
        $this->data = $this->container->get('JsonStore');
    }

    // Getters
    private function getPassword() 
    {
        return $this->password;
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

    public function getHandle() 
    {
        return $this->handle;
    } 

    public function getLogin() 
    {
        return $this->login;
    } 

    public function getEmail() 
    {
        return $this->email;
    } 

    public function getUsername() 
    {
        return $this->username;
    } 

    public function getStatus() 
    {
        return $this->status;
    } 

    public function getMigrated() 
    {
        return $this->migrated;
    } 

    public function getRole() 
    {
        return $this->role;
    } 
    
    public function getPicture() 
    {
        return $this->picture;
    } 

    public function getPictureUrl() 
    {
        $avatarKit = $this->container->get('AvatarKit');
        $api = $this->container['settings']['app']['data_api'];
        return $api.$avatarKit->getWebDir($this->getHandle(), 'user').'/'.$this->getPicture();
    } 

    public function getDataAsJson() 
    {
        return (string) $this->data;
    } 

    public function getData() 
    {
        return $this->data;
    } 

    public function getPendingEmail() 
    {
        return $this->data->getNode('account.pendingEmail');
    } 

    public function getAccountUnits() 
    {
        return $this->data->getNode('account.units');
    } 

    public function getAccountTheme() 
    {
        return $this->data->getNode('account.theme');
    } 

    public function getTwitterHandle() 
    {
        return $this->data->getNode('social.twitter');
    } 

    public function getInstagramHandle() 
    {
        return $this->data->getNode('social.instagram');
    } 

    public function getGithubHandle() 
    {
        return $this->data->getNode('social.github');
    } 

    public function getBadges() 
    {
        return $this->data->getNode('badges');
    } 

    public function getSocial() 
    {
        return $this->data->getNode('social');
    } 

    public function getPatron() 
    {
        return $this->data->getNode('patron');
    } 

    public function getPatronTier() 
    {
        return $this->data->getNode('patron.tier');
    }

    public function getPatronSince() 
    {
        return $this->data->getNode('patron.since');
    } 

    public function getPatronAddress() 
    {
        return $this->data->getNode('patron.address');
    } 

    public function getPatronBirthday() 
    {
        return $this->data->getNode('patron.birthday.day').'/'.$this->data->getNode('patron.birthday.month');
    } 

    // Setters
    private function setHandle($handle) 
    {
        $this->handle = $handle;
    } 

    public function setLogin($time=false) 
    {
        if($time === false) $time = date('Y-m-d H:i:s');
        $this->login = $time;
    } 

    public function setData(\Freesewing\Data\Objects\JsonStore $data) 
    {
        $this->data = ($data);
    }

    public function setEmail($email) 
    {
        $this->email = $email;
    } 

    public function setUsername($username) 
    {
        $this->username = $username;
    } 

    public function setStatus($status) 
    {
        if(in_array($status, $this->container['settings']['app']['user_status'])) {
            $this->status = $status;
            return true;
        } 
    
        return false;
    } 

    public function setMigrated($migrated) 
    {
        $this->migrated = $migrated;
    } 

    public function setRole($role) 
    {
        if(in_array($role, $this->container['settings']['app']['user_role'])) {
            $this->role = $role;
            return true;
        } 
    
        return false;
    } 

    public function setPicture($picture) 
    {
        $this->picture = $picture;
    } 

    public function setPassword($password) 
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setPendingEmail($email) 
    {
        $this->data->setNode('account.pendingEmail', $email);
    } 

    public function setAccountUnits($units) 
    {
        $this->data->setNode('account.units', $units);
    }

    public function setAccountTheme($theme) 
    {
        $this->data->setNode('account.theme', $theme);
    }

    public function setTwitterHandle($handle) 
    {
        if(strlen(str_replace('@','',$handle)) > 2) $this->data->setNode('social.twitter', str_replace('@','',$handle));
        else $this->data->unsetNode('social.twitter');
    }

    public function setInstagramHandle($handle) 
    {
        if(strlen(str_replace('@','',$handle)) > 2) $this->data->setNode('social.instagram', str_replace('@','',$handle));
        else $this->data->unsetNode('social.instagram');
    }

    public function setGithubHandle($handle) 
    {
        if(strlen(str_replace('@','',$handle)) > 2) $this->data->setNode('social.github', str_replace('@','',$handle));
        else $this->data->unsetNode('social.github');
    }
    public function setPatron($tier, $since, $address=false, $birthday=false, $birthmonth=false) 
    {
        $this->setPatronTier($tier);
        $this->setPatronSince($since);
        if($address) $this->setPatronAddress($address);
        if($birthday) $this->setPatronBirthday($birthday, $birthmonth);
    }

    public function setPatronTier($tier) 
    {
        $this->data->setNode('patron.tier', $tier);
    }

    public function setPatronSince($date) 
    {
        $this->data->setNode('patron.since', $date);
    }

    public function setPatronAddress($address) 
    {
        $this->data->setNode('patron.address', $address);
    }

    public function setPatronBirthday($day, $month) 
    {
        $this->data->setNode('patron.birthday.day', $day);
        $this->data->setNode('patron.birthday.month', $month);
    }

    public function unsetPendingEmail() 
    {
        $this->data->unsetNode('account.pendingEmail');
    } 

    public function isPatron() 
    {
        $tiers = $this->container['settings']['patrons']['tiers'];
        if(in_array($this->getPatronTier(), $tiers)) return true;
        else return false;
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
                if($key == 'data') {
                    if($val != '') $this->data->import($val);
                    else $this->data = $this->container->get('JsonStore');
                } else $this->$key = $val;
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

        // Set defaults
        $this->setAccountUnits('metric');
        $this->setAccountTheme('classic');

        // Store in database
        $db = $this->container->get('db');
        $sql = "INSERT into `users`(
            `email`,
            `username`,
            `handle`,
            `status`,
            `created`,
            `data`,
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
            ".$db->quote($this->getDataAsJson()).",
            'user',
            ".$db->quote($this->getPicture()).",
            ".$db->quote($this->getPassword()).",
            ".$db->quote($this->getEmail()).",
            ".$db->quote(hash('sha256', random_bytes(256)))."
            );";
        $db->exec($sql);

        // Retrieve user ID
        $id = $db->lastInsertId();

        // Set username to 'user ID' to encourage people to change it
        $sql = "UPDATE `users` SET `username` = 'user $id' WHERE `users`.`id` = '$id';";
        $db->exec($sql);

        // Update instance from database
        $this->loadFromId($id);
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
    public function usernameTaken($username) 
    {
        $db = $this->container->get('db');
        $sql = 'SELECT `username` FROM `users` WHERE  `username` = '.$db->quote($username).' LIMIT 1';
        
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
     * Loads all comments for a given user id
     */
    public function getComments() 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `comments` WHERE `user` =".$db->quote($this->getId());
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                $comments[$val->id] = $val;
            }
        } 
        return $comments;
    }
   
    /**
     * Loads all drafts for a given user id
     * but not the SVG or compare code to limit the response size
     *
     * @param $all bool Set this to true to also include svg/compare in the return
     */
    public function getDrafts($all=false) 
    {
        $db = $this->container->get('db');
        $sql = "SELECT * from `drafts` WHERE `user` =".$db->quote($this->getId());
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!$result) return false;
        else {
            foreach($result as $key => $val) {
                if(!$all) {
                    unset($val->svg);
                    unset($val->compared);
                }
                $val->data = json_decode($val->data);
                $drafts[$val->id] = $val;
            }
        } 
        return $drafts;
    }

    /** Adds a badge to the user */
    public function addBadge($badge)
    {
        if ($this->data->getNode("badges.$badge") == true) return false;
        else $this->data->setNode("badges.$badge", true);

        return true;
    }

    /** Makes user a patron */
    public function makePatron($tier)
    {
        $this->data->setNode('patron.tier', $tier);
        $this->data->setNode('patron.since', time());
    }

    /** Removes a badge from the user */
    public function removeBadge($badge)
    {
        $this->data->unsetNode("badges.$badge");
    }
    
    /**
     * Exports user data to disk (for download by user)
     *
     * @param string or false Name of the directory where the data is stored
     *
     * @return string name of the directory where the data is stored
     */
    public function export($token=false) 
    {
        // Copy user disk data to random directory
        if(!$token) $token = sha1(print_r($this,1).time());
        $dir = $this->container['settings']['storage']['static_path']."/export";
        if(!is_dir($dir)) $cmd = mkdir($dir);
        if(is_dir("$dir/$token")) `rm -rf $dir/$token`;
        if(is_dir("$dir/".$this->getHandle())) shell_exec("rm -rf $dir/".$this->getHandle());
        shell_exec("cp --recursive ".$this->container['settings']['storage']['static_path']."/users/".substr($this->getHandle(),0,1).'/'.$this->getHandle()." $dir ; mv $dir/".$this->getHandle()." $dir/$token");

        // Export user object
        $userData = [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'initial' => $this->getInitial(),
            'username' => $this->getUsername(),
            'handle' => $this->getHandle(),
            'status' => $this->getStatus(),
            'created' => $this->getCreated(),
            'migrated' => $this->getMigrated(),
            'login' => $this->getLogin(),
            'role' => $this->getRole(),
            'picture' => $this->getPicture(),
            'data' => $this->getData(),
        ];
        // Export as JSON
        $file = "$dir/$token/account/account";
        $fp = fopen("$file.json", 'w');
        fwrite($fp, json_encode($userData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        fclose($fp);

        // Export as YAML
        $fp = fopen("$file.yaml", 'w');
        fwrite($fp, Yaml::dump(json_decode(json_encode($userData),1),3));
        fclose($fp);

        // Export models
        foreach($this->getModels() as $model) {
            $file = "$dir/$token/models/".$model->handle.'/'.$model->handle;
            $modelData = [
                'id' => $model->id,
                'user' => $model->user,
                'name' => $model->name,
                'handle' => $model->handle,
                'body' => $model->body,
                'picture' => $model->picture,
                'data' => $model->data,
                'units' => $model->units,
                'created' => $model->created,
                'migrated' => $model->migrated,
                'shared' => $model->shared,
                'notes' => $model->notes,
            ];
            // Export as JSON
            $fp = fopen("$file.json", 'w');
            fwrite($fp, json_encode($modelData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            fclose($fp);
            // Export as YAML
            $fp = fopen("$file.yaml", 'w');
            fwrite($fp, Yaml::dump(json_decode(json_encode($modelData),1),3));
            fclose($fp);
        }

        // Export drafts
        foreach($this->getDrafts(true) as $draft) {
            $file = "$dir/$token/drafts/".$draft->handle.'/'.$draft->handle;
            $draftData = [
                'id' => $draft->id,
                'user' => $draft->user,
                'name' => $draft->name,
                'handle' => $draft->handle,
                'pattern' => $draft->pattern,
                'model' => $draft->model,
                'data' => $draft->data,
                'created' => $draft->created,
                'shared' => $draft->shared,
                'notes' => $draft->notes,
                'svg' => $draft->svg,
                'compared' => $draft->compared,
            ];
            // Export as JSON
            $fp = fopen("$file.json", 'w');
            fwrite($fp, json_encode($draftData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            fclose($fp);
            // Export as YAML
            $fp = fopen("$file.yaml", 'w');
            fwrite($fp, Yaml::dump(json_decode(json_encode($draftData),1),6));
            fclose($fp);
        }

        // Export comments
        mkdir("$dir/$token/comments");
        foreach($this->getComments() as $comment) {
            $file = "$dir/$token/comments/comment-".$comment->id.'.md';
            $commentData = "---\n";
            $commentData .= "id: ".$comment->id."\n";
            $commentData .= "user: ".$comment->user."\n";
            $commentData .= "page: ".$comment->page."\n";
            $commentData .= "status: ".$comment->status."\n";
            $commentData .= "parent: ".$comment->parent."\n";
            $commentData .= "time: ".$comment->time."\n";
            $commentData .= "---\n".$comment->comment;

            $fp = fopen($file, 'w');
            fwrite($fp, $commentData);
            fclose($fp);
        }

        // Zip it
        `cd $dir/$token; zip -r freesewing-export *`;
        // Clean up
        `cd $dir/$token; rm -rf account models drafts comments`;

        return '/static/export/'.$token.'/freesewing-export.zip';
    }
}
