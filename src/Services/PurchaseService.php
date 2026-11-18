<?php
declare(strict_types=1);

namespace VMS\Services;

use VMS\Core\Exceptions\NotFoundException;
use VMS\Core\Exceptions\OutOfStockException;
use VMS\Core\TransactionManager;
use VMS\Models\Transaction;
use VMS\Repositories\ProductRepository;
use VMS\Repositories\TransactionRepository;

final class PurchaseService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly TransactionRepository $transactions,
        private readonly TransactionManager $transactionManager,
    ) {
    }

    /**
     * Reserve stock and log the sale atomically. A failed reservation rolls
     * the whole transaction back via the TransactionManager.
     */
    public function purchase(int $productId, int $userId, int $quantity): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionManager->run(function () use ($productId, $userId, $quantity): Transaction {
            $product = $this->products->find($productId);

            if ($product === null) {
                throw new NotFoundException('Product not found.');
            }
            if ($quantity < 1) {
                throw new \InvalidArgumentException('Quantity must be at least 1.');
            }
            if ($quantity > $product->quantityAvailable) {
                throw new OutOfStockException("Only {$product->quantityAvailable} unit(s) available.");
            }
            if (!$this->products->decrementStock($productId, $quantity)) {
                throw new OutOfStockException('The requested quantity is no longer available.');
            }

            $total = number_format((float) $product->price * $quantity, 3, '.', '');

            return $this->transactions->log($productId, $userId, $quantity, $product->price, $total);
        });

        return $transaction;
    }
}
