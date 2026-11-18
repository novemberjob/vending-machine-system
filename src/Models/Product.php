<?php
declare(strict_types=1);

namespace VMS\Models;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $price,
        public readonly int $quantityAvailable,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (string) ($row['name'] ?? ''),
            (string) ($row['price'] ?? '0.000'),
            (int) ($row['quantity_available'] ?? 0),
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
        );
    }

    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 3, '.', ',');
    }

    public function slug(): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $this->name) ?? '', '-'));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'price'              => $this->price,
            'quantity_available' => $this->quantityAvailable,
            'created_at'         => $this->createdAt,
            'updated_at'         => $this->updatedAt,
        ];
    }
}
