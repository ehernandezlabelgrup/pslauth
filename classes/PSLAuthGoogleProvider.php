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

class PSLAuthGoogleProvider extends PSLAuthSocialProvider
{
    protected $name = 'google';
    
    /**
     * Check if Google provider is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)Configuration::get('PSLAUTH_GOOGLE_ENABLED');
    }
    
    /**
     * Get Google OAuth authorization URL
     * 
     * @return string
     */
    public function getAuthUrl()
    {
        $clientId = Configuration::get('PSLAUTH_GOOGLE_CLIENT_ID');
        $redirectUri = urlencode(Configuration::get('PSLAUTH_GOOGLE_REDIRECT_URI'));
        
        // Generate and store state for CSRF protection
        $state = Tools::encrypt(time() . Tools::getRemoteAddr());
        Context::getContext()->cookie->pslauth_google_state = $state;
        
        $scope = urlencode('email profile');
        
        return "https://accounts.google.com/o/oauth2/v2/auth?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope={$scope}&state={$state}";
    }
    
    /**
     * Exchange authorization code for user data
     * 
     * @param string $code Authorization code
     * @return array|false User data or false on failure
     */
    public function getUserDataFromCode($code)
    {
        // Verify state to prevent CSRF attacks
        $state = Tools::getValue('state', '');
        if (empty($state) || $state !== Context::getContext()->cookie->pslauth_google_state) {
            return false;
        }
        
        // Clear state from cookie
        Context::getContext()->cookie->pslauth_google_state = null;
        
        // Exchange code for token
        $clientId = Configuration::get('PSLAUTH_GOOGLE_CLIENT_ID');
        $clientSecret = Configuration::get('PSLAUTH_GOOGLE_CLIENT_SECRET');
        $redirectUri = Configuration::get('PSLAUTH_GOOGLE_REDIRECT_URI');
        
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $params = [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            PrestaShopLogger::addLog('Google OAuth token request failed: ' . $response, 3);
            return false;
        }
        
        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            return false;
        }
        
        // Get user info using the access token
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            PrestaShopLogger::addLog('Google user info request failed: ' . $response, 3);
            return false;
        }
        
        $userData = json_decode($response, true);
        
        // Validate required fields
        if (empty($userData['email']) || empty($userData['id'])) {
            return false;
        }
        
        // Mapear los campos de Google a los campos esperados por el sistema
        $userData['firstname'] = isset($userData['given_name']) ? $userData['given_name'] : '';
        $userData['lastname'] = isset($userData['family_name']) ? $userData['family_name'] : '';

        // Si no hay firstname o lastname, usa el nombre completo o email como fallback
        if (empty($userData['firstname']) && isset($userData['name'])) {
            $nameParts = explode(' ', $userData['name']);
            $userData['firstname'] = $nameParts[0];
            
            if (count($nameParts) > 1) {
                $userData['lastname'] = implode(' ', array_slice($nameParts, 1));
            } else {
                $userData['lastname'] = 'User';
            }
        } elseif (empty($userData['firstname'])) {
            $userData['firstname'] = 'Google';
        }

        if (empty($userData['lastname'])) {
            $userData['lastname'] = 'User';
        }
        
        return $userData;
    }
}