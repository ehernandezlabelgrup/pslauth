<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    Emilio Hernandez <ehernandez@okoiagency.com>
 * @copyright OKOI AGENCY S.L.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/PSLAuthSocialProvider.php';
require_once dirname(__FILE__) . '/../../classes/PSLAuthGoogleProvider.php';

class PSLAuthSocialModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be redirected to authentication page
     */
    public $auth = false;

    public function init()
    {
        parent::init();
        
        // Redirect to my-account if already logged in
        if ($this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=my-account');
        }
    }

    public function postProcess()
    {
        $provider = Tools::getValue('provider', '');
        $back = Tools::getValue('back', '');
        
        // Store back URL for later redirection after login
        if (!empty($back)) {
            $this->context->cookie->pslauth_back_url = $back;
        }
        
        switch ($provider) {
            case 'google':
                $this->processGoogleLogin();
                break;
            
            default:
                // Invalid provider, redirect to login page
                Tools::redirect($this->context->link->getModuleLink('pslauth', 'login'));
                break;
        }
    }
    
    /**
     * Process Google login
     */
    protected function processGoogleLogin()
    {
        $provider = new PSLAuthGoogleProvider();
        
        if (!$provider->isEnabled()) {
            $this->errors[] = $this->trans('Google login is not enabled', [], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
            return;
        }
        
        $authUrl = $provider->getAuthUrl();
        Tools::redirect($authUrl);
    }
}