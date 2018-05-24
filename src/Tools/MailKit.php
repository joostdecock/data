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
        $dir = $this->container['settings']['swiftmailer']['templates'];
        if($format === 'txt') {
            $template = file_get_contents("$dir/$scenario.txt");
            $template.= file_get_contents("$dir/blocks/txt/footer.txt");
        } else {
            $template = file_get_contents("$dir/blocks/html/open.html");
            $template.= file_get_contents("$dir/$scenario.html");
            $template.= file_get_contents("$dir/blocks/html/close.html");
        }

        return  $template;
    }

    /** Loads a language, falls back to en (English)  */
    private function loadLanguage($locale='en')
    {
        $dir = $this->container['settings']['i18n']['translations'];
        $file = (is_readable("$dir/$locale.yaml")  ? "$dir/$locale.yaml" : "$dir/en.yaml");
        
        $data = Yaml::parse(file_get_contents($file));
        foreach($data as $key => $value) {
            $data[$key] = str_replace(['DONE', 'TODO'], ['',''], $value);
        }
        
        return $data;
    }

    /* Send signup E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->hash => the has to look up the confirmation
     * $data->email => The user's email
     * $data->username => The user's username
     */
    public function signUp($data) 
    {
        // Load language file
        $i18n = $this->loadLanguage($data->locale);
        
        // Construct confirmation link
        if($data->locale == 'en') $lang = '';
        else $lang = '/'.$data->locale;

        // Add remaining tokens
        $i18n['LINK'] = $this->container['settings']['app']['site'].$lang.'/signup/confirm/'.$data->hash;
        $i18n['OPENING_LINE'] = $i18n['happyNewUser'];
        $i18n['HIDDEN'] = $i18n['happyNewUser'];
        $i18n['WHY'] = $i18n['whySignup'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('signup', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('signup',  'txt'));

        // Deliver message 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($i18n['welcomeAboard']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* Patron welcome E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The patron's email
     * $data->tier => The patron's tier
     */
    public function patron($data) 
    {
        // Load language file
        $i18n = $this->loadLanguage($data->locale);

        // Add tokens
        $i18n['OPENING_LINE'] = $i18n['wowYouAreAwesome'];
        $i18n['HIDDEN'] = $i18n['wowYouAreAwesome'];
        $i18n['WHY'] = $i18n['whyPatron'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('patron.welcome.'.$data->tier, 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('patron.welcome.'.$data->tier,  'txt'));

        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($i18n['thankYouForYourSupport']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;
    
        return $mailer->send($message);
    }

    /* New E-mail address E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->newemail => The user's new email
     * $data->oldemail => The user's old email
     * $data->hash => The hash to retrieve the confirmation
     */
    public function emailChange($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        $search = ['{username}', '{newAddress}', '{oldAddress}'];
        $replace = [$data->username, $data->newemail, $data->oldemail];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Construct confirmation link
        if($data->locale == 'en') $lang = '';
        else $lang = '/'.$data->locale;

        // Add remaining tokens
        $i18n['LINK'] = $this->container['settings']['app']['site'].$lang.'/email/confirm/'.$data->hash;
        $i18n['OPENING_LINE'] = $i18n['pleaseConfirmEmail'];
        $i18n['HIDDEN'] = $i18n['pleaseConfirmEmail'];
        $i18n['WHY'] = $i18n['whyEmailChange'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('newaddress', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('newaddress',  'txt'));

        // Deliver message 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($i18n['pleaseConfirmEmail']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->newemail)
            ->setCc($data->oldemail)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* Recover password E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     * $data->username => The user's username
     * $data->hash => The hash to retrieve the confirmation
     */
    public function recoverPassword($data) 
    {
        // Load language file and replace tokens
        $i18n = [];
        $search = ['{username}'];
        $replace = [$data->username];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Construct confirmation link
        if($data->locale == 'en') $lang = '';
        else $lang = '/'.$data->locale;

        // Add remaining tokens
        $i18n['LINK'] = $this->container['settings']['app']['site'].$lang.'/account/recover/'.$data->hash;
        $i18n['OPENING_LINE'] = $i18n['itSeemsYouForgotYourPassword'];
        $i18n['HIDDEN'] = $i18n['itSeemsYouForgotYourPassword'];
        $i18n['WHY'] = $i18n['whyRecover'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('recover', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('recover',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message($i18n['regainAccessToYourAccount']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* Goodbye E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     */
    public function goodbye($data) 
    {
        // Load language
        $i18n = $this->loadLanguage($data->locale);

        // Add tokens
        $i18n['OPENING_LINE'] = $i18n['canWeRemainFriends'];
        $i18n['HIDDEN'] = $i18n['canWeRemainFriends'];
        $i18n['WHY'] = $i18n['whyGoobye'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('goodbye', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('goodbye',  'txt'));
        
        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('😞 '.$i18n['goodbye']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* Comment reply notification E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     * $data->author => Username of the comment author
     * $data->comment => The reply comment
     * $data->commentId => ID of the reply comment
     * $data->commentLink => Link to the reply comment
     * $data->parentComment => The parent comment
     * $data->parentCommentLink => Link to the parent comment
     *
     */
    public function commentReply($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        $search = ['{author}'];
        $replace = [$data->author];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Construct confirmation link
        if($data->locale == 'en') $lang = '';
        else $lang = '/'.$data->locale;

        // Add remaining tokens
        $i18n['COMMENT'] = $data->comment;
        $i18n['COMMENT_LINK'] = $data->commentLink;
        $i18n['PARENT_COMMENT'] = $data->parentComment;
        $i18n['PARENT_COMMENT_LINK'] = $data->parentCommentLink;
        $i18n['OPENING_LINE'] = $i18n['authorPostedAReply'];
        $i18n['HIDDEN'] = $i18n['authorPostedAReply'];
        $i18n['WHY'] = $i18n['whyCommentReply'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('comment.reply', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('comment.reply',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('💬  '.$i18n['authorPostedAReply'].' [comment#'.$data->commentId.']'))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setReplyTo('comment@mg.freesewing.org')
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }


    /* Profile comment notification E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     * $data->author => Username of the comment author
     * $data->comment => The comment
     * $data->commentLink => Link to the comment
     *
     */
    public function commentProfile($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        $search = ['{author}'];
        $replace = [$data->author];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Construct confirmation link
        if($data->locale == 'en') $lang = '';
        else $lang = '/'.$data->locale;

        // Add remaining tokens
        $i18n['COMMENT'] = $data->comment;
        $i18n['COMMENT_LINK'] = $data->commentLink;
        $i18n['OPENING_LINE'] = $i18n['authorCommentedOnYourProfilePage'];
        $i18n['HIDDEN'] = $i18n['authorCommentedOnYourProfilePage'];
        $i18n['WHY'] = $i18n['whyProfileComment'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('profilecomment.reply', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('profilecomment.reply',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('💬 ' 
            .$i18n['authorCommentedOnYourProfilePage'].' [comment#'.$data->commentId.']'))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setReplyTo('comment@mg.freesewing.org')
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);

    }

    /* Profile consent given reminder E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     *
     */
    public function profileConsentGiven($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Add remaining tokens
        $i18n['OPENING_LINE'] = $i18n['betterToAsk'];
        $i18n['HIDDEN'] = $i18n['consentGiven'];
        $i18n['WHY'] = $i18n['whyConsent'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('consent.profile', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('consent.profile',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('🙌 ' 
            .$i18n['consentGiven']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* Model consent given reminder E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     *
     */
    public function modelConsentGiven($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Add remaining tokens
        $i18n['OPENING_LINE'] = $i18n['betterToAsk'];
        $i18n['HIDDEN'] = $i18n['consentGiven'];
        $i18n['WHY'] = $i18n['whyConsent'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('consent.model', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('consent.model',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('🙌 ' 
            .$i18n['consentGiven']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);
    }

    /* User removed, goodbye E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     *
     */
    public function userRemoved($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Add remaining tokens
        $i18n['OPENING_LINE'] = $i18n['canWeRemainFriends'];
        $i18n['HIDDEN'] = $i18n['canWeRemainFriends'];
        $i18n['WHY'] = $i18n['whyGoodbye'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('goodbye', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('goodbye',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('😞 ' 
            .$i18n['thankYouForGivingFreesewingAChance']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);

    }

    /* User removed, goodbye E-mail, expects the following data:
     *
     * $data->locale => language
     * $data->email => The user's email
     * $data->link => Download link
     *
     */
    public function dataExport($data)
    {
        // Load language file and replace tokens
        $i18n = [];
        foreach($this->loadLanguage($data->locale) as $key => $val) {
            $i18n[$key] = str_replace($search, $replace, $val);
        }
        
        // Add remaining tokens
        $i18n['LINK'] = $data->link;
        $i18n['OPENING_LINE'] = $i18n['deliveryFromFreesewing'];
        $i18n['HIDDEN'] = $i18n['hereIsYourData'];
        $i18n['WHY'] = $i18n['whyExport'];

        // Load email template and replace tokens       
        $search = [];
        foreach($i18n as $key => $val) $search[] = '__'.$key.'__';
        $replace = array_values($i18n);
        $html = str_replace($search, $replace, $this->loadTemplate('export', 'html'));
        $txt  = str_replace($search, $replace, $this->loadTemplate('export',  'txt'));

        // Send email via swiftmailer 
        $mailer = $this->container->get('SwiftMailer');
        $message = (new \Swift_Message('📦 ' 
            .$i18n['hereIsYourData']))
            ->setFrom([$this->container['settings']['swiftmailer']['from'] => $i18n['senderName']])
            ->setTo($data->email)
            ->setBody($txt)
            ->addPart($html, 'text/html')
        ;

        return $mailer->send($message);

    }
}
