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

/**
 * Class PSLAuthPasswordValidator
 * Provides password validation functionality
 */
class PSLAuthPasswordValidator
{
    const MIN_LENGTH = 6;
    
    /**
     * Validate a password against the required criteria
     * 
     * @param string $password Password to validate
     * 
     * @return array Array with 'valid' (boolean) and 'errors' (array of error messages)
     */
    public static function validate($password)
    {
        $errors = [];
        
        // Check minimum length
        if (Tools::strlen($password) < self::MIN_LENGTH) {
            $errors[] = sprintf('Password must be at least %d characters long', self::MIN_LENGTH);
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        // Check for special character
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}