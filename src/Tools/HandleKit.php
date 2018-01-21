<?php
/** Freesewing\Data\Tools\HandleKit class */
namespace Freesewing\Data\Tools;

/**
 * The HandleKit class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class HandleKit 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }
    
    /**
     * Returns a free handle
     *
     * A handle is a string of 5 characters that is unique to the user/model/draft
     * It is used in some URLs where we need a user identifier but want
     * to prevent scraping so we can't just use the ID. 
     * Furthermore, email and username (also unique) are unpractical in URLs.
     *
     * Note that while we check that a handle is unused, it is (in theory)
     * possible to get a collission if two parallel requests generate the same
     * handle before they are written to the database.
     * This is highly unlikely though.
     *
     * @param string $type One of user,model,draft
     *
     * @return sring The handle
     */
    public function create($type) 
    {
        // Don't trust the handle type
        if(!in_array($type, $this->container['settings']['app']['handle_type'])) return false;

        $uniq = false;
        while(!$uniq) {
            $handle = substr(str_shuffle("abcdefghkmnpqrstuvwxyz"), 0, 5);
            $uniq = $this->handleIsFree($handle, $type);
        }
 
        return $handle;
    }
    
    /** 
     * Checks whether a user handle is free (not used by another user)
     *
     * @return bool true if it's free, false if not
     */
    private function handleIsFree($handle, $type) 
    {
        $db = $this->container->get('db');
        $sql = 'SELECT handle FROM `'.$type.'s` WHERE  `handle` = '.$db->quote($handle).' LIMIT 1';
        
        $result = $db->query($sql)->fetch(\PDO::FETCH_OBJ);
        $db = null;
    
        if ($result) return false;
        else return true;
    }

}
