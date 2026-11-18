<?php
declare(strict_types=1);

namespace VMS\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private const TTL_SECONDS = 3600;

    public function __construct(private readonly string $secret)
    {
    }

    /** @param array<string, mixed> $claims */
    public function issue(array $claims): string
    {
        $now = time();

        return JWT::encode(
            array_merge($claims, [
                'iat' => $now,
                'nbf' => $now,
                'exp' => $now + self::TTL_SECONDS,
            ]),
            $this->secret,
            'HS256',
        );
    }

    /** @return array<string, mixed> */
    public function verify(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
