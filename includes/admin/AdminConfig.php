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

class PSLAuthAdminConfig
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * Load the configuration form
     * 
     * @return string HTML content
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitPslauthModule')) == true) {
            $this->postProcess();
        }
        // Usar el método público para acceder al contexto
        $this->module->getModuleContext()->smarty->assign('module_dir', $this->module->getModulePath());
    
        $output = $this->module->getModuleContext()->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/configure.tpl');
    
        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of the module.
     * 
     * @return string HTML form
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->module->name;
        $helper->module = $this->module;
        $helper->default_form_language = $this->module->getModuleContext()->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->module->getIdentifier();
        $helper->submit_action = 'submitPslauthModule';
        $helper->currentIndex = $this->module->getModuleContext()->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->module->getModuleContext()->controller->getLanguages(),
            'id_language' => $this->module->getModuleContext()->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of the configuration form
     * 
     * @return array Form structure
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->module->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->module->l('Live mode'),
                        'name' => 'PSLAUTH_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->module->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->module->l('Allow Account Deletion'),
                        'name' => 'PSLAUTH_ALLOW_ACCOUNT_DELETION',
                        'is_bool' => true,
                        'desc' => $this->module->l('Allow customers to delete their accounts from their account page'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Google Login'),
                        'name' => 'PSLAUTH_GOOGLE_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->module->l('Allow users to sign in with their Google account'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Google Client ID'),
                        'name' => 'PSLAUTH_GOOGLE_CLIENT_ID',
                        'desc' => $this->module->l('Enter your Google OAuth Client ID'),
                        'size' => 50,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Google Client Secret'),
                        'name' => 'PSLAUTH_GOOGLE_CLIENT_SECRET',
                        'desc' => $this->module->l('Enter your Google OAuth Client Secret'),
                        'size' => 50,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Google Redirect URI'),
                        'name' => 'PSLAUTH_GOOGLE_REDIRECT_URI',
                        'desc' => $this->module->l('Should be your shop URL followed by index.php?fc=module&module=pslauth&controller=callback&provider=google'),
                        'size' => 50,
                    ),
                    // Apple settings
                    array(
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Apple Login'),
                        'name' => 'PSLAUTH_APPLE_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->module->l('Allow users to sign in with their Apple account'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Apple Client ID'),
                        'name' => 'PSLAUTH_APPLE_CLIENT_ID',
                        'desc' => $this->module->l('Enter your Apple Service ID'),
                        'size' => 50,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Apple Team ID'),
                        'name' => 'PSLAUTH_APPLE_TEAM_ID',
                        'desc' => $this->module->l('Enter your Apple Developer Team ID'),
                        'size' => 20,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Apple Key ID'),
                        'name' => 'PSLAUTH_APPLE_KEY_ID',
                        'desc' => $this->module->l('Enter your Apple Private Key ID'),
                        'size' => 20,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->module->l('Apple Private Key'),
                        'name' => 'PSLAUTH_APPLE_PRIVATE_KEY',
                        'desc' => $this->module->l('Enter your Apple Private Key in PEM format'),
                        'rows' => 10,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Apple Redirect URI'),
                        'name' => 'PSLAUTH_APPLE_REDIRECT_URI',
                        'desc' => $this->module->l('Your callback URL for Apple Sign In'),
                        'size' => 50,
                    ),
                ),
                'submit' => array(
                    'title' => $this->module->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     * 
     * @return array Values for the form
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
            'PSLAUTH_APPLE_ENABLED' => Configuration::get('PSLAUTH_APPLE_ENABLED', false),
            'PSLAUTH_APPLE_CLIENT_ID' => Configuration::get('PSLAUTH_APPLE_CLIENT_ID', ''),
            'PSLAUTH_APPLE_TEAM_ID' => Configuration::get('PSLAUTH_APPLE_TEAM_ID', ''),
            'PSLAUTH_APPLE_KEY_ID' => Configuration::get('PSLAUTH_APPLE_KEY_ID', ''),
            'PSLAUTH_APPLE_PRIVATE_KEY' => Configuration::get('PSLAUTH_APPLE_PRIVATE_KEY', ''),
            'PSLAUTH_APPLE_REDIRECT_URI' => Configuration::get('PSLAUTH_APPLE_REDIRECT_URI', ''),
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

        $this->module->getModuleContext()->controller->confirmations[] = $this->module->l('Settings updated successfully');
    }
}