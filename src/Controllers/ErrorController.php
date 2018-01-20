<?php
/** Freesewing\Data\Controllers\ErrorController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Error as Error;

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

    /**
     * Helper function to format response and send CORS headers
     *
     * @param $data The data to return
     */
    private function prepResponse($response, $data, $status=200)
    {
        return $response
            ->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
   
    private function scrub($request, $key)
    {
        if(isset($request->getParsedBody()[$key])) return filter_var($request->getParsedBody()[$key], FILTER_SANITIZE_STRING);
        else return false;
    }

    /** log error */
    public function log($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->type = $this->scrub($request,'type');
        $in->level = $this->scrub($request,'level');
        $in->message = $this->scrub($request,'message');
        $in->origin = $this->scrub($request,'origin');
        if($this->hasRequiredInput($in) === false) {
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'missing_input',
            ], 400);
        }
        
        $in->file = $this->scrub($request,'file');
        $in->line = $this->scrub($request,'line');
        $in->user = $this->scrub($request,'user');
        $in->raw = $this->scrub($request,'raw');

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
            
            return $this->prepResponse($response, [
                'result' => 'ok', 
                'id' => $id,
            ]);
        } else {
            return $this->prepResponse($response, [
                'result' => 'ignored', 
                'reason' => 'error_is_familiar',
            ]);

        }
    }

    /** List recent error groups */
    public function loadRecentErrors($request, $response, $args) 
    {
        $errors = $this->getActiveGroups();
        if($errors === false) {
            return $this->prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ]);
        }

            return $this->prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ]);

    }

    /** List all error groups */
    public function loadAllErrors($request, $response, $args) 
    {
        $errors = $this->getAllGroups();
        if($errors === false) {
            return $this->prepResponse($response, [
                'result' => 'ok', 
                'count' => 0,
            ]);
        }

            return $this->prepResponse($response, [
                'result' => 'ok', 
                'count' => count($errors),
                'errors' => $errors
            ]);

    }

    /** Load error group */
    public function loadGroup($request, $response, $args) 
    {
        // Request data
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);

        $group = $this->getGroupInfo($hash);
        $group['count'] = $this->getGroupCount($hash);
        $group['status'] = $this->groupStatus($group['count']);
        $group['errors'] = $this->getGroupErrors($hash);

        if($group === false) {
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'failed_to_load_group',
            ], 400);
        }

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'group' => $group
        ]);
    }

    private function groupStatus($counters) {
        if($counters['new'] > 0) return 'new';
        if($counters['open'] > 0) return 'open';
        if($counters['muted'] > 0) return 'muted';
        return 'closed';
    }

    /** Update error group */
    public function updateGroup($request, $response, $args) 
    {
        // Request data
        $hash = filter_var($args['hash'], FILTER_SANITIZE_STRING);
        $status = $this->scrub($request,'status');

        // Get ID from authentication middleware
        $id = $request->getAttribute("jwt")->user;
        $admin = clone $this->container->get('User');
        $admin->loadFromId($id);

        // Is user an admin?
        if($admin->getRole() != 'admin') {
            $logger = $this->container->get('logger');
            $logger->info("Failed to update error group status: User ".$admin->getId()." is not an admin");

            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'access_denied', 
                'role' => $admin->getRole(), 
                'id' => $id, 
                ], 400);
        }

        // Does the group exist?
        if($this->getGroupInfo($hash) === false) {
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'no_such_group', 
                ], 400);
        }

        // Update status
        $this->setGroupStatus($status, $hash);
        
        return $this->prepResponse($response, [
            'result' => 'ok' 
        ]);
    }

    /** 
     * Updates the status for (all) entries in a group
     *
     * @param string $status The new status
     */
    private function setGroupStatus($status, $hash)
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
        
        return $db->exec($sql);
    }

    /**
     * Returns the common info for an error group
     *
     * @param string $hash The error hash of the group
     *
     * @return array
     */
    private function getGroupInfo($hash) 
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
        
        if(!$result) return false;
        
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
        
        if(!$result) return false;
        
        return $result;
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
        if(!$result) return false;
    
        $count['total'] = (int)$result['count'];
        $sql = "SELECT COUNT(`id`) as 'count', `status` FROM `errors` 
            WHERE hash=".$db->quote($hash)." GROUP BY `status`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
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
            AND `time` > NOW() - INTERVAL 24 HOUR 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

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
            `time` > NOW() - INTERVAL 28 DAY 
            GROUP BY `hash`
            ORDER BY `errors`.`time` DESC
            LIMIT 100";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

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
