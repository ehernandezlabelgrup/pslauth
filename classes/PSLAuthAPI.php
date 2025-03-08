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

class PSLAuthAPI
{
    /**
     * HTTP Status codes
     */
    const HTTP_OK = 200;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_ERROR = 500;

    /**
     * Return JSON success response
     *
     * @param mixed $data Data to return
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function success($data = null, $message = 'Success', $statusCode = self::HTTP_OK)
    {
        return self::response(true, $message, $data, $statusCode);
    }

    /**
     * Return JSON error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $data Additional data to return
     * @return void
     */
    public static function error($message = 'Error', $statusCode = self::HTTP_BAD_REQUEST, $data = null)
    {
        return self::response(false, $message, $data, $statusCode);
    }

    /**
     * Return JSON response
     *
     * @param bool $success Success status
     * @param string $message Response message
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function response($success, $message, $data = null, $statusCode = self::HTTP_OK)
    {
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Prepare response array
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        
        // Add data if provided
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        // Set content type header
        header('Content-Type: application/json');
        
        // Return JSON response
        echo json_encode($response);
        exit;
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is API
     *
     * @return bool
     */
    public static function isApi()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';;
        return strpos($requestUri, '/api/') !== false || 
               isset($_SERVER['HTTP_X_API_REQUEST']) || 
               (isset($_GET['fc']) && $_GET['fc'] === 'api');
    }

    /**
     * Get request data (supports GET, POST, JSON)
     *
     * @param string $key Optional key to get specific value
     * @param mixed $default Default value
     * @return mixed
     */
    public static function getRequestData($key = null, $default = null)
    {
        // JSON request
        $input = file_get_contents('php://input');
        if ($input) {
            $data = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($key !== null) {
                    return isset($data[$key]) ? $data[$key] : $default;
                }
                return $data;
            }
        }
        
        // POST & GET requests
        if ($key !== null) {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            
            return $default;
        }
        
        return array_merge($_GET, $_POST);
    }

    /**
     * Validate required fields
     *
     * @param array $fields Fields to validate
     * @param array $data Data to check
     * @return array [success, missing_fields]
     */
    public static function validateRequiredFields($fields, $data)
    {
        $missingFields = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        return [
            'success' => empty($missingFields),
            'missing_fields' => $missingFields
        ];
    }

    /**
     * Generate authentication token for API
     *
     * @param int $customerId Customer ID
     * @return string Generated token
     */
    public static function generateAuthToken($customerId)
    {
        $secret = Configuration::get('PSLAUTH_SECRET_KEY', Tools::hash(Configuration::get('PS_SHOP_NAME')));
        $payload = [
            'id' => $customerId,
            'time' => time(),
            'rand' => Tools::passwdGen(8)
        ];
        
        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);
        $signature = hash_hmac('sha256', $base64Payload, $secret);
        
        return $base64Payload . '.' . $signature;
    }

    /**
     * Verify authentication token
     *
     * @param string $token Authentication token
     * @return int|false Customer ID if valid or false
     */
    public static function verifyAuthToken($token)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        list($base64Payload, $signature) = $parts;
        
        $secret = Configuration::get('PSLAUTH_SECRET_KEY', Tools::hash(Configuration::get('PS_SHOP_NAME')));
        $expectedSignature = hash_hmac('sha256', $base64Payload, $secret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }
        
        $jsonPayload = base64_decode($base64Payload);
        $payload = json_decode($jsonPayload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Token expiration check (24 hours)
        if (!isset($payload['time']) || (time() - $payload['time']) > 86400) {
            return false;
        }
        
        return isset($payload['id']) ? (int) $payload['id'] : false;
    }
}