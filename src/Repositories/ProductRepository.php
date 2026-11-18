<?php
declare(strict_types=1);

namespace VMS\Repositories;

use VMS\Models\Product;

class ProductRepository extends BaseRepository
{
    private const SORTABLE = ['id', 'name', 'price', 'quantity_available'];

    /** @return array{items: list<Product>, total: int} */
    public function paginate(int $page, int $perPage, string $sort = 'id', string $direction = 'asc'): array
    {
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';
        $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $offset = max(0, ($page - 1) * $perPage);

        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $statement = $this->pdo->prepare(
            "SELECT * FROM products ORDER BY {$sort} {$dir}, id ASC LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        return [
            'items' => array_map(static fn (array $row): Product => Product::fromArray($row), $rows),
            'total' => $total,
        ];
    }

    public function find(int $id): ?Product
    {
        $row = $this->fetchOne('SELECT * FROM products WHERE id = :id', ['id' => $id]);
        return $row === null ? null : Product::fromArray($row);
    }

    public function findByName(string $name): ?Product
    {
        $row = $this->fetchOne('SELECT * FROM products WHERE name = :name', ['name' => $name]);
        return $row === null ? null : Product::fromArray($row);
    }

    /** @param array{name:string, price:string|float, quantity_available:int} $data */
    public function create(array $data): Product
    {
        $this->execute(
            'INSERT INTO products (name, price, quantity_available) VALUES (:name, :price, :quantity)',
            [
                'name'     => $data['name'],
                'price'    => number_format((float) $data['price'], 3, '.', ''),
                'quantity' => (int) $data['quantity_available'],
            ],
        );

        return $this->find((int) $this->pdo->lastInsertId());
    }

    /** @param array{name:string, price:string|float, quantity_available:int} $data */
    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE products SET name = :name, price = :price, quantity_available = :quantity WHERE id = :id',
            [
                'id'       => $id,
                'name'     => $data['name'],
                'price'    => number_format((float) $data['price'], 3, '.', ''),
                'quantity' => (int) $data['quantity_available'],
            ],
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM products WHERE id = :id', ['id' => $id]) > 0;
    }

    /**
     * Atomic stock reservation. Returns false when stock is insufficient
     * (or the product does not exist), so callers never oversell.
     */
    public function decrementStock(int $id, int $quantity): bool
    {
        return $this->execute(
            'UPDATE products SET quantity_available = quantity_available - :qty
             WHERE id = :id AND quantity_available >= :min_qty',
            ['id' => $id, 'qty' => $quantity, 'min_qty' => $quantity],
        ) === 1;
    }
}
