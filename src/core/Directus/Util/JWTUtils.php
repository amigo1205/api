<?php

namespace Directus\Util;

use Directus\Authentication\Exception\ExpiredTokenException;
use Directus\Authentication\Exception\InvalidTokenException;
use Directus\Exception\Exception;
use Directus\Exception\UnauthorizedException;
use Firebase\JWT\JWT;

class JWTUtils
{
    const TYPE_AUTH                 = 'auth';
    const TYPE_SSO_REQUEST_TOKEN    = 'request_token';
    const TYPE_INVITATION           = 'invitation';
    const TYPE_RESET_PASSWORD       = 'reset_password';

    /**
     * @param string $jwt
     * @param string $key
     * @param array $allowed_algs
     *
     * @throws Exception
     *
     * @return object
     */
    public static function decode($jwt, $key, array $allowed_algs = [])
    {
        try {
            $payload = JWT::decode($jwt, $key, $allowed_algs);
        } catch (\Exception $e) {
            switch ($e->getMessage()) {
                case 'Expired token':
                    $exception = new ExpiredTokenException();
                    break;
                default:
                    $exception = new InvalidTokenException();
            }

            throw $exception;
        }

        return $payload;
    }

    /**
     * @param array|object $payload
     * @param string $key
     * @param string $alg
     * @param null $keyId
     * @param null $head
     *
     * @return string
     */
    public static function encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null)
    {
        return JWT::encode($payload, $key, $alg, $keyId, $head);
    }

    /**
     * Checks whether a token is a JWT token
     *
     * @param $token
     *
     * @return bool
     */
    public static function isJWT($token)
    {
        if (!is_string($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        list($headb64, $bodyb64, $cryptob64) = $parts;
        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
            return false;
        }

        return $header->typ === 'JWT';
    }

    /**
     * Checks whether ot not the payload has the given type
     *
     * @param object $payload
     * @param string $type
     *
     * @return bool
     */
    public static function hasPayloadType($type, $payload)
    {
        if (is_object($payload) && property_exists($payload, 'type') && $payload->type === $type) {
            return true;
        }

        return false;
    }

    /**
     * Get the token payload object
     *
     * @param $token
     *
     * @return null|object
     */
    public static function getPayload($token)
    {
        if (!is_string($token)) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }

        list($headb64, $bodyb64, $cryptob64) = $parts;

        return JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
    }

    /**
     * Checks whether the token has expired
     *
     * @param $token
     *
     * @return bool|null
     */
    public static function hasExpired($token)
    {
        $expired = null;
        $payload = static::getPayload($token);

        if ($payload && isset($payload->exp)) {
            $expired = time() >= $payload->exp;
        }

        return $expired;
    }
}
