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

class PSLAuthHookHandler
{
    private $module;
    private $context;

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    /**
     * Hook for adding content to the header of the front office
     * 
     * @return string Template content
     */
    public function handleDisplayHeader()
    {
        $this->context->smarty->assign([
            'pslauth_live_mode' => Configuration::get('PSLAUTH_LIVE_MODE'),
            'pslauth_social_enabled' => true, // Activa los botones sociales en templates
            'pslauth_google_enabled' => Configuration::get('PSLAUTH_GOOGLE_ENABLED'),
            'pslauth_apple_enabled' => Configuration::get('PSLAUTH_APPLE_ENABLED'),
        ]);
        
    }

    /**
     * Hook for adding content to the header of the back office
     */
    public function handleDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->module->name) {
            $this->context->controller->addCSS($this->module->getLocalPath() . 'views/css/admin.css');
        }
    }

    /**
     * Hook for adding media to the front controller
     */
    public function handleActionFrontControllerSetMedia()
    {
        $this->context->controller->addJS($this->module->getLocalPath() . 'views/js/front.js');
                $this->context->controller->addCSS($this->module->getLocalPath() . 'views/css/front.css');
    }

    /**
     * Hook for overriding templates
     * 
     * @param array $params Hook parameters
     * @return bool|string Override template path or false
     */
    public function handleDisplayOverrideTemplate($params)
    {
        // Check if this is an authentication controller
        if ($params['template_file'] === 'customer/authentication') {
            // Get the current action (login or create-account)
            $action = Tools::getValue('create_account', false) ? 'register' : 'login';
            
            // Check if we should handle this template
            if (Configuration::get('PSLAUTH_LIVE_MODE')) {
                if ($action === 'login') {
                    // Redirect to our custom login page
                    Tools::redirect($this->context->link->getModuleLink('pslauth', 'login', [], true));
                    exit;
                } elseif ($action === 'register') {
                    // Redirect to our custom register page
                    Tools::redirect($this->context->link->getModuleLink('pslauth', 'register', [], true));
                    exit;
                }
            }
        }
        
        if ($params['template_file'] === 'customer/registration') {
            // Check if we should handle this template
            if (Configuration::get('PSLAUTH_LIVE_MODE')) {
                Tools::redirect($this->context->link->getModuleLink('pslauth', 'register', [], true));
                exit;
            }
        }
        
        return false;
    }

    /**
     * Hook for displaying content in the customer account section
     * 
     * @return string Template content
     */
    public function handleDisplayCustomerAccount()
    {
        $html = '';
        
        // Add the delete account link
        if (Configuration::get('PSLAUTH_ALLOW_ACCOUNT_DELETION', true)) {
            $html .= $this->module->display($this->module->file, 'views/templates/hook/customer_account_delete.tpl');
        }
        
        return $html;
    }

    /**
     * Hook called after a customer is deleted
     * 
     * @param array $params Hook parameters
     */
    public function handleActionObjectCustomerDeleteAfter($params)
    {
        if (isset($params['object']) && Validate::isLoadedObject($params['object'])) {
            $customer = $params['object'];
            $id_customer = (int)$customer->id;
            
            // Delete the corresponding PSLAuthUser
            Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'pslauth_user` WHERE `id_customer` = ' . $id_customer);
        }
    }
}