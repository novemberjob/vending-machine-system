<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testCanPurchaseOnlyForUserRole(): void
    {
        $this->assertTrue(can_purchase(['id' => 1, 'role' => 'User']));
        $this->assertFalse(can_purchase(['id' => 1, 'role' => 'Admin']));
        $this->assertFalse(can_purchase(['id' => 1]));
        $this->assertFalse(can_purchase(null));
    }
}
