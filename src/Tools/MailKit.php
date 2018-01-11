<?php
/** Freesewing\Data\Tools\MailKit class */
namespace Freesewing\Data\Tools;

use Mailgun\Mailgun;
use SwiftMessage;

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

    /** Checks wheter an email address is from a shitty email provider (SEP) */
    private function isSep($email)
    {
        if (in_array(substr($email, strrpos($email, '@')+1), $this->container['settings']['swiftmailer']['domains'])) return true;
        else return false;
    }

    public function signUp($user) 
    {
        // FIXME: Handle timeout of the mailgun API gracefully
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');

        if($user->getMigrated() != null) $template= 'migrated';
        else $template = 'default';

        // Send through mailgun
        try {
            $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Confirm your freesewing account', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("signup.$template.txt", $user),
          'html'    => $this->loadTemplate("signup.$template.html", $user),
        ]);
        } catch (Exception $e) {
            die( 'Caught exception: '.  $e->getMessage(). "\n");
        }

        // Also send through Gmail if it's a SEP domain
        // SEP: Shitty email provider
        if($this->isSep($user->getEmail())) {
            // Send email via swiftmailer 
            $mailer = $this->container->get('SwiftMailer');
            $message = (new Swift_Message('Confirm your freesewing account'))
                  ->setFrom(['joost@decock.org' => 'Joost from freesewing'])
                  ->setTo($user->getEmail())
                  ->setBody($this->loadTemplate("signup.$template.txt", $user))
                  ->addPart($this->loadTemplate("signup.$template.sep.html", $user), 'text/html')
            ;
            $mailer->send($message);
        }

        return true;
    }

    public function patron($user) 
    {
        // FIXME: Handle timeout of the mailgun API gracefully
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');

        $data = $user->getData();
        $tier =  $data->patron->tier;

        if($tier == 2) $template= "patron.welcome.2";
        else {
            $address = $data->patron->address;
            if(strlen($address)>10) $template= "patron.welcome.confirm-address.$tier";
            else $template= "patron.welcome.provide-address.$tier";
        }

        // Send through mailgun
        $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Thank you for your support', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("$template.txt", $user),
          'html'    => $this->loadTemplate("$template.html", $user),
        ]);

        // Also send through Gmail if it's a SEP domain
        // SEP: Shitty email provider
        if($this->isSep($user->getEmail())) {
            // Send email via swiftmailer 
            $mailer = $this->container->get('SwiftMailer');
            $message = (new Swift_Message('Thank you for your support'))
                  ->setFrom(['joost@decock.org' => 'Joost from freesewing'])
                  ->setTo($user->getEmail())
                  ->setBody($this->loadTemplate("$template.txt", $user))
                  ->addPart($this->loadTemplate("$template.html", $user), 'text/html')
            ;
            $mailer->send($message);
        }

        return true;
    }

    public function emailChange($user, $newEmailAddress) 
    {
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');
        
        $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $newEmailAddress, 
          'cc'      => $user->getEmail(), 
          'subject' => 'Please confirm your new email address', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("newaddress.txt", $user, $newEmailAddress),
          'html'    => $this->loadTemplate("newaddress.html", $user, $newEmailAddress),
        ]);
        
        // Also send through Gmail if it's a SEP domain
        // SEP: Shitty email provider
        if($this->isSep($user->getEmail()) || $this->isSep($newEmailAddress)) {
            // Send email via swiftmailer 
            $mailer = $this->container->get('SwiftMailer');
            $message = (new Swift_Message('Confirm your freesewing account'))
                  ->setFrom(['joost@decock.org' => 'Joost from freesewing'])
                  ->setTo($newEmailAddress)
                  ->setCc($user->getEmail())
                  ->setBody($this->loadTemplate("newaddress.txt", $user, $newEmailAddress))
                  ->addPart($this->loadTemplate("newaddress.sep.html", $user, $newEmailAddress), 'text/html')
            ;
            $mailer->send($message);
        }

        return true;
    }

    public function recover($user) 
    {
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');
        
        $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $user->getEmail(), 
          'subject' => 'Regain access to your acount', 
          'h:Reply-To' => 'Joost De Cock <joost@decock.org>',
          'text'    => $this->loadTemplate("recover.txt", $user),
          'html'    => $this->loadTemplate("recover.html", $user),
        ]);
        
        // Also send through Gmail if it's a SEP domain
        // SEP: Shitty email provider
        if($this->isSep($user->getEmail())) {
            // Send email via swiftmailer 
            $mailer = $this->container->get('SwiftMailer');
            $message = (new Swift_Message('Regain access to your acount'))
                  ->setFrom(['joost@decock.org' => 'Joost from freesewing'])
                  ->setTo($user->getEmail())
                  ->setBody($this->loadTemplate("recover.txt", $user))
                  ->addPart($this->loadTemplate("recover.sep.html", $user), 'text/html')
            ;
            $mailer->send($message);
        }

        return true;
    }

    public function goodbye($user) 
    {
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');
        
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
        $mg = $this->container->get('Mailgun');

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


    public function profileCommentNotify($user, $comment, $profile)
    {
        // Mailgun API instance
        $mg = $this->container->get('Mailgun');

        $instance = $this->container['settings']['mailgun']['instance'];
        if($instance == 'master') $replyTo = 'comment@mg.freesewing.org';
        else $replyTo = $this->container['settings']['mailgun']['instance'].'.comment@mg.freesewing.org';

        $templateData = [
            'user' => $user->getUsername(), 
            'comment' => $comment->getComment(), 
            'commentLink' => $this->container['settings']['app']['site'].$comment->getPage().'#comment-'.$comment->getId(), 
        ];

        return $mg->messages()->send('mg.freesewing.org', [
          'from'    => 'Joost from Freesewing <mg@freesewing.org>', 
          'to'      => $profile->getEmail(), 
          'subject' => $user->getUsername().' commented on your profile page [comment#'.$comment->getId().']', 
          'h:Reply-To' => $replyTo,
          'text'    => $this->loadTemplate("profilecomment.reply.txt", $user, $templateData),
          'html'    => $this->loadTemplate("profilecomment.reply.html", $user, $templateData),
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
        case 'profilecomment.reply.txt':
        case 'profilecomment.reply.html':
            array_push($search, '__AUTHOR__','__COMMENT__','__COMMENT_LINK__');
            array_push($replace, $data['user'], $data['comment'], $data['commentLink']);
            break;
        case 'patron.welcome.confirm-address.4.txt':
        case 'patron.welcome.confirm-address.8.txt':
            array_push($search, '__ADDRESS__');
            array_push($replace, $user->getData()->patron->address);
            break;
        case 'patron.welcome.confirm-address.4.html':
        case 'patron.welcome.confirm-address.8.html':
            array_push($search, '__ADDRESS__');
            array_push($replace, nl2br($user->getData()->patron->address));
            break;
        default:
            array_push($search, '__LINK__');
            array_push($replace, $this->container['settings']['app']['site'].'/confirm#'.$user->getHandle().'.'.$user->getActivationToken());
        }

        return str_replace($search, $replace, $t);
    }
}
