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
    public function __toString()
    {
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function setNode($location, $value) 
    {
        if(strpos($location,'.') === false)  $this->$location = $value;
        else {
            $levels = explode('.',$location);
            $target = array_pop($levels);
            $temp = &$this;
            foreach($levels as $key) {
                if(!isset($temp->$key)) $temp->$key = new \stdClass();
                $temp = &$temp->$key;
            }
            $temp->$target = $value;
        }
    } 

    public function unsetNode($location) 
    {
        if(strpos($location,'.') === false)  unset($this->$location);
        else {
            $levels = explode('.',$location);
            $target = array_pop($levels);
            $temp = &$this;
            foreach($levels as $key) $temp = &$temp->$key;
            unset($temp->$target);
        }
    } 
    
    public function getNode($location) 
    {
        if(strpos($location,'.') === false)  {
            if(isset($this->$location)) return $this->$location;
            else return false;
        } else {
            $levels = explode('.',$location);
            $target = array_pop($levels);
            $temp = &$this;
            foreach($levels as $key) $temp = &$temp->$key;
            if(isset($temp->$target)) return $temp->$target;
            else return false;
        }
    } 
    
    public function import($json) 
    {
        foreach((array)json_decode($json) as $key => $val) $this->$key = json_decode(json_encode($val));
    }
}
