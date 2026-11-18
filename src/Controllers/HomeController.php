<?php
declare(strict_types=1);

namespace VMS\Controllers;

use VMS\Core\Request;
use VMS\Core\Response;

final class HomeController
{
    public function __construct(private readonly Response $response)
    {
    }

    public function index(Request $request): Response
    {
        return $this->response->redirect('/products');
    }
}
