<?php
/** Freesewing\Data\Controllers\TaskController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\User as User;
use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * The task controller.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class TaskController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** Taskrunner */
    public function taskRunner($request, $response, $args) 
    {
        $tasks =  $this->loadTasks();
        if(!$tasks) return;
            
        foreach($tasks as $task) {
            $this->runTask($task);
        }
    }

    /** Load pending tasks from the database */
    private function loadTasks() 
    {
        $db = $this->container->get('db');
        
        $sql = "SELECT * FROM `tasks` WHERE `notBefore` < CURRENT_TIMESTAMP 
            ORDER BY `id` ASC LIMIT ".$this->container['settings']['app']['tasks'];
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if(!$result) return false;
        $tasks = [];
        foreach($result as $hit) {
            $task = clone $this->container->get('Task');
            $task->loadFromId($hit['id']);
            $tasks[$hit['id']] = $task;
        }

        return $tasks;
    }
    
    /** Runs a task */
    private function runTask($task) 
    {
        if($this->{'runTask__'.$task->getType()}($task)) {
            $task->remove();   
        }
    }
    
    /** Runs an emailSignup task */
    private function runTask__emailSignup($task) 
    {
        return $this->container->get('MailKit')->signup($task->getData());
    }
    
    /** Runs an emailSignup task */
    private function runTask__emailChange($task) 
    {
        return $this->container->get('MailKit')->emailChange($task->getData());
    }
}
