<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use VMS\Core\Exceptions\NotFoundException;
use VMS\Core\Exceptions\OutOfStockException;
use VMS\Core\TransactionManager;
use VMS\Models\Product;
use VMS\Models\Transaction;
use VMS\Repositories\ProductRepository;
use VMS\Repositories\TransactionRepository;
use VMS\Services\PurchaseService;

final class PurchaseServiceTest extends TestCase
{
    private ProductRepository $products;
    private TransactionRepository $transactions;
    private TransactionManager $manager;
    private PurchaseService $service;

    protected function setUp(): void
    {
        $this->products = $this->createMock(ProductRepository::class);
        $this->transactions = $this->createMock(TransactionRepository::class);
        $this->manager = $this->createMock(TransactionManager::class);
        $this->manager->method('run')->willReturnCallback(static fn (callable $fn): mixed => $fn());

        $this->service = new PurchaseService($this->products, $this->transactions, $this->manager);
    }

    public function testPurchaseReservesStockAndLogsSnapshot(): void
    {
        $this->products->method('find')->willReturn(new Product(3, 'Pepsi', '6.885', 4));
        $this->products->expects($this->once())->method('decrementStock')->with(3, 2)->willReturn(true);

        $this->transactions->expects($this->once())
            ->method('log')
            ->with(3, 9, 2, '6.885', '13.770')
            ->willReturn(new Transaction(1, 3, 9, 2, '6.885', '13.770'));

        $transaction = $this->service->purchase(3, 9, 2);

        $this->assertSame('13.770', $transaction->totalAmount);
    }

    public function testPurchaseThrowsWhenProductMissing(): void
    {
        $this->products->method('find')->willReturn(null);
        $this->products->expects($this->never())->method('decrementStock');
        $this->transactions->expects($this->never())->method('log');

        $this->expectException(NotFoundException::class);
        $this->service->purchase(1, 9, 1);
    }

    public function testPurchaseRejectsQuantityGreaterThanStock(): void
    {
        $this->products->method('find')->willReturn(new Product(1, 'Water', '0.500', 1));
        $this->products->expects($this->never())->method('decrementStock');
        $this->transactions->expects($this->never())->method('log');

        $this->expectException(OutOfStockException::class);
        $this->service->purchase(1, 9, 2);
    }

    public function testPurchaseThrowsWhenAtomicReservationFails(): void
    {
        $this->products->method('find')->willReturn(new Product(1, 'Water', '0.500', 1));
        $this->products->expects($this->once())->method('decrementStock')->willReturn(false);
        $this->transactions->expects($this->never())->method('log');

        $this->expectException(OutOfStockException::class);
        $this->service->purchase(1, 9, 1);
    }

    public function testPurchasePropagatesTransactionFailuresSoRollbackCanOccur(): void
    {
        $manager = $this->createMock(TransactionManager::class);
        $manager->expects($this->once())
            ->method('run')
            ->willThrowException(new \RuntimeException('DB failure'));

        $service = new PurchaseService($this->products, $this->transactions, $manager);

        $this->expectException(\RuntimeException::class);
        $service->purchase(1, 9, 1);
    }
}
