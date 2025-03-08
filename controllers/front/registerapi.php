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

class PSLAuthRegisterApiModuleFrontController extends ModuleFrontController
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
        $requiredFields = ['email', 'password', 'firstname', 'lastname'];
        $validation = PSLAuthAPI::validateRequiredFields($requiredFields, $data);
        if (!$validation['success']) {
            $missingFields = implode(', ', $validation['missing_fields']);
            return PSLAuthAPI::error('Missing required fields: ' . $missingFields, PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Get form data
        $email = trim($data['email']);
        $password = $data['password'];
        $firstname = trim($data['firstname']);
        $lastname = trim($data['lastname']);
        $newsletter = isset($data['newsletter']) ? (bool) $data['newsletter'] : false;
        $birthday = isset($data['birthday']) ? trim($data['birthday']) : null;
        $gender = isset($data['id_gender']) ? (int) $data['id_gender'] : null;

        // Validate email
        if (!Validate::isEmail($email)) {
            return PSLAuthAPI::error('Invalid email address', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Check if email already exists
        if (Customer::customerExists($email)) {
            return PSLAuthAPI::error('An account already exists with this email address', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Validate name fields
        if (!Validate::isName($firstname)) {
            return PSLAuthAPI::error('Invalid first name', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        if (!Validate::isName($lastname)) {
            return PSLAuthAPI::error('Invalid last name', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Validate password
        if (strlen($password) < 5) {
            return PSLAuthAPI::error('Password must be at least 5 characters long', PSLAuthAPI::HTTP_BAD_REQUEST);
        }
        
        // Validate birthday if provided
        if (!empty($birthday) && !Validate::isBirthDate($birthday)) {
            return PSLAuthAPI::error('Invalid birth date format (YYYY-MM-DD)', PSLAuthAPI::HTTP_BAD_REQUEST);
        }
        
        // Validate gender if provided
        if ($gender !== null && !in_array($gender, [1, 2])) {
            return PSLAuthAPI::error('Invalid gender selection', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        try {
            // Create new user with customer
            $user = PSLAuthUser::createWithCustomer(
                $email,
                $password,
                $firstname,
                $lastname,
                'email'
            );
            
            // After creating the user, update additional customer fields
            if ($user) {
                $customer = new Customer($user->id_customer);
                
                // Set gender if provided
                if ($gender !== null) {
                    $customer->id_gender = $gender;
                }
                
                // Set birthday if provided
                if (!empty($birthday)) {
                    $customer->birthday = $birthday;
                }
                
                $customer->update();
            }

            if (!$user) {
                return PSLAuthAPI::error('Failed to create account', PSLAuthAPI::HTTP_INTERNAL_ERROR);
            }

            // Update newsletter preference
            if ($newsletter) {
                $customer = new Customer($user->id_customer);
                $customer->newsletter = true;
                $customer->update();
            }

            // Generate the redirect URL after registration
            $redirectUrl = $this->getRedirectUrl($data);



            // For web requests, log the customer in automatically
            $customer = new Customer($user->id_customer);
            $this->context->updateCustomer($customer);
            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

            // Update cart for this customer
            $this->context->cart->id_customer = $customer->id;
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
            $this->context->cookie->id_cart = (int) $this->context->cart->id;
            $this->context->cookie->write();
            $this->context->cart->autosetProductAddress();

			if ($isApi) {
                $customer = new Customer($user->id_customer);
                
                return PSLAuthAPI::success([
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,
                    'redirect_url' => $redirectUrl
                ], 'Registration successful');
            }
			
			
            // For AJAX requests, return success with redirect URL
            if ($isAjax) {
                return PSLAuthAPI::success([
                    'redirect_url' => $redirectUrl
                ], 'Registration successful');
            }

            // For standard requests, redirect to the redirect URL
            Tools::redirect($redirectUrl);
        } catch (Exception $e) {
            return PSLAuthAPI::error('An error occurred during registration: ' . $e->getMessage(), PSLAuthAPI::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * Get the redirect URL after registration
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