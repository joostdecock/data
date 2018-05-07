<?php
/** Freesewing\Data\Tools\Utilities class */
namespace Freesewing\Data\Tools;

/**
 * The Utilities class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2018 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class Utilities
{
    /**
     * Returns input as a float
     *
     * The frontend allows both decimals (6.5) or fractions (6 1/2)
     * This normalizes that input to a float (6.5)
     *
     * @param string $value The user input
     *
     * @return float The input as float
     */
    static public function asFloat($value) 
    {
        // Do we have a fraction?
        if(!strpos($value,'/')) return floatval(rtrim($value));

        $parts = self::asScrubbedArray($value,'/');

        $divider = $parts[1];
        $parts = self::asScrubbedArray($parts[0],' ');
        $inches = $parts[0];
        $fraction = $parts[1];

        return $inches + ($fraction/$divider);
    }

    static public function asScrubbedArray($data, $separator = ' ')
    {
        $return = false;
        $array = explode($separator, $data);
        foreach ($array as $value) {
            if (rtrim($value) != '') $return[] = rtrim($value);
        }

        return $return;
    }

    /**
     * Helper function to format response and send CORS headers
     *
     * @param $data The data to return
     */
    static public function prepResponse($response, $data, $status=200, $cors='*')
    {
        return $response
            ->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', $cors)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Helper function to scrub input clean
     */
    static public function scrub($request, $key, $type='string')
    {
        switch($type) {
            case 'integer':
                $filter = FILTER_SANITIZE_NUMBER_INT;
            break;
            case 'email':
                $filter = FILTER_SANITIZE_EMAIL;
            break;
            case 'username':
                if(isset($request->getParsedBody()[$key])) {
                    return filter_var(preg_replace("/[^A-Za-z0-9-_.]/", '', $request->getParsedBody()[$key]), FILTER_SANITIZE_STRING);
                } else {
                    return false;
                }
            break;
            default:
                $filter = FILTER_SANITIZE_STRING;
        }

        if(isset($request->getParsedBody()[$key])) return filter_var($request->getParsedBody()[$key], $filter);
        else return false;
    }

    /**
     * Wrapper around libsodium secretbox
     */
    static public function encrypt($value, $nonce)
    {
        // Read base64-encoded key from the environment
        $key = base64_decode(getenv('LIBSODIUM_KEY'));
        
        // Nonce is stored as a base64 encoded string 
        $nonce = base64_decode($nonce);

        return base64_encode(sodium_crypto_secretbox($value, $nonce, $key));
    }

    /**
     * Wrapper around libsodium secretbox_open
     */
    static public function decrypt($value, $nonce)
    {
        // Read base64-encoded key from the environment
        $key = base64_decode(getenv('LIBSODIUM_KEY'));
        
        // Nonce is stored as a base64 encoded string 
        $nonce = base64_decode($nonce);
        
        return sodium_crypto_secretbox_open(base64_decode($value), $nonce, $key);
    }

    /**
     * Returns a (non-secure) token to verify email addresses and so on
     */
    static public function getToken($input) {
       return sha1(getEnv('JWT_SECRET').$input.getEnv('DB_PASS')); 
    }
}
