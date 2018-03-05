<?php
/** Freesewing\Data\Controllers\ErrorController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Error as Error;
use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * Holds errors
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class ErrorController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** log error */
    public function log($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->type = Utilities::scrub($request,'type');
        $in->level = Utilities::scrub($request,'level');
        $in->message = Utilities::scrub($request,'message');
        $in->origin = Utilities::scrub($request,'origin');
        if($this->hasRequiredInput($in) === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'missing_input',
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        $in->file = Utilities::scrub($request,'file');
        $in->line = Utilities::scrub($request,'line');
        $in->user = Utilities::scrub($request,'user');
        $in->raw = Utilities::scrub($request,'raw');

        // Get an error instance from the container
        $error = $this->container->get('Error');
        $error->setLevel($in->level);
        $error->setMessage($in->message);
        $error->setFile($in->file);
        $error->setLine($in->line);
        $error->setOrigin($in->origin);

        // That's all we need for a hash. Is this a familiar error?
        if($error->isFamiliar() === false) {
            $error->setUser($in->user);
            $error->setType($in->type);
            if(isset($_SERVER['REMOTE_ADDR'])) $error->setIp($_SERVER['REMOTE_ADDR']);
            $error->setRaw($in->raw);
            $id = $error->create();
            
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'id' => $id,
                'hash' => $error->getHash(),
            ], 200, $this->container['settings']['app']['origin']);
        } else {
            return Utilities::prepResponse($response, [
                'result' => 'ignored', 
                'reason' => 'error_is_familiar',
            ], 200, $this->container['settings']['app']['origin']);

        }
    }

    /** List recent error groups */
    public function loadRecentErrors($request, $response, $args) 
    {
        $errors = $this->getActiveGroups();
        if($errors === false) {
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ], 200, $this->container['settings']['app']['origin']);
        }

            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ], 200, $this->container['settings']['app']['origin']);

    }

    /** List all error groups */
    public function loadAllErrors($request, $response, $args) 
    {
        $errors = $this->getAllGroups();
        if($errors === false) {
            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ], 200, $this->container['settings']['app']['origin']);
        }

            return Utilities::prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ], 200, $this->container['settings']['app']['origin']);

    }

    /** Load error group */
    public function loadGroup($request, $response, $args) 
    {
        // Request data
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);

        $group = $this->getGroupInfo($hash);
        if($group === false) {
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'failed_to_load_group',
            ], 400, $this->container['settings']['app']['origin']);
        }
        $group['count'] = $this->getGroupCount($hash);
        $group['status'] = $this->groupStatus($group['count']);
        $group['errors'] = $this->getGroupErrors($hash);

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'group' => $group
        ], 200, $this->container['settings']['app']['origin']);
    }

    private function groupStatus($counters) {
        if($counters['new'] > 0) return 'new';
        if($counters['open'] > 0) return 'open';
        if($counters['muted'] > 0) return 'muted';
        return 'closed';
    }

    /** 
     * Updates the status for (all) entries in a group
     *
     * @param string $status The new status
     */
    public function setGroupStatus($status, $hash)
    {
        $db = $this->container->get('db');
        if($status == 'open' || $status == 'closed') {
            $sql = "UPDATE `errors` 
                SET `status` = ".$db->quote($status)." 
                WHERE `errors`.`hash` = ".$db->quote($hash); 
        }
        else if ($status == 'muted') {
            $sql = "UPDATE `errors` 
                SET `status` = ".$db->quote($status)." 
                WHERE `errors`.`hash` = ".$db->quote($hash)." 
                AND (
                    `errors`.`status` = 'new' OR
                    `errors`.`status` = 'open'
                )";
        }
        
        $result = $db->exec($sql);
        $db = null;
        return $result;
    }

    /**
     * Returns the common info for an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    public function getGroupInfo($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT 
            `hash`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `type`,
            `raw`,
            `time`
            FROM `errors` WHERE 
            `hash` = ".$db->quote($hash)."
            ORDER BY `time` DESC LIMIT 1";
        $result = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $db = null;
        
        if($result === false) return false;
        
        $result['last_seen'] = $result['time'];
        unset($result['time']);
        
        return $result;
    }

    /**
     * Returns list of errors for an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    private function getGroupErrors($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT 
            `id`,
            `ip`,
            `time`,
            `status`
            FROM `errors` WHERE 
            `hash` = ".$db->quote($hash)."
            ORDER BY `time` DESC LIMIT 50";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;
        
        if(!$result) return false;
        else return $result;
    }

    /**
     * Returns the error count by statusfor an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    private function getGroupCount($hash) 
    {
        $db = $this->container->get('db');

        $sql = "SELECT COUNT(`id`) as 'count' FROM `errors` 
            WHERE hash=".$db->quote($hash);
        $result = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);

        $count['total'] = (int)$result['count'];
        $sql = "SELECT COUNT(`id`) as 'count', `status` FROM `errors` 
            WHERE hash=".$db->quote($hash)." GROUP BY `status`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        $db = null;
        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $count[$val['status']] = $val['count'];
        }
        // Set missing status types to zero
        foreach(['new','open','muted','closed'] as $status) {
            if(!isset($count[$status])) {
                $count[$status] = 0;
            } else {
                $count[$status] = (int)$count[$status];
            }
        }

        return $count;
    }

    /**
     * Returns an overview of new/open groups in the last 24 hours
     */
    private function getActiveGroups() 
    {
        $db = $this->container->get('db');

        $time = date('Y-m-d H:i:s', time() - 24*60*60);
        $sql = "SELECT 
            COUNT(`id`) as 'count', 
            `type`,
            `time`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `hash`
            FROM `errors` WHERE 
            (`status` = 'new' OR `status` = 'open') 
            AND `time` > '$time' 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $result[$key]['status'] = $this->groupStatus($this->getGroupCount($val['hash']));
        }
        return $result;
    }

    /**
     * Returns an overview of all groups in the last 28 days
     */
    private function getAllGroups() 
    {
        $db = $this->container->get('db');

        $time = date('Y-m-d H:i:s', time() - 28*24*60*60);
        $sql = "SELECT 
            COUNT(`id`) as 'count', 
            `type`,
            `time`,
            `level`,
            `message`,
            `origin`,
            `file`,
            `line`,
            `hash`
            FROM `errors` WHERE 
            `time` > '$time' 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC
            LIMIT 100";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $db = null;

        if(!$result) return false;
        
        foreach($result as $key => $val) {
            $result[$key]['status'] = $this->groupStatus($this->getGroupCount($val['hash']));
        }
        return $result;
    }

    private function hasRequiredInput($obj)
    {
        if(
            isset($obj->type)    && $obj->type    !== false &&
            isset($obj->level)   && $obj->level   !== false &&
            isset($obj->message) && $obj->message !== false &&
            isset($obj->origin)  && $obj->origin  !== false
        ) {
            return true;
        } else {
            return false;
        }
    }
}
