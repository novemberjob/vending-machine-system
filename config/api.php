<?php
declare(strict_types=1);

// API routes are declared with #[Route(..., api: true)] attributes on the
// API controllers and discovered by Application::boot(). This file documents
// the public surface:

// POST   /api/v1/auth/login              -> token (public)
// GET    /api/v1/products                 -> list (JWT)
// GET    /api/v1/products/{id}            -> show (JWT)
// POST   /api/v1/products                 -> create (JWT + Admin)
// PUT    /api/v1/products/{id}            -> update (JWT + Admin)
// PATCH  /api/v1/products/{id}            -> update (JWT + Admin)
// DELETE /api/v1/products/{id}            -> delete (JWT + Admin)
// POST   /api/v1/products/{id}/purchase   -> purchase (JWT)

return [];
