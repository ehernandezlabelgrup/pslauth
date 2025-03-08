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

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayBackOfficeHeader') &&
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

        return parent::uninstall() && 
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
        ]);
        
        return $this->display(__FILE__, 'views/templates/hook/header.tpl');
    }

    /**
     * This method is used to render the customer account page link
     */
    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/hook/customer_account.tpl');
    }
}