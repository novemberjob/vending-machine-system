<?php
declare(strict_types=1);

namespace VMS\Models;

final class Transaction
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly int $userId,
        public readonly int $quantity,
        public readonly string $unitPrice,
        public readonly string $totalAmount,
        public readonly ?string $createdAt = null,
        public readonly ?string $productName = null,
        public readonly ?string $userName = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['product_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            (int) ($row['quantity'] ?? 0),
            (string) ($row['unit_price'] ?? '0.000'),
            (string) ($row['total_amount'] ?? '0.000'),
            $row['created_at'] ?? null,
            $row['product_name'] ?? null,
            $row['user_name'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->productId,
            'user_id'      => $this->userId,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unitPrice,
            'total_amount' => $this->totalAmount,
            'created_at'   => $this->createdAt,
            'product_name' => $this->productName,
            'user_name'    => $this->userName,
        ];
    }
}
