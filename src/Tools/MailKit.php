<?php
/** Freesewing\Data\Tools\MailKit class */
namespace Freesewing\Data\Tools;

use Mailgun\Mailgun;
use Symfony\Component\Yaml\Yaml;

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

    /** Returns template content */
    private function loadTemplate($scenario, $format='html')
    {
        return  file_get_contents($this->container['settings']['swiftmailer']['templates']."/$scenario.$format");
    }

    /** Loads a language  */
    private function loadLanguage($locale)
    {
        if(!in_array($locale, $this->container['settings']['i18n']['locales'])) $locale = 'en';
        
        return Yaml::parse(file_get_contents($this->container['settings']['i18n']['translations']."/$locale.yaml"));
    }

    public function signUp($task) 
    {
        // Load email template              
        $html = $this->loadTemplate('signup', 'html');
        $txt  = $this->loadTemplate('signup', 'txt');

        // Load translations
        $i18n = $this->loadLanguage($task->data->getNode('locale'));
        
        // Load text replacements from language
        $search = [
            '__LINK__',
            '__HIDDEN_PREHEADER_TEXT__',
            '__FREESEWING__',
            '__PLEASE_CONFIRM__',
            '__WELCOME__',
            '__THANK_YOU_NOW_CLICK_BUTTON__',
            '__THANK_YOU_NOW_CLICK_LINK__',
            '__CONFIRM_EMAIL__',
            '__OR_PASTE_LINK__',
            '__QUESTIONS_JUST_REPLY__',
            '__SIGNATURE__',
            '__REASON_WHY__',
            '__SLOGAN__',
            '__CREDITS__',
            '__WEBSITE__',
            '__CHAT_ON_GITTER__',
            '__TWITTER__',
            '__INSTAGRAM__',
            '__GITHUB__',
            '__LOCALE__',
        ]; 
        $replace = [
            $this->container['settings']['app']['site'].'/confirm/'.$task->data->getNode('hash'),
            $i18n['happyNewUser'],
            $i18n['freesewing'],
            $i18n['pleaseConfirmEmail'],
            $i18n['welcomeAboard'],
            $i18n['thankYouNowConfirmButton'],
            $i18n['thankYouNowConfirmLink'],
            $i18n['confirmYourAddress'],
            $i18n['pasteLink'],
            $i18n['questionsJustReply'],
            $i18n['signature'],
            $i18n['whySignup'],
            $i18n['slogan'],
            $i18n['credits'],
            $i18n['website'],
            $i18n['chatOnGitter'],
            $i18n['twitter'],
            $i18n['instagram'],
            $i18n['github'],
            $task->data->getNode('locale')
        ];
         
        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($i18n['confirmYourAddress']))
                ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
                ->setTo($task->data->getNode('email'))
                ->setBody(str_replace($search, $replace, $txt))
                ->addPart(str_replace($search, $replace, $html), 'text/html')
        ;
        return $mailer->send($message);
    }

    public function patron($user) 
    {
        if($user->getPatronTier() == 2) $template= "patron.welcome.2";
        else $template= 'patron.welcome.'.$user->getPatronTier();

        // Always send patron email through Gmail so we have a paper trail
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('Thank you for your support'))
                ->setFrom(['joost@decock.org' => 'Joost from freesewing'])
                ->setTo($user->getEmail())
                ->setBody($this->loadTemplate("$template.txt", $user))
                ->addPart($this->loadTemplate("$template.html", $user), 'text/html')
        ;
        $mailer->send($message);

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
            $message = (new \Swift_Message('Confirm your freesewing account'))
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
            $message = (new \Swift_Message('Regain access to your acount'))
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


    private function REMOVEMEloadTemplate($template, $user=false, $data=null)
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
            array_push($replace, $this->container['settings']['app']['site'].'/confirm/#'.$user->getHandle().'.'.$user->getActivationToken());
        }

        return str_replace($search, $replace, $t);
    }
}
