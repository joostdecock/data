<?php
/** Freesewing\Data\Controllers\CommentController class */
namespace Freesewing\Data\Controllers;

use \Freesewing\Data\Data\Comment as Comment;
use \Freesewing\Data\Tools\Utilities as Utilities;

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

    /** Create comment */
    public function create($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->page = Utilities::scrub($request,'page');
        $in->comment = Utilities::scrub($request,'comment');
        $in->parent = Utilities::scrub($request,'parent');
        
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
            // Don't notify when replying to own comment
            if($parentComment->getUser() != $user->getId()) {
                // Load parent author
                $parentAuthor = clone $this->container->get('User');
                $parentAuthor->loadFromId($parentComment->getUser());
                // Send email 
                $mailKit = $this->container->get('MailKit');
                $mailKit->commentNotify($user, $comment, $parentAuthor, $parentComment);
            }
        }
        if(substr($in->page,0,7) == '/users/') {
            // Comment on profile page. Notify owner
            $handle = substr($in->page,7);
            if($handle != $user->getHandle()) {
                // Get a user instance from the container
                $profile = clone $this->container->get('User');
                $profile->loadFromHandle($handle);
                if(!isset($mailkit)) $mailKit = $this->container->get('MailKit');
                $mailKit->profileCommentNotify($user, $comment, $profile);
            }
        } else $handle = false;

        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'message' => 'comment/created',
            'id' => $comment->getId(),
        ], 200, $this->container['settings']['app']['origin']);
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
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'count' => count($comments),
            'comments' => $comments,
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Get recent comments */
    public function recentComments($request, $response, $args) 
    {
        // Handle request
        $in = new \stdClass();
        $in->count = filter_var($args['count'], FILTER_SANITIZE_NUMBER_INT);
        
        $comments = $this->loadRecentComments($in->count);
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'count' => count($comments),
            'comments' => $comments,
        ], 200, $this->container['settings']['app']['origin']);
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
            return Utilities::prepResponse($response, [
                'result' => 'error', 
                'reason' => 'not_your_comment', 
            ], 400, $this->container['settings']['app']['origin']);
        }
        
        $comment->remove();
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'reason' => 'comment_removed', 
        ], 200, $this->container['settings']['app']['origin']);
    }

    /** Email reply */
    public function emailReply($request, $response, $args)
    {
        // Get info from Mailgun POST
        $in = new \stdClass();
        $in->sender = Utilities::scrub($request,'sender');
        $in->subject = Utilities::scrub($request,'subject');
        $in->comment = Utilities::scrub($request,'stripped-text');
        
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
        // Don't notify when replying to own comment
        if($parentComment->getUser() != $user->getId()) {
            // Send email 
            $mailKit = $this->container->get('MailKit');
            $mailKit->commentNotify($user, $comment, $parentAuthor, $parentComment);
        }
        
        return Utilities::prepResponse($response, [
            'result' => 'ok', 
            'message' => 'comment/created',
            'id' => $comment->getId(),
        ], 200, $this->container['settings']['app']['origin']);

    }


    private function loadPageComments($page)
    {
        // Strip trailing slashes
        if(substr($page,-1) == '/') $page = substr($page,0,-1);

        return $this->loadComments('page', $page);
    }

    private function loadRecentComments($count)
    {
        if(!is_numeric($count)) $count = 5;
        if($count > 100) $count = 100;
        
        $db = $this->container->get('db');
        $sql = "SELECT 
            `comments`.`id`,
            `comments`.`user`,
            `comments`.`comment`,
            `comments`.`page`,
            `comments`.`time`,
            `comments`.`status`,
            `users`.`username`,
            `users`.`picture`,
            `users`.`data`,
            `users`.`handle` as userhandle
            from `comments`,`users` 
            WHERE `comments`.`user` = `users`.`id`
            ORDER BY `comments`.`time` DESC LIMIT $count";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;
        
        if(!$result) return false;
        else {
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            foreach($result as $key => $val) {
                $val->picture = '/static'.$avatarKit->getDir($val->userhandle).'/'.$val->picture;
                $data = json_decode($val->data);
                if(isset($data->badges)) $val->badges = $data->badges;
                if(isset($data->social)) $val->social = $data->social;
                if(isset($data->patron)) $val->patron = $data->patron;
                unset($val->data);
                $comments[$val->id] = $val;
            }
        } 

        return $comments;
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
        $db = null;
        
        if(!$result) return false;
        else {
            // Get the AvatarKit to get the avatar url
            $avatarKit = $this->container->get('AvatarKit');
            foreach($result as $key => $val) {
                $val->picture = '/static'.$avatarKit->getDir($val->userhandle).'/'.$val->picture;
                $data = json_decode($val->data);
                if(isset($data->badges)) $val->badges = $data->badges;
                if(isset($data->social)) $val->social = $data->social;
                if(isset($data->patron)) $val->patron = $data->patron;
                unset($val->data);
                $comments[$val->id] = $val;
            }
        } 

        return $comments;
    }

}
