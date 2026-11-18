<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VMS\Controllers\ProductsController;
use VMS\Core\Exceptions\OutOfStockException;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Core\TransactionManager;
use VMS\Core\Validator;
use VMS\Core\View;
use VMS\Models\Product;
use VMS\Models\Transaction;
use VMS\Repositories\ProductRepository;
use VMS\Repositories\TransactionRepository;
use VMS\Services\ProductService;
use VMS\Services\PurchaseService;

final class ProductsControllerTest extends TestCase
{
    private ProductRepository $productRepository;
    private TransactionRepository $transactionRepository;
    private TransactionManager $transactionManager;
    private Session $session;
    private View $view;
    private Response $response;
    private ProductsController $controller;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);

        $this->transactionManager = $this->createMock(TransactionManager::class);
        $this->transactionManager->method('run')->willReturnCallback(
            static fn (callable $fn): mixed => $fn()
        );

        $this->session = $this->createMock(Session::class);
        $this->session->method('csrfToken')->willReturn('csrf-token');
        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('user')->willReturn(['id' => 7, 'name' => 'Buyer', 'role' => 'User']);

        $this->view = $this->createMock(View::class);
        $this->view->method('render')->willReturnCallback(
            static fn (string $template, array $data = []): string =>
                'TEMPLATE:' . $template . '|ERRORS:' . json_encode($data['errors'] ?? [])
        );

        $this->response = new Response();

        $products = new ProductService($this->productRepository);
        $purchases = new PurchaseService(
            $this->productRepository,
            $this->transactionRepository,
            $this->transactionManager,
        );

        $this->controller = new ProductsController(
            $products,
            $purchases,
            new Validator(),
            $this->session,
            $this->response,
            $this->view,
        );
    }

    public function testIndexPaginatesAndSorts(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('paginate')
            ->with(2, 5, 'name', 'desc')
            ->willReturn(['items' => [], 'total' => 12]);

        $response = $this->controller->index($this->request('GET', [
            'page'     => 2,
            'per_page' => 5,
            'sort'     => 'name',
            'dir'      => 'desc',
        ]));

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('TEMPLATE:products/index', $response->body());
    }

    public function testStoreCreatesAValidProduct(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('create')
            ->with([
                'name'               => 'Lemonade',
                'price'              => '2.500',
                'quantity_available' => 5,
            ])
            ->willReturn(new Product(4, 'Lemonade', '2.500', 5));

        $this->session->expects($this->once())->method('flash');

        $response = $this->controller->store($this->request('POST', [], [
            'name'               => '  Lemonade  ',
            'price'              => '2.5',
            'quantity_available' => '5',
            '_csrf'              => 'csrf-token',
        ]));

        $this->assertSame(302, $response->status());
    }

    public function testStoreRejectsNegativePriceAndNegativeQuantity(): void
    {
        $this->productRepository->expects($this->never())->method('create');

        $response = $this->controller->store($this->request('POST', [], [
            'name'               => 'Broken',
            'price'              => '-1',
            'quantity_available' => '-3',
            '_csrf'              => 'csrf-token',
        ]));

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('TEMPLATE:products/create', $response->body());
        $this->assertStringContainsString('price', $response->body());
        $this->assertStringContainsString('quantity_available', $response->body());
    }

    public function testStoreRejectsMissingFields(): void
    {
        $this->productRepository->expects($this->never())->method('create');

        $response = $this->controller->store($this->request('POST', [], ['_csrf' => 'csrf-token']));

        $this->assertSame(422, $response->status());
    }

    public function testUpdateReturns404ForMissingProduct(): void
    {
        $this->productRepository->method('find')->willReturn(null);
        $this->productRepository->expects($this->never())->method('update');

        $response = $this->controller->update($this->request('POST'), 999);

        $this->assertSame(404, $response->status());
    }

    public function testDestroyDeletesAnExistingProduct(): void
    {
        $product = new Product(1, 'Coke', '3.990', 10);
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->expects($this->once())->method('delete')->with(1);

        $response = $this->controller->destroy($this->request('POST', [], ['_csrf' => 'csrf-token']), 1);

        $this->assertSame(302, $response->status());
    }

    public function testPurchaseDecrementsStockAndLogsTransaction(): void
    {
        $product = new Product(1, 'Coke', '3.990', 10);
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->expects($this->once())->method('decrementStock')->with(1, 2)->willReturn(true);

        $this->transactionRepository
            ->expects($this->once())
            ->method('log')
            ->with(1, 7, 2, '3.990', '7.980')
            ->willReturn(new Transaction(10, 1, 7, 2, '3.990', '7.980'));

        $response = $this->controller->purchase($this->request('POST', [], [
            'quantity' => '2',
            '_csrf'    => 'csrf-token',
        ]), 1, 'coke');

        $this->assertSame(302, $response->status());
    }

    #[DataProvider('invalidQuantities')]
    public function testPurchaseRejectsInvalidQuantities(mixed $quantity): void
    {
        $product = new Product(1, 'Coke', '3.990', 10);
        $this->productRepository->method('find')->willReturn($product);
        $this->productRepository->expects($this->never())->method('decrementStock');
        $this->transactionRepository->expects($this->never())->method('log');

        $response = $this->controller->purchase($this->request('POST', [], [
            'quantity' => $quantity,
            '_csrf'    => 'csrf-token',
        ]), 1, 'coke');

        $this->assertSame(422, $response->status());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidQuantities(): array
    {
        return [
            'zero'      => [0],
            'negative'  => [-2],
            'decimal'   => [1.5],
            'text'      => ['abc'],
            'over stock'=> [11],
            'missing'   => [null],
        ];
    }

    public function testPurchaseReturns409WhenStockDisappears(): void
    {
        $product = new Product(1, 'Coke', '3.990', 1);
        $this->productRepository->method('find')->willReturn($product);
        $this->transactionRepository->expects($this->never())->method('log');

        $response = $this->controller->purchase($this->request('POST', [], [
            'quantity' => '1',
            '_csrf'    => 'csrf-token',
        ]), 1, 'coke');

        $this->assertSame(409, $response->status());
    }

    public function testPurchaseRendersErrorWhenServiceThrows(): void
    {
        $product = new Product(1, 'Coke', '3.990', 10);
        $this->productRepository->method('find')->willReturn($product);
        $this->transactionManager = $this->createMock(TransactionManager::class);
        $this->transactionManager->method('run')->willThrowException(new OutOfStockException('Sold out.'));

        $purchases = new PurchaseService(
            $this->productRepository,
            $this->transactionRepository,
            $this->transactionManager,
        );
        $this->controller = new ProductsController(
            new ProductService($this->productRepository),
            $purchases,
            new Validator(),
            $this->session,
            $this->response,
            $this->view,
        );

        $response = $this->controller->purchase($this->request('POST', [], [
            'quantity' => '1',
            '_csrf'    => 'csrf-token',
        ]), 1, 'coke');

        $this->assertSame(409, $response->status());
    }

    private function request(string $method = 'GET', array $query = [], array $post = []): Request
    {
        return new Request('/products', $method, $query, $post);
    }
}
