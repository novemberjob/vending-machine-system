<?php
declare(strict_types=1);

namespace VMS\Repositories;

use VMS\Models\Transaction;

class TransactionRepository extends BaseRepository
{
    /** Log a completed purchase. Called inside a transaction by PurchaseService. */
    public function log(int $productId, int $userId, int $quantity, string $unitPrice, string $totalAmount): Transaction
    {
        $this->execute(
            'INSERT INTO transactions (product_id, user_id, quantity, unit_price, total_amount)
             VALUES (:product_id, :user_id, :quantity, :unit_price, :total_amount)',
            [
                'product_id'   => $productId,
                'user_id'      => $userId,
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
                'total_amount' => $totalAmount,
            ],
        );

        $row = $this->fetchOne(
            'SELECT t.*, p.name AS product_name, u.name AS user_name
             FROM transactions t
             JOIN products p ON p.id = t.product_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = :id',
            ['id' => (int) $this->pdo->lastInsertId()],
        );

        return Transaction::fromArray($row ?? []);
    }

    /** @return list<Transaction> */
    public function recent(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.*, p.name AS product_name, u.name AS user_name
             FROM transactions t
             JOIN products p ON p.id = t.product_id
             JOIN users u ON u.id = t.user_id
             ORDER BY t.created_at DESC, t.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        return array_map(static fn (array $row): Transaction => Transaction::fromArray($row), $rows);
    }
}
