<?php
$title = 'Products';
$isAdmin = ($user['role'] ?? '') === 'Admin';
$sortLink = static function (string $column, string $label) use ($sort, $direction, $perPage): string {
    $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
    $url = '/products?' . http_build_query([
        'sort' => $column, 'dir' => $next, 'page' => 1, 'per_page' => $perPage,
    ]);
    $marker = $sort === $column ? ($direction === 'asc' ? ' &#9650;' : ' &#9660;') : '';
    return '<a href="' . e($url) . '">' . e($label) . $marker . '</a>';
};
$pageUrl = static function (int $target) use ($sort, $direction, $perPage): string {
    return '/products?' . http_build_query([
        'sort' => $sort, 'dir' => $direction, 'page' => $target, 'per_page' => $perPage,
    ]);
};
?>
<section class="card">
    <div class="card-header">
        <h1>Products</h1>
        <?php if ($isAdmin): ?>
            <a class="button primary" href="/products/create">+ Add product</a>
        <?php endif; ?>
    </div>

    <?php if (empty($result['items'])): ?>
        <p class="empty">No products found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><?= $sortLink('id', 'ID') ?></th>
                    <th><?= $sortLink('name', 'Name') ?></th>
                    <th><?= $sortLink('price', 'Price (USD)') ?></th>
                    <th><?= $sortLink('quantity_available', 'Available') ?></th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($result['items'] as $product): ?>
                    <tr>
                        <td>#<?= e($product->id) ?></td>
                        <td><a href="/products/<?= e($product->id) ?>"><?= e($product->name) ?></a></td>
                        <td class="price"><?= e($product->formattedPrice()) ?></td>
                        <td>
                            <span class="stock <?= $product->quantityAvailable === 0 ? 'out' : 'in' ?>">
                                <?= e($product->quantityAvailable) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <?php if (can_purchase($user)): ?>
                                <a class="button small" href="/products/<?= e($product->id) ?>-<?= e($product->slug()) ?>/buy">Buy</a>
                            <?php elseif ($user === null): ?>
                                <a class="button small" href="/login">Login to buy</a>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                                <a class="button small ghost" href="/products/<?= e($product->id) ?>/edit">Edit</a>
                                <form class="inline" method="post" action="/products/<?= e($product->id) ?>/delete"
                                      data-confirm="Delete <?= e($product->name) ?>?">
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <button class="button small danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a href="<?= e($pageUrl($page - 1)) ?>">&laquo; Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= max(1, $result['pages']); $i++): ?>
                <a class="<?= $i === $page ? 'current' : '' ?>" href="<?= e($pageUrl($i)) ?>"><?= e($i) ?></a>
            <?php endfor; ?>
            <?php if ($page < $result['pages']): ?>
                <a href="<?= e($pageUrl($page + 1)) ?>">Next &raquo;</a>
            <?php endif; ?>
        </nav>
        <p class="muted"><?= e($result['total']) ?> product(s) &middot; page <?= e($page) ?> of <?= e(max(1, $result['pages'])) ?></p>
    <?php endif; ?>
</section>
