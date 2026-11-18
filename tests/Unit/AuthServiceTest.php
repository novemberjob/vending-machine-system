<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use VMS\Core\Exceptions\ValidationException;
use VMS\Models\User;
use VMS\Repositories\UserRepository;
use VMS\Services\AuthService;

final class AuthServiceTest extends TestCase
{
    public function testAuthenticateAcceptsCorrectPassword(): void
    {
        $hash = password_hash('secret123', PASSWORD_ARGON2ID);
        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->willReturn(new User(1, 'Buyer', 'buyer@example.com', 'User', $hash));

        $user = (new AuthService($users))->authenticate('BUYER@example.com', 'secret123');

        $this->assertSame('User', $user->role);
    }

    public function testAuthenticateRejectsWrongPassword(): void
    {
        $hash = password_hash('secret123', PASSWORD_ARGON2ID);
        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->willReturn(new User(1, 'Buyer', 'buyer@example.com', 'User', $hash));

        $this->expectException(ValidationException::class);
        (new AuthService($users))->authenticate('buyer@example.com', 'wrong');
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->willReturn(new User(1, 'Buyer', 'buyer@example.com', 'User'));
        $users->expects($this->never())->method('create');

        $this->expectException(ValidationException::class);
        (new AuthService($users))->register([
            'name'     => 'Other',
            'email'    => 'buyer@example.com',
            'password' => 'secret123',
        ]);
    }
}
