<?php
/** App\Controllers\CommentController class */
namespace App\Controllers;

use \App\Data\Comment as Comment;

/**
 * Holds data for a comment.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class CommentController 
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
    private function prepResponse($response, $data)
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
   
    private function scrub($request, $key)
    {
        if(isset($request->getParsedBody()[$key])) return filter_var($request->getParsedBody()[$key], FILTER_SANITIZE_STRING);
        else return false;
    }

    /** Create comment */
    public function create($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->page = $this->scrub($request,'page');
        $in->comment = $this->scrub($request,'comment');
        $in->parent = $this->scrub($request,'parent');
        
        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a comment instance from the container, set info and store
        $comment = $this->container->get('Comment');
        $comment->setPage($in->page);
        $comment->setComment($in->comment);
        if($in->parent) $comment->setParent($in->parent);
        $comment->create($user);
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'message' => 'comment/created',
            'id' => $comment->getId(),
        ]);
    }

    /** Get page comments */
    public function pageComments($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->page = '/'.filter_var($args['page'], FILTER_SANITIZE_STRING);
        
        $comments = $this->loadPageComments($in->page);
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'count' => count($comments),
            'comments' => $comments,
        ]);
    }

    /** Remove comment */
    public function remove($request, $response, $args) 
    {
        // Get ID from authentication middleware
        $userid = $request->getAttribute("jwt")->user;
        $in = new \stdClass();
        $in->id = filter_var($args['id'], FILTER_SANITIZE_STRING);
        
        // Get a comment instance from the container and load comment data
        $comment = $this->container->get('Comment');
        $comment->load($in->id);

        // Get a logger instance from the container
        $logger = $this->container->get('logger');

        // Does this comment belong to the user?
        if($comment->getUser() != $userid) {
            $logger->info("Access blocked: Attempt to remove comment ".$comment->getId()." by user: ".$userid);
            return $this->prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not_your_comment', 
            ]);
        }
        
        $comment->remove();
        
        return $this->prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'comment_removed', 
        ]);
    } 
    private function loadPageComments($page)
    {
        return $this->loadComments('page', $page);
    }

    private function loadUserComments($user)
    {
        return $this->loadComments('user', $user);
    }

    private function loadComments($key, $val)
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
            `users`.`picture`,
            `users`.`handle` as userhandle
            from `comments`,`users` 
            WHERE `comments`.`user` = `users`.`id` AND
            `comments`.`$key` =".$db->quote($val);
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!$result) return false;
        else {
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            foreach($result as $key => $val) {
                $val->picture = '/static'.$avatarKit->getDir($val->userhandle).'/'.$val->picture;
                $comments[$val->id] = $val;
            }
        } 

        return $comments;
    }

}
