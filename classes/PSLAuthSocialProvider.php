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

abstract class PSLAuthSocialProvider
{
    protected $name;
    protected $context;
    
    public function __construct()
    {
        $this->context = Context::getContext();
    }
    
    /**
     * Get provider name
     * 
     * @return string Provider name
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Check if provider is enabled
     * 
     * @return bool
     */
    abstract public function isEnabled();
    
    /**
     * Get authorization URL
     * 
     * @return string
     */
    abstract public function getAuthUrl();
    
    /**
     * Exchange authorization code for user data
     * 
     * @param string $code Authorization code
     * @return array|false User data or false on failure
     */
    abstract public function getUserDataFromCode($code);
    
    /**
     * Process login with social data
     * 
     * @param array $userData User data from social provider
     * @return Customer|false
     */
    public function processLogin($userData)
    {
        if (empty($userData['email']) || empty($userData['id'])) {
            return false;
        }
        
        // Check if user exists with this provider
        $user = $this->getUserByProviderId($userData['id']);
        
        if ($user) {
            // User exists, update last login and return customer
            $user->updateLastLogin();
            return new Customer($user->id_customer);
        }
        
        // Check if user exists with this email
        $user = PSLAuthUser::getByEmail($userData['email']);
        
        if ($user) {
            // User exists with this email but different provider
            // Here you can either:
            // 1. Link this social account to existing user (recommended)
            // 2. Show error that account already exists with different provider
            
            // For this example, we'll link the account:
            $user->auth_provider = $this->getName();
            $user->provider_id = $userData['id'];
            $user->update();
            $user->updateLastLogin();
            
            return new Customer($user->id_customer);
        }
        
        // Create new user
        $password = Tools::passwdGen(8); // Generate random password
        
        $user = PSLAuthUser::createWithCustomer(
            $userData['email'],
            $password,
            $userData['firstname'],
            $userData['lastname'],
            $this->getName(),
            $userData['id']
        );
        
        if (!$user) {
            return false;
        }
        
        $user->updateLastLogin();
        return new Customer($user->id_customer);
    }
    
    /**
     * Get user by provider ID
     * 
     * @param string $providerId Provider-specific user ID
     * @return PSLAuthUser|false
     */
    protected function getUserByProviderId($providerId)
    {
        $sql = new DbQuery();
        $sql->select('id_pslauth_user');
        $sql->from('pslauth_user');
        $sql->where('auth_provider = "' . pSQL($this->getName()) . '"');
        $sql->where('provider_id = "' . pSQL($providerId) . '"');
        
        $id = Db::getInstance()->getValue($sql);
        
        if ($id) {
            return new PSLAuthUser($id);
        }
        
        return false;
    }
}