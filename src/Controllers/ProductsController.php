<?php
declare(strict_types=1);

namespace VMS\Controllers;

use PDOException;
use Throwable;
use VMS\Core\Attributes\RequiresAuth;
use VMS\Core\Attributes\RequiresRole;
use VMS\Core\Attributes\Route;
use VMS\Core\Exceptions\NotFoundException;
use VMS\Core\Exceptions\OutOfStockException;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Core\Validator;
use VMS\Core\View;
use VMS\Services\ProductService;
use VMS\Services\PurchaseService;

final class ProductsController
{
    public function __construct(
        private readonly ProductService $products,
        private readonly PurchaseService $purchases,
        private readonly Validator $validator,
        private readonly Session $session,
        private readonly Response $response,
        private readonly View $view,
    ) {
    }

    #[Route('/products', methods: ['GET'], name: 'products.index')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $sort = (string) $request->query('sort', 'id');
        $direction = (string) $request->query('dir', 'asc');
        $perPage = max(1, min(100, (int) $request->query('per_page', env('PER_PAGE', 10))));

        $result = $this->products->list($page, $perPage, $sort, $direction);

        return $this->render('products/index', [
            'result'    => $result,
            'page'      => $page,
            'perPage'   => $perPage,
            'sort'      => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/products/create', methods: ['GET'], name: 'products.create')]
    #[RequiresRole('Admin')]
    public function create(Request $request): Response
    {
        return $this->render('products/create', ['errors' => [], 'old' => []]);
    }

    #[Route('/products', methods: ['POST'], name: 'products.store')]
    #[RequiresRole('Admin')]
    public function store(Request $request): Response
    {
        if ($error = $this->csrfFailure($request)) {
            return $error;
        }

        $data = $request->all();

        if (!$this->validator->validate($data, Validator::productRules())) {
            return $this->render('products/create', [
                'errors' => $this->validator->errors(),
                'old'    => $data,
            ], 422);
        }

        try {
            $this->products->create($data);
            $this->session->flash('success', 'Product created successfully.');
            return $this->response->redirect('/products');
        } catch (PDOException) {
            return $this->render('products/create', [
                'errors' => ['name' => 'A product with that name already exists.'],
                'old'    => $data,
            ], 422);
        }
    }

    #[Route('/products/{id}', methods: ['GET'], name: 'products.show')]
    public function show(Request $request, int $id): Response
    {
        try {
            $product = $this->products->get($id);
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        }

        return $this->render('products/show', ['product' => $product]);
    }

    #[Route('/products/{id}/edit', methods: ['GET'], name: 'products.edit')]
    #[RequiresRole('Admin')]
    public function edit(Request $request, int $id): Response
    {
        try {
            $product = $this->products->get($id);
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        }

        return $this->render('products/edit', [
            'product' => $product,
            'errors'  => [],
            'old'     => [],
        ]);
    }

    #[Route('/products/{id}', methods: ['POST'], name: 'products.update')]
    #[RequiresRole('Admin')]
    public function update(Request $request, int $id): Response
    {
        if ($error = $this->csrfFailure($request)) {
            return $error;
        }

        try {
            $this->products->get($id);
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        }

        $data = $request->all();

        if (!$this->validator->validate($data, Validator::productRules())) {
            return $this->render('products/edit', [
                'product' => $this->products->find($id),
                'errors'  => $this->validator->errors(),
                'old'     => $data,
            ], 422);
        }

        try {
            $this->products->update($id, $data);
            $this->session->flash('success', 'Product updated successfully.');
            return $this->response->redirect('/products/' . $id);
        } catch (PDOException) {
            return $this->render('products/edit', [
                'product' => $this->products->find($id),
                'errors'  => ['name' => 'A product with that name already exists.'],
                'old'     => $data,
            ], 422);
        }
    }

    #[Route('/products/{id}/delete', methods: ['POST'], name: 'products.destroy')]
    #[RequiresRole('Admin')]
    public function destroy(Request $request, int $id): Response
    {
        if ($error = $this->csrfFailure($request)) {
            return $error;
        }

        try {
            $this->products->delete($id);
            $this->session->flash('success', 'Product deleted successfully.');
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        } catch (PDOException) {
            $this->session->flash('error', 'Cannot delete a product that has purchase history.');
        }

        return $this->response->redirect('/products');
    }

    #[Route('/products/{id}-{slug}/buy', methods: ['GET', 'POST'], name: 'products.purchase')]
    #[RequiresAuth]
    #[RequiresRole('User')]
    public function purchase(Request $request, int $id, string $slug = ''): Response
    {
        try {
            $product = $this->products->get($id);
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        }

        if ($request->method() !== 'POST') {
            return $this->render('products/purchase', [
                'product' => $product,
                'errors'  => [],
                'old'     => ['quantity' => 1],
            ]);
        }

        if ($error = $this->csrfFailure($request)) {
            return $error;
        }

        $quantity = $request->input('quantity');

        if (!$this->validator->validate(['quantity' => $quantity], Validator::purchaseRules($product->quantityAvailable))) {
            return $this->render('products/purchase', [
                'product' => $product,
                'errors'  => $this->validator->errors(),
                'old'     => ['quantity' => $quantity],
            ], 422);
        }

        try {
            $user = $this->session->user();
            $transaction = $this->purchases->purchase($product->id, (int) ($user['id'] ?? 0), (int) $quantity);
            $this->session->flash(
                'success',
                "Purchased {$quantity} × {$product->name} for {$transaction->totalAmount} USD."
            );
            return $this->response->redirect('/products/' . $product->id);
        } catch (NotFoundException) {
            return $this->render('errors/404', [], 404);
        } catch (OutOfStockException $exception) {
            return $this->render('products/purchase', [
                'product' => $this->products->find($id),
                'errors'  => ['quantity' => $exception->getMessage()],
                'old'     => ['quantity' => $quantity],
            ], 409);
        } catch (Throwable) {
            return $this->render('products/purchase', [
                'product' => $this->products->find($id),
                'errors'  => ['quantity' => 'The purchase could not be completed. Please try again.'],
                'old'     => ['quantity' => $quantity],
            ], 500);
        }
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        $data['user'] = $this->session->user();
        $data['csrf'] = $this->session->csrfToken();
        $data['flashSuccess'] = $this->session->pullFlash('success');
        $data['flashError'] = $this->session->pullFlash('error');

        return $this->response->html($this->view->render($template, $data), $status);
    }

    private function csrfFailure(Request $request): ?Response
    {
        if ($this->session->validateCsrf($request->input('_csrf'))) {
            return null;
        }

        return $this->response->html('Invalid CSRF token.', 419);
    }
}
