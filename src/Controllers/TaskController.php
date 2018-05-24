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
    
    /** Runs an recoverPassword task */
    private function runTask__recoverPassword($task) 
    {
        return $this->container->get('MailKit')->recoverPassword($task->getData());
    }
    
    /** Runs a commentReply task */
    private function runTask__commentReply($task) 
    {
        return $this->container->get('MailKit')->commentReply($task->getData());
    }
    
    /** Runs a commentProfile task */
    private function runTask__commentProfile($task) 
    {
        return $this->container->get('MailKit')->commentProfile($task->getData());
    }
    
    /** Runs a profileConsentGiven task */
    private function runTask__profileConsentGiven($task) 
    {
        return $this->container->get('MailKit')->profileConsentGiven($task->getData());
    }

    /** Runs a modelConsentGiven task */
    private function runTask__modelConsentGiven($task) 
    {
        return $this->container->get('MailKit')->modelConsentGiven($task->getData());
    }

    /** Runs a userRemoved task */
    private function runTask__userRemoved($task) 
    {
        return $this->container->get('MailKit')->userRemoved($task->getData());
    }

    /** Runs a dataExport task */
    private function runTask__dataExport($task) 
    {
        // Get a user instance from the container and load user data
        $user = clone $this->container->get('User');
        $user->loadFromId($task->getData()->getNode('user'));

        // Queue email & export data
        $taskData = new \stdClass();
        $taskData->email = $user->getEmail();
        $taskData->locale = $user->getLocale();
        $taskData->hash = Utilities::getToken('dataExportEmail'.$user->getEmail());
        $taskData->link = $this->container['settings']['app']['data_api'].$user->export();
        $task = clone $this->container->get('Task');
        $task->create('dataExportEmail', $taskData);

        return true; 
    }

    /** Runs a dataExportEmail task */
    private function runTask__dataExportEmail($task) 
    {
        return $this->container->get('MailKit')->dataExport($task->getData());
    }
}
