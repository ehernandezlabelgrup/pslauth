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

class PSLAuthLoginApiModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be redirected to authentication page
     */
    public $auth = false;

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

        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isApi || $isAjax) {
                return PSLAuthAPI::error('Invalid request method, only POST is allowed', PSLAuthAPI::HTTP_BAD_REQUEST);
            }
            Tools::redirect('index.php?controller=authentication');
            exit;
        }

        // Get request data
        $data = PSLAuthAPI::getRequestData();

        // Validate required fields
        $validation = PSLAuthAPI::validateRequiredFields(['email', 'password'], $data);
        if (!$validation['success']) {
            $missingFields = implode(', ', $validation['missing_fields']);
            return PSLAuthAPI::error('Missing required fields: ' . $missingFields, PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Get email and password from request
        $email = trim($data['email']);
        $password = $data['password'];
        $stayLoggedIn = isset($data['stay_logged_in']) ? (bool) $data['stay_logged_in'] : false;

        // Validate email
        if (!Validate::isEmail($email)) {
            return PSLAuthAPI::error('Invalid email address', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Try to authenticate
        $customer = PSLAuthUser::authenticate($email, $password);
        
        if (!$customer) {
            // Check if this customer exists in the standard PrestaShop system
            // This allows compatibility with existing accounts
            $psCustomer = Customer::getCustomersByEmail($email);
            
            if (!empty($psCustomer)) {
                $psCustomer = new Customer($psCustomer[0]['id_customer']);
                
                // Verify password using PrestaShop's method
                if (Validate::isLoadedObject($psCustomer) && $psCustomer->active) {
                    if (Validate::isMd5($psCustomer->passwd) || Tools::encrypt($password) === $psCustomer->passwd) {
                        // Password is correct, create a PSLAuthUser record for this customer
                        $pslAuthUser = new PSLAuthUser();
                        $pslAuthUser->id_customer = $psCustomer->id;
                        $pslAuthUser->email = $email;
                        $pslAuthUser->setPassword($password); // Hash password for PSLAuthUser
                        $pslAuthUser->auth_provider = 'email';
                        $pslAuthUser->date_add = date('Y-m-d H:i:s');
                        $pslAuthUser->date_upd = date('Y-m-d H:i:s');
                        
                        if ($pslAuthUser->add()) {
                            $pslAuthUser->updateLastLogin();
                            $customer = $psCustomer;
                        }
                    }
                }
            }
            
            // If authentication is still not successful
            if (!$customer) {
                return PSLAuthAPI::error('Invalid email or password', PSLAuthAPI::HTTP_UNAUTHORIZED);
            }
        }

        // Check if account is active
        if (!$customer->active) {
            return PSLAuthAPI::error('Your account is not active', PSLAuthAPI::HTTP_FORBIDDEN);
        }

        // Generate the redirect URL after login
        $redirectUrl = $this->getRedirectUrl($data);

        // If this is an API request, return the authentication token
        if ($isApi) {
            $authToken = PSLAuthAPI::generateAuthToken($customer->id);
            
            return PSLAuthAPI::success([
                'token' => $authToken,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'redirect_url' => $redirectUrl
            ], 'Authentication successful');
        }

        // For web requests, create the customer session
        $this->context->updateCustomer($customer);
        Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

        // Update cart for this customer
        $this->context->cart->id_customer = $customer->id;
        $this->context->cart->secure_key = $customer->secure_key;
        $this->context->cart->save();
        $this->context->cookie->id_cart = (int) $this->context->cart->id;
        $this->context->cookie->write();
        $this->context->cart->autosetProductAddress();

        // Handle "stay logged in" option
        if ($stayLoggedIn) {
            $this->context->cookie->setExpiration(time() + (60 * 60 * 24 * 30)); // 30 days
        }

        // For AJAX requests, return success with redirect URL
        if ($isAjax) {
            return PSLAuthAPI::success([
                'redirect_url' => $redirectUrl
            ], 'Authentication successful');
        }

        // For standard requests, redirect to the redirect URL
        Tools::redirect($redirectUrl);
    }

    /**
     * Get the redirect URL after login
     * 
     * @param array $data Request data
     * @return string Redirect URL
     */
    protected function getRedirectUrl($data)
    {
        // Check if back URL is specified in the request
        if (isset($data['back']) && !empty($data['back'])) {
            $back = $data['back'];
            
            // Validate the URL to prevent open redirect vulnerabilities
            if (Validate::isUrl($back) && strpos($back, Tools::getShopDomain()) !== false) {
                return $back;
            }
        }

        // Default redirect to My Account page
        return $this->context->link->getPageLink('my-account');
    }
}