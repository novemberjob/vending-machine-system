<?php
declare(strict_types=1);

namespace VMS\Api\Controllers;

use PDOException;
use Throwable;
use VMS\Core\Attributes\RequiresRole;
use VMS\Core\Attributes\Route;
use VMS\Core\Exceptions\NotFoundException;
use VMS\Core\Exceptions\OutOfStockException;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Validator;
use VMS\Services\ProductService;
use VMS\Services\PurchaseService;

final class ProductApiController
{
    public function __construct(
        private readonly ProductService $products,
        private readonly PurchaseService $purchases,
        private readonly Validator $validator,
        private readonly Response $response,
    ) {
    }

    #[Route('/api/v1/products', methods: ['GET'], name: 'api.products.index', api: true)]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', env('PER_PAGE', 10))));
        $sort = (string) $request->query('sort', 'id');
        $direction = (string) $request->query('dir', 'asc');

        $result = $this->products->list($page, $perPage, $sort, $direction);

        return $this->response->json([
            'data' => array_map(static fn ($product): array => $product->toArray(), $result['items']),
            'meta' => [
                'total'       => $result['total'],
                'per_page'    => $perPage,
                'current_page'=> $page,
                'last_page'   => $result['pages'],
            ],
        ], 200);
    }

    #[Route('/api/v1/products/{id}', methods: ['GET'], name: 'api.products.show', api: true)]
    public function show(Request $request, int $id): Response
    {
        try {
            return $this->response->json(['data' => $this->products->get($id)->toArray()], 200);
        } catch (NotFoundException) {
            return $this->notFound();
        }
    }

    #[Route('/api/v1/products', methods: ['POST'], name: 'api.products.store', api: true)]
    #[RequiresRole('Admin')]
    public function store(Request $request): Response
    {
        return $this->save(null, $request);
    }

    #[Route('/api/v1/products/{id}', methods: ['PUT', 'PATCH'], name: 'api.products.update', api: true)]
    #[RequiresRole('Admin')]
    public function update(Request $request, int $id): Response
    {
        return $this->save($id, $request);
    }

    #[Route('/api/v1/products/{id}', methods: ['DELETE'], name: 'api.products.destroy', api: true)]
    #[RequiresRole('Admin')]
    public function destroy(Request $request, int $id): Response
    {
        try {
            $this->products->delete($id);
            return $this->response->json(['data' => null, 'message' => 'Product deleted.'], 200);
        } catch (NotFoundException) {
            return $this->notFound();
        } catch (PDOException) {
            return $this->response->json(['errors' => ['product' => 'Cannot delete a product with purchase history.']], 409);
        }
    }

    #[Route('/api/v1/products/{id}/purchase', methods: ['POST'], name: 'api.products.purchase', api: true)]
    #[RequiresRole('User')]
    public function purchase(Request $request, int $id): Response
    {
        try {
            $product = $this->products->get($id);
        } catch (NotFoundException) {
            return $this->notFound();
        }

        $quantity = $request->input('quantity');

        if (!$this->validator->validate(['quantity' => $quantity], Validator::purchaseRules($product->quantityAvailable))) {
            return $this->response->json(['errors' => $this->validator->errors()], 422);
        }

        try {
            $userId = (int) ($request->attributes['auth']['sub'] ?? 0);
            $transaction = $this->purchases->purchase($id, $userId, (int) $quantity);

            return $this->response->json(['data' => $transaction->toArray()], 201);
        } catch (NotFoundException) {
            return $this->notFound();
        } catch (OutOfStockException $exception) {
            return $this->response->json(['errors' => ['quantity' => $exception->getMessage()]], 409);
        } catch (Throwable) {
            return $this->response->json(['errors' => ['purchase' => 'Purchase failed.']], 500);
        }
    }

    private function save(?int $id, Request $request): Response
    {
        $data = $request->all();

        if (!$this->validator->validate($data, Validator::productRules())) {
            return $this->response->json(['errors' => $this->validator->errors()], 422);
        }

        try {
            $product = $id === null
                ? $this->products->create($data)
                : $this->products->update($id, $data);
        } catch (NotFoundException) {
            return $this->notFound();
        } catch (PDOException) {
            return $this->response->json(['errors' => ['name' => 'A product with that name already exists.']], 409);
        }

        return $this->response->json(['data' => $product->toArray()], $id === null ? 201 : 200);
    }

    private function notFound(): Response
    {
        return $this->response->json(['errors' => ['product' => 'Product not found.']], 404);
    }
}
