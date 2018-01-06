<?php
/** App\Data\JsonStore class */
namespace App\Data;

/**
 * The JsonStore class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class JsonStore
{
    /** @var object $data Data stored in this object */
    private $data;

    public function __construct() 
    {
        $this->data = new \stdClass();
    }

    public function __toString()
    {
        return json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function export() 
    {
        return $this->data;
    } 

    public function import($data) 
    {
        if(is_object($data)) $this->data = $data;
        else $this->data = json_decode($data);
    } 

    public function setNode($location, $value) 
    {
        if(strpos($location,'.') === false)  $this->data->$location = $value;
        else {
            $levels = explode('.',$location);
            $data = $this->data;
            $temp = &$data;
            foreach($levels as $key) $temp = &$temp->$key;
            $temp = $value;
            unset($temp);
            $this->data = $data;
        }
    } 

    public function unsetNode($location) 
    {
        if(strpos($location,'.') === false)  unset($this->data->$location);
        else {
            $levels = explode('.',$location);
            $target = array_pop($levels);
            $data = $this->data;
            $temp = &$data;
            foreach($levels as $key) $temp = &$temp->$key;
            unset($temp->$target);
            $this->data = $data;
            unset($temp);
        }
    } 
    
    public function getNode($location) 
    {
        if(strpos($location,'.') === false)  {
            if(isset($this->data->$location)) return $this->data->$location;
            else return false;
        } else {
            $levels = explode('.',$location);
            $target = array_pop($levels);
            $data = $this->data;
            $temp = &$data;
            foreach($levels as $key) $temp = &$temp->$key;
            if(isset($temp->$target)) return $temp->$target;
            else return false;
        }
    } 
}
