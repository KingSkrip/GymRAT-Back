<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    public static function generateToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24);

        return JWT::encode(
            $payload,
            env('JWT_SECRET'),
            'HS256'
        );
    }

    // ← esto faltaba
    public static function validateToken(string $token): object
    {
        return JWT::decode(
            $token,
            new Key(env('JWT_SECRET'), 'HS256')
        );
    }
}
