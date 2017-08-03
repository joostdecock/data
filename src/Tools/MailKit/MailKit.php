<?php
/** App\Tools\MailKit class */
namespace App\Tools;

use Mailgun\Mailgun;

/**
 * The MailKit class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class MailKit 
{
    /** @var \Slim\Container $container The container instance */
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }

    public function signUp($user) 
    {
        // FIXME: Handle timeout of the mailgun API gracefully
        // Mailgun API instance
        $mg = $this->initApi();

        if($user->getMigrated() != null) $template= 'migrated';
        else $template = 'default';

        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Confirm your freesewing account', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("signup.$template.txt", $user),
          'html'    => $this->loadTemplate("signup.$template.html", $user),
        ]);
    }

    public function emailChange($user, $newEmailAddress) 
    {
        // Mailgun API instance
        $mg = $this->initApi();
        
        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $newEmailAddress, 
          'cc'      => $user->getEmail(), 
          'subject' => 'Please confirm your new email address', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("newaddress.txt", $user, $newEmailAddress),
          'html'    => $this->loadTemplate("newaddress.html", $user, $newEmailAddress),
        ]);
    }

    public function recover($user) 
    {
        // Mailgun API instance
        $mg = $this->initApi();
        
        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Regain access to your acount', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("recover.txt", $user),
          'html'    => $this->loadTemplate("recover.html", $user),
        ]);
    }

    public function goodbye($user) 
    {
        // Mailgun API instance
        $mg = $this->initApi();
        
        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Goodbye', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("goodbye.txt", $user),
          'html'    => $this->loadTemplate("goodbye.html", $user),
        ]);
    }

    public function commentNotify($user, $comment, $parentAuthor, $parentComment)
    {
        // Mailgun API instance
        $mg = $this->initApi();

        $instance = $this->container['settings']['mailgun']['instance'];
        if($instance == 'master') $replyTo = 'comment@mg.freesewing.org';
        else $replyTo = $this->container['settings']['mailgun']['instance'].'.comment@mg.freesewing.org';

        $templateData = [
            'user' => $user->getUsername(), 
            'comment' => $comment->getComment(), 
            'parentComment' => $parentComment->getComment(), 
            'commentLink' => $this->container['settings']['app']['site'].$comment->getPage().'#comment-'.$comment->getId(), 
            'parentCommentLink' => $this->container['settings']['app']['site'].$parentComment->getPage().'#comment-'.$parentComment->getId(), 
        ];

        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $parentAuthor->getEmail(), 
          'subject' => $user->getUsername().' replied to your comment [comment#'.$comment->getId().']', 
          'h:Reply-To' => $replyTo,
          'text'    => $this->loadTemplate("comment.reply.txt", $user, $templateData),
          'html'    => $this->loadTemplate("comment.reply.html", $user, $templateData),
        ]);
    }

    private function loadTemplate($template, $user, $data=null)
    {
        $t = file_get_contents($this->container['settings']['mailgun']['template_path']."/".$template);
        $search = ['__API__','__SITE__','__STATIC__','__USERNAME__'];
        $replace = [
            $this->container['settings']['app']['data_api'], 
            $this->container['settings']['app']['site'], 
            $this->container['settings']['app']['static_path'], 
            $user->getUsername()
        ];

        switch($template) {
        case 'newaddress.txt':
        case 'newaddress.html':
            array_push($search, '__NEW_ADDRESS__','__OLD_ADDRESS__','__LINK__');
            array_push($replace, $data, $user->getEmail(), $this->container['settings']['app']['site'].'/email-confirm#'.$user->getHandle().'.'.$user->getActivationToken());
            break;
        case 'recover.txt':
        case 'recover.html':
            array_push($search, '__LINK__');
            array_push($replace, $this->container['settings']['app']['site'].'/reset#'.$user->getHandle().'.'.$user->getResetToken());
            break;
        case 'comment.reply.txt':
        case 'comment.reply.html':
            array_push($search, '__AUTHOR__','__COMMENT__','__COMMENT_LINK__','__PARENT_COMMENT__','__PARENT_COMMENT_LINK__');
            array_push($replace, $data['user'], $data['comment'], $data['commentLink'], $data['parentComment'], $data['parentCommentLink']);
            break;
        default:
            array_push($search, '__LINK__');
            array_push($replace, $this->container['settings']['app']['site'].'/confirm#'.$user->getHandle().'.'.$user->getActivationToken());
        }

        return str_replace($search, $replace, $t);
    }

    private function initApi() 
    {
        return Mailgun::create($this->container['settings']['mailgun']['api_key']);
    }    
}
