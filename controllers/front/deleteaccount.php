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

class PSLAuthDeleteAccountModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be redirected to authentication page
     */
    public $auth = true;

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'page_title' => $this->trans('Delete My Account', [], 'Modules.Pslauth.Shop'),
            'customer_email' => $this->context->customer->email
        ]);

        $this->setTemplate('module:pslauth/views/templates/front/delete_account.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => $this->trans('My Account', [], 'Shop.Theme.Customeraccount'),
            'url' => $this->context->link->getPageLink('my-account')
        ];
        $breadcrumb['links'][] = [
            'title' => $this->trans('Delete My Account', [], 'Modules.Pslauth.Shop'),
            'url' => $this->context->link->getModuleLink('pslauth', 'deleteaccount', [], true)
        ];

        return $breadcrumb;
    }

    public function setMedia()
    {
        parent::setMedia();
        
        // Add module specific JS and CSS
        $this->registerJavascript(
            'module-pslauth-delete-account',
            'modules/'.$this->module->name.'/views/js/delete_account.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        $this->registerStylesheet(
            'module-pslauth-delete',
            'modules/'.$this->module->name.'/views/css/front.css',
            ['media' => 'all', 'priority' => 200]
        );
    }
}