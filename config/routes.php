<?php
declare(strict_types=1);

use VMS\Controllers\AuthController;
use VMS\Controllers\HomeController;

return [
    ['GET',  '/',          HomeController::class, 'index'],
    ['GET',  '/login',     AuthController::class, 'login'],
    ['POST', '/login',     AuthController::class, 'postLogin'],
    ['GET',  '/register',  AuthController::class, 'register'],
    ['POST', '/register',  AuthController::class, 'postRegister'],
    ['POST', '/logout',    AuthController::class, 'logout', ['auth' => true]],
];
