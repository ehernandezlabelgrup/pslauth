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

class PSLAuthDeleteAccountApiModuleFrontController extends ModuleFrontController
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

        if (!$isApi && !$isAjax) {
            Tools::redirect('index.php?controller=authentication');
            exit;
        }

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
        $requiredFields = ['email', 'confirmation'];
        $validation = PSLAuthAPI::validateRequiredFields($requiredFields, $data);
        if (!$validation['success']) {
            $missingFields = implode(', ', $validation['missing_fields']);
            return PSLAuthAPI::error('Missing required fields: ' . $missingFields, PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        // Get email and confirmation from request
        $email = trim($data['email']);
        $confirmation = $data['confirmation'];

        // API authentication check - get user either from API token or session
        $customer = null;

        if ($this->context->customer->isLogged()) {
            $customer = $this->context->customer;
        } else {
            return PSLAuthAPI::error('Authentication required', PSLAuthAPI::HTTP_UNAUTHORIZED);
        }

        // Verify that provided email matches the authenticated user's email
        if (!Validate::isLoadedObject($customer) || $customer->email !== $email) {
            return PSLAuthAPI::error('Email address does not match authenticated user', PSLAuthAPI::HTTP_FORBIDDEN);
        }

        // Verify confirmation is "DELETE-MY-ACCOUNT"
        if ($confirmation !== 'DELETE-MY-ACCOUNT') {
            return PSLAuthAPI::error('Invalid confirmation code. Please type "DELETE-MY-ACCOUNT" to confirm.', PSLAuthAPI::HTTP_BAD_REQUEST);
        }

        try {
            // Get PSLAuthUser associated with this customer
            $pslAuthUser = PSLAuthUser::getByCustomerId($customer->id);
            
            // Begin transaction
            Db::getInstance()->execute('START TRANSACTION');
            
            // Delete PSLAuthUser if exists
            if ($pslAuthUser) {
                $pslAuthUser->delete();
            }
            
            // Log the deletion for audit purposes
            PrestaShopLogger::addLog(
                'Account deletion requested by user ID: ' . $customer->id . ', Email: ' . $customer->email,
                1,
                null,
                'Customer',
                $customer->id,
                true
            );
            
            // Delete the customer
            if (!$customer->delete()) {
                Db::getInstance()->execute('ROLLBACK');
                return PSLAuthAPI::error('Failed to delete customer account', PSLAuthAPI::HTTP_INTERNAL_ERROR);
            }
            
            // Commit the transaction
            Db::getInstance()->execute('COMMIT');
            
            // If web session, logout the user
            if ($this->context->customer->isLogged()) {
                $this->context->customer->logout();
            }
            
            // Return success response
            if ($isApi) {
                return PSLAuthAPI::success(null, 'Account successfully deleted', PSLAuthAPI::HTTP_OK);
            } else {
                return PSLAuthAPI::success([
                    'redirect_url' => $this->context->link->getPageLink('index')
                ], 'Account successfully deleted');
            }
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            return PSLAuthAPI::error('An error occurred while deleting your account: ' . $e->getMessage(), PSLAuthAPI::HTTP_INTERNAL_ERROR);
        }
    }
}