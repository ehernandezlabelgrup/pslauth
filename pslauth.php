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

require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthUser.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthSocialProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthGoogleProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthAppleProvider.php';

require_once _PS_MODULE_DIR_ . 'pslauth/includes/front/HookHandler.php';
require_once _PS_MODULE_DIR_ . 'pslauth/includes/front/RouteManager.php';
require_once _PS_MODULE_DIR_ . 'pslauth/includes/admin/AdminConfig.php';
require_once _PS_MODULE_DIR_ . 'pslauth/includes/admin/TabManager.php';



class Pslauth extends Module
{
    protected $config_form = false;
    protected $hookHandler;
    protected $routeManager;
    protected $adminConfig;
    protected $tabManager;

    

    public function __construct()
    {
        $this->name = 'pslauth';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Emilio Hernandez';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Login Authentication');
        $this->description = $this->l('This module allows users to sign in to the PrestaShop store using email and password, Google, or Apple authentication.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);

        $this->hookHandler = new PSLAuthHookHandler($this);
        $this->adminConfig = new PSLAuthAdminConfig($this);
        $this->tabManager = new PSLAuthTabManager($this);
        $this->routeManager = new PSLAuthRouteManager();

    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        $this->setupInitialConfiguration();

        return parent::install() &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('displayCustomerAccount') &&
        $this->registerHook('actionFrontControllerSetMedia') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('displayOverrideTemplate') && // Añadir este hook
        $this->registerHook('moduleRoutes') && // Añadir este hook
        $this->registerHook('actionObjectCustomerDeleteAfter') &&
        $this->tabManager->installTab();

    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        $this->removeConfiguration();       
    
        return parent::uninstall() && 
        $this->unregisterHook('displayOverrideTemplate') &&
        $this->tabManager->uninstallTab();
    }

        /**
     * Setup initial configuration values
     */
    private function setupInitialConfiguration()
    {
        Configuration::updateValue('PSLAUTH_LIVE_MODE', false);
        Configuration::updateValue('PSLAUTH_GOOGLE_ENABLED', false);
        Configuration::updateValue('PSLAUTH_GOOGLE_CLIENT_ID', '');
        Configuration::updateValue('PSLAUTH_GOOGLE_CLIENT_SECRET', '');
        Configuration::updateValue('PSLAUTH_GOOGLE_REDIRECT_URI', $this->context->shop->getBaseURL() . 'index.php?fc=module&module=pslauth&controller=callback&provider=google');
        Configuration::updateValue('PSLAUTH_ALLOW_ACCOUNT_DELETION', true);
        Configuration::updateValue('PSLAUTH_APPLE_ENABLED', false);
        Configuration::updateValue('PSLAUTH_APPLE_CLIENT_ID', '');
        Configuration::updateValue('PSLAUTH_APPLE_TEAM_ID', '');
        Configuration::updateValue('PSLAUTH_APPLE_KEY_ID', '');
        Configuration::updateValue('PSLAUTH_APPLE_PRIVATE_KEY', '');
        Configuration::updateValue('PSLAUTH_APPLE_REDIRECT_URI', $this->context->shop->getBaseURL() . 'index.php?fc=module&module=pslauth&controller=callback&provider=apple');
        Configuration::updateValue('PSLAUTH_SECRET_KEY', Tools::hash(Configuration::get('PS_SHOP_NAME')));
    }

    /**
     * Remove configuration values
     */
    private function removeConfiguration()
    {
        Configuration::deleteByName('PSLAUTH_LIVE_MODE');
        Configuration::deleteByName('PSLAUTH_GOOGLE_ENABLED');
        Configuration::deleteByName('PSLAUTH_GOOGLE_CLIENT_ID');
        Configuration::deleteByName('PSLAUTH_GOOGLE_CLIENT_SECRET');
        Configuration::deleteByName('PSLAUTH_GOOGLE_REDIRECT_URI');
        Configuration::deleteByName('PSLAUTH_ALLOW_ACCOUNT_DELETION');
        Configuration::deleteByName('PSLAUTH_APPLE_ENABLED');
        Configuration::deleteByName('PSLAUTH_APPLE_CLIENT_ID');
        Configuration::deleteByName('PSLAUTH_APPLE_TEAM_ID');
        Configuration::deleteByName('PSLAUTH_APPLE_KEY_ID');
        Configuration::deleteByName('PSLAUTH_APPLE_PRIVATE_KEY');
        Configuration::deleteByName('PSLAUTH_APPLE_REDIRECT_URI');
        Configuration::deleteByName('PSLAUTH_SECRET_KEY');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return $this->adminConfig->getContent();
    }

    public function getModuleContext()
{
    return $this->context;
}

public function getModulePath()
{
    return $this->_path;
}

public function getLocalPath()
{
    return $this->local_path;
}

public function getIdentifier()
{
    return $this->identifier;
}

   

        /**
     * Add scripts in the header of the FO
     */
    public function hookDisplayHeader()
    {
        return $this->hookHandler->handleDisplayHeader();
    }

    /**
     * Add the CSS & JavaScript files for the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        return $this->hookHandler->handleDisplayBackOfficeHeader();
    }

    /**
     * Add the CSS & JavaScript files for the FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        return $this->hookHandler->handleActionFrontControllerSetMedia();
    }

    /**
     * Override default templates for authentication
     */
    public function hookDisplayOverrideTemplate($params)
    {
        return $this->hookHandler->handleDisplayOverrideTemplate($params);
    }

    /**
     * Define custom routes for the module
     */
    public function hookModuleRoutes()
    {
        return $this->routeManager->getModuleRoutes();
    }

    /**
     * Hook called after a customer is deleted
     * 
     * @param array $params Hook parameters
     */
    public function hookActionObjectCustomerDeleteAfter($params)
    {
        return $this->hookHandler->handleObjectCustomerDeleteAfter($params);
    }

    /**
     * This method is used to render the delete account link in the customer account page
     */
    public function hookDisplayCustomerAccount()
    {
        return $this->hookHandler->handleCustomerAccount();
    }


}