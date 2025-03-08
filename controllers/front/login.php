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

class PSLAuthLoginModuleFrontController extends ModuleFrontController
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

    public function initContent()
    {
        parent::initContent();

        // Check if back URL is provided
        $back = Tools::getValue('back', '');
        if (!empty($back)) {
            $this->context->smarty->assign('back', $back);
        }

        $this->context->smarty->assign([
            'page_title' => $this->trans('Log in to your account', [], 'Modules.Pslauth.Shop'),
        ]);

        $this->setTemplate('module:pslauth/views/templates/front/login.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => $this->trans('Log in', [], 'Modules.Pslauth.Shop'),
            'url' => $this->context->link->getModuleLink('pslauth', 'login', [], true)
        ];

        return $breadcrumb;
    }

    public function setMedia()
    {
        parent::setMedia();
        
        // Add module specific JS and CSS
        $this->registerJavascript(
            'module-pslauth-login',
            'modules/'.$this->module->name.'/views/js/login.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        $this->registerStylesheet(
            'module-pslauth-login',
            'modules/'.$this->module->name.'/views/css/front.css',
            ['media' => 'all', 'priority' => 200]
        );
    }
}