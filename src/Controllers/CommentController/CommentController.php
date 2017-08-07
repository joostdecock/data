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
        
        // Strip trailing slashes
        if(substr($in->page,-1) == '/') $in->page = substr($in->page,0,-1);

        // Get ID from authentication middleware
        $in->id = $request->getAttribute("jwt")->user;
        
        // Get a user instance from the container
        $user = $this->container->get('User');
        $user->loadFromId($in->id);

        // Get a comment instance from the container, set info and store
        $comment = $this->container->get('Comment');
        $comment->setPage($in->page);
        $comment->setComment($in->comment);
        if($in->parent) {
            $comment->setParent($in->parent);
            $user->addBadge('replied');
        } else {
            $user->addBadge('commented');
        }
        $comment->create($user);
        $user->save();

        // Notify if needed
        if($in->parent) {
            // Load parent comment
            $parentComment = clone $this->container->get('Comment');
            $parentComment->load($in->parent);
            // Load parent author
            $parentAuthor = clone $this->container->get('User');
            $parentAuthor->loadFromId($parentComment->getUser());
            // Send email 
            $mailKit = $this->container->get('MailKit');
            $mailKit->commentNotify($user, $comment, $parentAuthor, $parentComment);
        }

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
        
        // Strip trailing slashes
        if(substr($in->page,-1) == '/') $in->page = substr($in->page,0,-1);
        
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

    /** Email reply */
    public function emailReply($request, $response, $args)
    {
        // Get info from Mailgun POST
        $in = new \stdClass();
        $in->sender = $this->scrub($request,'sender');
        $in->subject = $this->scrub($request,'subject');
        $in->comment = $this->scrub($request,'stripped-text');
        
        // Load the user from email address
        $user = $this->container->get('User');
        $user->loadFromEmail($in->sender);

        // Load the parent comment
        preg_match('/\[comment\#(\d+)\]/',$in->subject, $match);
        $parentId = $match[1];
        $parentComment = $this->container->get('Comment');
        $parentComment->load($parentId);

        // Get a comment instance from the container, set info and store
        $comment = clone $this->container->get('Comment');
        $comment->setPage($parentComment->getPage());
        $comment->setComment($in->comment);
        $comment->setParent($parentId);
        $comment->create($user);

        // Notify 
        // Load parent author
        $parentAuthor = clone $this->container->get('User');
        $parentAuthor->loadFromId($parentComment->getUser());
        // Send email 
        $mailKit = $this->container->get('MailKit');
        $mailKit->commentNotify($user, $comment, $parentAuthor, $parentComment);

        return $this->prepResponse($response, [
            'result' => 'ok', 
            'message' => 'comment/created',
            'id' => $comment->getId(),
        ]);

    }


    private function loadPageComments($page)
    {
        // Strip trailing slashes
        if(substr($page,-1) == '/') $page = substr($page,0,-1);

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
            `users`.`data`,
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
                $data = json_decode($val->data);
                if(isset($data->badges)) $val->badges = $data->badges;
                unset($val->data);
                $comments[$val->id] = $val;
            }
        } 

        return $comments;
    }

}
