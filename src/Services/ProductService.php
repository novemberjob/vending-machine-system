<?php
declare(strict_types=1);

namespace VMS\Services;

use VMS\Core\Exceptions\NotFoundException;
use VMS\Models\Product;
use VMS\Repositories\ProductRepository;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    /** @return array{items: list<Product>, total: int, pages: int} */
    public function list(int $page, int $perPage, string $sort, string $direction): array
    {
        $result = $this->products->paginate($page, $perPage, $sort, $direction);
        $result['pages'] = $perPage > 0 ? (int) ceil($result['total'] / $perPage) : 1;
        return $result;
    }

    public function find(int $id): ?Product
    {
        return $this->products->find($id);
    }

    /** @throws NotFoundException */
    public function get(int $id): Product
    {
        $product = $this->products->find($id);
        if ($product === null) {
            throw new NotFoundException('Product not found.');
        }
        return $product;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Product
    {
        return $this->products->create($this->normalize($data));
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Product
    {
        $this->get($id);
        $this->products->update($id, $this->normalize($data));
        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $this->get($id);
        $this->products->delete($id);
    }

    /** @param array<string, mixed> $data */
    private function normalize(array $data): array
    {
        return [
            'name'               => trim((string) ($data['name'] ?? '')),
            'price'              => number_format((float) ($data['price'] ?? 0), 3, '.', ''),
            'quantity_available' => (int) ($data['quantity_available'] ?? 0),
        ];
    }
}
