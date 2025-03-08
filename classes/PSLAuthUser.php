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

class PSLAuthUser extends ObjectModel
{
    /**
     * @var int ID of the user
     */
    public $id_pslauth_user;

    /**
     * @var int ID of the customer associated with this user
     */
    public $id_customer;

    /**
     * @var string Email of the user
     */
    public $email;

    /**
     * @var string Password of the user (hashed)
     */
    public $password;

    /**
     * @var string Authentication provider (email, google, etc.)
     */
    public $auth_provider;

    /**
     * @var string Provider ID (for OAuth providers)
     */
    public $provider_id;

    /**
     * @var string Last login date
     */
    public $last_login;

    /**
     * @var string Creation date
     */
    public $date_add;

    /**
     * @var string Last update date
     */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'pslauth_user',
        'primary' => 'id_pslauth_user',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255],
            'password' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 255],
            'auth_provider' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32, 'default' => 'email'],
            'provider_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'last_login' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    /**
     * Get PSLAuthUser by email
     *
     * @param string $email User email
     * @param string $authProvider Authentication provider
     * @return PSLAuthUser|false User found or false
     */
    public static function getByEmail($email, $authProvider = 'email')
    {
        $sql = new DbQuery();
        $sql->select('id_pslauth_user');
        $sql->from(self::$definition['table']);
        $sql->where('email = "' . pSQL($email) . '"');
        $sql->where('auth_provider = "' . pSQL($authProvider) . '"');

        $id = Db::getInstance()->getValue($sql);
        
        if ($id) {
            return new self($id);
        }
        
        return false;
    }

    /**
     * Get PSLAuthUser by Customer ID
     *
     * @param int $idCustomer Customer ID
     * @return PSLAuthUser|false User found or false
     */
    public static function getByCustomerId($idCustomer)
    {
        $sql = new DbQuery();
        $sql->select('id_pslauth_user');
        $sql->from(self::$definition['table']);
        $sql->where('id_customer = ' . (int)$idCustomer);

        $id = Db::getInstance()->getValue($sql);
        
        if ($id) {
            return new self($id);
        }
        
        return false;
    }

    /**
     * Verify the password
     *
     * @param string $plaintextPassword The password to verify
     * @return bool True if password is correct
     */
    public function verifyPassword($plaintextPassword)
    {
        return password_verify($plaintextPassword, $this->password);
    }
    
    /**
     * Set password (hashed)
     *
     * @param string $plaintextPassword Password to hash and set
     * @return bool True if password was set
     */
    public function setPassword($plaintextPassword)
    {
        $this->password = password_hash($plaintextPassword, PASSWORD_DEFAULT);
        return true;
    }

    /**
     * Update last login date
     *
     * @return bool True if updated
     */
    public function updateLastLogin()
    {
        $this->last_login = date('Y-m-d H:i:s');
        return $this->update();
    }

    /**
     * Create a new user and associated customer
     *
     * @param string $email User email
     * @param string $password User password (plaintext)
     * @param string $firstname Customer firstname
     * @param string $lastname Customer lastname
     * @param string $authProvider Authentication provider
     * @param string $providerId Provider ID (for OAuth providers)
     * @return PSLAuthUser|false New user or false on failure
     */
    public static function createWithCustomer($email, $password, $firstname, $lastname, $authProvider = 'email', $providerId = null)
    {
        // Start a database transaction
        Db::getInstance()->execute('START TRANSACTION');
        
        try {
            // Create a new customer
            $customer = new Customer();
            $customer->email = $email;
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->passwd = Tools::encrypt($password); // Encrypt password for customer
            $customer->active = true;
            $customer->newsletter = false;
            $customer->optin = false;
            
            if (!$customer->add()) {
                Db::getInstance()->execute('ROLLBACK');
                return false;
            }
            
            // Create a new PSLAuthUser
            $pslAuthUser = new PSLAuthUser();
            $pslAuthUser->id_customer = $customer->id;
            $pslAuthUser->email = $email;
            $pslAuthUser->setPassword($password); // Hash password for PSLAuthUser
            $pslAuthUser->auth_provider = $authProvider;
            $pslAuthUser->provider_id = $providerId;
            $pslAuthUser->date_add = date('Y-m-d H:i:s');
            $pslAuthUser->date_upd = date('Y-m-d H:i:s');
            
            if (!$pslAuthUser->add()) {
                Db::getInstance()->execute('ROLLBACK');
                return false;
            }
            
            // Commit the transaction
            Db::getInstance()->execute('COMMIT');
            
            return $pslAuthUser;
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Authenticate a user
     *
     * @param string $email User email
     * @param string $password User password (plaintext)
     * @param string $authProvider Authentication provider
     * @return Customer|false Customer if authentication succeeded
     */
    public static function authenticate($email, $password, $authProvider = 'email')
    {
        $user = self::getByEmail($email, $authProvider);
        
        if (!$user) {
            return false;
        }
        
        if (!$user->verifyPassword($password)) {
            return false;
        }
        
        $user->updateLastLogin();
        
        // Return the customer associated with this user
        $customer = new Customer($user->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            return false;
        }
        
        return $customer;
    }
}