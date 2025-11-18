<?php

declare(strict_types=1);

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT Helper for authentication
 */
class JWTHelper
{
    private static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'change_this_secret_key';
    }

    private static function getAlgorithm(): string
    {
        return 'HS256';
    }

    /**
     * Encode data to JWT token
     */
    public static function encode(array $data): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (int) ($_ENV['JWT_EXPIRY'] ?? 7200);

        $payload = array_merge($data, [
            'iat' => $issuedAt,
            'exp' => $expire,
        ]);

        return JWT::encode($payload, self::getSecret(), self::getAlgorithm());
    }

    /**
     * Decode JWT token
     */
    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(self::getSecret(), self::getAlgorithm()));
    }

    /**
     * Generate refresh token
     */
    public static function encodeRefreshToken(array $data): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $payload = array_merge($data, [
            'iat' => $issuedAt,
            'exp' => $expire,
            'refresh' => true,
        ]);

        return JWT::encode($payload, self::getSecret(), self::getAlgorithm());
    }
}
