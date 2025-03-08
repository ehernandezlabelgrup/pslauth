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

require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthAPI.php';

class PSLAuthMeApiModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be redirected to authentication page
     */
    public $auth = true;

    /**
     * @var bool If set to false, page not found will be displayed
     */
    public $page_not_found_activated = false;

    public function init()
    {
        parent::init();
        $this->ajax = true;
    }

    public function postProcess()
    {
        // Check if this is an API request
        $isApi = PSLAuthAPI::isApi();
        $isAjax = PSLAuthAPI::isAjax();

        if (!$isApi && !$isAjax) {
            Tools::redirect('index.php?controller=authentication');
            exit;
        }

        // Verify token and get customer ID
        $customerId = $this->context->customer->id;
        
        if (!$customerId) {
            return PSLAuthAPI::error('Invalid or expired token', PSLAuthAPI::HTTP_UNAUTHORIZED);
        }

        // Load customer data
        $customer = new Customer($customerId);
        
        if (!Validate::isLoadedObject($customer) || !$customer->active) {
            return PSLAuthAPI::error('User not found or inactive', PSLAuthAPI::HTTP_NOT_FOUND);
        }

        // Return user data
        return $this->returnUserData($customer);
    }

    /**
     * Format and return user data
     * 
     * @param Customer $customer Customer object
     * @return void
     */
    protected function returnUserData($customer)
    {
        // Get PSLAuth user data
        $pslAuthUser = PSLAuthUser::getByCustomerId($customer->id);
        
        // Basic customer data
        $userData = [
            'id' => $customer->id,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'gender' => $customer->id_gender,
            'birthday' => $customer->birthday,
            'newsletter' => (bool)$customer->newsletter,
            'date_add' => $customer->date_add,
            'is_guest' => (bool)$customer->is_guest,
        ];
        
        // Add PSLAuth specific data if available
        if ($pslAuthUser) {
            $userData['auth_provider'] = $pslAuthUser->auth_provider;
            $userData['last_login'] = $pslAuthUser->last_login;
        }

        // Add customer addresses count
        $addresses = $customer->getAddresses($this->context->language->id);
        $userData['addresses_count'] = count($addresses);
        
        // Add order history stats
        $orders = Order::getCustomerOrders($customer->id);
        $userData['orders_count'] = count($orders);
        
        return PSLAuthAPI::success($userData, 'User data retrieved successfully');
    }
}