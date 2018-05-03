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
        
        // Construct confirmation link
        if($task->data->getNode('locale') == 'en') $lang = '';
        else $lang = '/'.$task->data->getNode('locale');
        $link = $this->container['settings']['app']['site'].$lang.'/signup/confirm/'.$task->data->getNode('hash');
        
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
            $link,
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

        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('Thank you for your support'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($user->getEmail())
                ->setBody($this->loadTemplate("$template.txt", $user))
                ->addPart($this->loadTemplate("$template.html", $user), 'text/html')
        ;
        $mailer->send($message);

        return true;
    }

    public function emailChange($task, $newEmailAddress) 
    {
        $text = $this->loadTemplate("newaddress.txt")
        $html = $this->loadTemplate("newaddress.html")
        
        // Load translations
        $i18n = $this->loadLanguage($task->data->getNode('locale'));

        // Load text replacements from language
        $search = [
            '__LINK__',
            '__USERNAME__',
            '__NEW_ADDRESS__',
            '__OLD_ADDRESS__',
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
            $link,
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


        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('Confirm your freesewing account'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($data->email)
                ->setCc($data->oldemail)
                ->setBody($text)
                ->addPart($html)
            ;
            $mailer->send($message);

        return true;
    }

    public function recover($user) 
    {
        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('Regain access to your acount'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($user->getEmail())
                ->setBody($this->loadTemplate("recover.txt", $user))
                ->addPart($this->loadTemplate("recover.sep.html", $user), 'text/html')
        ;
        $mailer->send($message);

        return true;
    }

    public function goodbye($user) 
    {
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('Goodbye'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($user->getEmail())
                ->setBody($this->loadTemplate("goodbye.txt", $user))
                ->addPart($this->loadTemplate("goodbye.html", $user), 'text/html')
        ;
        $mailer->send($message);
    }

    public function commentNotify($user, $comment, $parentAuthor, $parentComment)
    {
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

        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($user->getUsername().' replied to your comment [comment#'.$comment->getId().']'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($parentAuthor->getEmail())
                ->setReplyTo($replyTo)
                ->setBody($this->loadTemplate("goodbye.txt", $user, $templateData))
                ->addPart($this->loadTemplate("goodbye.html", $user, $templateData), 'text/html')
        ;
        $mailer->send($message);
    }


    public function profileCommentNotify($user, $comment, $profile)
    {
        $instance = $this->container['settings']['mailgun']['instance'];
        if($instance == 'master') $replyTo = 'comment@mg.freesewing.org';
        else $replyTo = $this->container['settings']['mailgun']['instance'].'.comment@mg.freesewing.org';

        $templateData = [
            'user' => $user->getUsername(), 
            'comment' => $comment->getComment(), 
            'commentLink' => $this->container['settings']['app']['site'].$comment->getPage().'#comment-'.$comment->getId(), 
        ];

        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($user->getUsername().' commented on your profile page [comment#'.$comment->getId().']'))
                ->setFrom(['info@freesewing.org' => 'Joost from freesewing'])
                ->setTo($profile->getEmail())
                ->setReplyTo($replyTo)
                ->setBody($this->loadTemplate("profilecomment.reply.txt", $user, $templateData))
                ->addPart($this->loadTemplate("profilecomment.reply.html", $user, $templateData), 'text/html')
        ;
        $mailer->send($message);
    }
}
