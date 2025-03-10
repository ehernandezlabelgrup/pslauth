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

require_once _PS_MODULE_DIR_ . 'pslauth/classes/JWT.php';

/**
 * Class PSLAuthAppleProvider
 * Manages authentication with Apple Sign In
 */
class PSLAuthAppleProvider extends PSLAuthSocialProvider
{
    protected $name = 'apple';
    
    /**
     * Check if Apple provider is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)Configuration::get('PSLAUTH_APPLE_ENABLED');
    }
    
    /**
     * Get Apple OAuth authorization URL
     * 
     * @return string
     */
    public function getAuthUrl()
    {
        $clientId = Configuration::get('PSLAUTH_APPLE_CLIENT_ID');
        $redirectUri = urlencode(Configuration::get('PSLAUTH_APPLE_REDIRECT_URI'));
        
        // Generate and store state for CSRF protection
        $state = Tools::encrypt(time() . Tools::getRemoteAddr());
        Context::getContext()->cookie->pslauth_apple_state = $state;
        
        // For Apple Sign In, we need a nonce (a random string) to prevent replay attacks
        $nonce = bin2hex(random_bytes(32));
        Context::getContext()->cookie->pslauth_apple_nonce = $nonce;
        
        // Scope for Apple Sign In (email and name are the basic ones)
        $scope = urlencode('email name');
        
        // Apple's authorization endpoint
        return "https://appleid.apple.com/auth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code id_token&scope={$scope}&response_mode=form_post&state={$state}&nonce={$nonce}";
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
        if (empty($state) || $state !== Context::getContext()->cookie->pslauth_apple_state) {
            return false;
        }
        
        // Clear state from cookie
        Context::getContext()->cookie->pslauth_apple_state = null;
        
        // Get identity token if available
        $idToken = Tools::getValue('id_token', '');
        $userData = $this->parseIdentityToken($idToken);
        
        // If we couldn't get user data from the identity token, try to get it from the token endpoint
        if (!$userData) {
            $userData = $this->getUserDataFromToken($code);
        }
        
        // If we still don't have the user's email, we can't proceed
        if (empty($userData) || empty($userData['email'])) {
            return false;
        }
        
        return $userData;
    }
    
    /**
     * Parse Apple's identity token to get user data
     * 
     * @param string $idToken JWT identity token
     * @return array|false User data or false on failure
     */
    private function parseIdentityToken($idToken)
    {
        if (empty($idToken)) {
            return false;
        }
        
        // Split the JWT token
        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) != 3) {
            return false;
        }
        
        // Get the payload part (second part)
        $payload = $tokenParts[1];
        
        // Base64 decode and JSON decode
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
        $tokenData = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Verify the token's audience matches our client ID
        if ($tokenData['aud'] !== Configuration::get('PSLAUTH_APPLE_CLIENT_ID')) {
            return false;
        }
        
        // Verify nonce if we stored one
        if (isset(Context::getContext()->cookie->pslauth_apple_nonce) && 
            (!isset($tokenData['nonce']) || $tokenData['nonce'] !== Context::getContext()->cookie->pslauth_apple_nonce)) {
            return false;
        }
        
        // Clear nonce from cookie
        Context::getContext()->cookie->pslauth_apple_nonce = null;
        
        // Format user data
        $userData = [
            'id' => $tokenData['sub'], // Apple's user ID
            'email' => isset($tokenData['email']) ? $tokenData['email'] : '',
            'email_verified' => isset($tokenData['email_verified']) ? (bool)$tokenData['email_verified'] : false,
        ];
        
        // Try to get user name from the request if it was sent
        $userNameData = Tools::getValue('user', '');
        if (!empty($userNameData)) {
            $userNameJson = json_decode($userNameData, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($userNameJson['name'])) {
                $userData['firstname'] = $userNameJson['name']['firstName'] ?? '';
                $userData['lastname'] = $userNameJson['name']['lastName'] ?? '';
            }
        }
        
        // If we don't have the first or last name, handle it
        if (empty($userData['firstname'])) {
            $userData['firstname'] = 'Apple';
        }
        
        if (empty($userData['lastname'])) {
            $userData['lastname'] = 'User';
        }
        
        return $userData;
    }
    
    /**
     * Exchange authorization code for tokens at Apple's token endpoint
     * 
     * @param string $code Authorization code
     * @return array|false User data or false on failure
     */
    private function getUserDataFromToken($code)
    {
        $clientId = Configuration::get('PSLAUTH_APPLE_CLIENT_ID');
        $clientSecret = $this->generateClientSecret();
        $redirectUri = Configuration::get('PSLAUTH_APPLE_REDIRECT_URI');
        
        $tokenUrl = 'https://appleid.apple.com/auth/token';
        $params = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri
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
            PrestaShopLogger::addLog('Apple OAuth token request failed: ' . $response, 3);
            return false;
        }
        
        $tokenData = json_decode($response, true);
        if (!isset($tokenData['id_token'])) {
            return false;
        }
        
        // Parse the id_token to get user info
        return $this->parseIdentityToken($tokenData['id_token']);
    }
    
    /**
     * Generate Apple client secret (JWT)
     * 
     * @return string JWT client secret
     */
    private function generateClientSecret()
    {
        // We need the JWT library for this
        // If you don't have it, you'll need to include it in your module
        // Example using firebase/php-jwt
        
        // Check if we have the JWT class available
        if (!class_exists('JWT')) {
            // Include JWT library or implement a basic JWT signing method
            require_once _PS_MODULE_DIR_ . 'pslauth/lib/JWT.php';
        }
        
        $teamId = Configuration::get('PSLAUTH_APPLE_TEAM_ID');
        $keyId = Configuration::get('PSLAUTH_APPLE_KEY_ID');
        $privateKey = Configuration::get('PSLAUTH_APPLE_PRIVATE_KEY');
        $clientId = Configuration::get('PSLAUTH_APPLE_CLIENT_ID');
        
        // JWT headers
        $header = [
            'kid' => $keyId,
            'alg' => 'ES256',
        ];
        
        // JWT claims
        $claims = [
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 86400 * 180, // 180 days
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];
        
        // Sign the JWT
        $clientSecret = JWT::encode($claims, $privateKey, 'ES256', null, $header);
        
        return $clientSecret;
    }
}