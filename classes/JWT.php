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

/**
 * Simple JWT implementation for Apple authentication
 * Inspired by firebase/php-jwt but simplified for our needs
 */
class JWT
{
    /**
     * Supported algorithms
     */
    private static $supported_algs = [
        'ES256' => ['openssl', 'SHA256'],
    ];

    /**
     * Encode a JWT
     *
     * @param array $payload JWT payload
     * @param string $key Private key
     * @param string $alg Algorithm (only ES256 supported for now)
     * @param string $keyId Optional key ID
     * @param array $header Optional header claims
     * @return string JWT
     * @throws Exception If the algorithm is not supported
     */
    public static function encode($payload, $key, $alg = 'ES256', $keyId = null, $header = [])
    {
        if (!isset(self::$supported_algs[$alg])) {
            throw new Exception('Algorithm not supported');
        }

        // Create standard header
        $header = array_merge([
            'typ' => 'JWT',
            'alg' => $alg
        ], $header);

        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }

        // Encode the header
        $segments = [
            self::urlsafeB64Encode(json_encode($header)),
            self::urlsafeB64Encode(json_encode($payload))
        ];

        // Sign the JWT
        $signing_input = implode('.', $segments);
        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode a JWT
     *
     * @param string $jwt The JWT
     * @param string|array $key The key, or array of keys
     * @param array $allowed_algs Allowed algorithms
     * @return object The JWT's payload as a PHP object
     * @throws Exception If the JWT is invalid
     */
    public static function decode($jwt, $key, array $allowed_algs = [])
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new Exception('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        
        // Decode header
        $header = json_decode(self::urlsafeB64Decode($headb64), true);
        if (null === $header) {
            throw new Exception('Invalid header encoding');
        }
        
        // Decode payload
        $payload = json_decode(self::urlsafeB64Decode($bodyb64), true);
        if (null === $payload) {
            throw new Exception('Invalid claims encoding');
        }
        
        // Verify signature
        $sig = self::urlsafeB64Decode($cryptob64);
        if (empty($header['alg'])) {
            throw new Exception('Empty algorithm');
        }
        if (empty(self::$supported_algs[$header['alg']])) {
            throw new Exception('Algorithm not supported');
        }
        if (!in_array($header['alg'], $allowed_algs)) {
            throw new Exception('Algorithm not allowed');
        }
        if (!self::verify("$headb64.$bodyb64", $sig, $key, $header['alg'])) {
            throw new Exception('Signature verification failed');
        }
        
        // Verify expiration time
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Expired token');
        }
        
        return $payload;
    }

    /**
     * Sign a string with a given key and algorithm
     *
     * @param string $msg The message to sign
     * @param string $key The secret key
     * @param string $alg The signing algorithm (ES256)
     * @return string The signature
     * @throws Exception If the algorithm is not supported
     */
    private static function sign($msg, $key, $alg = 'ES256')
    {
        if (!isset(self::$supported_algs[$alg])) {
            throw new Exception('Algorithm not supported');
        }
        
        list($function, $algorithm) = self::$supported_algs[$alg];
        
        switch ($function) {
            case 'openssl':
                // For ES256, the key needs to be in PEM format
                if (!openssl_sign($msg, $signature, $key, $algorithm)) {
                    throw new Exception('OpenSSL unable to sign data');
                }
                
                // Convert signature from ASN.1 format to raw concatenated r||s format
                if ($alg === 'ES256') {
                    $signature = self::signatureFromDER($signature);
                }
                
                return $signature;
            
            default:
                throw new Exception('Signing algorithm not supported');
        }
    }

    /**
     * Verify a signature with the message, key and method
     *
     * @param string $msg The original message
     * @param string $signature The signature
     * @param string $key The key
     * @param string $alg The algorithm (ES256)
     * @return bool
     * @throws Exception If the algorithm is not supported
     */
    private static function verify($msg, $signature, $key, $alg)
    {
        if (!isset(self::$supported_algs[$alg])) {
            throw new Exception('Algorithm not supported');
        }
        
        list($function, $algorithm) = self::$supported_algs[$alg];
        
        switch ($function) {
            case 'openssl':
                // For ES256, convert signature from raw concatenated r||s format to ASN.1 format
                if ($alg === 'ES256') {
                    $signature = self::signatureToDER($signature);
                }
                
                // Verify signature
                return openssl_verify($msg, $signature, $key, $algorithm) === 1;
            
            default:
                throw new Exception('Verification algorithm not supported');
        }
    }

    /**
     * URL safe base64 encoding
     *
     * @param string $input The string to encode
     * @return string The base64 encode string
     */
    private static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * URL safe base64 decoding
     *
     * @param string $input The base64 encoded string
     * @return string The decoded string
     */
    private static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Convert a DER formatted signature to a raw concatenated r||s format
     *
     * @param string $der The DER formatted signature
     * @return string The raw concatenated r||s format
     */
    private static function signatureFromDER($der)
    {
        // Parse the ASN.1 DER format to extract r and s values
        $offset = 0;
        
        // Check sequence
        if (ord($der[$offset++]) !== 0x30) {
            throw new Exception('Invalid signature format');
        }
        
        // Get length of sequence
        $length = ord($der[$offset++]);
        if ($length === 0x81) {
            $length = ord($der[$offset++]);
        } elseif ($length === 0x82) {
            $length = ord($der[$offset++]) << 8 | ord($der[$offset++]);
        }
        
        // Extract r value
        if (ord($der[$offset++]) !== 0x02) {
            throw new Exception('Invalid signature format');
        }
        
        // Get length of r
        $rLength = ord($der[$offset++]);
        $rOffset = $offset;
        $offset += $rLength;
        
        // Extract s value
        if (ord($der[$offset++]) !== 0x02) {
            throw new Exception('Invalid signature format');
        }
        
        // Get length of s
        $sLength = ord($der[$offset++]);
        $sOffset = $offset;
        
        // Extract r and s values
        $r = substr($der, $rOffset, $rLength);
        $s = substr($der, $sOffset, $sLength);
        
        // Remove leading zeroes
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        
        // Pad r and s to 32 bytes
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
        
        // Concatenate r and s
        return $r . $s;
    }

    /**
     * Convert a raw concatenated r||s signature to DER format
     *
     * @param string $sig The raw concatenated r||s signature
     * @return string The DER formatted signature
     */
    private static function signatureToDER($sig)
    {
        // Split signature into r and s parts
        $r = substr($sig, 0, 32);
        $s = substr($sig, 32, 32);
        
        // Remove leading zeroes
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        
        // Make sure r and s are positive
        if (ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }
        
        if (ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }
        
        // Create DER structure
        $rDER = "\x02" . chr(strlen($r)) . $r;
        $sDER = "\x02" . chr(strlen($s)) . $s;
        
        // Combine into DER sequence
        $der = "\x30" . chr(strlen($rDER) + strlen($sDER)) . $rDER . $sDER;
        
        return $der;
    }
}