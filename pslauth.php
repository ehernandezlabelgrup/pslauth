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

class Pslauth extends Module
{
    protected $config_form = false;

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
        $this->description = $this->l('This module allows users to sign in to the PrestaShop store using email and password.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        Configuration::updateValue('PSLAUTH_LIVE_MODE', false);
        Configuration::updateValue('PSLAUTH_GOOGLE_ENABLED', false);
Configuration::updateValue('PSLAUTH_GOOGLE_CLIENT_ID', '');
Configuration::updateValue('PSLAUTH_GOOGLE_CLIENT_SECRET', '');
Configuration::updateValue('PSLAUTH_GOOGLE_REDIRECT_URI', $this->context->shop->getBaseURL() . 'index.php?fc=module&module=pslauth&controller=callback&provider=google');
Configuration::updateValue('PSLAUTH_ALLOW_ACCOUNT_DELETION', true);


        return parent::install() &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('displayCustomerAccount') &&
        $this->registerHook('actionFrontControllerSetMedia') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('displayOverrideTemplate') && // Añadir este hook
        $this->registerHook('moduleRoutes') && // Añadir este hook
        $this->registerHook('actionObjectCustomerDeleteAfter') &&

        $this->installTab();
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        Configuration::deleteByName('PSLAUTH_LIVE_MODE');
        Configuration::deleteByName('PSLAUTH_GOOGLE_ENABLED');
Configuration::deleteByName('PSLAUTH_GOOGLE_CLIENT_ID');
Configuration::deleteByName('PSLAUTH_GOOGLE_CLIENT_SECRET');
Configuration::deleteByName('PSLAUTH_GOOGLE_REDIRECT_URI');
Configuration::deleteByName('PSLAUTH_ALLOW_ACCOUNT_DELETION');

        return parent::uninstall() && 
        $this->unregisterHook('displayOverrideTemplate') &&
        $this->uninstallTab();
    }

    /**
     * Install Admin Tab
     *
     * @return bool
     */
    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPSLAuth';
        $tab->name = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'PSL Authentication';
        }
        
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentCustomer');
        $tab->module = $this->name;
        
        return $tab->add();
    }

    /**
     * Uninstall Admin Tab
     *
     * @return bool
     */
    public function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminPSLAuth');
        
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitPslauthModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPslauthModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'PSLAUTH_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Allow Account Deletion'),
                        'name' => 'PSLAUTH_ALLOW_ACCOUNT_DELETION',
                        'is_bool' => true,
                        'desc' => $this->l('Allow customers to delete their accounts from their account page'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Google Login'),
                        'name' => 'PSLAUTH_GOOGLE_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Allow users to sign in with their Google account'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Google Client ID'),
                        'name' => 'PSLAUTH_GOOGLE_CLIENT_ID',
                        'desc' => $this->l('Enter your Google OAuth Client ID'),
                        'size' => 50,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Google Client Secret'),
                        'name' => 'PSLAUTH_GOOGLE_CLIENT_SECRET',
                        'desc' => $this->l('Enter your Google OAuth Client Secret'),
                        'size' => 50,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Google Redirect URI'),
                        'name' => 'PSLAUTH_GOOGLE_REDIRECT_URI',
                        'desc' => $this->l('Should be your shop URL followed by index.php?fc=module&module=pslauth&controller=callback&provider=google'),
                        'size' => 50,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PSLAUTH_LIVE_MODE' => Configuration::get('PSLAUTH_LIVE_MODE'),
            'PSLAUTH_GOOGLE_ENABLED' => Configuration::get('PSLAUTH_GOOGLE_ENABLED', false),
'PSLAUTH_GOOGLE_CLIENT_ID' => Configuration::get('PSLAUTH_GOOGLE_CLIENT_ID', ''),
'PSLAUTH_GOOGLE_CLIENT_SECRET' => Configuration::get('PSLAUTH_GOOGLE_CLIENT_SECRET', ''),
'PSLAUTH_GOOGLE_REDIRECT_URI' => Configuration::get('PSLAUTH_GOOGLE_REDIRECT_URI', ''),
'PSLAUTH_ALLOW_ACCOUNT_DELETION' => Configuration::get('PSLAUTH_ALLOW_ACCOUNT_DELETION', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    /**
     * This hook is used to add scripts in the header of the FO
     */
    public function hookDisplayHeader()
    {


        $this->context->smarty->assign([
            'pslauth_live_mode' => Configuration::get('PSLAUTH_LIVE_MODE'),
            'pslauth_social_enabled' => true, // Activa los botones sociales en templates
            'pslauth_google_enabled' => Configuration::get('PSLAUTH_GOOGLE_ENABLED'),
        ]);
        
        return $this->display(__FILE__, 'views/templates/hook/header.tpl');
    }



    /**
     * Override default templates for authentication
     */
    public function hookDisplayOverrideTemplate($params)
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
 * Define custom routes for the module
 */
public function hookModuleRoutes()
{
    return [
        'module-pslauth-login' => [
            'controller' => 'login',
            'rule' => 'auth/login',
            'keywords' => [],
            'params' => [
                'fc' => 'module',
                'module' => 'pslauth',
            ]
        ],
        'module-pslauth-register' => [
            'controller' => 'register',
            'rule' => 'auth/register',
            'keywords' => [],
            'params' => [
                'fc' => 'module',
                'module' => 'pslauth',
            ]
        ],
        'module-pslauth-social' => [
            'controller' => 'social',
            'rule' => 'auth/social/{provider}',
            'keywords' => [
                'provider' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'provider']
            ],
            'params' => [
                'fc' => 'module',
                'module' => 'pslauth',
            ]
        ],
        'module-pslauth-callback' => [
            'controller' => 'callback',
            'rule' => 'auth/callback/{provider}',
            'keywords' => [
                'provider' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'provider']
            ],
            'params' => [
                'fc' => 'module',
                'module' => 'pslauth',
            ]
        ],
        'module-pslauth-deleteaccount' => [
    'controller' => 'deleteaccount',
    'rule' => 'auth/delete-account',
    'keywords' => [],
    'params' => [
        'fc' => 'module',
        'module' => 'pslauth',
    ]
],
    ];
    
}

    /**
     * Hook called after a customer is deleted
     * 
     * @param array $params Hook parameters
     */
    public function hookActionObjectCustomerDeleteAfter($params)
    {
        if (isset($params['object']) && Validate::isLoadedObject($params['object'])) {
            $customer = $params['object'];
            $id_customer = (int)$customer->id;
            
            // Delete the corresponding PSLAuthUser
            Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'pslauth_user` WHERE `id_customer` = ' . $id_customer);
        }
    }

    
// Add this new hook function to the pslauth.php file:
/**
 * This method is used to render the delete account link in the customer account page
 */
public function hookDisplayCustomerAccount()
{
    // Only show the original customer account link
    $html = '';
    // Now add the delete account link
    if (Configuration::get('PSLAUTH_ALLOW_ACCOUNT_DELETION', true)) {
        $html .= $this->display(__FILE__, 'views/templates/hook/customer_account_delete.tpl');
    }
    
    return $html;
}
}