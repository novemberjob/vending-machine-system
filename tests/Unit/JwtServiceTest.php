<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use VMS\Services\JwtService;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'phpunit-test-secret';

    public function testIssuedTokenCanBeVerified(): void
    {
        $service = new JwtService(self::SECRET);
        $token = $service->issue(['sub' => '7', 'role' => 'Admin']);

        $claims = $service->verify($token);

        $this->assertSame('7', $claims['sub']);
        $this->assertSame('Admin', $claims['role']);
        $this->assertArrayHasKey('exp', $claims);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = JWT::encode([
            'sub' => '7',
            'role' => 'User',
            'exp' => time() - 60,
        ], self::SECRET, 'HS256');

        $this->expectException(\Throwable::class);
        (new JwtService(self::SECRET))->verify($token);
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        $token = JWT::encode(['sub' => '7', 'exp' => time() + 60], 'wrong-secret', 'HS256');

        $this->expectException(\Throwable::class);
        (new JwtService(self::SECRET))->verify($token);
    }
}
