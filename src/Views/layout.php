<?php
$user = $user ?? null;
$title = $title ?? 'Vending Machine System';
$csrf = $csrf ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/products">VMS <span>Vending</span></a>
    <nav>
        <a href="/products">Products</a>
        <?php if ($user !== null): ?>
            <?php if (($user['role'] ?? '') === 'Admin'): ?>
                <a href="/products/create">New product</a>
            <?php endif; ?>
            <span class="who">Hi, <?= e($user['name'] ?? '') ?> (<?= e($user['role'] ?? '') ?>)</span>
            <form class="inline" method="post" action="/logout">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="link-button">Logout</button>
            </form>
        <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert success" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert error" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="site-footer">VMS &middot; PHP 8.2 &middot; PDO &middot; JWT API</footer>
<script src="/assets/js/validate.js"></script>
</body>
</html>
