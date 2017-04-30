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

    private function loadTemplate($template, $user)
    {
        $t = file_get_contents($this->container['settings']['mailgun']['template_path']."/".$template);
        switch($template) {
        case 'recover.txt':
        case 'recover.html':
            $search = ['__LINK__','__USERNAME__'];
            $replace = [
                $this->container['settings']['app']['site'].'/account/reset#'.$user->getHandle().'.'.$user->getResetToken(), 
                $user->getUsername()
            ];
            break;
        default:
            $search = ['__LINK__','__USERNAME__'];
            $replace = [
                $this->container['settings']['app']['site'].'/account/confirm#'.$user->getHandle().'.'.$user->getActivationToken(), 
                $user->getUsername()
            ];
        }

        return str_replace($search, $replace, $t);
    }

    private function initApi() 
    {
        return Mailgun::create($this->container['settings']['mailgun']['api_key']);
    }    
}
