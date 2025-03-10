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

require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthSocialProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthGoogleProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthAppleProvider.php';

class PSLAuthCallbackModuleFrontController extends ModuleFrontController
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
        $code = Tools::getValue('code', '');
        $error = Tools::getValue('error', '');
        
        // Handle error from OAuth provider
        if (!empty($error)) {
            $this->errors[] = $this->trans('Authentication failed: %error%', ['%error%' => $error], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
            return;
        }
        
        // Validate required parameters
        if (empty($provider)) {
            $this->errors[] = $this->trans('Invalid authentication request', [], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
            return;
        }
        
        switch ($provider) {
            case 'google':
                if (empty($code)) {
                    $this->errors[] = $this->trans('Invalid authentication request', [], 'Modules.Pslauth.Shop');
                    $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                    return;
                }
                $this->processGoogleCallback($code);
                break;
            
            case 'apple':
                // Apple returns code in POST data after form_post response_mode
                $this->processAppleCallback($code);
                break;
            
            default:
                // Invalid provider, redirect to login page
                $this->errors[] = $this->trans('Invalid authentication provider', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                break;
        }
    }
    
    /**
     * Process Google login callback
     * 
     * @param string $code Authorization code
     */
    protected function processGoogleCallback($code)
    {
        $provider = new PSLAuthGoogleProvider();
        
        if (!$provider->isEnabled()) {
            $this->errors[] = $this->trans('Google login is not enabled', [], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
            return;
        }
        
        try {
            // Get user data using the authorization code
            $userData = $provider->getUserDataFromCode($code);
            
            if (!$userData) {
                $this->errors[] = $this->trans('Failed to get user data from Google', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                return;
            }
            
            // Log user data for debugging (in development environment only)
            if (_PS_MODE_DEV_) {
                PrestaShopLogger::addLog('Google user data: ' . json_encode($userData), 1);
            }
            
            // Process login with user data
            $customer = $provider->processLogin($userData);
            
            if (!$customer) {
                $this->errors[] = $this->trans('Failed to create or log in user', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                return;
            }
            
            // Log in the customer
            $this->context->updateCustomer($customer);
            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);
            
            // Update cart for this customer
            $this->context->cart->id_customer = $customer->id;
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
            $this->context->cookie->id_cart = (int) $this->context->cart->id;
            $this->context->cookie->write();
            $this->context->cart->autosetProductAddress();
            
            // Success message
            $this->success[] = $this->trans('Successfully logged in with Google', [], 'Modules.Pslauth.Shop');
            
            // Determine redirect URL
            $redirectUrl = $this->context->link->getPageLink('my-account');
            
            // Check if we have a stored back URL
            if (isset($this->context->cookie->pslauth_back_url)) {
                $back = $this->context->cookie->pslauth_back_url;
                $this->context->cookie->pslauth_back_url = null;
                
                // Validate the URL to prevent open redirect vulnerabilities
                if (Validate::isUrl($back) && strpos($back, Tools::getShopDomain()) !== false) {
                    $redirectUrl = $back;
                }
            }
            
            Tools::redirect($redirectUrl);
        } catch (Exception $e) {
            // Log the error
            PrestaShopLogger::addLog('Google authentication error: ' . $e->getMessage(), 3);
            
            // Show error to user
            $this->errors[] = $this->trans('An error occurred during authentication: %error%', ['%error%' => $e->getMessage()], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
        }
    }
    
    /**
     * Process Apple login callback
     * 
     * @param string $code Authorization code (optional, may be in POST)
     */
    protected function processAppleCallback($code)
    {
        $provider = new PSLAuthAppleProvider();
        
        if (!$provider->isEnabled()) {
            $this->errors[] = $this->trans('Apple login is not enabled', [], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
            return;
        }
        
        try {
            // If code is not provided, try to get it from POST data (Apple uses form_post response_mode)
            if (empty($code) && isset($_POST['code'])) {
                $code = $_POST['code'];
            }
            
            // Verify we have a code
            if (empty($code)) {
                $this->errors[] = $this->trans('Invalid authentication request', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                return;
            }
            
            // Get user data using the authorization code
            $userData = $provider->getUserDataFromCode($code);
            
            if (!$userData) {
                $this->errors[] = $this->trans('Failed to get user data from Apple', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                return;
            }
            
            // Log user data for debugging (in development environment only)
            if (_PS_MODE_DEV_) {
                PrestaShopLogger::addLog('Apple user data: ' . json_encode($userData), 1);
            }
            
            // Process login with user data
            $customer = $provider->processLogin($userData);
            
            if (!$customer) {
                $this->errors[] = $this->trans('Failed to create or log in user', [], 'Modules.Pslauth.Shop');
                $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
                return;
            }
            
            // Log in the customer
            $this->context->updateCustomer($customer);
            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);
            
            // Update cart for this customer
            $this->context->cart->id_customer = $customer->id;
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
            $this->context->cookie->id_cart = (int) $this->context->cart->id;
            $this->context->cookie->write();
            $this->context->cart->autosetProductAddress();
            
            // Success message
            $this->success[] = $this->trans('Successfully logged in with Apple', [], 'Modules.Pslauth.Shop');
            
            // Determine redirect URL
            $redirectUrl = $this->context->link->getPageLink('my-account');
            
            // Check if we have a stored back URL
            if (isset($this->context->cookie->pslauth_back_url)) {
                $back = $this->context->cookie->pslauth_back_url;
                $this->context->cookie->pslauth_back_url = null;
                
                // Validate the URL to prevent open redirect vulnerabilities
                if (Validate::isUrl($back) && strpos($back, Tools::getShopDomain()) !== false) {
                    $redirectUrl = $back;
                }
            }
            
            Tools::redirect($redirectUrl);
        } catch (Exception $e) {
            // Log the error
            PrestaShopLogger::addLog('Apple authentication error: ' . $e->getMessage(), 3);
            
            // Show error to user
            $this->errors[] = $this->trans('An error occurred during authentication: %error%', ['%error%' => $e->getMessage()], 'Modules.Pslauth.Shop');
            $this->redirectWithNotifications($this->context->link->getModuleLink('pslauth', 'login'));
        }
    }
}